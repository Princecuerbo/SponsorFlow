<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Requests\Admin\UpdateSecuritySettingsRequest;
use App\Models\DatabaseBackup;
use App\Models\User;
use App\Support\AdminAccountProtection;
use App\Support\BackupFiles;
use Illuminate\Container\Container;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** No application bootstrap; a database connection is forbidden in these tests. */
class AdminSafetyTest extends TestCase
{
    private ?ConnectionResolverInterface $previousResolver;
    private $previousFacadeApp;
    private Container $previousContainer;
    private Factory $validator;
    private array $temporaryPaths = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousContainer = Container::getInstance();
        $this->previousResolver = Model::getConnectionResolver();
        $resolver = $this->createMock(ConnectionResolverInterface::class);
        $resolver->expects($this->never())->method('connection');
        Model::setConnectionResolver($resolver);
        $this->previousFacadeApp = Facade::getFacadeApplication();
        $container = new Container;
        $this->validator = new Factory(new Translator(new ArrayLoader, 'en'), $container);
        $container->instance('validator', $this->validator);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($container);
    }

    protected function tearDown(): void
    {
        // Only remove the exact temporary files/directories created by this test.
        foreach (array_reverse($this->temporaryPaths) as $path) {
            is_dir($path) ? rmdir($path) : unlink($path);
        }
        $this->previousResolver ? Model::setConnectionResolver($this->previousResolver) : Model::unsetConnectionResolver();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->previousFacadeApp);
        Container::setInstance($this->previousContainer);
        parent::tearDown();
    }

    private function user(int $id, UserRole $role = UserRole::Admin, UserStatus $status = UserStatus::Active): User
    {
        $user = new User;
        $user->setRawAttributes(['id' => $id, 'role' => $role->value, 'status' => $status->value]);

        return $user;
    }

    public static function protectedChanges(): array
    {
        return [
            'self demotion' => [true, UserRole::Student, UserStatus::Active, 2],
            'self deactivation' => [true, UserRole::Admin, UserStatus::Inactive, 2],
            'last admin demotion' => [false, UserRole::Sponsor, UserStatus::Active, 1],
            'last admin deactivation' => [false, UserRole::Admin, UserStatus::Inactive, 1],
        ];
    }

    #[DataProvider('protectedChanges')]
    public function test_protected_account_changes_are_rejected(bool $self, UserRole $role, UserStatus $status, int $count): void
    {
        $this->expectException(ValidationException::class);
        AdminAccountProtection::validate($this->user(1), $this->user($self ? 1 : 2), $role, $status, $count);
    }

    public function test_unchanged_self_role_and_other_account_management_remain_allowed(): void
    {
        AdminAccountProtection::validate($this->user(1), $this->user(1), UserRole::Admin, UserStatus::Active, 1);
        AdminAccountProtection::validate($this->user(1), $this->user(2), UserRole::Student, UserStatus::Inactive, 2);
        AdminAccountProtection::validate($this->user(1), $this->user(3, UserRole::Student), UserRole::Student, UserStatus::Inactive, 1);
        $this->addToAssertionCount(3);
    }

    public static function securitySettings(): array
    {
        return [
            'valid all' => [['maintenance_mode' => 'false', 'max_login_attempts' => '5', 'session_timeout_minutes' => '120'], true],
            'valid minimums' => [['maintenance_mode' => '1', 'max_login_attempts' => '1', 'session_timeout_minutes' => '1'], true],
            'valid maximums' => [['max_login_attempts' => '20', 'session_timeout_minutes' => '1440'], true],
            'empty' => [[], false],
            'unknown key' => [['session_driver' => 'database'], false],
            'partly invalid' => [['maintenance_mode' => 'true', 'max_login_attempts' => 'zero'], false],
            'maintenance malformed' => [['maintenance_mode' => 'enabled'], false],
            'zero attempts' => [['max_login_attempts' => 0], false],
            'negative attempts' => [['max_login_attempts' => -1], false],
            'attempts too high' => [['max_login_attempts' => 21], false],
            'fractional timeout' => [['session_timeout_minutes' => '1.5'], false],
            'timeout too high' => [['session_timeout_minutes' => 1441], false],
            'timeout missing' => [['session_timeout_minutes' => null], false],
            'timeout zero' => [['session_timeout_minutes' => 0], false],
            'nested payload' => [['max_login_attempts' => ['5']], false],
        ];
    }

    #[DataProvider('securitySettings')]
    public function test_settings_validation(array $settings, bool $valid): void
    {
        $request = new UpdateSecuritySettingsRequest;
        $this->assertSame($valid, $this->validator->make(['settings' => $settings], $request->rules())->passes());
    }

    public function test_settings_require_an_active_admin(): void
    {
        $request = new UpdateSecuritySettingsRequest;
        foreach ([null, $this->user(1, UserRole::Student), $this->user(1, UserRole::Admin, UserStatus::Inactive)] as $user) {
            $request->setUserResolver(fn () => $user);
            $this->assertFalse($request->authorize());
        }
        $request->setUserResolver(fn () => $this->user(1));
        $this->assertTrue($request->authorize());
    }

    public function test_restore_is_rejected_without_reading_files_or_connecting_to_a_database(): void
    {
        $this->expectException(ValidationException::class);
        (new BackupController)->restore(Request::create('/fixture', 'POST'), new DatabaseBackup);
    }

    public function test_production_validation_keeps_field_errors_and_explicit_responses(): void
    {
        // Construct the exception configuration only. Do not boot the application,
        // load .env, register application providers, dispatch routes, or connect to SQL.
        $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $app->instance('env', 'production');
        $app->instance('config', new Repository(['app' => ['debug' => false]]));
        $app->instance('validator', $this->validator);
        Facade::setFacadeApplication($app);
        Facade::clearResolvedInstances();
        $session = new \Illuminate\Session\Store('admin-fixture', new \Illuminate\Session\ArraySessionHandler(120));
        $webRequest = Request::create('http://fixture.test/admin/users', 'POST', ['name' => 'Entered Name', 'password' => 'never-flash'], [], [], ['HTTP_REFERER' => 'http://fixture.test/admin/users']);
        $webRequest->setLaravelSession($session);
        $url = new \Illuminate\Routing\UrlGenerator(new \Illuminate\Routing\RouteCollection, $webRequest);
        $app->instance('url', $url);
        $redirector = new \Illuminate\Routing\Redirector($url);
        $redirector->setSession($session);
        $app->instance('redirect', $redirector);
        $app->instance(\Illuminate\Contracts\Routing\ResponseFactory::class, new \Illuminate\Routing\ResponseFactory(
            $this->createMock(\Illuminate\Contracts\View\Factory::class), $redirector
        ));
        $handler = $app->make(ExceptionHandler::class);
        $request = Request::create('/fixture', 'POST', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = $handler->render($request, ValidationException::withMessages(['account' => 'Protected account.']));
        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(['Protected account.'], $response->getData(true)['errors']['account']);
        $explicit = new JsonResponse(['message' => 'Validation failed.'], 422);
        $this->assertSame($explicit, $handler->render($request, new HttpResponseException($explicit)));
        $webResponse = $handler->render($webRequest, ValidationException::withMessages(['account' => 'Protected account.']));
        $this->assertSame(302, $webResponse->getStatusCode());
        $this->assertSame('http://fixture.test/admin/users', $webResponse->getTargetUrl());
        $this->assertSame('Entered Name', $session->getOldInput('name'));
        $this->assertNull($session->getOldInput('password'));
        $this->assertTrue($session->get('errors')->has('account'));
        $this->assertFalse($app->isBooted());
        $this->assertFalse($app->bound('db'));
    }

    public static function snapshotScenarios(): array
    {
        return [
            'read-only snapshot' => [0, false, [], null],
            'metadata failure rolls back' => [0, true, [], 'Fixture metadata failure.'],
            'nontransactional table rolls back' => [0, false, [(object) ['name' => 'fixture', 'kind' => 'BASE TABLE', 'engine' => 'MyISAM']], 'Use the controlled backup procedure'],
            'nested transaction rejected' => [1, false, [], 'A database snapshot requires its own read-only transaction.'],
        ];
    }

    #[DataProvider('snapshotScenarios')]
    public function test_snapshot_transaction_guards(int $level, bool $fail, array $tables, ?string $error): void
    {
        // A recording double with no PDO, file, or database access.
        $database = new class($level, $fail, $tables) {
            public array $calls = [];
            public function __construct(private int $level, private bool $fail, private array $tables) {}
            public function connection(): self { return $this; }
            public function getDriverName(): string { return 'mysql'; }
            public function transactionLevel(): int { return $this->level; }
            public function statement(string $sql): void { $this->calls[] = $sql; }
            public function beginTransaction(): void { $this->calls[] = 'begin'; }
            public function rollBack(): void { $this->calls[] = 'rollback'; }
            public function select(string $sql): array
            {
                $this->calls[] = $sql;
                if ($this->fail) { throw new \RuntimeException('Fixture metadata failure.'); }
                return $this->tables;
            }
        };
        Facade::getFacadeApplication()->instance('db', $database);
        try {
            $dump = (new \ReflectionMethod(BackupController::class, 'buildDatabaseDump'))->invoke(new BackupController);
            $this->assertNull($error);
            $this->assertStringContainsString('-- SponsorFlow System Backup', $dump);
        } catch (\RuntimeException $exception) {
            $this->assertNotNull($error);
            $this->assertStringContainsString($error, $exception->getMessage());
        }
        if ($level > 0) {
            $this->assertSame([], $database->calls);
        } else {
            $this->assertSame(['SET TRANSACTION ISOLATION LEVEL REPEATABLE READ', 'SET TRANSACTION READ ONLY', 'begin'], array_slice($database->calls, 0, 3));
            $this->assertStringStartsWith('SELECT ', $database->calls[3]);
            $this->assertSame('rollback', $database->calls[4]);
            $this->assertCount(5, $database->calls);
        }
    }

    public function test_backup_write_verifies_bytes_and_uses_unique_private_paths(): void
    {
        $stored = [];
        $disk = $this->createMock(FilesystemAdapter::class);
        $disk->expects($this->exactly(2))->method('put')->willReturnCallback(function ($path, $contents, $options) use (&$stored) {
            $this->assertSame('private', $options);
            $this->assertArrayNotHasKey($path, $stored);
            $stored[$path] = $contents;
            return true;
        });
        $disk->expects($this->exactly(2))->method('get')->willReturnCallback(function ($path) use (&$stored) { return $stored[$path]; });
        $files = new BackupFiles($disk, '/unused');
        $first = $files->write('fixture contents');
        $second = $files->write('fixture contents');
        $this->assertSame(16, $first['file_size']);
        $this->assertNotSame($first['file_path'], $second['file_path']);
        $this->assertSame('backups/'.$first['file_name'], $first['file_path']);
    }

    public static function failedWrites(): array
    {
        return ['write refused' => [false, null], 'readback mismatch' => [true, 'incomplete'], 'readback missing' => [true, null]];
    }

    #[DataProvider('failedWrites')]
    public function test_failed_backup_files_are_not_marked_complete(bool $written, ?string $saved): void
    {
        $disk = $this->createMock(FilesystemAdapter::class);
        $disk->method('put')->willReturn($written);
        $disk->method('get')->willReturn($saved);
        $this->expectException(\RuntimeException::class);
        (new BackupFiles($disk, '/unused'))->write('complete fixture');
    }

    public function test_private_and_legacy_files_resolve_but_traversal_and_missing_paths_do_not(): void
    {
        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sf-admin-'.bin2hex(random_bytes(8));
        foreach ([$root, $root.'/private', $root.'/private/backups', $root.'/legacy'] as $dir) {
            mkdir($dir);
            $this->temporaryPaths[] = $dir;
        }
        foreach ([$root.'/private/backups/new.sql', $root.'/legacy/old.sql'] as $path) {
            file_put_contents($path, '-- synthetic fixture');
            $this->temporaryPaths[] = $path;
        }
        $disk = $this->createMock(FilesystemAdapter::class);
        $disk->method('path')->with('backups')->willReturn($root.'/private/backups');
        $files = new BackupFiles($disk, $root.'/legacy');
        $this->assertSame(realpath($root.'/private/backups/new.sql'), $files->resolve('backups/new.sql'));
        $this->assertSame(realpath($root.'/legacy/old.sql'), $files->resolve('backups/old.sql'));
        foreach (['backups/missing.sql', '../.env', 'backups/../old.sql', 'backups\\old.sql', 'C:/data.sql', 'https://example.test/file.sql'] as $path) {
            $this->assertNull($files->resolve($path));
        }
    }
}

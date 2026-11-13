<?php

namespace Tests\Unit;

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Student\SleVerificationController;
use App\Http\Requests\Student\UpdateVerificationRequest;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\PresenceVerifierInterface;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/** No Laravel bootstrap or PDO: catalog lookups and persistence are test doubles. */
class StudentAcademicProgramValidationTest extends TestCase
{
    private ?ConnectionResolverInterface $previousResolver;

    private Container $previousContainer;

    private mixed $previousFacadeApplication;

    private Factory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousResolver = Model::getConnectionResolver();
        $this->previousContainer = Container::getInstance();
        $this->previousFacadeApplication = Facade::getFacadeApplication();
        $container = new Container;
        Container::setInstance($container);
        Facade::setFacadeApplication($container);
        Facade::clearResolvedInstances();
        $resolver = $this->createMock(ConnectionResolverInterface::class);
        $resolver->expects($this->never())->method('connection');
        Model::setConnectionResolver($resolver);

        $this->factory = new Factory(new Translator(new ArrayLoader, 'en'));
        $container->instance('validator', $this->factory);
        $presence = $this->createMock(PresenceVerifierInterface::class);
        $presence->method('getCount')->willReturnCallback(function ($table, $column, $id, $exclude, $idColumn, $extra): int {
            if ($table === 'student_profiles') {
                return 0; // Unique student ID; no database involved.
            }
            $this->assertSame('academic_programs', $table);
            $this->assertSame('program_id', $column);
            $this->assertEquals(['is_active' => 1, 'is_undergraduate' => 1], $extra);
            $catalog = [
                1 => ['is_active' => 1, 'is_undergraduate' => 1],
                2 => ['is_active' => 0, 'is_undergraduate' => 1],
                3 => ['is_active' => 1, 'is_undergraduate' => 0],
            ];
            $program = $catalog[$id] ?? null;

            return $program !== null
                && $program['is_active'] === (int) $extra['is_active']
                && $program['is_undergraduate'] === (int) $extra['is_undergraduate'] ? 1 : 0;
        });
        $presence->expects($this->never())->method('getMultiCount');
        $this->factory->setPresenceVerifier($presence);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->previousFacadeApplication);
        Container::setInstance($this->previousContainer);
        if ($this->previousResolver !== null) {
            Model::setConnectionResolver($this->previousResolver);
        } else {
            Model::unsetConnectionResolver();
        }
        parent::tearDown();
    }

    private function profile(array $attributes = []): StudentProfile
    {
        $profile = new StudentProfile;
        $profile->setRawAttributes(array_replace([
            'id' => 10, 'user_id' => 20, 'student_id_number' => '2024-0001',
            'academic_program_id' => 2, 'course' => 'Historical course spelling',
            'year_level' => 2, 'active_sponsorship_id' => 30,
        ], $attributes), true);
        $profile->exists = true;

        return $profile;
    }

    private function profileRequest(array $input, ?StudentProfile $profile = null): UpdateVerificationRequest
    {
        $user = new User;
        $user->setRawAttributes(['id' => 20, 'role' => 'Student']);
        $user->setRelation('studentProfile', $profile);
        $request = new UpdateVerificationRequest;
        $request->setUserResolver(fn () => $user);
        $request->replace(array_replace(['student_id_number' => '2024-0001', 'year_level' => 3], $input));

        return $request;
    }

    private function profileValidator(UpdateVerificationRequest $request): Validator
    {
        $validator = $this->factory->make($request->all(), $request->rules(), $request->messages());
        $request->setValidator($validator);

        return $validator;
    }

    private function registrationValidator(array $input): Validator
    {
        // Exercise the controller's real rule array, stopping before account creation.
        $request = new class($this->factory, $input) extends Request
        {
            public Validator $captured;

            public function __construct(private Factory $factory, private array $inputFixture)
            {
                parent::__construct();
            }

            public function validate(array $rules, array $messages): array
            {
                $this->captured = $this->factory->make($this->inputFixture, [
                    'academic_program_id' => $rules['academic_program_id'],
                ], $messages);
                throw new RuntimeException('Registration validation captured');
            }
        };
        try {
            (new RegisteredUserController)->store($request);
            $this->fail('Registration must stop before database persistence.');
        } catch (RuntimeException $e) {
            $this->assertSame('Registration validation captured', $e->getMessage());
        }

        return $request->captured;
    }

    public static function programIdentifiers(): array
    {
        return [
            'active undergraduate' => [1, true],
            'HTML select string' => ['1', true],
            'inactive undergraduate' => [2, false],
            'active postgraduate' => [3, false],
            'nonexistent' => [999, false],
            'null' => [null, false],
            'empty' => ['', false],
            'zero' => [0, false],
            'negative' => [-1, false],
            'program code' => ['BSIT', false],
            'decimal string' => ['1.5', false],
            'scientific notation' => ['1e0', false],
            'array' => [[1], false],
            'boolean' => [true, false],
            'float' => [1.0, false],
            'signed string' => ['+1', false],
        ];
    }

    #[DataProvider('programIdentifiers')]
    public function test_registration_and_profile_selections_use_the_same_catalog_restrictions(mixed $id, bool $valid): void
    {
        $input = ['academic_program_id' => $id];
        foreach ([
            $this->registrationValidator($input),
            $this->profileValidator($this->profileRequest($input, $this->profile())),
        ] as $validator) {
            $this->assertSame($valid, $validator->passes());
            $this->assertSame(! $valid, $validator->errors()->has('academic_program_id'));
        }
    }

    public function test_registration_and_new_profiles_cannot_use_free_text_to_bypass_program_selection(): void
    {
        $input = ['course' => 'Invented program'];
        foreach ([
            $this->registrationValidator($input),
            $this->profileValidator($this->profileRequest($input)),
            $this->profileValidator($this->profileRequest($input, $this->profile([
                'academic_program_id' => null, 'course' => null,
            ]))),
        ] as $validator) {
            $this->assertTrue($validator->fails());
            $this->assertTrue($validator->errors()->has('academic_program_id'));
        }
    }

    public function test_free_text_alone_cannot_change_an_existing_program(): void
    {
        foreach ([2, null] as $id) {
            $validator = $this->profileValidator($this->profileRequest(
                ['course' => 'Different course'],
                $this->profile(['academic_program_id' => $id]),
            ));
            $this->assertTrue($validator->fails());
            $this->assertTrue($validator->errors()->has('course'));
        }
    }

    public static function historicalUpdates(): array
    {
        return [
            'inactive ID omitted' => [2, []],
            'postgraduate ID omitted' => [3, []],
            'legacy course without ID' => [null, []],
            'null course is not a clearing operation' => [2, ['course' => null]],
            'same legacy text' => [null, ['course' => 'Historical course spelling']],
            'same valid ID preserves stored text' => [1, ['academic_program_id' => '1']],
        ];
    }

    #[DataProvider('historicalUpdates')]
    public function test_unrelated_profile_updates_preserve_historical_program_and_sponsorship_fields(?int $id, array $input): void
    {
        $profile = $this->profile(['academic_program_id' => $id]);
        $before = $profile->getAttributes();
        $request = $this->profileRequest($input, $profile);
        $this->assertTrue($this->profileValidator($request)->passes());
        [$sql, $bindings] = $this->runProfileUpdate($request, $profile);
        $this->assertStringContainsString('`year_level` = ?', $sql);
        foreach (['academic_program_id', 'course', 'active_sponsorship_id'] as $field) {
            $this->assertStringNotContainsString('`'.$field.'` =', $sql);
        }
        $this->assertContains(3, $bindings);
        $this->assertSame($before, $profile->getAttributes());
    }

    public function test_program_change_uses_catalog_name_instead_of_submitted_course_text(): void
    {
        $profile = $this->profile();
        $request = $this->profileRequest(['academic_program_id' => '1', 'course' => 'Forged title'], $profile);
        $this->assertTrue($this->profileValidator($request)->passes());
        [$sql, $bindings] = $this->runProfileUpdate($request, $profile, [
            'program_id' => 1, 'name' => 'Canonical catalog title', 'is_active' => 1, 'is_undergraduate' => 1,
        ]);
        $this->assertStringContainsString('`academic_program_id` = ?', $sql);
        $this->assertStringContainsString('`course` = ?', $sql);
        $this->assertContains('Canonical catalog title', $bindings);
        $this->assertNotContains('Forged title', $bindings);
        $this->assertStringNotContainsString('`active_sponsorship_id` =', $sql);
    }

    public function test_unavailable_program_at_persistence_is_rejected_before_any_profile_update(): void
    {
        $profile = $this->profile();
        $request = $this->profileRequest(['academic_program_id' => 1], $profile);
        $this->assertTrue($this->profileValidator($request)->passes());
        try {
            $this->runProfileUpdate($request, $profile, expectUpdate: false);
            $this->fail('The unavailable program must be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('academic_program_id', $e->errors());
        }
    }

    /**
     * Run the real controller against mock SQL results. No PDO or application boot.
     * The captured UPDATE is only a string; the mock never executes it.
     */
    private function runProfileUpdate(
        UpdateVerificationRequest $request,
        StudentProfile $profile,
        ?array $program = null,
        bool $expectUpdate = true,
    ): array {
        $grammar = new MySqlGrammar;
        $connection = $this->createMock(Connection::class);
        $connection->method('getQueryGrammar')->willReturn($grammar);
        $connection->method('query')->willReturnCallback(fn () => new Builder($connection, $grammar, new Processor));
        $connection->expects($this->never())->method('getPdo');
        $connection->expects($this->never())->method('getReadPdo');
        $connection->expects($this->never())->method('insert');
        $connection->expects($this->never())->method('delete');
        $connection->expects($this->never())->method('statement');
        $connection->method('select')->willReturnCallback(function (string $sql, array $bindings) use ($profile, $program): array {
            if (str_contains($sql, '`academic_programs`')) {
                $this->assertStringContainsString('`is_active` = ?', $sql);
                $this->assertStringContainsString('`is_undergraduate` = ?', $sql);
                $this->assertEquals([true, true, 1], $bindings);

                return $program === null ? [] : [(object) $program];
            }
            $this->assertStringContainsString('`student_profiles`', $sql);

            return [(object) $profile->getAttributes()];
        });
        $captured = [];
        $connection->expects($expectUpdate ? $this->once() : $this->never())->method('update')
            ->willReturnCallback(function (string $sql, array $bindings) use (&$captured): int {
                $captured = [$sql, $bindings];

                return 1;
            });
        $resolver = $this->createMock(ConnectionResolverInterface::class);
        $resolver->method('connection')->willReturn($connection);
        Model::setConnectionResolver($resolver);
        $connection->method('transaction')->willReturnCallback(fn ($callback) => $callback());
        DB::swap($connection);
        $response = $this->createMock(RedirectResponse::class);
        $response->method('with')->willReturnSelf();
        $redirect = $this->createMock(Redirector::class);
        $redirect->method('route')->with('student.sle-fhe')->willReturn($response);
        Container::getInstance()->instance('redirect', $redirect);
        $controller = new class extends SleVerificationController
        {
            protected function audit(Request $request, string $action, string $targetModule): void
            {
                // Audit persistence is unrelated; disabled in this database-free harness.
            }
        };
        $this->assertSame($response, $controller->update($request));

        return $captured;
    }
}

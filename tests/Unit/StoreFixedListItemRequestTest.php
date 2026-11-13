<?php

namespace Tests\Unit;

use App\Http\Requests\Fassg\StoreFixedListItemRequest;
use App\Models\AcademicProgram;
use App\Models\SleFheRegistry;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\PresenceVerifierInterface;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

/** No application bootstrap: record lookups are mocked and database access is forbidden. */
class StoreFixedListItemRequestTest extends TestCase
{
    private ?ConnectionResolverInterface $previousResolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousResolver = Model::getConnectionResolver();
        $resolver = $this->createMock(ConnectionResolverInterface::class);
        $resolver->expects($this->never())->method('connection');
        Model::setConnectionResolver($resolver);
    }

    protected function tearDown(): void
    {
        if ($this->previousResolver !== null) {
            Model::setConnectionResolver($this->previousResolver);
        } else {
            Model::unsetConnectionResolver();
        }
        parent::tearDown();
    }

    private function program(): AcademicProgram
    {
        $program = new AcademicProgram;
        $program->setRawAttributes(['program_id' => 7, 'name' => 'Bachelor of Science in Information Technology',
            'code' => 'BSIT', 'short_name' => 'Information Technology']);

        return $program;
    }

    private function request(array $input = [], ?StudentProfile $profile = null, ?SleFheRegistry $registry = null): StoreFixedListItemRequest
    {
        $request = new class($this->program(), $profile, $registry) extends StoreFixedListItemRequest
        {
            public function __construct(
                private AcademicProgram $programFixture,
                private ?StudentProfile $profileFixture,
                private ?SleFheRegistry $registryFixture,
            ) {
                parent::__construct();
            }

            protected function availableAcademicPrograms(): Collection
            {
                return collect([$this->programFixture]);
            }

            protected function matchingStudentProfile(string $studentId): ?StudentProfile
            {
                return $studentId === '2024-0001' ? $this->profileFixture : null;
            }

            protected function matchingRegistryRecord(string $studentId): ?SleFheRegistry
            {
                return $studentId === '2024-0001' ? $this->registryFixture : null;
            }
        };
        $request->replace(array_replace(['student_name' => 'Example Student', 'student_id_number' => '2024-0001',
            'course' => $this->program()->name, 'year_level' => '3', 'campus' => 'Main Campus (City of Mati)'], $input));
        return $request;
    }

    private function validator(StoreFixedListItemRequest $request): Validator
    {
        $factory = new Factory(new Translator(new ArrayLoader, 'en'));
        // Only the legacy endpoint uses exists:fixed_lists; emulate it without any database.
        $presence = $this->createMock(PresenceVerifierInterface::class);
        $presence->method('getCount')->willReturn(1);
        $factory->setPresenceVerifier($presence);
        $validator = $factory->make($request->all(), $request->rules(), $request->messages());
        $validator->after($request->after());

        return $validator;
    }

    public function test_official_full_program_name_and_all_supported_years_are_accepted(): void
    {
        foreach (StoreFixedListItemRequest::YEAR_LEVELS as $year) {
            $validator = $this->validator($this->request(['year_level' => (string) $year]));
            $this->assertTrue($validator->passes());
            $this->assertSame($this->program()->name, $validator->validated()['course']);
            $this->assertArrayNotHasKey('is_sle_fhe_verified', $validator->validated());
        }
    }

    public function test_unlisted_names_codes_and_invalid_years_cannot_bypass_the_dropdowns(): void
    {
        foreach (['', 'BSIT', 'Invented Program', 'Master in Public Administration', ['BSIT']] as $course) {
            $validator = $this->validator($this->request(['course' => $course]));
            $this->assertTrue($validator->fails());
            $this->assertTrue($validator->errors()->has('course'));
        }
        foreach (['', '0', '6', '-1', '2.5', 'Third', ['3']] as $year) {
            $validator = $this->validator($this->request(['year_level' => $year]));
            $this->assertTrue($validator->fails());
            $this->assertTrue($validator->errors()->has('year_level'));
        }
    }

    public function test_matching_profile_and_registry_are_read_without_mutation_or_verification(): void
    {
        $profile = new StudentProfile;
        $profile->setRawAttributes(['id' => 9, 'academic_program_id' => 7, 'year_level' => 3]);
        $registry = new SleFheRegistry;
        $registry->setRawAttributes(['course' => ' bsit ', 'year_level' => '3', 'is_verified' => false]);
        $before = [$profile->getAttributes(), $registry->getAttributes()];
        $validator = $this->validator($this->request([], $profile, $registry));

        $this->assertTrue($validator->passes());
        $this->assertSame($before, [$profile->getAttributes(), $registry->getAttributes()]);
        $this->assertFalse($registry->is_verified);
    }

    public function test_program_and_year_conflicts_in_either_source_block_encoding(): void
    {
        foreach ([StudentProfile::class, SleFheRegistry::class] as $type) {
            $record = new $type;
            $record->setRawAttributes(['academic_program_id' => 8, 'course' => 'Other Program', 'year_level' => 2]);
            $before = $record->getAttributes();
            $request = $record instanceof StudentProfile
                ? $this->request([], $record) : $this->request([], null, $record);
            $validator = $this->validator($request);

            $this->assertTrue($validator->fails());
            $this->assertTrue($validator->errors()->has('course'));
            $this->assertTrue($validator->errors()->has('year_level'));
            $this->assertSame($before, $record->getAttributes());
        }
    }

    public function test_legacy_text_can_match_existing_catalog_aliases_but_is_not_guessed(): void
    {
        foreach ([$this->program()->name, 'BSIT', ' information   technology '] as $course) {
            $profile = new StudentProfile;
            $profile->setRawAttributes(['course' => $course, 'year_level' => 3]);
            $this->assertTrue($this->validator($this->request([], $profile))->passes());
        }
        $registry = new SleFheRegistry;
        $registry->setRawAttributes(['course' => 'BS Information Tech', 'year_level' => 'Third Year']);
        $validator = $this->validator($this->request([], null, $registry));
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('course'));
        $this->assertTrue($validator->errors()->has('year_level'));
    }

    public function test_masterlist_catalog_names_and_codes_match_the_registered_program(): void
    {
        $profile = new StudentProfile;
        $profile->setRawAttributes(['academic_program_id' => 7, 'course' => $this->program()->name, 'year_level' => 3]);

        foreach ([$this->program()->name, 'BSIT', $this->program()->short_name,
            "  BACHELOR  OF SCIENCE\tIN INFORMATION TECHNOLOGY  ", "\tbsit\n"] as $course) {
            $registry = new SleFheRegistry;
            $registry->setRawAttributes(['course' => $course, 'year_level' => '3', 'is_verified' => true]);
            $before = [$profile->getAttributes(), $registry->getAttributes()];

            $this->assertTrue($this->validator($this->request([], $profile, $registry))->passes(), $course);
            $this->assertSame($before, [$profile->getAttributes(), $registry->getAttributes()]);
        }
    }

    public function test_malformed_unknown_or_different_masterlist_programs_remain_rejected(): void
    {
        $profile = new StudentProfile;
        $profile->setRawAttributes(['academic_program_id' => 7, 'course' => $this->program()->name, 'year_level' => 3]);

        foreach (['Bachelor of Science in Information TechnologyNOLOGY',
            'Bachelor of Science in Nursing', 'Unknown Program'] as $course) {
            $registry = new SleFheRegistry;
            $registry->setRawAttributes(['course' => $course, 'year_level' => '3', 'is_verified' => true]);
            $before = [$profile->getAttributes(), $registry->getAttributes()];
            $validator = $this->validator($this->request([], $profile, $registry));

            $this->assertTrue($validator->fails(), $course);
            $this->assertSame(['course'], $validator->errors()->keys());
            $this->assertStringContainsString('institutional masterlist', $validator->errors()->first('course'));
            $this->assertSame($before, [$profile->getAttributes(), $registry->getAttributes()]);
        }
    }

    public function test_matching_masterlist_cannot_override_a_conflicting_registered_program_id(): void
    {
        $profile = new StudentProfile;
        $profile->setRawAttributes(['academic_program_id' => 8, 'course' => $this->program()->name, 'year_level' => 3]);
        $registry = new SleFheRegistry;
        $registry->setRawAttributes(['course' => 'BSIT', 'year_level' => '3']);
        $before = [$profile->getAttributes(), $registry->getAttributes()];
        $validator = $this->validator($this->request([], $profile, $registry));

        $this->assertTrue($validator->fails());
        $this->assertSame(['course'], $validator->errors()->keys());
        $this->assertStringContainsString('registered student profile', $validator->errors()->first('course'));
        $this->assertSame($before, [$profile->getAttributes(), $registry->getAttributes()]);
    }

    public function test_missing_record_values_are_not_fabricated(): void
    {
        $profile = new StudentProfile;
        $profile->setRawAttributes(['course' => null, 'year_level' => null]);
        $registry = new SleFheRegistry;
        $registry->setRawAttributes(['course' => null, 'year_level' => null]);
        $before = [$profile->getAttributes(), $registry->getAttributes()];
        $this->assertTrue($this->validator($this->request([], $profile, $registry))->passes());
        $this->assertSame($before, [$profile->getAttributes(), $registry->getAttributes()]);
    }

    public function test_legacy_manual_route_uses_the_same_rules_and_requires_its_list_id(): void
    {
        $request = $this->request(['course' => 'Unlisted', 'year_level' => 6]);
        $request->setRouteResolver(fn () => (new Route('POST', '/fixed-lists/encode', fn () => null))->name('fassg.fixed-lists.encode'));
        $validator = $this->validator($request);
        $this->assertTrue($validator->fails());
        foreach (['course', 'year_level', 'fixed_list_id'] as $field) {
            $this->assertTrue($validator->errors()->has($field));
        }
        $request->merge(['course' => $this->program()->name, 'year_level' => 3, 'fixed_list_id' => 1]);
        $this->assertTrue($this->validator($request)->passes());
    }

    public function test_only_fassg_is_authorized(): void
    {
        $request = $this->request();
        $request->setUserResolver(fn () => null);
        $this->assertFalse($request->authorize());
        foreach ([true, false] as $isFassg) {
            $user = $this->createMock(User::class);
            $user->method('isFassg')->willReturn($isFassg);
            $request->setUserResolver(fn () => $user);
            $this->assertSame($isFassg, $request->authorize());
        }
    }
}

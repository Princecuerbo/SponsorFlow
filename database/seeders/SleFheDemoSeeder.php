<?php

namespace Database\Seeders;

use App\Models\SleFheRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Dedicated demonstration seeder for SLE-FHE Institutional Registry testing.
 *
 * Excluded from DatabaseSeeder and production deployment.
 * Never modifies or deletes existing registry records.
 * Generates 15 synthetic, non-institutional student records with DEMO-2026-XXXX IDs.
 */
class SleFheDemoSeeder extends Seeder
{
    /**
     * Documented absent student IDs for testing "student not found in registry" workflows.
     * These IDs are intentionally NEVER seeded.
     */
    public const ABSENT_STUDENT_IDS = [
        'DEMO-2026-9001',
        'DEMO-2026-9002',
    ];

    /**
     * 15 synthetic SLE-FHE registry records for technical system testing.
     *
     * Scenarios included:
     * - Verified standard matches across diverse degree programs and campuses (10 records).
     * - Unverified registry records (is_verified = false) testing FASSG override requirements (2 records).
     * - Mismatched course / location scenarios for testing FASSG discrepancy detection (2 records).
     * - Multi-year progression scenario (1 record).
     *
     * @return list<array<string, mixed>>
     */
    public static function records(): array
    {
        return [
            [
                'student_id' => 'DEMO-2026-0001',
                'sex' => 'Male',
                'civil_status' => 'Single',
                'purok' => 'Purok Narra',
                'barangay' => 'Central',
                'municipality' => 'City of Mati',
                'course' => 'Bachelor of Science in Information Technology',
                'year_level' => '1',
                'campus' => 'DOrSU Main Campus',
                'district' => 2,
                'is_verified' => true,
            ],
            [
                'student_id' => 'DEMO-2026-0002',
                'sex' => 'Female',
                'civil_status' => 'Single',
                'purok' => 'Purok Pag-asa',
                'barangay' => 'Dahican',
                'municipality' => 'City of Mati',
                'course' => 'Bachelor of Science in Environmental Science',
                'year_level' => '2',
                'campus' => 'DOrSU Main Campus',
                'district' => 2,
                'is_verified' => true,
            ],
            [
                'student_id' => 'DEMO-2026-0003',
                'sex' => 'Female',
                'civil_status' => 'Single',
                'purok' => 'Purok 3',
                'barangay' => 'Kinablangan',
                'municipality' => 'Baganga',
                'course' => 'Bachelor of Science in Agribusiness Management',
                'year_level' => '1',
                'campus' => 'DOrSU Main Campus',
                'district' => 1,
                'is_verified' => true,
            ],
            [
                'student_id' => 'DEMO-2026-0004',
                'sex' => 'Male',
                'civil_status' => 'Single',
                'purok' => 'Purok Mangga',
                'barangay' => 'Poblacion',
                'municipality' => 'Lupon',
                'course' => 'Bachelor of Science in Computer Science',
                'year_level' => '3',
                'campus' => 'DOrSU Main Campus',
                'district' => 2,
                'is_verified' => true,
            ],
            [
                'student_id' => 'DEMO-2026-0005',
                'sex' => 'Female',
                'civil_status' => 'Single',
                'purok' => 'Purok 2',
                'barangay' => 'San Roque',
                'municipality' => 'Governor Generoso',
                'course' => 'Bachelor of Science in Hospitality Management',
                'year_level' => '2',
                'campus' => 'DOrSU Main Campus',
                'district' => 2,
                'is_verified' => true,
            ],
            [
                'student_id' => 'DEMO-2026-0006',
                'sex' => 'Male',
                'civil_status' => 'Single',
                'purok' => 'Purok Baybay',
                'barangay' => 'Poblacion',
                'municipality' => 'Cateel',
                'course' => 'Bachelor of Science in Civil Engineering',
                'year_level' => '4',
                'campus' => 'Cateel Campus',
                'district' => 1,
                'is_verified' => true,
            ],
            [
                'student_id' => 'DEMO-2026-0007',
                'sex' => 'Female',
                'civil_status' => 'Married',
                'purok' => 'Purok Maharlika',
                'barangay' => 'Sainz',
                'municipality' => 'City of Mati',
                'course' => 'Bachelor of Elementary Education',
                'year_level' => '3',
                'campus' => 'DOrSU Main Campus',
                'district' => 2,
                'is_verified' => true,
            ],
            [
                'student_id' => 'DEMO-2026-0008',
                'sex' => 'Male',
                'civil_status' => 'Single',
                'purok' => 'Purok Centro',
                'barangay' => 'Poblacion',
                'municipality' => 'Banaybanay',
                'course' => 'Bachelor of Science in Business Administration major in Financial Management',
                'year_level' => '1',
                'campus' => 'Banaybanay Campus',
                'district' => 2,
                'is_verified' => true,
            ],
            [
                'student_id' => 'DEMO-2026-0009',
                'sex' => 'Female',
                'civil_status' => 'Single',
                'purok' => 'Purok 4',
                'barangay' => 'San Pedro',
                'municipality' => 'San Isidro',
                'course' => 'Bachelor of Arts in Political Science',
                'year_level' => '2',
                'campus' => 'San Isidro Campus',
                'district' => 2,
                'is_verified' => true,
            ],
            [
                'student_id' => 'DEMO-2026-0010',
                'sex' => 'Male',
                'civil_status' => 'Single',
                'purok' => 'Purok Acacia',
                'barangay' => 'Matiao',
                'municipality' => 'City of Mati',
                'course' => 'Bachelor of Science in Nursing',
                'year_level' => '1',
                'campus' => 'DOrSU Main Campus',
                'district' => 2,
                'is_verified' => true,
            ],
            [
                'student_id' => 'DEMO-2026-0011',
                'sex' => 'Female',
                'civil_status' => 'Single',
                'purok' => 'Purok Riverside',
                'barangay' => 'Don Mariano Marcos',
                'municipality' => 'Lupon',
                'course' => 'Bachelor of Science in Information Technology',
                'year_level' => '2',
                'campus' => 'DOrSU Main Campus',
                'district' => 2,
                'is_verified' => false, // Unverified in registry -> tests FASSG override requirement
            ],
            [
                'student_id' => 'DEMO-2026-0012',
                'sex' => 'Male',
                'civil_status' => 'Single',
                'purok' => 'Purok Masagana',
                'barangay' => 'Poblacion',
                'municipality' => 'Caraga',
                'course' => 'Bachelor of Science in Agribusiness Management',
                'year_level' => '3',
                'campus' => 'DOrSU Main Campus',
                'district' => 1,
                'is_verified' => false, // Unverified in registry -> tests FASSG override requirement
            ],
            [
                'student_id' => 'DEMO-2026-0013',
                'sex' => 'Female',
                'civil_status' => 'Single',
                'purok' => 'Purok Malinawon',
                'barangay' => 'Lucatan',
                'municipality' => 'Tarragona',
                'course' => 'Bachelor of Elementary Education',
                'year_level' => '1',
                'campus' => 'DOrSU Main Campus',
                'district' => 1,
                'is_verified' => true, // Tests profile-to-registry discrepancy detection (e.g. course difference)
            ],
            [
                'student_id' => 'DEMO-2026-0014',
                'sex' => 'Male',
                'civil_status' => 'Single',
                'purok' => 'Purok 1',
                'barangay' => 'Central',
                'municipality' => 'Manay',
                'course' => 'Bachelor of Science in Computer Science',
                'year_level' => '2',
                'campus' => 'DOrSU Main Campus',
                'district' => 1,
                'is_verified' => true, // Tests address discrepancy detection (Manay vs other city)
            ],
            [
                'student_id' => 'DEMO-2026-0015',
                'sex' => 'Female',
                'civil_status' => 'Single',
                'purok' => 'Purok Sampaguita',
                'barangay' => 'Poblacion',
                'municipality' => 'Boston',
                'course' => 'Bachelor of Science in Environmental Science',
                'year_level' => '4',
                'campus' => 'Cateel Campus',
                'district' => 1,
                'is_verified' => true, // Tests multi-year progression scenario
            ],
        ];
    }

    /**
     * Run the database seeds idempotently without modifying existing records.
     */
    public function run(): void
    {
        $records = self::records();

        DB::transaction(function () use ($records): void {
            foreach ($records as $record) {
                // Strict validation: student_id must follow the DEMO- pattern
                if (! str_starts_with($record['student_id'], 'DEMO-')) {
                    throw new RuntimeException("Refusing to seed non-demo student ID: {$record['student_id']}");
                }

                // Protect any existing institutional records against collision
                $existing = SleFheRegistry::query()
                    ->where('student_id', $record['student_id'])
                    ->first();

                if ($existing !== null && ! str_starts_with($existing->student_id, 'DEMO-')) {
                    throw new RuntimeException(
                        "Collision detected: student_id {$record['student_id']} already exists as an institutional record. Aborting."
                    );
                }

                // Idempotent upsert keyed on synthetic student_id
                SleFheRegistry::query()->updateOrCreate(
                    ['student_id' => $record['student_id']],
                    $record
                );
            }
        });
    }
}

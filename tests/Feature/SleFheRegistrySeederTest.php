<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/** Static guards only: do not bootstrap Laravel or execute any seeder. */
class SleFheRegistrySeederTest extends TestCase
{
    public function test_general_and_container_seeding_cannot_invoke_registry_import(): void
    {
        $root = dirname(__DIR__, 2);
        $seeder = file_get_contents($root.'/database/seeders/DatabaseSeeder.php');
        $startup = file_get_contents($root.'/entrypoint.sh');
        $this->assertStringNotContainsString('SleFheRegistrySeeder::', $seeder);
        $this->assertStringNotContainsString('SleFheRegistrySeeder', $startup);
        foreach (['LocalAddressSeeder', 'AcademicProgramSeeder', 'RoleAndUserSeeder', 'SponsorshipProgramSeeder'] as $class) {
            $this->assertStringContainsString($class.'::class', $seeder);
        }
        $this->assertStringContainsString('php artisan db:seed --force', $startup);
    }

    public function test_direct_registry_seeder_is_an_unconditional_failure_with_no_persistence_path(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/database/seeders/SleFheRegistrySeeder.php');
        $this->assertMatchesRegularExpression('/function run\(\): void\s*\{\s*throw new RuntimeException\(/', $source);
        $this->assertStringContainsString('Registry seeding is disabled.', $source);
        $this->assertStringContainsString('sponsorflow:registry-preflight', $source);
        foreach (['DB::', 'SleFheRegistry::', 'upsert(', 'insert(', 'update(', 'persist(', 'is_verified'] as $writer) {
            $this->assertStringNotContainsString($writer, $source);
        }
    }
}

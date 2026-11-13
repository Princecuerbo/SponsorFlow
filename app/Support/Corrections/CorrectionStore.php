<?php

namespace App\Support\Corrections;

use Closure;

interface CorrectionStore
{
    public function transaction(bool $write, Closure $work): mixed;
    public function identity(): array;
    public function catalog(): array;
    public function registry(int $id): ?array;
    public function receipt(string $manifestHash): ?array;
    public function collisions(array $item): array;
    public function sameText(string $left, string $right): bool;
    public function insertProgram(array $item): array;
    public function changeCourse(array $before, string $course, ?string $timestamp = null, bool $restoreTimestamp = false): array;
    public function record(array $metadata, array $changes): void;
}

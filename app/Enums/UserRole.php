<?php

namespace App\Enums;

enum UserRole: string
{
    case Student = 'student';
    case Fassg = 'fassg';
    case Sponsor = 'sponsor';
    case Accounting = 'accounting';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Student',
            self::Fassg => 'FASSG',
            self::Sponsor => 'Sponsor',
            self::Accounting => 'Accounting',
            self::Admin => 'Admin',
        };
    }

    /**
     * @return list<array{label: string, route: string, icon: string}>
     */
    public function navItems(): array
    {
        return match ($this) {
            self::Student => [
                ['label' => 'Dashboard', 'route' => 'student.dashboard', 'icon' => 'bi-speedometer2'],
                ['label' => 'Verification', 'route' => 'student.verification.show', 'icon' => 'bi-person-check'],
                ['label' => 'Programs', 'route' => 'student.programs.index', 'icon' => 'bi-grid'],
                ['label' => 'My applications', 'route' => 'student.applications.index', 'icon' => 'bi-hourglass-split'],
            ],
            self::Fassg => [
                ['label' => 'Dashboard', 'route' => 'fassg.dashboard', 'icon' => 'bi-speedometer2'],
                ['label' => 'Programs', 'route' => 'fassg.programs.index', 'icon' => 'bi-journal-richtext'],
                ['label' => 'Applicants', 'route' => 'fassg.applications.index', 'icon' => 'bi-people'],
                ['label' => 'Fixed lists', 'route' => 'fassg.fixed-lists.index', 'icon' => 'bi-list-check'],
                ['label' => 'Reports', 'route' => 'fassg.reports.index', 'icon' => 'bi-bar-chart'],
            ],
            self::Sponsor => [
                ['label' => 'Dashboard', 'route' => 'sponsor.dashboard', 'icon' => 'bi-speedometer2'],
                ['label' => 'Fixed lists', 'route' => 'sponsor.lists.index', 'icon' => 'bi-clipboard-check'],
                ['label' => 'Applicants', 'route' => 'sponsor.applicants.index', 'icon' => 'bi-people'],
            ],
            self::Accounting => [
                ['label' => 'Dashboard', 'route' => 'accounting.dashboard', 'icon' => 'bi-speedometer2'],
                ['label' => 'Beneficiaries', 'route' => 'accounting.beneficiaries.index', 'icon' => 'bi-wallet2'],
                ['label' => 'Export CSV', 'route' => 'accounting.beneficiaries.export', 'icon' => 'bi-download'],
            ],
            self::Admin => [
                ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'bi-speedometer2'],
                ['label' => 'Users', 'route' => 'admin.users.index', 'icon' => 'bi-person-gear'],
                ['label' => 'Programs', 'route' => 'admin.programs.index', 'icon' => 'bi-journal-richtext'],
                ['label' => 'Audit logs', 'route' => 'admin.audit-logs.index', 'icon' => 'bi-shield-check'],
            ],
        };
    }
}

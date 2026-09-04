<?php

namespace App\Http\Controllers\Concerns;

use App\Models\AuditLog;
use App\Models\Sponsor;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\Request;

trait ResolvesModuleContext
{
    protected function actor(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }

    protected function studentProfile(Request $request, bool $required = true): ?StudentProfile
    {
        $profile = $this->actor($request)->studentProfile;

        if ($required && $profile === null) {
            abort(403, 'Complete student ID verification before continuing.');
        }

        return $profile;
    }

    protected function sponsorOrganization(Request $request): Sponsor
    {
        $user = $this->actor($request);
        $sponsor = $user->sponsor;

        if ($sponsor === null) {
            $sponsor = Sponsor::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'company_organization_name' => $user->name,
                    'contact_person' => $user->name,
                    'contact_email' => $user->email,
                ],
            );
            $user->setRelation('sponsor', $sponsor);
        }

        return $sponsor;
    }

    protected function audit(Request $request, string $action, string $targetModule): void
    {
        AuditLog::record(
            $action,
            $targetModule,
            $this->actor($request),
            $request->ip(),
        );
    }
}

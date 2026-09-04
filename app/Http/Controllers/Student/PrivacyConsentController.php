<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrivacyConsentController extends Controller
{
    public function show(Request $request): View
    {
        $user = $this->actor($request);

        return view('student.privacy-consent', [
            'user' => $user,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'privacy_consent' => ['required', 'accepted'],
        ]);

        $user = $request->user();
        $user->update(['privacy_consent_at' => now()]);
        $request->session()->put('privacy_consented_session', true);

        $this->audit($request, 'student.privacy_consent.accepted', 'users');

        return redirect()
            ->route('student.dashboard')
            ->with('status', 'Privacy consent recorded. Welcome to SponsorFlow.');
    }

    protected function actor(Request $request)
    {
        return $request->user();
    }

    protected function audit(Request $request, string $action, string $entityType): void
    {
        $user = $request->user();

        if (! $user) {
            return;
        }

        \App\Models\AuditLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => null,
            'old_values' => null,
            'new_values' => null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}

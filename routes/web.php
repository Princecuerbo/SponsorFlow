<?php

use App\Http\Controllers\Accounting\ReferenceController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Accounting\DashboardController as AccountingDashboardController;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\StaffLoginController;
use App\Http\Controllers\Auth\StudentLoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\Fassg\ApplicantVerificationController;
use App\Http\Controllers\Fassg\FixedListController;
use App\Http\Controllers\Fassg\ProgramManagementController;
use App\Http\Controllers\Fassg\ReportsController;
use App\Http\Controllers\Fassg\VerificationController as FassgVerificationController;
use App\Http\Controllers\Sponsor\ApprovalUploadController;
use App\Http\Controllers\Sponsor\ReviewController;
use App\Http\Controllers\Student\ApplicationController as StudentApplicationController;
use App\Http\Controllers\Student\ProfileController;
use App\Http\Controllers\Student\VerificationController;
use App\Http\Controllers\Student\PrivacyConsentController;
use App\Models\User;
use App\Models\SponsorshipProgram;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', function () {
    try {
        $programs = Schema::hasTable('sponsorship_programs')
            ? SponsorshipProgram::query()->open()->with('sponsor')->latest()->take(6)->get()
            : collect();
    } catch (\Throwable) {
        $programs = collect();
    }

    return view('welcome', compact('programs'));
})->name('landing');

Route::middleware('guest')->group(function () {
    Route::get('/login', [StudentLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [StudentLoginController::class, 'login'])->name('login.store');
    Route::post('/login/verify', [StudentLoginController::class, 'verify'])->name('login.verify');
    Route::post('/login/complete', [StudentLoginController::class, 'complete'])->name('login.complete');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');

    Route::get('/staff/login', [StaffLoginController::class, 'showLoginForm'])->name('staff.login');
    Route::post('/staff/login', [StaffLoginController::class, 'login'])->name('staff.login.store');

    Route::get('/admin/login', [AdminLoginController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/admin/login', [AdminLoginController::class, 'login'])->name('admin.login.store');
});

Route::post('/logout', [LogoutController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->get('/documents/{document}', [DocumentController::class, 'show'])
    ->name('documents.show');

Route::middleware('auth')->get('/dashboard', function () {
    $user = Auth::user();

    abort_unless($user instanceof User, 401);

    /** @var User $user */
    return redirect()->route($user->homeRoute());
})->name('dashboard');

Route::middleware(['web', 'auth', 'EnsureUserRole:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

        Route::get('/verification', [VerificationController::class, 'show'])->name('verification.show');
        Route::get('/sle-fhe', [VerificationController::class, 'show'])->name('sle-fhe');
        Route::put('/verification', [VerificationController::class, 'update'])->name('verification.update');
        Route::get('/verify', [VerificationController::class, 'show'])->name('verification');
        Route::post('/verify', [VerificationController::class, 'update'])->name('verify.store');

        Route::get('/programs', [StudentApplicationController::class, 'programs'])->name('programs.index');
        Route::get('/applications/create/{sponsorshipProgram}', [StudentApplicationController::class, 'create'])->name('applications.create');
        Route::get('/applications', [StudentApplicationController::class, 'index'])->name('applications.index');
        Route::get('/status', [StudentApplicationController::class, 'index'])->name('status');
        Route::get('/apply/{sponsorshipProgram}', [StudentApplicationController::class, 'create'])->name('apply');
        Route::post('/applications', [StudentApplicationController::class, 'store'])->name('applications.store');
        Route::get('/applications/{application}', [StudentApplicationController::class, 'show'])->name('applications.show');
        Route::get('/applications/{application}/documents/{documentType}', [StudentApplicationController::class, 'downloadDocument'])
            ->name('applications.documents.download');
    });

Route::middleware(['auth', 'EnsureUserRole:fassg'])
    ->prefix('fassg')
    ->name('fassg.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/programs', [ProgramManagementController::class, 'index'])->name('programs.index');
        Route::get('/programs/create', [ProgramManagementController::class, 'create'])->name('programs.create');
        Route::post('/programs', [ProgramManagementController::class, 'store'])->name('programs.store');
        Route::get('/programs/{sponsorshipProgram}/edit', [ProgramManagementController::class, 'edit'])->name('programs.edit');
        Route::put('/programs/{sponsorshipProgram}', [ProgramManagementController::class, 'update'])->name('programs.update');
        Route::patch('/programs/{sponsorshipProgram}/open', [ProgramManagementController::class, 'open'])->name('programs.open');
        Route::patch('/programs/{sponsorshipProgram}/reopen', [ProgramManagementController::class, 'reopen'])->name('programs.reopen');
        Route::patch('/programs/{sponsorshipProgram}/close', [ProgramManagementController::class, 'close'])->name('programs.close');
        Route::patch('/programs/{sponsorshipProgram}/expire', [ProgramManagementController::class, 'expire'])->name('programs.expire');
        Route::post('/programs/{sponsorshipProgram}/toggle', [ProgramManagementController::class, 'toggleStatus'])->name('programs.toggle');
        Route::patch('/programs/{sponsorshipProgram}/toggle-status', [ProgramManagementController::class, 'toggleStatus'])->name('programs.toggle-status');
        Route::delete('/programs/{sponsorshipProgram}', [ProgramManagementController::class, 'destroy'])->name('programs.destroy');

        Route::get('/applications', [ApplicantVerificationController::class, 'index'])->name('applications.index');
        Route::get('/verification', [FassgVerificationController::class, 'index'])->name('verification.index');
        Route::get('/applications/{application}', [ApplicantVerificationController::class, 'show'])->name('applications.show');
        Route::get('/verification/{application}', [FassgVerificationController::class, 'show'])->name('verification.show');
        Route::get('/applications/{application}/documents/{document}', [FassgVerificationController::class, 'viewDocument'])
            ->name('applications.documents.show');
        Route::patch('/applications/{application}/verify', [ApplicantVerificationController::class, 'verify'])->name('applications.verify');
        Route::patch('/applications/{application}/reject', [ApplicantVerificationController::class, 'reject'])->name('applications.reject');
        Route::post('/verification/{application}/update', [ApplicantVerificationController::class, 'updateStatus'])->name('verification.update');
        Route::patch('/verification/{application}/verify', [FassgVerificationController::class, 'verify'])->name('verification.verify');
        Route::patch('/verification/{application}/reject', [FassgVerificationController::class, 'reject'])->name('verification.reject');
        Route::patch('/verification/{application}/request-revision', [ApplicantVerificationController::class, 'reject'])->name('verification.request-revision');
        Route::get('/applications/{application}/documents/{applicationDocument}', [ApplicantVerificationController::class, 'downloadDocument'])
            ->name('applications.documents.download');
        Route::patch('/students/{studentProfile}/sle-fhe', [ApplicantVerificationController::class, 'verifySleFhe'])
            ->name('students.verify-sle-fhe');
        Route::post('/verification/students/{studentProfile}/verify', [FassgVerificationController::class, 'verifyStudent'])
            ->name('verification.students.verify');
        Route::post('/verification/students/{studentProfile}/reject', [FassgVerificationController::class, 'rejectStudent'])
            ->name('verification.students.reject');

        Route::get('/fixed-lists', [FixedListController::class, 'index'])->name('fixed-lists.index');
        Route::post('/fixed-lists', [FixedListController::class, 'store'])->name('fixed-lists.store');
        Route::post('/fixed-lists/upload', [FixedListController::class, 'upload'])->name('fixed-lists.upload');
        Route::post('/fixed-lists/encode', [FixedListController::class, 'encode'])->name('fixed-lists.encode');
        Route::get('/fixed-lists/{fixedList}/edit', [FixedListController::class, 'edit'])->name('fixed-lists.edit');
        Route::put('/fixed-lists/{fixedList}', [FixedListController::class, 'update'])->name('fixed-lists.update');
        Route::delete('/fixed-lists/{fixedList}', [FixedListController::class, 'destroy'])->name('fixed-lists.destroy');
        Route::get('/fixed-lists/{fixedList}', [FixedListController::class, 'show'])->name('fixed-lists.show');
        Route::post('/fixed-lists/{fixedList}/items', [FixedListController::class, 'storeItem'])->name('fixed-lists.items.store');
        Route::post('/fixed-lists/{fixedList}/import', [FixedListController::class, 'import'])->name('fixed-lists.import');
        Route::patch('/fixed-lists/{fixedList}/submit', [FixedListController::class, 'submit'])->name('fixed-lists.submit');
        Route::patch('/fixed-lists/{fixedList}/publish', [FixedListController::class, 'publish'])->name('fixed-lists.publish');
        Route::post('/fixed-lists/{fixedList}/forward', [FixedListController::class, 'submit'])->name('fixed-lists.forward');
        Route::patch('/fixed-lists/{fixedList}/items/{fixedListItem}/verify', [FixedListController::class, 'verifyItem'])
            ->name('fixed-lists.items.verify');

        Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
        Route::get('/reports/export-pdf', [ReportsController::class, 'exportPdf'])->name('reports.export-pdf');
        Route::get('/monitoring', [ReportsController::class, 'index'])->name('monitoring.index');
    });

Route::middleware(['auth', 'EnsureUserRole:sponsor'])
    ->prefix('sponsor')
    ->name('sponsor.')
    ->group(function () {
        Route::get('/dashboard', [ReviewController::class, 'dashboard'])->name('dashboard');
        Route::get('/programs', [ReviewController::class, 'programs'])->name('programs.index');
        Route::get('/approvals', [ReviewController::class, 'index'])->name('approvals.index');
        Route::get('/approvals/history', [ReviewController::class, 'history'])->name('approvals.history');
        Route::get('/review', [ReviewController::class, 'index'])->name('review.index');
        Route::get('/lists', [ReviewController::class, 'index'])->name('lists.index');
        Route::get('/lists/{fixedList}', [ReviewController::class, 'show'])->name('lists.show');
        Route::get('/applicants', [ReviewController::class, 'applicants'])->name('applicants.index');
        Route::get('/applicants/{application}', [ReviewController::class, 'showApplicant'])->name('applicants.show');
        Route::get('/applicants/{application}/approval-document', [ReviewController::class, 'downloadApprovalDocument'])->name('applicants.approval-document');
        Route::post('/applicants/{application}/confirm', [ReviewController::class, 'confirmApplication'])->name('applicants.confirm');
        Route::post('/applicants/{application}/reject', [ReviewController::class, 'reject'])->name('applicants.reject');
        Route::post('/lists/{fixedList}/approvals', [ApprovalUploadController::class, 'store'])->name('approvals.store');
        Route::post('/lists/{fixedList}/upload-approval', [ApprovalUploadController::class, 'store'])->name('lists.upload-approval');
        Route::patch('/lists/{fixedList}/confirm', [ApprovalUploadController::class, 'confirm'])->name('approvals.confirm');
        Route::patch('/lists/{fixedList}/confirm-beneficiaries', [ApprovalUploadController::class, 'confirm'])->name('lists.confirm');
        Route::patch('/lists/{fixedList}/reject', [ApprovalUploadController::class, 'reject'])->name('approvals.reject');
        Route::get('/approvals/{sponsorApproval}/document', [ApprovalUploadController::class, 'download'])->name('approvals.download');
    });

Route::middleware(['auth', 'EnsureUserRole:accounting'])
    ->prefix('accounting')
    ->name('accounting.')
    ->group(function () {
        Route::get('/dashboard', [AccountingDashboardController::class, 'index'])->name('dashboard');
        Route::get('/beneficiaries', [ReferenceController::class, 'index'])->name('beneficiaries.index');
        Route::get('/beneficiaries/export', [ReferenceController::class, 'export'])->name('beneficiaries.export');
        Route::get('/beneficiaries/{application}', [ReferenceController::class, 'show'])->name('beneficiaries.show');
        Route::get('/applications/{application}/document', [ReferenceController::class, 'viewApplicationDocument'])->name('applications.document');
        Route::get('/applications/{application}/document-reference', [ReferenceController::class, 'viewApplicationDocument'])->name('documents.view');
        Route::get('/fixed-lists/{fixedList}/document', [ReferenceController::class, 'viewFixedListDocument'])->name('fixed-lists.document');
        Route::get('/export', [ReferenceController::class, 'export'])->name('export');
        Route::get('/programs', [ReferenceController::class, 'index'])->name('programs.index');
        Route::get('/applications', [ReferenceController::class, 'index'])->name('applications.index');
        Route::get('/fixed-lists', [ReferenceController::class, 'index'])->name('fixed-lists.index');
        Route::get('/reports', [ReferenceController::class, 'index'])->name('reports.index');
        Route::get('/audit-logs', [ReferenceController::class, 'index'])->name('audit-logs.index');
    });

Route::middleware(['auth', 'EnsureUserRole:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/deactivate', [AdminUserController::class, 'deactivate'])->name('users.deactivate');
        Route::post('/users/{user}/restore', [AdminUserController::class, 'restore'])->name('users.restore');
        Route::post('/users/{user}/toggle', [AdminUserController::class, 'toggleStatus'])->name('users.toggle');
        Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/settings', [AdminSettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [AdminSettingController::class, 'update'])->name('settings.update');
        Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
        Route::post('/backups', [BackupController::class, 'store'])->name('backups.store');
        Route::post('/backups/{backup}/restore', [BackupController::class, 'restore'])->name('backups.restore');
        Route::get('/backups/{backup}/download', [BackupController::class, 'download'])->name('backups.download');
        Route::post('/backup', [BackupController::class, 'run'])->name('backup.run');
    });

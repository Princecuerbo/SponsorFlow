<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudentNotificationsRedesignTest extends TestCase
{
    use RefreshDatabase;

    private function createStudent(string $name = 'Maria Santos'): array
    {
        $student = User::factory()->create([
            'role' => UserRole::Student,
            'name' => $name,
            'privacy_consent_at' => now(),
        ]);

        $profile = StudentProfile::factory()->verified()->create([
            'user_id' => $student->id,
            'first_name' => explode(' ', $name)[0],
            'last_name' => explode(' ', $name)[1] ?? 'Student',
        ]);

        return [$student, $profile];
    }

    private function createNotification(User $user, array $data = [], bool $isRead = false): mixed
    {
        return $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\ApplicationStatusUpdated',
            'data' => array_merge([
                'icon' => 'patch-check',
                'title' => 'Application Progress Update',
                'message' => 'Your application for DORSU Grant has moved to Approved.',
                'url' => '/student/applications',
            ], $data),
            'read_at' => $isRead ? now() : null,
        ]);
    }

    /** 1. Student with zero notifications */
    public function test_student_with_zero_notifications_sees_minimal_empty_state(): void
    {
        [$student] = $this->createStudent();

        $response = $this->actingAs($student)
            ->withSession(['data_privacy_consented' => true, 'privacy_consented_session' => true])
            ->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('sf-notif-panel', false);
        $response->assertSee('No notifications yet.');
        $response->assertSee('Updates about your verification and sponsorship applications will appear here.');
        $response->assertDontSee('class="sf-notif-item', false);
        $response->assertDontSee('Mark all as read');
    }

    /** 2. Student with one notification */
    public function test_student_with_one_notification_sees_single_row(): void
    {
        [$student] = $this->createStudent();
        $this->createNotification($student, [
            'title' => 'Single Notification Title',
            'message' => 'Single notification content message.',
        ]);

        $response = $this->actingAs($student)
            ->withSession(['data_privacy_consented' => true, 'privacy_consented_session' => true])
            ->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('Single Notification Title');
        $response->assertSee('Single notification content message.');
        $response->assertSee('sf-notif-item', false);
        $response->assertDontSee('No notifications yet.');
    }

    /** 3. Student with multiple notifications */
    public function test_student_with_multiple_notifications_sees_all_rows(): void
    {
        [$student] = $this->createStudent();
        $this->createNotification($student, ['title' => 'First Notification']);
        $this->createNotification($student, ['title' => 'Second Notification']);
        $this->createNotification($student, ['title' => 'Third Notification']);

        $response = $this->actingAs($student)
            ->withSession(['data_privacy_consented' => true, 'privacy_consented_session' => true])
            ->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('First Notification');
        $response->assertSee('Second Notification');
        $response->assertSee('Third Notification');
    }

    /** 4. Long notification titles and messages */
    public function test_long_notification_title_and_message_renders_without_breaking(): void
    {
        [$student] = $this->createStudent();
        $longTitle = 'Notice of Comprehensive Institutional SLE-FHE Qualification and Sponsorship Board Review Outcome';
        $longMessage = 'We are pleased to inform you that your application for the Davao Oriental State University Academic Year 2026-2027 Provincial Tertiary Education Support Program has been formally verified and forwarded to the Office of Student Affairs and FASSG Board for final confirmation and allocation.';

        $this->createNotification($student, [
            'title' => $longTitle,
            'message' => $longMessage,
        ]);

        $response = $this->actingAs($student)
            ->withSession(['data_privacy_consented' => true, 'privacy_consented_session' => true])
            ->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee($longTitle);
        $response->assertSee($longMessage);
    }

    /** 5. Read and unread notifications distinction */
    public function test_read_and_unread_notifications_have_distinct_badges_and_classes(): void
    {
        [$student] = $this->createStudent();
        $this->createNotification($student, ['title' => 'Unread Notice'], isRead: false);
        $this->createNotification($student, ['title' => 'Already Read Notice'], isRead: true);

        $response = $this->actingAs($student)
            ->withSession(['data_privacy_consented' => true, 'privacy_consented_session' => true])
            ->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('is-unread', false);
        $response->assertSee('is-read', false);
        $response->assertSee('Unread');
        $response->assertSee('Read');
    }

    /** 6. Marking a notification as read */
    public function test_student_can_mark_individual_notification_as_read(): void
    {
        [$student] = $this->createStudent();
        $notif = $this->createNotification($student, isRead: false);

        $this->assertNull($notif->read_at);

        $response = $this->actingAs($student)
            ->withSession(['data_privacy_consented' => true, 'privacy_consented_session' => true])
            ->patch(route('notifications.read', $notif->id));

        $response->assertRedirect();
        $this->assertNotNull($notif->fresh()->read_at);
    }

    /** 7. Clicking notification details marks it as read and redirects */
    public function test_clicking_notification_details_marks_it_read_and_redirects(): void
    {
        [$student, $profile] = $this->createStudent();
        $program = SponsorshipProgram::factory()->create();
        $application = Application::factory()->create([
            'student_profile_id' => $profile->id,
            'sponsorship_program_id' => $program->id,
        ]);

        $targetUrl = route('student.applications.show', $application);
        $notif = $this->createNotification($student, ['url' => $targetUrl], isRead: false);

        $response = $this->actingAs($student)
            ->withSession(['data_privacy_consented' => true, 'privacy_consented_session' => true])
            ->get(route('student.notifications.read', $notif));

        $response->assertRedirect($targetUrl);
        $this->assertNotNull($notif->fresh()->read_at);
    }

    /** 8. Notification count updates correctly */
    public function test_notification_count_updates_correctly(): void
    {
        [$student] = $this->createStudent();
        $this->createNotification($student, isRead: false);
        $this->createNotification($student, isRead: false);

        $this->assertEquals(2, $student->unreadNotifications()->count());

        // Mark all as read
        $response = $this->actingAs($student)
            ->withSession(['data_privacy_consented' => true, 'privacy_consented_session' => true])
            ->post(route('notifications.readAll'));

        $response->assertRedirect();
        $this->assertEquals(0, $student->unreadNotifications()->count());
    }

    /** 9. Refresh button is present and links safely */
    public function test_refresh_button_is_present_with_refresh_link(): void
    {
        [$student] = $this->createStudent();

        $response = $this->actingAs($student)
            ->withSession(['data_privacy_consented' => true, 'privacy_consented_session' => true])
            ->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('sf-btn-refresh', false);
        $response->assertSee(route('notifications.index'));
        $response->assertSee('Refresh');
    }

    /** 10. Unauthorized access to another student's notifications */
    public function test_student_cannot_access_or_mark_another_students_notification(): void
    {
        [$student1] = $this->createStudent('Student One');
        [$student2] = $this->createStudent('Student Two');

        $notif2 = $this->createNotification($student2, isRead: false);

        // Student 1 tries to mark Student 2's notification as read
        $response = $this->actingAs($student1)
            ->withSession(['data_privacy_consented' => true, 'privacy_consented_session' => true])
            ->patch(route('notifications.read', $notif2->id));

        $response->assertNotFound();
        $this->assertNull($notif2->fresh()->read_at);

        // Student 1 tries to read Student 2's notification via GET route
        $responseGet = $this->actingAs($student1)
            ->withSession(['data_privacy_consented' => true, 'privacy_consented_session' => true])
            ->get(route('student.notifications.read', $notif2));

        $responseGet->assertNotFound();
        $this->assertNull($notif2->fresh()->read_at);
    }
}

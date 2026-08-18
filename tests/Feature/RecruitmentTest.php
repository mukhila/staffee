<?php

namespace Tests\Feature;

use App\Models\HR\OnboardingChecklist;
use App\Models\HR\OnboardingTask;
use App\Models\Recruitment\JobApplication;
use App\Models\Recruitment\JobPosting;
use App\Models\User;
use App\Services\HR\OnboardingService;
use App\Services\Recruitment\JobApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class RecruitmentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makePosting(array $overrides = []): JobPosting
    {
        return JobPosting::create(array_merge([
            'title'           => 'Software Engineer',
            'employment_type' => 'full_time',
            'status'          => 'open',
            'posted_by'       => $this->admin->id,
            'published_at'    => now(),
            'openings'        => 1,
        ], $overrides));
    }

    // ── 1. Public index lists open postings, excludes drafts ──────────────────

    public function test_public_index_lists_open_postings_and_excludes_drafts(): void
    {
        $open  = $this->makePosting(['title' => 'Open Role',  'status' => 'open']);
        $draft = $this->makePosting(['title' => 'Draft Role', 'status' => 'draft']);

        $response = $this->get(route('jobs.index'));

        $response->assertStatus(200);
        $response->assertSee('Open Role');
        $response->assertDontSee('Draft Role');
    }

    // ── 2. Valid apply creates application with resume stored on local disk ───

    public function test_apply_creates_application_with_resume(): void
    {
        Storage::fake('local');

        $posting = $this->makePosting();

        $response = $this->post(route('jobs.apply', $posting), [
            'applicant_name'  => 'Jane Smith',
            'applicant_email' => 'jane@example.com',
            'applicant_phone' => '555-1234',
            'resume'          => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
            'cover_letter'    => 'I am very interested in this role.',
            'source'          => 'LinkedIn',
        ]);

        $response->assertRedirect(route('jobs.index'));

        $app = JobApplication::first();
        $this->assertNotNull($app);
        $this->assertSame('jane@example.com', $app->applicant_email);
        $this->assertNotNull($app->resume_path);
        Storage::disk('local')->assertExists($app->resume_path);
    }

    // ── 3. Apply to closed posting returns error ──────────────────────────────

    public function test_apply_to_closed_posting_returns_error(): void
    {
        $posting = $this->makePosting(['status' => 'closed']);

        $response = $this->post(route('jobs.apply', $posting), [
            'applicant_name'  => 'Bob',
            'applicant_email' => 'bob@example.com',
        ]);

        // Should redirect back with errors (service throws exception, controller catches it)
        $response->assertRedirect();
        $this->assertSame(0, JobApplication::count());
    }

    // ── 4. Admin can advance application status ───────────────────────────────

    public function test_admin_can_advance_application_status(): void
    {
        $posting = $this->makePosting();
        $app = JobApplication::create([
            'job_posting_id'  => $posting->id,
            'applicant_name'  => 'Alice',
            'applicant_email' => 'alice@example.com',
            'status'          => 'new',
            'applied_at'      => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.recruitment.applications.status', $app), [
                'status' => 'screening',
            ]);

        $response->assertRedirect();
        $this->assertSame('screening', $app->fresh()->status);
    }

    // ── 5. Admin hire creates onboarding checklist ────────────────────────────

    public function test_admin_hire_creates_onboarding_checklist(): void
    {
        $posting = $this->makePosting();
        $app = JobApplication::create([
            'job_posting_id'  => $posting->id,
            'applicant_name'  => 'Charlie',
            'applicant_email' => 'charlie@example.com',
            'status'          => 'offer_sent',
            'applied_at'      => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.recruitment.applications.hire', $app));

        $response->assertRedirect();
        $this->assertSame('hired', $app->fresh()->status);
        $this->assertSame(1, OnboardingChecklist::count());
        $this->assertTrue(OnboardingTask::count() > 0);
    }

    // ── 6. Service: valid status transitions work ─────────────────────────────

    public function test_service_valid_status_transition(): void
    {
        $posting = $this->makePosting();
        $app = JobApplication::create([
            'job_posting_id'  => $posting->id,
            'applicant_name'  => 'Dave',
            'applicant_email' => 'dave@example.com',
            'status'          => 'new',
            'applied_at'      => now(),
        ]);

        $service = app(JobApplicationService::class);
        $service->advanceStatus($app, 'screening', $this->admin->id);

        $this->assertSame('screening', $app->fresh()->status);
    }

    // ── 7. Service: invalid transition throws exception ───────────────────────

    public function test_service_invalid_transition_throws(): void
    {
        $posting = $this->makePosting();
        $app = JobApplication::create([
            'job_posting_id'  => $posting->id,
            'applicant_name'  => 'Eve',
            'applicant_email' => 'eve@example.com',
            'status'          => 'new',
            'applied_at'      => now(),
        ]);

        $service = app(JobApplicationService::class);

        $this->expectException(InvalidArgumentException::class);
        $service->advanceStatus($app, 'hired', $this->admin->id);
    }

    // ── 8. Completing all tasks advances checklist to completed ───────────────

    public function test_complete_task_advances_checklist_status(): void
    {
        $checklist = OnboardingChecklist::create([
            'user_id'       => $this->admin->id,
            'template_name' => 'Test',
            'status'        => 'pending',
        ]);

        $task = OnboardingTask::create([
            'checklist_id' => $checklist->id,
            'title'        => 'Single task',
            'status'       => 'pending',
            'sort_order'   => 1,
        ]);

        $service = app(OnboardingService::class);
        $service->completeTask($task, $this->admin->id);

        $this->assertSame('done', $task->fresh()->status);
        $this->assertSame('completed', $checklist->fresh()->status);
        $this->assertNotNull($checklist->fresh()->completed_at);
    }

    // ── 9. Completing one of many tasks sets checklist to in_progress ─────────

    public function test_completing_one_task_of_many_sets_in_progress(): void
    {
        $checklist = OnboardingChecklist::create([
            'user_id'       => $this->admin->id,
            'template_name' => 'Test Multi',
            'status'        => 'pending',
        ]);

        $task1 = OnboardingTask::create(['checklist_id'=>$checklist->id,'title'=>'T1','status'=>'pending','sort_order'=>1]);
        $task2 = OnboardingTask::create(['checklist_id'=>$checklist->id,'title'=>'T2','status'=>'pending','sort_order'=>2]);

        $service = app(OnboardingService::class);
        $service->completeTask($task1, $this->admin->id);

        $this->assertSame('in_progress', $checklist->fresh()->status);
    }
}

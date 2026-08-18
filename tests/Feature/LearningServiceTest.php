<?php

namespace Tests\Feature;

use App\Models\Learning\EmployeeEnrollment;
use App\Models\Learning\LearningCourse;
use App\Models\User;
use App\Services\Learning\LearningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LearningServiceTest extends TestCase
{
    use RefreshDatabase;

    private LearningService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LearningService();
    }

    private function makeCourse(): LearningCourse
    {
        $creator = User::factory()->create();
        return LearningCourse::create([
            'title'      => 'Test Course',
            'status'     => 'active',
            'cost'       => '0',
            'created_by' => $creator->id,
        ]);
    }

    private function makeUser(): User
    {
        return User::factory()->create();
    }

    public function test_enroll_creates_enrollment_record(): void
    {
        $course = $this->makeCourse();
        $user   = $this->makeUser();
        $actor  = $this->makeUser();

        $enrollment = $this->service->enroll($course, $user, $actor->id);

        $this->assertInstanceOf(EmployeeEnrollment::class, $enrollment);
        $this->assertEquals($user->id, $enrollment->user_id);
        $this->assertEquals($course->id, $enrollment->course_id);
        $this->assertEquals('enrolled', $enrollment->status);
    }

    public function test_double_enroll_throws(): void
    {
        $course = $this->makeCourse();
        $user   = $this->makeUser();
        $actor  = $this->makeUser();

        $this->service->enroll($course, $user, $actor->id);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->enroll($course, $user, $actor->id);
    }

    public function test_start_changes_status_to_in_progress(): void
    {
        $course     = $this->makeCourse();
        $user       = $this->makeUser();
        $enrollment = $this->service->enroll($course, $user, $user->id);

        $this->service->startCourse($enrollment, $user->id);

        $this->assertEquals('in_progress', $enrollment->fresh()->status);
    }

    public function test_start_already_completed_throws(): void
    {
        $course     = $this->makeCourse();
        $user       = $this->makeUser();
        $enrollment = $this->service->enroll($course, $user, $user->id);
        $enrollment->update(['status' => 'completed']);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->startCourse($enrollment->fresh(), $user->id);
    }

    public function test_complete_changes_status_and_stores_certificate(): void
    {
        Storage::fake('local');

        $course     = $this->makeCourse();
        $user       = $this->makeUser();
        $enrollment = $this->service->enroll($course, $user, $user->id);
        $this->service->startCourse($enrollment, $user->id);

        $file = UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf');
        $this->service->completeCourse($enrollment->fresh(), [
            'completion_score' => '85.00',
            'certificate'      => $file,
        ], $user->id);

        $fresh = $enrollment->fresh();
        $this->assertEquals('completed', $fresh->status);
        $this->assertNotNull($fresh->completed_at);
        $this->assertNotNull($fresh->certificate_path);
        Storage::disk('local')->assertExists($fresh->certificate_path);
    }

    public function test_complete_when_not_in_progress_or_enrolled_throws(): void
    {
        $course     = $this->makeCourse();
        $user       = $this->makeUser();
        $enrollment = $this->service->enroll($course, $user, $user->id);
        $enrollment->update(['status' => 'dropped']);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->completeCourse($enrollment->fresh(), [], $user->id);
    }

    public function test_drop_changes_status_to_dropped(): void
    {
        $course     = $this->makeCourse();
        $user       = $this->makeUser();
        $enrollment = $this->service->enroll($course, $user, $user->id);

        $this->service->dropCourse($enrollment, $user->id);

        $this->assertEquals('dropped', $enrollment->fresh()->status);
    }
}

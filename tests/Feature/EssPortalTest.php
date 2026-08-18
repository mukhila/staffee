<?php

namespace Tests\Feature;

use App\Models\HR\DocumentRequest;
use App\Models\HR\Suggestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EssPortalTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private User $otherStaff;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->staff      = User::factory()->create(['role' => 'staff']);
        $this->otherStaff = User::factory()->create(['role' => 'staff']);
        $this->admin      = User::factory()->create(['role' => 'admin']);
    }

    // ── ESS Dashboard ────────────────────────────────────────────────────────

    public function test_ess_index_renders_for_authenticated_staff(): void
    {
        $response = $this->actingAs($this->staff)
            ->get(route('staff.ess.index'));

        $response->assertOk();
        $response->assertViewIs('staff.ess.index');
    }

    public function test_ess_index_requires_authentication(): void
    {
        $response = $this->get(route('staff.ess.index'));

        $response->assertRedirect(route('login'));
    }

    // ── Document Requests ────────────────────────────────────────────────────

    public function test_document_request_create_view_renders(): void
    {
        $response = $this->actingAs($this->staff)
            ->get(route('staff.document-requests.create'));

        $response->assertOk();
        $response->assertViewIs('staff.document-requests.create');
    }

    public function test_document_request_store_creates_record(): void
    {
        $response = $this->actingAs($this->staff)
            ->post(route('staff.document-requests.store'), [
                'document_type' => 'experience_letter',
                'purpose'       => 'Needed for visa application',
            ]);

        $response->assertRedirect(route('staff.document-requests.index'));

        $this->assertDatabaseHas('document_requests', [
            'user_id'       => $this->staff->id,
            'document_type' => 'experience_letter',
            'purpose'       => 'Needed for visa application',
            'status'        => 'pending',
        ]);
    }

    public function test_document_request_store_custom_type(): void
    {
        $this->actingAs($this->staff)
            ->post(route('staff.document-requests.store'), [
                'document_type' => 'custom',
                'custom_type'   => 'Visa Support Letter',
                'purpose'       => 'Embassy requirement',
            ]);

        $this->assertDatabaseHas('document_requests', [
            'user_id'       => $this->staff->id,
            'document_type' => 'custom',
            'custom_type'   => 'Visa Support Letter',
        ]);
    }

    public function test_document_request_index_only_shows_own_requests(): void
    {
        // Other staff's request
        DocumentRequest::create([
            'user_id'       => $this->otherStaff->id,
            'document_type' => 'noc',
            'status'        => 'pending',
            'requested_at'  => now(),
        ]);

        // Own request
        DocumentRequest::create([
            'user_id'       => $this->staff->id,
            'document_type' => 'experience_letter',
            'status'        => 'pending',
            'requested_at'  => now(),
        ]);

        $response = $this->actingAs($this->staff)
            ->get(route('staff.document-requests.index'));

        $response->assertOk();
        // The page should show 1 request (own) and not show the other's
        $response->assertViewHas('requests', function ($requests) {
            return $requests->count() === 1
                && (int)$requests->first()->user_id === $this->staff->id;
        });
    }

    // ── Suggestions ──────────────────────────────────────────────────────────

    public function test_suggestion_store_non_anonymous_creates_with_user_id(): void
    {
        $response = $this->actingAs($this->staff)
            ->post(route('staff.suggestions.store'), [
                'title'        => 'Better coffee in breakroom',
                'body'         => 'We need better coffee options.',
                'category'     => 'Culture',
                'is_anonymous' => '0',
            ]);

        $response->assertRedirect(route('staff.suggestions.index'));

        $this->assertDatabaseHas('suggestions', [
            'user_id'      => $this->staff->id,
            'title'        => 'Better coffee in breakroom',
            'is_anonymous' => false,
        ]);
    }

    public function test_suggestion_store_anonymous_stores_user_id_null(): void
    {
        $response = $this->actingAs($this->staff)
            ->post(route('staff.suggestions.store'), [
                'title'        => 'Anonymous feedback',
                'body'         => 'This is anonymous.',
                'is_anonymous' => '1',
            ]);

        $response->assertRedirect(route('staff.suggestions.index'));

        $this->assertDatabaseHas('suggestions', [
            'user_id'      => null,
            'title'        => 'Anonymous feedback',
            'is_anonymous' => true,
        ]);
    }
}

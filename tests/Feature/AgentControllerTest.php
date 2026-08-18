<?php

namespace Tests\Feature;

use App\Models\Monitoring\MonitoringSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature tests for the Agent API endpoints and AgentAuthenticate middleware.
 *
 * All agent routes live under /api/agent and require a valid Bearer token whose
 * SHA-256 hash is stored in users.agent_token.
 */
class AgentControllerTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Create a user with a known plain-text token and its SHA-256 hash stored
     * in agent_token.  Returns [$user, $plainToken].
     */
    private function makeAgentUser(array $attrs = []): array
    {
        $plainToken = 'test-agent-token-' . uniqid();
        $user = User::factory()->create(array_merge([
            'is_active'   => true,
            'agent_token' => hash('sha256', $plainToken),
        ], $attrs));

        return [$user, $plainToken];
    }

    /**
     * Make a GET/POST request with a Bearer token header.
     */
    private function withBearerToken(string $token): array
    {
        return ['Authorization' => 'Bearer ' . $token];
    }

    /**
     * Create an active MonitoringSession for the given user.
     */
    private function createSession(User $user): MonitoringSession
    {
        return MonitoringSession::create([
            'user_id'           => $user->id,
            'started_at'        => now(),
            'last_heartbeat_at' => now(),
            'ip_address'        => '127.0.0.1',
            'status'            => 'active',
        ]);
    }

    // ─── Authentication ───────────────────────────────────────────────────────

    public function test_valid_bearer_token_grants_access(): void
    {
        [$user, $token] = $this->makeAgentUser();

        $response = $this->postJson('/api/agent/session/start', [], $this->withBearerToken($token));

        // Should not be 401 or 403 — any 2xx is acceptable here
        $response->assertSuccessful();
    }

    public function test_invalid_bearer_token_returns_401(): void
    {
        $this->makeAgentUser(); // create a real user to ensure DB is not empty

        $response = $this->postJson('/api/agent/heartbeat', [], $this->withBearerToken('wrong-token'));

        $response->assertStatus(401);
    }

    public function test_missing_authorization_header_returns_401(): void
    {
        $response = $this->postJson('/api/agent/heartbeat', []);

        $response->assertStatus(401);
    }

    public function test_query_string_token_is_rejected_with_401(): void
    {
        [$user, $token] = $this->makeAgentUser();

        // Token in query string — middleware only accepts Authorization: Bearer
        $response = $this->postJson('/api/agent/heartbeat?agent_token=' . $token);

        $response->assertStatus(401);
    }

    public function test_inactive_user_is_rejected_with_403(): void
    {
        [$user, $token] = $this->makeAgentUser(['is_active' => false]);

        $response = $this->postJson('/api/agent/heartbeat', [], $this->withBearerToken($token));

        $response->assertStatus(403);
    }

    public function test_token_must_be_sent_as_bearer_not_basic(): void
    {
        [$user, $token] = $this->makeAgentUser();

        // Wrong auth scheme
        $response = $this->withHeaders(['Authorization' => 'Basic ' . base64_encode($token . ':')
        ])->postJson('/api/agent/heartbeat');

        $response->assertStatus(401);
    }

    // ─── Heartbeat ───────────────────────────────────────────────────────────

    public function test_heartbeat_updates_session_last_heartbeat(): void
    {
        [$user, $token] = $this->makeAgentUser();
        $session = $this->createSession($user);

        // Push last_heartbeat back so we can detect the update
        $oldTime = now()->subMinutes(5);
        $session->update(['last_heartbeat_at' => $oldTime]);

        $response = $this->postJson('/api/agent/heartbeat', [
            'session_id' => $session->id,
        ], $this->withBearerToken($token));

        $response->assertOk()
                 ->assertJsonPath('ok', true);

        $session->refresh();
        $this->assertTrue(
            $session->last_heartbeat_at->gt($oldTime),
            'last_heartbeat_at should have been updated'
        );
    }

    public function test_heartbeat_with_no_session_still_returns_ok(): void
    {
        [$user, $token] = $this->makeAgentUser();

        // session_id that doesn't exist — heartbeat still returns 200
        $response = $this->postJson('/api/agent/heartbeat', [
            'session_id' => 99999,
        ], $this->withBearerToken($token));

        $response->assertOk();
    }

    // ─── Screenshot ───────────────────────────────────────────────────────────

    public function test_screenshot_stores_file_on_local_disk_not_public(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        [$user, $token] = $this->makeAgentUser();
        $session = $this->createSession($user);

        $file = UploadedFile::fake()->image('screenshot.jpg');

        $response = $this->postJson('/api/agent/screenshot', [
            'session_id'  => $session->id,
            'captured_at' => now()->toIso8601String(),
            'file'        => $file,
        ], $this->withBearerToken($token));

        $response->assertOk()
                 ->assertJsonPath('ok', true);

        // File should land on the local disk only, NOT on the public disk
        $localFiles  = Storage::disk('local')->allFiles();
        $publicFiles = Storage::disk('public')->allFiles();

        $this->assertNotEmpty($localFiles, 'Screenshot must be stored on the local disk');
        $this->assertEmpty($publicFiles,   'Screenshot must NOT be stored on the public disk');

        // Path structure: monitoring/screenshots/{user_id}/{Y/m/d}/...
        $storedPath = $localFiles[0];
        $this->assertStringContainsString('monitoring/screenshots/' . $user->id, $storedPath);
    }

    public function test_screenshot_without_file_returns_422(): void
    {
        [$user, $token] = $this->makeAgentUser();
        $session = $this->createSession($user);

        $response = $this->postJson('/api/agent/screenshot', [
            'session_id'  => $session->id,
            'captured_at' => now()->toIso8601String(),
            // No 'file'
        ], $this->withBearerToken($token));

        $response->assertStatus(422);
    }

    public function test_screenshot_with_invalid_session_returns_422(): void
    {
        Storage::fake('local');

        [$user, $token] = $this->makeAgentUser();
        $file = UploadedFile::fake()->image('shot.jpg');

        $response = $this->postJson('/api/agent/screenshot', [
            'session_id'  => 99999,
            'captured_at' => now()->toIso8601String(),
            'file'        => $file,
        ], $this->withBearerToken($token));

        $response->assertStatus(422);
    }

    // ─── Session start / end ─────────────────────────────────────────────────

    public function test_session_start_returns_session_id_and_config(): void
    {
        [$user, $token] = $this->makeAgentUser();

        $response = $this->postJson('/api/agent/session/start', [
            'hostname'      => 'dev-laptop',
            'agent_version' => '1.0.0',
        ], $this->withBearerToken($token));

        $response->assertOk()
                 ->assertJsonStructure(['session_id', 'config']);

        $this->assertDatabaseHas('monitoring_sessions', [
            'user_id' => $user->id,
            'status'  => 'active',
        ]);
    }

    public function test_session_start_expires_previous_active_session(): void
    {
        [$user, $token] = $this->makeAgentUser();
        $oldSession = $this->createSession($user);

        $this->postJson('/api/agent/session/start', [], $this->withBearerToken($token));

        $oldSession->refresh();
        $this->assertSame('expired', $oldSession->status);
    }
}

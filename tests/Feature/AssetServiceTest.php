<?php

namespace Tests\Feature;

use App\Models\Asset\Asset;
use App\Models\Asset\AssetAssignment;
use App\Models\User;
use App\Services\Asset\AssetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetServiceTest extends TestCase
{
    use RefreshDatabase;

    private AssetService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AssetService();
    }

    private function makeAsset(string $status = 'available'): Asset
    {
        return Asset::create([
            'asset_tag' => 'TST-' . rand(1000, 9999),
            'name'      => 'Test Asset',
            'category'  => 'laptop',
            'status'    => $status,
        ]);
    }

    private function makeUser(): User
    {
        return User::factory()->create();
    }

    public function test_assign_available_asset_creates_assignment_and_sets_status(): void
    {
        $asset = $this->makeAsset('available');
        $user  = $this->makeUser();
        $actor = $this->makeUser();

        $assignment = $this->service->assign($asset, $user, $actor->id);

        $this->assertInstanceOf(AssetAssignment::class, $assignment);
        $this->assertEquals($user->id, $assignment->user_id);
        $this->assertTrue($assignment->is_current);
        $this->assertEquals('assigned', $asset->fresh()->status);
    }

    public function test_assigning_non_available_asset_throws(): void
    {
        $asset = $this->makeAsset('assigned');
        $user  = $this->makeUser();
        $actor = $this->makeUser();

        $this->expectException(\InvalidArgumentException::class);
        $this->service->assign($asset, $user, $actor->id);
    }

    public function test_return_asset_sets_status_available_and_closes_assignment(): void
    {
        $asset = $this->makeAsset('available');
        $user  = $this->makeUser();
        $actor = $this->makeUser();

        $assignment = $this->service->assign($asset, $user, $actor->id);
        $this->service->returnAsset($assignment, 'good', 'All fine', $actor->id);

        $this->assertFalse($assignment->fresh()->is_current);
        $this->assertEquals('good', $assignment->fresh()->return_condition);
        $this->assertEquals('available', $asset->fresh()->status);
    }

    public function test_return_already_closed_assignment_throws(): void
    {
        $asset = $this->makeAsset('available');
        $user  = $this->makeUser();
        $actor = $this->makeUser();

        $assignment = $this->service->assign($asset, $user, $actor->id);
        $this->service->returnAsset($assignment, 'good', null, $actor->id);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->returnAsset($assignment->fresh(), 'good', null, $actor->id);
    }

    public function test_send_for_repair_sets_status_in_repair(): void
    {
        $asset = $this->makeAsset('available');
        $actor = $this->makeUser();

        $this->service->sendForRepair($asset, $actor->id);

        $this->assertEquals('in_repair', $asset->fresh()->status);
    }

    public function test_send_for_repair_already_in_repair_throws(): void
    {
        $asset = $this->makeAsset('in_repair');
        $actor = $this->makeUser();

        $this->expectException(\InvalidArgumentException::class);
        $this->service->sendForRepair($asset, $actor->id);
    }
}

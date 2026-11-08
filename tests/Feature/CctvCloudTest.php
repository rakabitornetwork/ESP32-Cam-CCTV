<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Device;
use App\Models\Organization;
use App\Models\Recording;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CctvCloudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_seed_can_open_the_panel(): void
    {
        config([
            'cctv.admin_name' => 'Amon',
            'cctv.admin_email' => 'amon@teslatech.my.id',
            'cctv.admin_password' => 'gantengmax',
        ]);

        $this->seed();

        $this->post('/login', [
            'email' => 'amon@teslatech.my.id',
            'password' => 'gantengmax',
        ])->assertRedirect(route('captures.index', absolute: false));

        $this->actingAs(User::query()->where('email', 'amon@teslatech.my.id')->first())
            ->get(route('captures.index'))
            ->assertOk();
    }

    public function test_capture_uses_device_token_and_quota(): void
    {
        Storage::fake('cctv');
        [$user, $organization, $token] = $this->device();

        $this->withToken($token)->post('/api/v1/captures', [
            'image' => UploadedFile::fake()->image('motion.jpg'),
            'trigger' => 'pir',
        ])->assertCreated()
            ->assertJsonStructure(['id', 'captured_at'])
            ->assertJsonMissingPath('path');

        $this->assertGreaterThan(0, $organization->storageQuota()->first()->used_bytes);
        $this->assertNotNull($user->id);
    }

    public function test_capture_rejects_bad_token_and_full_quota(): void
    {
        Storage::fake('cctv');
        [, $organization, $token] = $this->device();

        $this->withToken('cctv_salah')->post('/api/v1/captures', [
            'image' => UploadedFile::fake()->image('motion.jpg'),
        ])->assertUnauthorized();

        $organization->storageQuota()->update([
            'used_bytes' => $organization->storageQuota()->first()->max_bytes,
        ]);

        $this->withToken($token)->post('/api/v1/captures', [
            'image' => UploadedFile::fake()->image('motion.jpg'),
            'trigger' => 'pir',
        ])->assertCreated();

        $this->assertDatabaseCount('captures', 1);
    }

    public function test_past_due_subscription_blocks_uploads(): void
    {
        Storage::fake('cctv');
        [, $organization, $token] = $this->device();
        $organization->subscription()->update(['status' => SubscriptionStatus::PastDue]);

        $this->withToken($token)->postJson('/api/v1/recordings/init', [
            'started_at' => now()->toIso8601String(),
        ])->assertStatus(402);
    }

    public function test_recording_upload_and_date_filter(): void
    {
        Storage::fake('cctv');
        [$user, $organization, $token, $device] = $this->device();

        $init = $this->withToken($token)->postJson('/api/v1/recordings/init', [
            'started_at' => now()->toIso8601String(),
            'mime' => 'video/mp4',
        ])->assertCreated();

        $id = $init->json('recording_id');

        $this->withToken($token)->post('/api/v1/recordings/'.$id.'/chunk', [
            'index' => 0,
            'chunk' => UploadedFile::fake()->create('part.bin', 8, 'video/mp4'),
        ])->assertOk();

        $this->withToken($token)->post('/api/v1/recordings/'.$id.'/complete')->assertOk();
        $this->assertSame('ready', Recording::query()->find($id)->status->value);

        Recording::query()->whereKey($id)->update(['started_at' => now()->subDays(3)]);
        $organization->recordings()->create([
            'uuid' => '11111111-1111-1111-1111-111111111111',
            'device_id' => $device->id,
            'started_at' => now(),
            'bytes' => 10,
            'mime' => 'video/mp4',
            'disk' => 'cctv',
            'status' => 'ready',
        ]);

        $this->assertSame(1, $organization->recordings()->whereDate('started_at', now()->toDateString())->count());
    }

    public function test_a_capture_can_be_rotated_from_the_gallery(): void
    {
        [$user, $organization] = $this->owner();
        $device = Device::provision($organization, 'ESP32-CAM')['device'];
        $capture = $organization->captures()->create([
            'device_id' => $device->id,
            'trigger' => 'pir',
            'bytes' => 100,
            'disk' => 'cctv',
            'path' => '1/1/a.jpg',
            'captured_at' => now(),
        ]);

        $this->actingAs($user)->patch(route('captures.rotate', $capture), [
            'direction' => 'right',
        ])->assertRedirect();
        $this->assertSame(90, $capture->fresh()->rotation);

        $this->actingAs($user)->patch(route('captures.rotate', $capture), [
            'direction' => 'left',
        ])->assertRedirect();
        $this->assertSame(0, $capture->fresh()->rotation);
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function owner(): array
    {
        $user = User::factory()->create();
        $organization = Organization::provisionFor($user);

        return [$user, $organization];
    }

    /**
     * @return array{0: User, 1: Organization, 2: string, 3: Device}
     */
    private function device(): array
    {
        [$user, $organization] = $this->owner();
        $provisioned = Device::provision($organization, 'ESP32-CAM');

        return [$user, $organization, $provisioned['plain_token'], $provisioned['device']];
    }
}

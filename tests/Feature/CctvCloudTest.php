<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Device;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Recording;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
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
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_member_cannot_open_admin_panel(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.update'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.update.apply'))->assertForbidden();
    }

    public function test_admin_can_open_the_update_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Organization::provisionFor($admin);

        $this->actingAs($admin)->get(route('admin.update'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Update')
                ->where('status.available', true)
                ->where('status.branch', 'main'));
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
        ])->assertStatus(402);

        $this->assertDatabaseCount('captures', 0);
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

        $this->actingAs($user)->get(route('recordings.index', [
            'from' => now()->toDateString(),
            'to' => now()->toDateString(),
        ]))->assertInertia(fn (Assert $page) => $page
            ->component('Recordings/Index')
            ->has('recordings.data', 1));
    }

    public function test_second_device_is_blocked_on_the_free_plan(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)->post(route('devices.store'), [
            'name' => 'Kamera satu',
        ])->assertRedirect();

        $this->actingAs($user)->post(route('devices.store'), [
            'name' => 'Kamera dua',
        ])->assertSessionHasErrors('name');
    }

    public function test_device_coordinates_must_be_a_pair_and_stay_private(): void
    {
        [$user, $organization] = $this->owner();
        $device = Device::provision($organization, 'Gerbang')['device'];
        $stranger = User::factory()->create();
        Organization::provisionFor($stranger);

        $this->actingAs($stranger)->get(route('devices.show', $device))->assertForbidden();

        $this->actingAs($user)->put(route('devices.update', $device), [
            'name' => 'Gerbang',
            'latitude' => -6.2,
        ])->assertSessionHasErrors('longitude');
    }

    public function test_marking_an_invoice_paid_is_idempotent(): void
    {
        [$user, $organization] = $this->owner();
        $plan = Plan::query()->where('slug', 'dasar')->firstOrFail();

        $this->actingAs($user)->post(route('billing.store'), [
            'plan_id' => $plan->id,
        ])->assertRedirect();

        $invoice = $organization->invoices()->firstOrFail();
        $this->assertSame('pending', $invoice->status->value);

        $this->actingAs($user)->post(route('billing.pay', $invoice))->assertRedirect();
        $paidAt = $invoice->fresh()->paid_at;

        $this->actingAs($user)->post(route('billing.pay', $invoice))->assertRedirect();

        $this->assertTrue($invoice->fresh()->paid_at->eq($paidAt));
        $this->assertSame($plan->id, $organization->subscription()->first()->plan_id);
        $this->assertSame($plan->max_bytes, $organization->storageQuota()->first()->max_bytes);
    }

    public function test_device_image_quality_is_sent_to_the_camera(): void
    {
        [$user, , $token, $device] = $this->device();

        $this->withToken($token)->getJson('/api/v1/devices/me')
            ->assertOk()
            ->assertJsonPath('image_quality', 'standar');

        $this->actingAs($user)->put(route('devices.update', $device), [
            'name' => 'ESP32-CAM',
            'image_quality' => 'tajam',
        ])->assertRedirect();

        $this->assertSame('tajam', $device->fresh()->image_quality);

        $this->withToken($token)->getJson('/api/v1/devices/me')
            ->assertOk()
            ->assertJsonPath('image_quality', 'tajam');

        $this->actingAs($user)->put(route('devices.update', $device), [
            'name' => 'ESP32-CAM',
            'image_quality' => 'sangat-tajam',
        ])->assertSessionHasErrors('image_quality');
    }

    public function test_device_custom_image_settings_are_sent_to_the_camera(): void
    {
        [$user, , $token, $device] = $this->device();

        $this->withToken($token)->getJson('/api/v1/devices/me')
            ->assertOk()
            ->assertJsonPath('image_settings.brightness', 0)
            ->assertJsonPath('image_settings.saturation', -1)
            ->assertJsonPath('image_settings.hmirror', false);

        $this->actingAs($user)->put(route('devices.update', $device), [
            'name' => 'ESP32-CAM',
            'image_quality' => 'standar',
            'image_settings' => [
                'brightness' => 1,
                'contrast' => -1,
                'saturation' => 0,
                'sharpness' => 2,
                'ae_level' => 1,
                'wb_mode' => 4,
                'hmirror' => true,
                'vflip' => false,
            ],
        ])->assertRedirect();

        $this->withToken($token)->getJson('/api/v1/devices/me')
            ->assertOk()
            ->assertJsonPath('image_settings.brightness', 1)
            ->assertJsonPath('image_settings.contrast', -1)
            ->assertJsonPath('image_settings.sharpness', 2)
            ->assertJsonPath('image_settings.wb_mode', 4)
            ->assertJsonPath('image_settings.hmirror', true)
            ->assertJsonPath('image_settings.vflip', false);

        $this->actingAs($user)->put(route('devices.update', $device), [
            'name' => 'ESP32-CAM',
            'image_quality' => 'standar',
            'image_settings' => [
                'brightness' => 9,
                'contrast' => 0,
                'saturation' => 0,
                'sharpness' => 0,
                'ae_level' => 0,
                'wb_mode' => 0,
                'hmirror' => false,
                'vflip' => false,
            ],
        ])->assertSessionHasErrors('image_settings.brightness');
    }

    public function test_viewer_cannot_change_plan(): void
    {
        [, $organization] = $this->owner();
        $viewer = User::factory()->create();
        $organization->users()->attach($viewer->id, ['role' => 'viewer']);
        $plan = Plan::query()->where('slug', 'dasar')->firstOrFail();

        $this->actingAs($viewer)->post(route('billing.store'), [
            'plan_id' => $plan->id,
        ])->assertForbidden();
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

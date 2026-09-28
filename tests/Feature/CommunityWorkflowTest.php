<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommunityWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_and_report_submission_award_ten_points(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Ayu Lestari',
            'rt_rw' => 'RT 02 / RW 04',
            'email' => 'ayu@example.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('portal.dashboard'));

        $this->post(route('portal.reports.store'), [
            'location' => 'Kali Cempaka dekat jembatan',
            'density' => 'sedang',
            'description' => 'Eceng gondok menutup sebagian aliran.',
        ])->assertRedirect(route('portal.reports.index'));

        $this->assertDatabaseHas('users', ['email' => 'ayu@example.test', 'points' => 10]);
        $this->assertDatabaseHas('reports', [
            'location' => 'Kali Cempaka dekat jembatan',
            'density' => 'sedang',
            'status' => 'baru',
        ]);
    }

    public function test_resident_cannot_change_report_status(): void
    {
        $resident = User::factory()->create();
        $report = Report::create([
            'user_id' => $resident->id,
            'location' => 'Kali Cempaka',
            'density' => 'ringan',
            'description' => 'Tumbuhan mulai terlihat.',
        ]);

        $this->actingAs($resident)
            ->patch(route('admin.reports.update', $report), ['status' => 'selesai'])
            ->assertForbidden();

        $this->assertDatabaseHas('reports', ['id' => $report->id, 'status' => 'baru']);
    }

    public function test_report_photo_is_saved_to_public_storage(): void
    {
        Storage::fake('public');
        $resident = User::factory()->create();

        $this->actingAs($resident)
            ->post(route('portal.reports.store'), [
                'location' => 'Sungai Melati',
                'density' => 'ringan',
                'description' => 'Eceng gondok mulai terlihat.',
                'photo' => UploadedFile::fake()->create('river.jpg', 100, 'image/jpeg'),
            ])
            ->assertRedirect(route('portal.reports.index'));

        $report = Report::firstOrFail();
        $this->assertNotNull($report->photo_path);
        Storage::disk('public')->assertExists($report->photo_path);
    }

    public function test_admin_can_update_report_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $report = Report::create([
            'user_id' => User::factory()->create()->id,
            'location' => 'Sungai Melati',
            'density' => 'parah',
            'description' => 'Aliran hampir tertutup.',
        ]);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->get(route('portal.workdays'))->assertOk();

        $this->actingAs($admin)
            ->patch(route('admin.reports.update', $report), ['status' => 'diproses'])
            ->assertRedirect();

        $this->assertDatabaseHas('reports', ['id' => $report->id, 'status' => 'diproses']);
    }

    public function test_redemption_deducts_points_and_records_request(): void
    {
        $resident = User::factory()->create(['points' => 20]);

        $this->actingAs($resident)
            ->post(route('portal.redeem'), [
                'reward' => 'pulsa',
                'points' => 10,
                'destination' => '081234567890',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $resident->id, 'points' => 10]);
        $this->assertDatabaseHas('redemptions', [
            'user_id' => $resident->id,
            'reward' => 'pulsa',
            'points' => 10,
            'status' => 'menunggu',
        ]);
    }
}
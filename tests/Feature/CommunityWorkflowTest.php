<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\Redemption;
use App\Models\User;
use App\Models\ActivityRegistration;
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
        ])->assertRedirect(route('login'))
            ->assertSessionHas('success');
        $this->assertGuest();

        $resident = User::where('email', 'ayu@example.test')->firstOrFail();
        $this->assertDatabaseHas('users', ['id' => $resident->id, 'registration_status' => 'menunggu']);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->patch(route('admin.residents.update', $resident), ['registration_status' => 'disetujui'])
            ->assertRedirect();

        $this->actingAs($resident)->post(route('portal.reports.store'), [
            'location' => 'Kali Cempaka dekat jembatan',
            'latitude' => '-7.45',
            'longitude' => '112.72',
            'density' => 'sedang',
            'description' => 'Eceng gondok menutup sebagian aliran.',
        ])->assertRedirect(route('portal.reports.index'));

        $this->assertDatabaseHas('users', ['email' => 'ayu@example.test', 'points' => 10]);
        $this->assertDatabaseHas('reports', [
            'location' => 'Kali Cempaka dekat jembatan',
            'latitude' => '-7.4500000',
            'longitude' => '112.7200000',
            'density' => 'sedang',
            'status' => 'baru',
        ]);
    }

    public function test_pending_resident_cannot_log_in_until_admin_approves_registration(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Budi Santoso',
            'rt_rw' => 'RT 02 / RW 04',
            'email' => 'budi@example.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('login'));

        $this->post(route('login.store'), [
            'email' => 'budi@example.test',
            'password' => 'rahasia123',
        ])->assertSessionHasErrors('email');

        $resident = User::where('email', 'budi@example.test')->firstOrFail();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('admin.residents.index'))
            ->assertOk()
            ->assertSee('Budi Santoso');

        $this->patch(route('admin.residents.update', $resident), ['registration_status' => 'disetujui'])
            ->assertRedirect();

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->post(route('login.store'), [
            'email' => 'budi@example.test',
            'password' => 'rahasia123',
        ])->assertRedirect(route('portal.dashboard'));

        $this->assertDatabaseHas('users', ['id' => $resident->id, 'registration_status' => 'disetujui']);
    }

    public function test_admin_can_reject_pending_resident_registration(): void
    {
        $resident = User::factory()->create(['registration_status' => 'menunggu']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patch(route('admin.residents.update', $resident), ['registration_status' => 'ditolak'])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $resident->id, 'registration_status' => 'ditolak']);
        $this->post(route('logout'));
        $this->post(route('login.store'), [
            'email' => $resident->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
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
                'latitude' => '-7.45',
                'longitude' => '112.72',
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
            'latitude' => '-7.45',
            'longitude' => '112.72',
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

    public function test_report_map_only_shows_reports_with_coordinates(): void
    {
        $resident = User::factory()->create();
        Report::create([
            'user_id' => $resident->id,
            'location' => 'Kali Cempaka',
            'latitude' => -7.45,
            'longitude' => 112.72,
            'density' => 'sedang',
            'description' => 'Titik yang dipetakan.',
        ]);

        $this->actingAs($resident)
            ->get(route('portal.reports.index'))
            ->assertOk()
            ->assertSee('Titik eceng gondok di Sidoarjo')
            ->assertSee('Kali Cempaka')
            ->assertSee('112.72');
    }

    public function test_portal_header_has_blank_profile_logout_label_and_no_hero_points_stamp(): void
    {
        $resident = User::factory()->create(['rt_rw' => '1/1']);

        $this->actingAs($resident)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee('class="user-avatar" aria-hidden="true"></span>', false)
            ->assertSee('RT1/RW1')
            ->assertSee('>Keluar</button>', false)
            ->assertDontSee('POIN PEDULI');
    }

    public function test_education_page_shows_individual_crafts_and_community_biogas_paths(): void
    {
        $resident = User::factory()->create();

        $this->actingAs($resident)
            ->get(route('portal.education'))
            ->assertOk()
            ->assertSee('Kerajinan untuk ibu-ibu')
            ->assertSee('Tas · keranjang · tikar · dompet · tempat pensil')
            ->assertSee('Biogas untuk bapak-bapak')
            ->assertSee('Daftar isi kegiatan')
            ->assertSee('href="#kerajinan-eceng-gondok"', false)
            ->assertSee('href="#biogas-eceng-gondok"', false)
            ->assertSee('Biogas untuk bapak-bapak (wajib berkelompok)')
            ->assertSee('Pengolahan biogas dilakukan sebagai kegiatan komunitas, bukan percobaan perorangan.')
            ->assertSee('Bentuk kelompok warga dan koordinasikan rencana dengan RT/RW.');
    }

    public function test_resident_can_register_for_crafts_and_view_admin_decision(): void
    {
        $resident = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($resident)
            ->get(route('portal.education'))
            ->assertOk()
            ->assertSee('Daftar kegiatan kerajinan');

        $this->post(route('portal.education.register'), ['activity' => 'kerajinan'])
            ->assertRedirect(route('portal.education'));
        $this->assertDatabaseHas('activity_registrations', [
            'user_id' => $resident->id,
            'activity' => 'kerajinan',
            'group_name' => null,
            'status' => 'menunggu',
        ]);

        $this->get(route('portal.education'))
            ->assertSee('Status pendaftaran:')
            ->assertSee('Menunggu');

        $registration = ActivityRegistration::firstOrFail();
        $this->actingAs($admin)
            ->get(route('admin.activity-registrations.index'))
            ->assertOk()
            ->assertSee('Kerajinan eceng gondok');

        $this->patch(route('admin.activity-registrations.update', $registration), ['status' => 'disetujui'])
            ->assertRedirect();

        $this->patch(route('admin.activity-registrations.update', $registration), ['status' => 'disetujui'])
            ->assertRedirect()
            ->assertSessionHas('success', 'Keputusan pendaftaran kegiatan ini sudah tersimpan sebelumnya.');

        $this->patch(route('admin.activity-registrations.update', $registration), ['status' => 'ditolak'])
            ->assertRedirect()
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('activity_registrations', [
            'id' => $registration->id,
            'status' => 'disetujui',
        ]);
    }

    public function test_biogas_registration_requires_a_group_name(): void
    {
        $resident = User::factory()->create();

        $this->actingAs($resident)
            ->from(route('portal.education'))
            ->post(route('portal.education.register'), ['activity' => 'biogas'])
            ->assertRedirect(route('portal.education'))
            ->assertSessionHasErrors(['group_name', 'members']);

        $this->post(route('portal.education.register'), [
            'activity' => 'biogas',
            'group_name' => 'Kelompok Sungai Bersih',
            'members' => [
                ['name' => 'Budi Santoso', 'rt_rw' => 'RT 01 / RW 02'],
            ],
        ])->assertSessionHasErrors('members');

        $this->post(route('portal.education.register'), [
            'activity' => 'biogas',
            'group_name' => 'Kelompok Sungai Bersih',
            'members' => [
                ['name' => 'Budi Santoso', 'rt_rw' => 'RT 01 / RW 02'],
                ['name' => 'Doni Wijaya', 'rt_rw' => 'RT 02 / RW 03'],
            ],
        ])->assertRedirect(route('portal.education'));

        $this->assertDatabaseHas('activity_registrations', [
            'user_id' => $resident->id,
            'activity' => 'biogas',
            'status' => 'menunggu',
        ]);

        $this->assertDatabaseHas('activity_registrations', [
            'user_id' => $resident->id,
            'group_name' => 'Kelompok Sungai Bersih',
            'members' => json_encode([
                ['name' => 'Budi Santoso', 'rt_rw' => 'RT 01 / RW 02'],
                ['name' => 'Doni Wijaya', 'rt_rw' => 'RT 02 / RW 03'],
            ]),
        ]);

        $this->get(route('portal.education'))
            ->assertSee('Budi Santoso')
            ->assertSee('RT 01 / RW 02')
            ->assertSee('Doni Wijaya');

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.activity-registrations.index'))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('RT 01 / RW 02')
            ->assertSee('Doni Wijaya')
            ->assertSee('RT 02 / RW 03');
    }

    public function test_report_coordinates_must_be_inside_the_sidoarjo_map_area(): void
    {
        $resident = User::factory()->create();

        $this->actingAs($resident)
            ->from(route('portal.reports.create'))
            ->post(route('portal.reports.store'), [
                'location' => 'Di luar wilayah',
                'latitude' => '-6.9',
                'longitude' => '112.72',
                'density' => 'ringan',
                'description' => 'Titik di luar area peta.',
            ])
            ->assertSessionHasErrors('latitude');

        $this->assertDatabaseCount('reports', 0);
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

    public function test_ewallet_redemption_stores_selected_provider(): void
    {
        $resident = User::factory()->create(['points' => 20]);

        $this->actingAs($resident)
            ->post(route('portal.redeem'), [
                'reward' => 'e-wallet',
                'provider' => 'gopay',
                'points' => 10,
                'destination' => '081234567890',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('redemptions', [
            'user_id' => $resident->id,
            'reward' => 'e-wallet',
            'provider' => 'gopay',
            'destination' => '081234567890',
        ]);
        $this->assertDatabaseHas('users', ['id' => $resident->id, 'points' => 10]);
    }

    public function test_ewallet_redemption_requires_a_valid_provider(): void
    {
        $resident = User::factory()->create(['points' => 20]);

        $this->actingAs($resident)
            ->from(route('portal.rewards'))
            ->post(route('portal.redeem'), [
                'reward' => 'e-wallet',
                'points' => 10,
                'destination' => '081234567890',
            ])
            ->assertSessionHasErrors('provider');

        $this->assertDatabaseCount('redemptions', 0);
        $this->assertDatabaseHas('users', ['id' => $resident->id, 'points' => 20]);
    }

    public function test_admin_can_process_a_resident_redemption(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $resident = User::factory()->create(['points' => 10]);
        $redemption = Redemption::create([
            'user_id' => $resident->id,
            'reward' => 'pulsa',
            'points' => 10,
            'destination' => '081234567890',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.redemptions.index'))
            ->assertOk()
            ->assertSee($resident->name)
            ->assertSee('081234567890');

        $this->patch(route('admin.redemptions.update', $redemption), ['status' => 'diproses'])
            ->assertRedirect();

        $this->assertDatabaseHas('redemptions', ['id' => $redemption->id, 'status' => 'diproses']);
        $this->assertDatabaseHas('users', ['id' => $resident->id, 'points' => 10]);
    }

    public function test_rejected_redemption_refunds_points_only_once(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $resident = User::factory()->create(['points' => 5]);
        $redemption = Redemption::create([
            'user_id' => $resident->id,
            'reward' => 'token listrik',
            'points' => 10,
            'destination' => '1234567890',
            'status' => 'diproses',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.redemptions.update', $redemption), ['status' => 'ditolak'])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $resident->id, 'points' => 15]);
        $this->patch(route('admin.redemptions.update', $redemption), ['status' => 'diproses'])
            ->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $resident->id, 'points' => 15]);
    }

    public function test_resident_cannot_manage_redemptions(): void
    {
        $resident = User::factory()->create();
        $redemption = Redemption::create([
            'user_id' => $resident->id,
            'reward' => 'pulsa',
            'points' => 10,
            'destination' => '081234567890',
        ]);

        $this->actingAs($resident)
            ->get(route('admin.redemptions.index'))
            ->assertForbidden();

        $this->patch(route('admin.redemptions.update', $redemption), ['status' => 'selesai'])
            ->assertForbidden();
        $this->assertDatabaseHas('redemptions', ['id' => $redemption->id, 'status' => 'menunggu']);
    }
}
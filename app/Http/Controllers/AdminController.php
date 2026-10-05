<?php

namespace App\Http\Controllers;

use App\Models\ActivityRegistration;
use App\Models\Redemption;
use App\Models\Report;
use App\Models\User;
use App\Models\Workday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        return view('admin.dashboard', [
            'reports' => Report::with('user')->latest()->take(8)->get(),
            'newCount' => Report::where('status', 'baru')->count(),
            'inProgressCount' => Report::where('status', 'diproses')->count(),
            'resolvedCount' => Report::where('status', 'selesai')->count(),
            'workdayCount' => Workday::where('starts_at', '>=', now())->count(),
            'pendingRedemptionCount' => Redemption::whereIn('status', ['menunggu', 'diproses'])->count(),
            'pendingResidentCount' => User::where('role', 'warga')->where('registration_status', 'menunggu')->count(),
            'pendingActivityRegistrationCount' => ActivityRegistration::where('status', 'menunggu')->count(),
        ]);
    }

    public function residents(Request $request): View
    {
        $status = $request->query('status');

        return view('admin.residents', [
            'residents' => User::whereIn('role', ['warga', 'rt_rw'])
                ->when(in_array($status, ['menunggu', 'disetujui', 'ditolak'], true), fn ($query) => $query->where('registration_status', $status))
                ->latest()
                ->paginate(12)
                ->withQueryString(),
            'status' => $status,
            'pendingCount' => User::whereIn('role', ['warga', 'rt_rw'])->where('registration_status', 'menunggu')->count(),
            'approvedCount' => User::whereIn('role', ['warga', 'rt_rw'])->where('registration_status', 'disetujui')->count(),
            'rejectedCount' => User::whereIn('role', ['warga', 'rt_rw'])->where('registration_status', 'ditolak')->count(),
        ]);
    }

    public function updateResident(Request $request, User $user): RedirectResponse
    {
        $attributes = $request->validate([
            'registration_status' => ['required', 'in:disetujui,ditolak'],
        ]);

        DB::transaction(function () use ($user, $attributes): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            abort_if($lockedUser->role !== 'warga', 404);
            abort_if($lockedUser->registration_status !== 'menunggu', 422, 'Pendaftaran warga ini sudah diputuskan.');

            $lockedUser->update($attributes);
        });

        $message = $attributes['registration_status'] === 'disetujui'
            ? 'Pendaftaran warga berhasil dikonfirmasi.'
            : 'Pendaftaran warga ditolak.';

        return back()->with('success', $message);
    }

    public function updateResidentRole(Request $request, User $user): RedirectResponse
    {
        $attributes = $request->validate([
            'role' => ['required', 'in:warga,rt_rw'],
        ]);

        DB::transaction(function () use ($user, $attributes): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            abort_unless(in_array($lockedUser->role, ['warga', 'rt_rw'], true), 404);
            abort_if($lockedUser->registration_status !== 'disetujui', 422, 'Hanya akun yang disetujui yang dapat diberi peran RT/RW.');
            abort_if($attributes['role'] === 'rt_rw' && blank($lockedUser->rt_rw), 422, 'Akun RT/RW harus memiliki wilayah RT/RW.');

            $lockedUser->update($attributes);
        });

        return back()->with('success', 'Peran akun berhasil diperbarui.');
    }

    public function activityRegistrations(Request $request): View
    {
        $status = $request->query('status');

        return view('admin.activity-registrations', [
            'registrations' => ActivityRegistration::with('user')
                ->when(in_array($status, ['menunggu', 'disetujui', 'ditolak'], true), fn ($query) => $query->where('status', $status))
                ->latest()
                ->paginate(12)
                ->withQueryString(),
            'status' => $status,
            'pendingCount' => ActivityRegistration::where('status', 'menunggu')->count(),
            'approvedCount' => ActivityRegistration::where('status', 'disetujui')->count(),
            'rejectedCount' => ActivityRegistration::where('status', 'ditolak')->count(),
        ]);
    }

    public function updateActivityRegistration(Request $request, ActivityRegistration $activityRegistration): RedirectResponse
    {
        $attributes = $request->validate([
            'status' => ['required', 'in:disetujui,ditolak'],
        ]);

        $result = DB::transaction(function () use ($activityRegistration, $attributes): array {
            $lockedRegistration = ActivityRegistration::query()->lockForUpdate()->findOrFail($activityRegistration->id);

            if ($lockedRegistration->status !== 'menunggu') {
                return ['updated' => false, 'status' => $lockedRegistration->status];
            }

            $points = $attributes['status'] === 'disetujui'
                ? ($lockedRegistration->activity === 'biogas' ? 20 : 10)
                : 0;

            if ($points > 0) {
                User::query()->lockForUpdate()->findOrFail($lockedRegistration->user_id)->increment('points', $points);
            }

            $lockedRegistration->update([
                ...$attributes,
                'points_awarded' => $points,
            ]);

            return ['updated' => true, 'status' => $lockedRegistration->status, 'points' => $points];
        });

        if (! $result['updated']) {
            if ($result['status'] === $attributes['status']) {
                return back()->with('success', 'Keputusan pendaftaran kegiatan ini sudah tersimpan sebelumnya.');
            }

            return back()->withErrors(['status' => 'Pendaftaran kegiatan ini sudah diputuskan dan tidak dapat diubah.']);
        }

        return back()->with('success', "Status pendaftaran kegiatan berhasil diperbarui. {$result['points']} poin diberikan.");
    }

    public function redemptions(Request $request): View
    {
        $status = $request->query('status');

        return view('admin.redemptions', [
            'redemptions' => Redemption::with('user')
                ->when(in_array($status, ['menunggu', 'diproses', 'selesai', 'ditolak'], true), fn ($query) => $query->where('status', $status))
                ->latest()
                ->paginate(12)
                ->withQueryString(),
            'status' => $status,
            'pendingCount' => Redemption::where('status', 'menunggu')->count(),
            'inProgressCount' => Redemption::where('status', 'diproses')->count(),
            'completedCount' => Redemption::where('status', 'selesai')->count(),
            'rejectedCount' => Redemption::where('status', 'ditolak')->count(),
        ]);
    }

    public function updateRedemption(Request $request, Redemption $redemption): RedirectResponse
    {
        $attributes = $request->validate([
            'status' => ['required', 'in:menunggu,diproses,selesai,ditolak'],
        ]);

        DB::transaction(function () use ($redemption, $attributes): void {
            $lockedRedemption = Redemption::query()->lockForUpdate()->findOrFail($redemption->id);
            abort_if(in_array($lockedRedemption->status, ['selesai', 'ditolak'], true), 422, 'Permintaan yang sudah selesai tidak dapat diubah.');

            if ($attributes['status'] === 'ditolak' && $lockedRedemption->status !== 'ditolak') {
                User::query()->lockForUpdate()->findOrFail($lockedRedemption->user_id)->increment('points', $lockedRedemption->points);
            }

            $lockedRedemption->update($attributes);
        });

        return back()->with('success', 'Status penukaran warga berhasil diperbarui.');
    }

    public function updateReport(Request $request, Report $report): RedirectResponse
    {
        $attributes = $request->validate(['status' => ['required', 'in:baru,diproses,selesai']]);
        $report->update($attributes);

        return back()->with('success', 'Status laporan diperbarui.');
    }

    public function validateReport(Request $request, Report $report): RedirectResponse
    {
        $attributes = $request->validate([
            'validation_status' => ['required', 'in:valid,duplikat'],
        ]);

        $result = DB::transaction(function () use ($report, $attributes): array {
            $lockedReport = Report::query()->lockForUpdate()->findOrFail($report->id);

            if ($lockedReport->validation_status !== 'menunggu') {
                return ['updated' => false, 'status' => $lockedReport->validation_status];
            }

            $points = $attributes['validation_status'] === 'valid'
                ? ($lockedReport->deposit_weight ? $lockedReport->deposit_weight * 10 : 4)
                : 0;

            if ($points > 0) {
                User::query()->lockForUpdate()->findOrFail($lockedReport->user_id)->increment('points', $points);
            }

            $lockedReport->update([
                'validation_status' => $attributes['validation_status'],
                'points_awarded' => $points,
            ]);

            return ['updated' => true, 'status' => $attributes['validation_status'], 'points' => $points];
        });

        if (! $result['updated']) {
            if ($result['status'] === $attributes['validation_status']) {
                return back()->with('success', 'Validasi laporan ini sudah tersimpan sebelumnya.');
            }

            return back()->withErrors(['validation_status' => 'Laporan ini sudah divalidasi dan tidak dapat diubah.']);
        }

        $message = $attributes['validation_status'] === 'valid'
            ? "Laporan dinyatakan valid. {$result['points']} poin diberikan."
            : 'Laporan ditandai sebagai duplikat; tidak ada poin yang diberikan.';

        return back()->with('success', $message);
    }

    public function storeWorkday(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'location' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        Workday::create([...$attributes, 'created_by' => $request->user()->id]);

        return redirect()->route('portal.workdays')->with('success', 'Jadwal kerja bakti berhasil diterbitkan.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Redemption;
use App\Models\Workday;
use App\Models\User;
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
        ]);
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
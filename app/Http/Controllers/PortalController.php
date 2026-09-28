<?php

namespace App\Http\Controllers;

use App\Models\Redemption;
use App\Models\Report;
use App\Models\Workday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function dashboard(): View
    {
        $user = auth()->user();

        return view('portal.index', [
            'page' => 'dashboard',
            'myReports' => $user->reports()->latest()->take(3)->get(),
            'reportCount' => Report::count(),
            'resolvedCount' => Report::where('status', 'selesai')->count(),
            'upcomingWorkday' => Workday::where('starts_at', '>=', now())->orderBy('starts_at')->first(),
        ]);
    }

    public function createReport(): View
    {
        return view('portal.index', ['page' => 'report-form']);
    }

    public function storeReport(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'location' => ['required', 'string', 'max:255'],
            'density' => ['required', 'in:ringan,sedang,parah'],
            'description' => ['required', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        DB::transaction(function () use ($request, $attributes): void {
            $report = Report::create([
                'user_id' => $request->user()->id,
                'location' => $attributes['location'],
                'density' => $attributes['density'],
                'description' => $attributes['description'],
                'photo_path' => $request->file('photo')?->store('reports', 'public'),
            ]);

            $request->user()->increment('points', 10);
        });

        return redirect()->route('portal.reports.index')->with('success', 'Laporan terkirim. 10 poin sudah ditambahkan ke saldo Anda.');
    }

    public function reports(Request $request): View
    {
        $reports = Report::with('user')->latest()
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('density'), fn ($query, $density) => $query->where('density', $density))
            ->paginate(10)->withQueryString();

        return view('portal.index', ['page' => 'reports', 'reports' => $reports]);
    }

    public function workdays(): View
    {
        return view('portal.index', [
            'page' => 'workdays',
            'workdays' => Workday::with('creator')->orderBy('starts_at')->paginate(10),
        ]);
    }

    public function rewards(): View
    {
        return view('portal.index', [
            'page' => 'rewards',
            'redemptions' => auth()->user()->redemptions()->latest()->take(8)->get(),
        ]);
    }

    public function redeem(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'reward' => ['required', 'in:pulsa,token listrik,e-wallet'],
            'points' => ['required', 'integer', 'min:10', 'multiple_of:10'],
            'destination' => ['required', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($request, $attributes): void {
            $user = $request->user()->newQuery()->lockForUpdate()->findOrFail($request->user()->id);
            abort_if($user->points < $attributes['points'], 422, 'Saldo poin tidak mencukupi.');

            $user->decrement('points', $attributes['points']);
            Redemption::create([...$attributes, 'user_id' => $user->id]);
        });

        return back()->with('success', 'Permintaan penukaran berhasil dicatat dan menunggu diproses.');
    }

    public function education(): View
    {
        return view('portal.index', ['page' => 'education']);
    }
}
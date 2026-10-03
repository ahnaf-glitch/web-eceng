<?php

namespace App\Http\Controllers;

use App\Models\Redemption;
use App\Models\Report;
use App\Models\Workday;
use App\Models\ActivityRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
            'latitude' => ['required', 'numeric', 'between:-7.7,-7.18'],
            'longitude' => ['required', 'numeric', 'between:112.45,113.15'],
            'density' => ['required', 'in:ringan,sedang,parah'],
            'description' => ['required', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        DB::transaction(function () use ($request, $attributes): void {
            $report = Report::create([
                'user_id' => $request->user()->id,
                'location' => $attributes['location'],
                'latitude' => $attributes['latitude'],
                'longitude' => $attributes['longitude'],
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
        $filteredReports = Report::query()
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('density'), fn ($query, $density) => $query->where('density', $density));

        $mapReports = (clone $filteredReports)
            ->with('user:id,name')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->latest()
            ->get(['id', 'user_id', 'location', 'latitude', 'longitude', 'density', 'status', 'created_at'])
            ->map(fn (Report $report): array => [
                'id' => $report->id,
                'location' => $report->location,
                'latitude' => (float) $report->latitude,
                'longitude' => (float) $report->longitude,
                'density' => $report->density,
                'status' => $report->status,
                'reporter' => $report->user->name,
                'created_at' => $report->created_at->translatedFormat('d M Y'),
            ])->values();

        $reports = (clone $filteredReports)->with('user')->latest()->paginate(10)->withQueryString();

        return view('portal.index', ['page' => 'reports', 'reports' => $reports, 'mapReports' => $mapReports]);
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
            'provider' => ['required_if:reward,e-wallet', 'nullable', 'in:dana,gopay,ovo,shopeepay'],
            'points' => ['required', 'integer', 'min:10', 'multiple_of:10'],
            'destination' => ['required', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($request, $attributes): void {
            $user = $request->user()->newQuery()->lockForUpdate()->findOrFail($request->user()->id);
            abort_if($user->points < $attributes['points'], 422, 'Saldo poin tidak mencukupi.');

            $user->decrement('points', $attributes['points']);
            Redemption::create([
                ...$attributes,
                'provider' => $attributes['reward'] === 'e-wallet' ? $attributes['provider'] : null,
                'user_id' => $user->id,
            ]);
        });

        return back()->with('success', 'Permintaan penukaran berhasil dicatat dan menunggu diproses.');
    }

    public function education(): View
    {
        return view('portal.index', [
            'page' => 'education',
            'activityRegistrations' => auth()->user()->isAdmin()
                ? collect()
                : ActivityRegistration::where('user_id', auth()->id())->get()->keyBy('activity'),
        ]);
    }

    public function registerActivity(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'activity' => [
                'required',
                'in:kerajinan,biogas',
                Rule::unique('activity_registrations', 'activity')->where('user_id', $request->user()->id),
            ],
            'group_name' => ['required_if:activity,biogas', 'nullable', 'string', 'max:120'],
            'members' => ['required_if:activity,biogas', 'array', 'min:2'],
            'members.*.name' => ['required_if:activity,biogas', 'string', 'max:120'],
            'members.*.rt_rw' => ['required_if:activity,biogas', 'string', 'max:40'],
        ]);

        ActivityRegistration::create([
            'user_id' => $request->user()->id,
            'activity' => $attributes['activity'],
            'group_name' => $attributes['activity'] === 'biogas' ? $attributes['group_name'] : null,
            'members' => $attributes['activity'] === 'biogas' ? $attributes['members'] : null,
        ]);

        return redirect()->route('portal.education')
            ->with('success', 'Pendaftaran kegiatan berhasil dikirim dan menunggu konfirmasi admin.');
    }
}
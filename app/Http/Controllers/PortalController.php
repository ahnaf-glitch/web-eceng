<?php

namespace App\Http\Controllers;

use App\Models\ActivityRegistration;
use App\Models\Redemption;
use App\Models\Report;
use App\Models\Workday;
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
        $reportsInScope = Report::query()->when(
            $user->isRtRw(),
            fn ($query) => $query->forRtRw((string) $user->rt_rw)
        );

        return view('portal.index', [
            'page' => 'dashboard',
            'myReports' => $user->isRtRw()
                ? (clone $reportsInScope)->with('user')->latest()->take(3)->get()
                : $user->reports()->latest()->take(3)->get(),
            'upcomingWorkday' => Workday::where('starts_at', '>=', now())->orderBy('starts_at')->first(),
        ]);
    }

    public function createReport(): View
    {
        abort_unless(auth()->user()->isResident(), 403);

        return view('portal.index', ['page' => 'report-form']);
    }

    public function storeReport(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isResident(), 403);

        $attributes = $request->validate([
            'location' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-7.7,-7.18'],
            'longitude' => ['required', 'numeric', 'between:112.45,113.15'],
            'density' => ['required', 'in:ringan,sedang,parah'],
            'description' => ['required', 'string', 'max:2000'],
            'deposit_weight' => ['nullable', 'integer', 'min:1'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        DB::transaction(function () use ($request, $attributes): void {
            $report = Report::create([
                'user_id' => $request->user()->id,
                'rt_rw' => $request->user()->rt_rw,
                'location' => $attributes['location'],
                'latitude' => $attributes['latitude'],
                'longitude' => $attributes['longitude'],
                'density' => $attributes['density'],
                'description' => $attributes['description'],
                'deposit_weight' => $attributes['deposit_weight'] ?? null,
                'photo_path' => $request->file('photo')?->store('reports', 'public'),
            ]);
        });

        return redirect()->route('portal.reports.index')
            ->with('success', 'Laporan terkirim dan menunggu validasi Kelurahan. Poin diberikan setelah laporan dinyatakan valid.');
    }

    public function reports(Request $request): View
    {
        $filteredReports = Report::query()
            ->when(
                $request->user()->isRtRw(),
                fn ($query) => $query->forRtRw((string) $request->user()->rt_rw)
            )
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
        abort_unless(auth()->user()->isResident(), 403);

        return view('portal.index', [
            'page' => 'rewards',
            'redemptions' => auth()->user()->redemptions()->latest()->take(8)->get(),
            'rewards' => Redemption::REWARDS,
        ]);
    }

    public function redeem(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isResident(), 403);

        $attributes = $request->validate([
            'reward' => ['required', Rule::in(array_keys(Redemption::REWARDS))],
            'phone' => [
                'required',
                'string',
                'max:25',
                'regex:/^(?:\+?62|0)?8[0-9\s().-]{7,18}$/',
                function (string $attribute, string $value, \Closure $fail): void {
                    $digits = preg_replace('/\D+/', '', $value);
                    if (! is_string($digits) || strlen($digits) < 8 || strlen($digits) > 15) {
                        $fail('Nomor HP harus terdiri dari 8 sampai 15 angka.');
                    }
                },
            ],
        ]);
        $reward = Redemption::REWARDS[$attributes['reward']];

        DB::transaction(function () use ($request, $attributes, $reward): void {
            $user = $request->user()->newQuery()->lockForUpdate()->findOrFail($request->user()->id);
            abort_if($user->points < $reward['points'], 422, 'Saldo poin tidak mencukupi untuk hadiah ini.');

            $user->decrement('points', $reward['points']);
            Redemption::create([
                ...$attributes,
                'points' => $reward['points'],
                'user_id' => $user->id,
            ]);
        });

        return back()->with('success', 'Permintaan penukaran berhasil dicatat dan menunggu diproses.');
    }

    public function education(): View
    {
        abort_unless(auth()->user()->isResident(), 403);

        return view('portal.index', [
            'page' => 'education',
            'activityRegistrations' => auth()->user()->isAdmin()
                ? collect()
                : ActivityRegistration::where('user_id', auth()->id())->get()->keyBy('activity'),
        ]);
    }

    public function registerActivity(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isResident(), 403);

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

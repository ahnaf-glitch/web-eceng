<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Workday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        ]);
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
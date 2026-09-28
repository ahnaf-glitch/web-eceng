@extends('layouts.site')

@section('title', 'Dashboard admin')

@section('content')
    <div class="page-heading"><div><span class="eyebrow">RUANG PENGELOLA LINGKUNGAN</span><h1>Ringkasan warga</h1><p>Kelola tindak lanjut laporan dan jadwal kegiatan lingkungan.</p></div><a class="button button-primary" href="{{ route('portal.reports.index') }}">Buka papan laporan →</a></div>
    <section class="admin-stats"><div class="admin-stat"><span>Laporan baru</span><strong>{{ $newCount }}</strong></div><div class="admin-stat"><span>Sedang diproses</span><strong>{{ $inProgressCount }}</strong></div><div class="admin-stat"><span>Selesai ditangani</span><strong>{{ $resolvedCount }}</strong></div><div class="admin-stat"><span>Kerja bakti mendatang</span><strong>{{ $workdayCount }}</strong></div></section>
    <section class="panel admin-report-table"><div class="section-title"><h2>Laporan terbaru</h2><a class="text-link" href="{{ route('portal.reports.index') }}">Kelola semua laporan →</a></div><div class="table-scroll"><table class="table"><thead><tr><th>Lokasi</th><th>Pelapor</th><th>Kepadatan</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
        @forelse($reports as $report)
            <tr><td>{{ $report->location }}</td><td>{{ $report->user->name }}<br><span class="report-by">{{ $report->user->rt_rw }}</span></td><td><span class="density-pill density-{{ $report->density }}">{{ ucfirst($report->density) }}</span></td><td>{{ $report->created_at->translatedFormat('d M Y') }}</td><td><span class="status-pill status-{{ $report->status }}">{{ ucfirst($report->status) }}</span></td><td><form class="report-controls" action="{{ route('admin.reports.update', $report) }}" method="POST">@csrf @method('PATCH')<select name="status" aria-label="Status laporan {{ $report->location }}"><option value="baru" @selected($report->status === 'baru')>Baru</option><option value="diproses" @selected($report->status === 'diproses')>Diproses</option><option value="selesai" @selected($report->status === 'selesai')>Selesai</option></select><button class="button button-secondary" type="submit">Simpan</button></form></td></tr>
        @empty
            <tr><td colspan="6" class="empty-state">Belum ada laporan warga.</td></tr>
        @endforelse
    </tbody></table></div></section>
    <div style="margin-top:17px"><a class="button button-secondary" href="{{ route('portal.workdays') }}">Atur jadwal kerja bakti</a></div>
@endsection
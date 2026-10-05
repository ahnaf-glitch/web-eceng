@extends('layouts.site')

@section('title', 'Pendaftaran kegiatan edukasi')

@section('content')
    <div class="page-heading">
        <div>
            <span class="eyebrow">PROGRAM EDUKASI</span>
            <h1>Pendaftaran kegiatan</h1>
            <p>Tinjau pendaftaran kerajinan dan biogas dari warga.</p>
        </div>
        <a class="button button-outline" href="{{ route('admin.dashboard') }}">Kembali ke ringkasan</a>
    </div>

    <section class="admin-stats">
        <div class="admin-stat"><span>Menunggu</span><strong>{{ $pendingCount }}</strong></div>
        <div class="admin-stat"><span>Disetujui</span><strong>{{ $approvedCount }}</strong></div>
        <div class="admin-stat"><span>Ditolak</span><strong>{{ $rejectedCount }}</strong></div>
    </section>

    <form class="filter-bar" method="GET" action="{{ route('admin.activity-registrations.index') }}">
        <div class="field">
            <label for="activity-status">Status pendaftaran</label>
            <select id="activity-status" name="status">
                <option value="">Semua status</option>
                <option value="menunggu" @selected($status === 'menunggu')>Menunggu</option>
                <option value="disetujui" @selected($status === 'disetujui')>Disetujui</option>
                <option value="ditolak" @selected($status === 'ditolak')>Ditolak</option>
            </select>
        </div>
        <button class="button button-secondary" type="submit">Terapkan filter</button>
        <a class="button button-outline" href="{{ route('admin.activity-registrations.index') }}">Reset</a>
    </form>

    <section class="panel admin-report-table">
        <div class="section-title">
            <h2>Daftar warga</h2>
            <span class="map-count">{{ $registrations->total() }} pendaftaran</span>
        </div>
        <div class="table-scroll">
            <table class="table">
                <thead>
                    <tr><th>Warga</th><th>RT / RW</th><th>Kegiatan</th><th>Kelompok</th><th>Tanggal daftar</th><th>Status</th><th>Tindakan</th></tr>
                </thead>
                <tbody>
                    @forelse($registrations as $registration)
                        <tr>
                            <td>{{ $registration->user->name }}</td>
                            <td>{{ $registration->user->rt_rw }}</td>
                            <td>{{ $registration->activity === 'kerajinan' ? 'Kerajinan eceng gondok' : 'Biogas eceng gondok' }}</td>
                            <td>
                                {{ $registration->group_name ?? 'Individu' }}
                                @if($registration->members)
                                    <ul class="admin-member-list">
                                        @foreach($registration->members as $member)
                                            <li>{{ $member['name'] }} — {{ $member['rt_rw'] }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                            <td>{{ $registration->created_at->translatedFormat('d M Y') }}</td>
                            <td><span class="status-pill status-{{ $registration->status }}">{{ ucfirst($registration->status) }}</span>@if($registration->points_awarded > 0)<br><span class="report-by">{{ $registration->points_awarded }} poin diberikan</span>@endif</td>
                            <td>
                                @if($registration->status === 'menunggu')
                                    <div class="resident-actions">
                                        <form action="{{ route('admin.activity-registrations.update', $registration) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="disetujui">
                                            <button class="button button-secondary" type="submit">Konfirmasi</button>
                                        </form>
                                        <form action="{{ route('admin.activity-registrations.update', $registration) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="ditolak">
                                            <button class="button button-outline" type="submit">Tolak</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="field-help">Sudah diputuskan</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-state">Belum ada pendaftaran kegiatan untuk filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $registrations->links() }}</div>
    </section>
@endsection

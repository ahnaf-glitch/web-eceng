@extends('layouts.site')

@section('title', 'Kelola pendaftaran warga')

@section('content')
    <div class="page-heading">
        <div>
            <span class="eyebrow">VERIFIKASI AKUN</span>
            <h1>Pendaftaran warga</h1>
            <p>Konfirmasi akun dan tetapkan akun warga sebagai pengurus RT/RW wilayahnya bila diperlukan.</p>
        </div>
        <a class="button button-outline" href="{{ route('admin.dashboard') }}">Kembali ke ringkasan</a>
    </div>

    <section class="admin-stats">
        <div class="admin-stat"><span>Menunggu</span><strong>{{ $pendingCount }}</strong></div>
        <div class="admin-stat"><span>Disetujui</span><strong>{{ $approvedCount }}</strong></div>
        <div class="admin-stat"><span>Ditolak</span><strong>{{ $rejectedCount }}</strong></div>
    </section>

    <form class="filter-bar" method="GET" action="{{ route('admin.residents.index') }}">
        <div class="field">
            <label for="resident-status">Status pendaftaran</label>
            <select id="resident-status" name="status">
                <option value="">Semua status</option>
                <option value="menunggu" @selected($status === 'menunggu')>Menunggu</option>
                <option value="disetujui" @selected($status === 'disetujui')>Disetujui</option>
                <option value="ditolak" @selected($status === 'ditolak')>Ditolak</option>
            </select>
        </div>
        <button class="button button-secondary" type="submit">Terapkan filter</button>
        <a class="button button-outline" href="{{ route('admin.residents.index') }}">Reset</a>
    </form>

    <section class="panel admin-report-table">
        <div class="section-title">
            <h2>Daftar pendaftar</h2>
            <span class="map-count">{{ $residents->total() }} warga</span>
        </div>
        <div class="table-scroll">
            <table class="table">
                <thead>
                    <tr><th>Warga</th><th>RT / RW</th><th>Email</th><th>Tanggal daftar</th><th>Status akun</th><th>Peran</th><th>Tindakan</th></tr>
                </thead>
                <tbody>
                    @forelse($residents as $resident)
                        <tr>
                            <td>{{ $resident->name }}</td>
                            <td>{{ $resident->rt_rw }}</td>
                            <td>{{ $resident->email }}</td>
                            <td>{{ $resident->created_at->translatedFormat('d M Y') }}</td>
                            <td><span class="status-pill status-{{ $resident->registration_status }}">{{ ucfirst($resident->registration_status) }}</span></td>
                            <td>{{ $resident->role === 'rt_rw' ? 'Pengurus RT/RW' : 'Warga' }}</td>
                            <td>
                                @if($resident->registration_status === 'menunggu')
                                    <div class="resident-actions">
                                        <form action="{{ route('admin.residents.update', $resident) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="registration_status" value="disetujui">
                                            <button class="button button-secondary" type="submit">Konfirmasi</button>
                                        </form>
                                        <form action="{{ route('admin.residents.update', $resident) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="registration_status" value="ditolak">
                                            <button class="button button-outline" type="submit">Tolak</button>
                                        </form>
                                    </div>
                                @elseif($resident->registration_status === 'disetujui')
                                    <form class="report-controls" action="{{ route('admin.residents.role.update', $resident) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <label class="sr-only" for="role-{{ $resident->id }}">Peran {{ $resident->name }}</label>
                                        <select id="role-{{ $resident->id }}" name="role">
                                            <option value="warga" @selected($resident->role === 'warga')>Warga</option>
                                            <option value="rt_rw" @selected($resident->role === 'rt_rw')>Pengurus RT/RW</option>
                                        </select>
                                        <button class="button button-secondary" type="submit">Simpan peran</button>
                                    </form>
                                @else
                                    <span class="field-help">Pendaftaran ditolak</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-state">Belum ada pendaftaran warga untuk filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $residents->links() }}</div>
    </section>
@endsection

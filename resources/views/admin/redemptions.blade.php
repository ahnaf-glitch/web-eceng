@extends('layouts.site')

@section('title', 'Kelola warga peduli')

@section('content')
    <div class="page-heading">
        <div>
            <span class="eyebrow">APRESIASI WARGA</span>
            <h1>Kelola warga peduli</h1>
            <p>Tinjau permintaan penukaran poin dan perbarui proses hadiah warga.</p>
        </div>
        <a class="button button-outline" href="{{ route('admin.dashboard') }}">Kembali ke ringkasan</a>
    </div>

    <section class="admin-stats">
        <div class="admin-stat"><span>Menunggu</span><strong>{{ $pendingCount }}</strong></div>
        <div class="admin-stat"><span>Diproses</span><strong>{{ $inProgressCount }}</strong></div>
        <div class="admin-stat"><span>Selesai</span><strong>{{ $completedCount }}</strong></div>
        <div class="admin-stat"><span>Ditolak</span><strong>{{ $rejectedCount }}</strong></div>
    </section>

    <form class="filter-bar" method="GET" action="{{ route('admin.redemptions.index') }}">
        <div class="field">
            <label for="redemption-status">Status permintaan</label>
            <select id="redemption-status" name="status">
                <option value="">Semua status</option>
                <option value="menunggu" @selected($status === 'menunggu')>Menunggu</option>
                <option value="diproses" @selected($status === 'diproses')>Diproses</option>
                <option value="selesai" @selected($status === 'selesai')>Selesai</option>
                <option value="ditolak" @selected($status === 'ditolak')>Ditolak</option>
            </select>
        </div>
        <button class="button button-secondary" type="submit">Terapkan filter</button>
        <a class="button button-outline" href="{{ route('admin.redemptions.index') }}">Reset</a>
    </form>

    <section class="panel admin-report-table">
        <div class="section-title">
            <h2>Permintaan penukaran</h2>
            <span class="map-count">{{ $redemptions->total() }} permintaan</span>
        </div>
        <div class="table-scroll">
            <table class="table redemption-table">
                <thead>
                    <tr><th>Warga</th><th>Hadiah</th><th>Nilai</th><th>Tujuan</th><th>Diajukan</th><th>Status</th><th>Tindakan</th></tr>
                </thead>
                <tbody>
                    @forelse($redemptions as $redemption)
                        <tr>
                            <td>{{ $redemption->user->name }}<br><span class="report-by">{{ $redemption->user->rt_rw }}</span></td>
                            <td>{{ ucfirst($redemption->reward) }}@if($redemption->provider)<br><span class="report-by">{{ ['dana' => 'DANA', 'gopay' => 'GoPay', 'ovo' => 'OVO', 'shopeepay' => 'ShopeePay'][$redemption->provider] ?? ucfirst($redemption->provider) }}</span>@endif</td>
                            <td>{{ $redemption->points }} poin<br><span class="report-by">Rp{{ number_format($redemption->points * 100, 0, ',', '.') }}</span></td>
                            <td>{{ $redemption->destination }}</td>
                            <td>{{ $redemption->created_at->translatedFormat('d M Y') }}</td>
                            <td><span class="status-pill status-{{ $redemption->status }}">{{ ucfirst($redemption->status) }}</span></td>
                            <td>
                                @if(in_array($redemption->status, ['menunggu', 'diproses'], true))
                                    <form class="report-controls" action="{{ route('admin.redemptions.update', $redemption) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <label class="sr-only" for="redemption-{{ $redemption->id }}">Status baru untuk {{ $redemption->user->name }}</label>
                                        <select id="redemption-{{ $redemption->id }}" name="status">
                                            <option value="menunggu" @selected($redemption->status === 'menunggu')>Menunggu</option>
                                            <option value="diproses" @selected($redemption->status === 'diproses')>Diproses</option>
                                            <option value="selesai">Selesai</option>
                                            <option value="ditolak">Tolak + kembalikan poin</option>
                                        </select>
                                        <button class="button button-secondary" type="submit">Simpan</button>
                                    </form>
                                @else
                                    <span class="field-help">Sudah ditutup</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-state">Belum ada permintaan penukaran untuk filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $redemptions->links() }}</div>
    </section>

    <p class="redemption-note">Saat permintaan ditolak, poin dikembalikan ke saldo warga secara otomatis. Permintaan yang selesai atau ditolak tidak dapat diubah lagi.</p>
@endsection
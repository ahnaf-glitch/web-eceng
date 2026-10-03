@extends('layouts.site')

@section('title', match($page) {
    'dashboard' => 'Beranda',
    'report-form' => 'Buat laporan',
    'reports' => 'Papan laporan',
    'workdays' => 'Kerja bakti',
    'rewards' => 'Warga peduli',
    default => 'Edukasi',
})

@if(in_array($page, ['report-form', 'reports'], true))
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script src="{{ asset('js/reports-map.js') }}" defer></script>
    @endpush
@endif
@if($page === 'rewards')
    @push('scripts')
        <script src="{{ asset('js/rewards.js') }}" defer></script>
    @endpush
@endif

@section('content')
    @if($page === 'dashboard')
        <section class="dashboard-hero">
            <div class="hero-copy">
                <span class="eyebrow eyebrow-light"><span class="live-dot"></span> RAWAT SUNGAI, RAWAT MASA DEPAN</span>
                <h1>Halo, {{ explode(' ', auth()->user()->name)[0] }}.<br>Yuk jaga sungai <em>kita.</em></h1>
                <p>Satu laporan kecil bisa menggerakkan satu lingkungan. Pantau kondisi sungai dan ikut aksi nyata bersama warga.</p>
                <a class="button button-primary" href="{{ route('portal.reports.create') }}">Laporkan eceng gondok</a>
            </div>
        </section>
        <section class="stats-row" aria-label="Ringkasan kondisi lingkungan">
            <div class="stat"><span><strong>{{ $reportCount }}</strong><span>Laporan warga</span></span></div>
            <div class="stat"><span><strong>{{ $resolvedCount }}</strong><span>Laporan ditangani</span></span></div>
            <div class="stat"><span><strong>{{ auth()->user()->points }}</strong><span>Poin yang tersedia</span></span></div>
        </section>
        <div class="dashboard-columns">
            <section>
                <div class="section-title"><h2>Laporan terbarumu</h2><a class="text-link" href="{{ route('portal.reports.index') }}">Lihat papan</a></div>
                <div class="report-mini-list">
                    @forelse($myReports as $report)
                        <div class="report-mini"><div><strong>{{ $report->location }}</strong><span>{{ $report->created_at->translatedFormat('d M Y') }} · Kepadatan {{ $report->density }}</span></div><span class="status-pill status-{{ $report->status }}">{{ ucfirst($report->status) }}</span></div>
                    @empty
                        <p class="empty-state">Belum ada laporan. Mulai dengan berbagi kondisi sungai di sekitarmu.</p>
                    @endforelse
                </div>
            </section>
            <section>
                <div class="section-title"><h2>Aksi terdekat</h2><a class="text-link" href="{{ route('portal.workdays') }}">Semua jadwal</a></div>
                @if($upcomingWorkday)
                    <div class="next-event"><div><span class="event-date">{{ $upcomingWorkday->starts_at->translatedFormat('l, d F Y · H.i') }} WIB</span><h3>{{ $upcomingWorkday->title }}</h3><p>{{ $upcomingWorkday->location }}</p></div><a class="text-link" href="{{ route('portal.workdays') }}">Lihat detail</a></div>
                @else
                    <div class="next-event"><div><span class="event-date">BELUM ADA JADWAL</span><h3>Waktunya bergerak bersama</h3><p>Jadwal kerja bakti lingkungan akan muncul di sini.</p></div><a class="text-link" href="{{ route('portal.workdays') }}">Cek jadwal</a></div>
                @endif
            </section>
        </div>
    @elseif($page === 'report-form')
        <div class="page-heading"><div><span class="eyebrow">LAPORAN WARGA</span><h1>Ceritakan kondisi sungai</h1><p>Informasi lokasi dan tingkat kepadatan membantu pengurus menentukan penanganan.</p></div></div>
        <div class="form-layout">
            <form class="panel form-panel" action="{{ route('portal.reports.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-grid">
                    <div class="field field-full"><label for="location">Lokasi sungai</label><input id="location" name="location" value="{{ old('location') }}" placeholder="Contoh: Kali Cempaka, dekat jembatan RT 02" required></div>
                    <div class="field field-full"><label>Titik eceng gondok di peta Sidoarjo</label><div id="report-map" class="report-map" data-map-mode="pick" aria-label="Peta untuk menentukan titik laporan"></div><input id="latitude" name="latitude" type="hidden" value="{{ old('latitude') }}"><input id="longitude" name="longitude" type="hidden" value="{{ old('longitude') }}"><span id="map-selection" class="field-help" aria-live="polite">Pilih titik pada peta agar pengurus dapat menemukan lokasi secara akurat.</span></div>
                    <div class="field field-full"><label for="density">Tingkat kepadatan eceng gondok</label><select id="density" name="density" required><option value="">Pilih tingkat kepadatan</option><option value="ringan" @selected(old('density') === 'ringan')>Ringan · tumbuh tersebar</option><option value="sedang" @selected(old('density') === 'sedang')>Sedang · menutup sebagian aliran</option><option value="parah" @selected(old('density') === 'parah')>Parah · menutup sebagian besar aliran</option></select></div>
                    <div class="field field-full"><label for="description">Keterangan laporan</label><textarea id="description" name="description" maxlength="2000" placeholder="Ceritakan kondisi yang terlihat, patokan lokasi, atau hal yang perlu diperhatikan." required>{{ old('description') }}</textarea><span class="field-help">Maksimal 2.000 karakter. Jangan masuk ke sungai atau mengambil risiko saat mengambil foto.</span></div>
                    <div class="field field-full"><label for="photo">Foto kondisi (opsional)</label><input id="photo" type="file" name="photo" accept="image/*"><span class="field-help">Format gambar, maksimal 5 MB.</span></div>
                    <div class="field field-full"><button class="button button-primary" type="submit">Kirim laporan</button></div>
                </div>
            </form>
            <aside class="report-aside"><span class="eyebrow">WARGA PEDULI</span><h3>Suaramu jadi aksi.</h3><p>Setiap laporan yang berhasil dikirim mendapat 10 poin. Pengurus RT/RW akan memeriksa laporan dan memperbarui statusnya.</p><div class="points-callout"><strong>10</strong><span>poin untuk setiap laporan<br>senilai Rp1.000</span></div></aside>
        </div>
    @elseif($page === 'reports')
        <div class="page-heading"><div><span class="eyebrow">TRANSPARANSI LINGKUNGAN</span><h1>Papan laporan</h1><p>Pantau laporan warga dan perkembangan penanganan eceng gondok di lingkungan.</p></div>@unless(auth()->user()->isAdmin())<a class="button button-primary" href="{{ route('portal.reports.create') }}">+ Buat laporan</a>@endunless</div>
        <form class="filter-bar" method="GET" action="{{ route('portal.reports.index') }}">
            <div class="field"><label for="status-filter">Status</label><select id="status-filter" name="status"><option value="">Semua status</option><option value="baru" @selected(request('status') === 'baru')>Baru</option><option value="diproses" @selected(request('status') === 'diproses')>Diproses</option><option value="selesai" @selected(request('status') === 'selesai')>Selesai</option></select></div>
            <div class="field"><label for="density-filter">Kepadatan</label><select id="density-filter" name="density"><option value="">Semua tingkat</option><option value="ringan" @selected(request('density') === 'ringan')>Ringan</option><option value="sedang" @selected(request('density') === 'sedang')>Sedang</option><option value="parah" @selected(request('density') === 'parah')>Parah</option></select></div>
            <button class="button button-secondary" type="submit">Terapkan filter</button><a class="button button-outline" href="{{ route('portal.reports.index') }}">Reset</a>
        </form>
        <section class="map-section" aria-labelledby="report-map-title">
            <div class="map-heading"><div><span class="eyebrow">PEMETAAN LAPORAN</span><h2 id="report-map-title">Titik eceng gondok di Sidoarjo</h2></div><span class="map-count">{{ $mapReports->count() }} titik pada peta</span></div>
            <div id="reports-map" class="report-map report-map-board" data-map-mode="markers" aria-label="Peta titik laporan eceng gondok di Sidoarjo"></div>
            <script id="map-report-data" type="application/json">@json($mapReports)</script>
            <div class="map-legend" aria-label="Legenda tingkat kepadatan"><span><i style="background:#4d9862"></i>Ringan</span><span><i style="background:#d89c39"></i>Sedang</span><span><i style="background:#d95e4b"></i>Parah</span></div>
            <p class="map-attribution">Peta © <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>. Pin laporan lama tidak ditampilkan jika belum memiliki koordinat.</p>
        </section>
        <div class="report-list">
            @forelse($reports as $report)
                <article class="panel report-card">
                    <div><h3>{{ $report->location }}</h3><div class="report-meta"><span class="density-pill density-{{ $report->density }}">Kepadatan {{ ucfirst($report->density) }}</span><span class="status-pill status-{{ $report->status }}">{{ ucfirst($report->status) }}</span><span class="report-by">{{ $report->user->name }} · {{ $report->created_at->translatedFormat('d M Y') }}</span></div><p>{{ $report->description }}</p>@if($report->photo_path)<a href="{{ asset('storage/'.$report->photo_path) }}" target="_blank" rel="noopener"><img class="report-photo" src="{{ asset('storage/'.$report->photo_path) }}" alt="Foto laporan eceng gondok"></a>@endif</div>
                    @if(auth()->user()->isAdmin())
                        <form class="report-controls" action="{{ route('admin.reports.update', $report) }}" method="POST">@csrf @method('PATCH')<label class="field-help" for="status-{{ $report->id }}">Ubah status</label><select id="status-{{ $report->id }}" name="status"><option value="baru" @selected($report->status === 'baru')>Baru</option><option value="diproses" @selected($report->status === 'diproses')>Diproses</option><option value="selesai" @selected($report->status === 'selesai')>Selesai</option></select><button class="button button-secondary" type="submit">Simpan</button></form>
                    @endif
                </article>
            @empty
                <div class="panel empty-state">Belum ada laporan yang sesuai dengan filter ini.</div>
            @endforelse
        </div>
        <div class="pagination-wrap">{{ $reports->links() }}</div>
    @elseif($page === 'workdays')
        <div class="page-heading"><div><span class="eyebrow">AKSI BERSAMA</span><h1>Kerja bakti lingkungan</h1><p>Luangkan waktu sebentar untuk membuat perubahan yang terasa di sungai kita.</p></div></div>
        <div class="workday-layout">
            <div class="event-list">
                @forelse($workdays as $workday)
                    <article class="panel event-item"><div class="event-calendar"><strong>{{ $workday->starts_at->format('d') }}</strong><span>{{ $workday->starts_at->translatedFormat('M') }}</span></div><div><h3>{{ $workday->title }}</h3><p>◷ {{ $workday->starts_at->translatedFormat('l, d F Y · H.i') }} WIB</p><p>⌖ {{ $workday->location }}</p>@if($workday->notes)<p>{{ $workday->notes }}</p>@endif</div></article>
                @empty
                    <div class="panel empty-state">Belum ada jadwal kerja bakti. Nantikan pengumuman dari pengurus RT/RW.</div>
                @endforelse
                <div class="pagination-wrap">{{ $workdays->links() }}</div>
            </div>
            @if(auth()->user()->isAdmin())
                <form class="panel admin-form" action="{{ route('admin.workdays.store') }}" method="POST">@csrf<h2>Buat jadwal baru</h2><div class="field"><label for="title">Nama kegiatan</label><input id="title" name="title" value="{{ old('title') }}" placeholder="Bersih Kali Cempaka" required></div><div class="field"><label for="starts_at">Tanggal dan waktu</label><input id="starts_at" type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" required></div><div class="field"><label for="location">Tempat berkumpul</label><input id="location" name="location" value="{{ old('location') }}" placeholder="Pos RT 02 / jembatan" required></div><div class="field"><label for="notes">Catatan (opsional)</label><textarea id="notes" name="notes" placeholder="Perlengkapan yang perlu dibawa">{{ old('notes') }}</textarea></div><button class="button button-primary" type="submit">Terbitkan jadwal</button></form>
            @else
                <aside class="report-aside"><span class="eyebrow">GOTONG ROYONG</span><h3>Satu pagi untuk sungai yang lebih sehat.</h3><p>Periksa tanggal, titik kumpul, dan catatan persiapan sebelum berangkat.</p></aside>
            @endif
        </div>
    @elseif($page === 'rewards')
        <div class="page-heading"><div><span class="eyebrow">APRESIASI UNTUK WARGA</span><h1>Warga peduli</h1><p>Kontribusi nyata mendapat apresiasi. Kumpulkan dan tukarkan poin kepedulianmu.</p></div></div>
        <section class="rewards-hero"><div><span class="eyebrow eyebrow-light">SALDO POIN SAYA</span><h2>{{ auth()->user()->points }} poin</h2><p>Setiap 10 poin bernilai Rp1.000.</p></div><div class="rupiah-note">Poin didapat dari laporan warga dan dapat ditukar menjadi pulsa, token listrik, atau saldo e-wallet.</div></section>
        <div class="reward-layout">
            <form class="panel reward-form" action="{{ route('portal.redeem') }}" method="POST">@csrf<h2>Tukar poin</h2><div class="field"><label for="reward">Pilih hadiah</label><select id="reward" name="reward" required><option value="">Pilih bentuk penukaran</option><option value="pulsa" @selected(old('reward') === 'pulsa')>Pulsa</option><option value="token listrik" @selected(old('reward') === 'token listrik')>Token listrik</option><option value="e-wallet" @selected(old('reward') === 'e-wallet')>E-wallet</option></select></div><div class="field wallet-provider" id="wallet-provider-field" style="margin-top:13px" @if(old('reward') !== 'e-wallet') hidden @endif><label for="provider">Pilih e-wallet</label><select id="provider" name="provider" @if(old('reward') !== 'e-wallet') disabled @endif><option value="">Pilih penyedia e-wallet</option><option value="dana" @selected(old('provider') === 'dana')>DANA</option><option value="gopay" @selected(old('provider') === 'gopay')>GoPay</option><option value="ovo" @selected(old('provider') === 'ovo')>OVO</option><option value="shopeepay" @selected(old('provider') === 'shopeepay')>ShopeePay</option></select></div><div class="field" style="margin-top:13px"><label for="points">Jumlah poin</label><input id="points" type="number" name="points" min="10" step="10" value="{{ old('points', 10) }}" required><span class="field-help">Kelipatan 10 poin. 10 poin setara Rp1.000.</span></div><div class="field" style="margin-top:13px"><label for="destination">Nomor tujuan</label><input id="destination" name="destination" value="{{ old('destination') }}" placeholder="Nomor HP atau ID akun tujuan" required></div><button class="button button-primary" style="margin-top:17px" type="submit">Ajukan penukaran</button></form>
            <section class="panel history-panel"><h2>Riwayat penukaran</h2>@forelse($redemptions as $redemption)<div class="history-row"><div><strong>{{ ucfirst($redemption->reward) }}@if($redemption->provider) · {{ ['dana' => 'DANA', 'gopay' => 'GoPay', 'ovo' => 'OVO', 'shopeepay' => 'ShopeePay'][$redemption->provider] ?? ucfirst($redemption->provider) }}@endif · {{ $redemption->destination }}</strong><small>{{ $redemption->created_at->translatedFormat('d M Y') }} · {{ $redemption->points }} poin (Rp{{ number_format($redemption->points * 100, 0, ',', '.') }})</small></div><span class="status-pill status-{{ $redemption->status === 'menunggu' ? 'diproses' : 'selesai' }}">{{ ucfirst($redemption->status) }}</span></div>@empty<p class="empty-state">Belum ada penukaran poin.</p>@endforelse</section>
        </div>
    @else
        <div class="page-heading"><div><span class="eyebrow">KENALI, CEGAH, OLAH</span><h1>Belajar tentang eceng gondok</h1><p>Kenali dampaknya dan pilihan penanganan yang bijak untuk ekosistem sungai.</p></div></div>
        <div class="education-grid">
            <article class="panel edu-card"><span class="edu-number">01</span><h2>Jika dibiarkan</h2><p>Eceng gondok berkembang cepat dan membentuk hamparan rapat. Tutupan berlebih menghambat cahaya dan pertukaran oksigen, mengganggu biota air, serta dapat menyumbat aliran dan memperparah banjir.</p></article>
            <article class="panel edu-card"><span class="edu-number">02</span><h2>Solusi bersama</h2><p>Pantau titik pertumbuhan, laporkan lokasi dan tingkat kepadatan, lalu lakukan pengangkatan terjadwal. Tanaman yang diangkat perlu ditangani jauh dari bantaran agar tidak hanyut dan tumbuh kembali.</p></article>
            <article class="panel edu-card"><span class="edu-number">03</span><h2>Diolah dengan bijak</h2><p>Setelah dibersihkan dan dikeringkan dengan benar, eceng gondok dapat dimanfaatkan sebagai bahan kerajinan, kompos, atau bahan baku olahan sesuai kapasitas dan keamanan setempat.</p></article>
            <article class="panel edu-card edu-wide"><span class="eyebrow">PENTING UNTUK DIINGAT</span><h2>Jangan bekerja sendirian di sungai.</h2><p>Pengangkatan tanaman di perairan berisiko. Koordinasikan kegiatan dengan RT/RW, gunakan alat pelindung, hindari kontak dengan air tercemar, dan pastikan olahan tidak berasal dari perairan yang terkontaminasi logam berat atau limbah berbahaya.</p></article>
        </div>
        <section class="education-activities" aria-labelledby="education-activities-title">
            <div class="section-title"><div><span class="eyebrow">PILIH JALUR KEGIATAN</span><h2 id="education-activities-title">Eceng gondok jadi karya dan energi</h2></div></div>
            <nav class="education-toc" aria-label="Daftar isi kegiatan">
                <strong>Daftar isi kegiatan</strong>
                <a href="#kerajinan-eceng-gondok">Kerajinan eceng gondok untuk ibu-ibu</a>
                <a href="#biogas-eceng-gondok">Biogas untuk bapak-bapak (wajib berkelompok)</a>
            </nav>
            <div class="education-pathways">
                <article class="panel edu-pathway edu-craft" id="kerajinan-eceng-gondok">
                    <div class="pathway-heading"><span class="pathway-mark" aria-hidden="true">01</span><div><span class="eyebrow">INDIVIDU</span><h3>Kerajinan untuk ibu-ibu</h3></div></div>
                    <p>Kerjakan sendiri dari rumah, mulai dari produk sederhana lalu kembangkan sesuai keterampilan.</p>
                    <div class="pathway-products"><strong>Ide produk</strong><span>Tas · keranjang · tikar · dompet · tempat pensil</span></div>
                    <details class="pathway-details">
                        <summary>Lihat langkah membuat kerajinan</summary>
                        <ol>
                            <li>Pilih batang yang sudah diangkat melalui kegiatan bersih sungai terjadwal.</li>
                            <li>Bersihkan, belah atau pilah sesuai kebutuhan, lalu jemur sampai benar-benar kering.</li>
                            <li>Anyam bahan menjadi produk sederhana dan rapikan ujungnya.</li>
                            <li>Simpan di tempat kering; gunakan pewarna atau pelapis sesuai petunjuk keamanan produk.</li>
                        </ol>
                    </details>
                    @if(isset($activityRegistrations['kerajinan']))
                        <p class="activity-registration-status">Status pendaftaran: <span class="status-pill status-{{ $activityRegistrations['kerajinan']->status }}">{{ ucfirst($activityRegistrations['kerajinan']->status) }}</span></p>
                    @else
                        <form class="activity-registration-form" action="{{ route('portal.education.register') }}" method="POST">
                            @csrf
                            <input type="hidden" name="activity" value="kerajinan">
                            <button class="button button-primary" type="submit">Daftar kegiatan kerajinan</button>
                        </form>
                    @endif
                </article>
                <article class="panel edu-pathway edu-biogas" id="biogas-eceng-gondok">
                    <div class="pathway-heading"><span class="pathway-mark" aria-hidden="true">02</span><div><span class="eyebrow">WAJIB BERKELOMPOK</span><h3>Biogas untuk bapak-bapak</h3></div></div>
                    <p>Pengolahan biogas dilakukan sebagai kegiatan komunitas, bukan percobaan perorangan.</p>
                    <div class="pathway-products"><strong>Mulai dengan</strong><span>Bentuk kelompok warga dan koordinasikan rencana dengan RT/RW.</span></div>
                    <details class="pathway-details">
                        <summary>Lihat tahapan kegiatan komunitas</summary>
                        <ol>
                            <li>Ajak warga membentuk kelompok, tetapkan koordinator dan pembagian tugas.</li>
                            <li>Koordinasikan sumber bahan baku, lokasi, dan kebutuhan bersama pengurus lingkungan.</li>
                            <li>Minta pendampingan tenaga teknis untuk menilai kelayakan dan merancang instalasi.</li>
                            <li>Operasikan dan rawat instalasi bersama sesuai prosedur keselamatan dari pendamping.</li>
                        </ol>
                    </details>
                    <p class="pathway-safety"><strong>Keselamatan:</strong> Jangan merakit digester atau menangani gas tanpa pendamping teknis. Hindari api dan hentikan kegiatan bila tercium kebocoran.</p>
                    @if(isset($activityRegistrations['biogas']))
                        <div class="activity-registration-status">
                            <p>Status pendaftaran kelompok: {{ $activityRegistrations['biogas']->group_name }} · <span class="status-pill status-{{ $activityRegistrations['biogas']->status }}">{{ ucfirst($activityRegistrations['biogas']->status) }}</span></p>
                            @if($activityRegistrations['biogas']->members)
                                <strong>Warga yang ikut mengolah</strong>
                                <ul class="activity-member-list">
                                    @foreach($activityRegistrations['biogas']->members as $member)
                                        <li>{{ $member['name'] }} <span>{{ $member['rt_rw'] }}</span></li>
                                    @endforeach
                                </ul>
                            @else
                                <span class="field-help">Data anggota belum tercatat pada pendaftaran ini.</span>
                            @endif
                        </div>
                    @else
                        @php($biogasMembers = old('activity') === 'biogas' ? old('members', []) : [])
                        <form class="activity-registration-form" action="{{ route('portal.education.register') }}" method="POST" data-group-registration>
                            @csrf
                            <input type="hidden" name="activity" value="biogas">
                            <label for="biogas-group-name">Nama kelompok warga <span aria-hidden="true">*</span></label>
                            <input id="biogas-group-name" name="group_name" value="{{ old('activity') === 'biogas' ? old('group_name') : '' }}" maxlength="120" placeholder="Contoh: Kelompok Sungai Bersih" required>
                            <span class="field-help">Isi minimal dua warga yang ikut mengolah, beserta RT/RW masing-masing.</span>
                            <div class="activity-members" data-members-list>
                                @for($memberIndex = 0; $memberIndex < max(2, count($biogasMembers)); $memberIndex++)
                                    <fieldset class="activity-member">
                                        <legend>Warga <span data-member-number>{{ $memberIndex + 1 }}</span></legend>
                                        <div class="activity-member-fields">
                                            <label>Nama warga
                                                <input name="members[{{ $memberIndex }}][name]" value="{{ old('activity') === 'biogas' ? old('members.'.$memberIndex.'.name') : '' }}" maxlength="120" autocomplete="name" required>
                                            </label>
                                            <label>RT / RW
                                                <input name="members[{{ $memberIndex }}][rt_rw]" value="{{ old('activity') === 'biogas' ? old('members.'.$memberIndex.'.rt_rw') : '' }}" maxlength="40" placeholder="Contoh: RT 01 / RW 02" required>
                                            </label>
                                        </div>
                                        @if($memberIndex >= 2)
                                            <button class="member-remove" type="button" data-remove-member>Hapus warga</button>
                                        @endif
                                    </fieldset>
                                @endfor
                            </div>
                            <button class="button button-outline" type="button" data-add-member>+ Tambah warga</button>
                            <button class="button button-primary" type="submit">Daftar kelompok biogas</button>
                            <template data-member-template>
                                <fieldset class="activity-member">
                                    <legend>Warga <span data-member-number></span></legend>
                                    <div class="activity-member-fields">
                                        <label>Nama warga
                                            <input name="members[__INDEX__][name]" maxlength="120" autocomplete="name" required>
                                        </label>
                                        <label>RT / RW
                                            <input name="members[__INDEX__][rt_rw]" maxlength="40" placeholder="Contoh: RT 01 / RW 02" required>
                                        </label>
                                    </div>
                                    <button class="member-remove" type="button" data-remove-member>Hapus warga</button>
                                </fieldset>
                            </template>
                        </form>
                    @endif
                </article>
            </div>
        </section>
    @endif
@endsection

@if($page === 'education')
    @push('scripts')
        <script src="{{ asset('js/activity-registration.js') }}" defer></script>
    @endpush
@endif
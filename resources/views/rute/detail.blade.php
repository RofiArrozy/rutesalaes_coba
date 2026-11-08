<x-layout title="Detail Rute: {{ $visit->store->name ?? 'Toko' }}">
    <!-- Top Breadcrumb & Status -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-2">
                <a href="{{ route('rute.index') }}" class="text-blue-600 hover:text-blue-800 min-h-[36px] flex items-center">&larr; Agenda Rute</a>
                <span>/</span>
                <span class="text-slate-600 font-mono">URI Dinamis: /rute/detail/{{ $visit->id }}</span>
            </div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Rute Kunjungan #{{ $visit->id }}</h1>
                <x-badge :status="$visit->status" />
                @if($visit->result_status)
                    <x-badge :status="$visit->result_status" />
                @endif
            </div>
            <p class="text-sm text-slate-500 mt-1">
                Jadwal Kunjungan: <strong class="text-slate-700">{{ \Carbon\Carbon::parse($visit->visit_date)->translatedFormat('l, d F Y') }}</strong>
                &bull; Sales: <strong class="text-slate-700">{{ $visit->user->name ?? '-' }} ({{ $visit->user->employee_code ?? '-' }})</strong>
            </p>
        </div>

        <div class="flex items-center gap-2">
            {{-- Edit hanya bisa diakses Admin, atau Sales jika kunjungan belum selesai --}}
            @can('update', $visit)
                <a href="{{ route('rute.edit', $visit->id) }}" class="min-h-[48px] px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-semibold shadow-xs transition-all flex items-center justify-center">
                    Edit Jadwal
                </a>
            @endcan

            {{-- Hapus hanya bisa diakses Admin --}}
            @can('delete', $visit)
                <form action="{{ route('rute.destroy', $visit->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus rute kunjungan ini?');" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="min-h-[48px] px-4 py-2.5 bg-rose-50 text-rose-600 hover:bg-rose-100 rounded-xl text-xs font-semibold transition-all flex items-center justify-center">
                        Hapus
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Kolom Kiri (Data Toko yang Harus Dikunjungi Sesuai Persyaratan) -->
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                            🏬
                        </div>
                        <div>
                            <h2 class="font-bold text-slate-900 text-base">Toko yang Harus Dikunjungi</h2>
                            <p class="text-xs text-slate-400">Data profil outlet sasaran rute</p>
                        </div>
                    </div>
                    <a href="{{ route('stores.show', $visit->store->id) }}" class="text-xs font-semibold text-blue-600 hover:underline min-h-[36px] flex items-center">
                        Lihat Profil &rarr;
                    </a>
                </div>

                <div class="mt-5 space-y-4 text-sm">
                    <div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Nama Toko / Warung</span>
                        <span class="text-lg font-bold text-slate-900 block mt-0.5">{{ $visit->store->name }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Pemilik Toko</span>
                            <span class="text-slate-800 font-semibold mt-0.5 block">{{ $visit->store->owner_name }}</span>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Wilayah / Cluster</span>
                            <span class="text-slate-800 font-semibold mt-0.5 block">{{ $visit->store->area ?? 'Belum diatur' }}</span>
                        </div>
                    </div>

                    <div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">No. Telepon / WhatsApp</span>
                        <div class="mt-0.5 flex items-center gap-2">
                            <span class="text-slate-800 font-semibold">{{ $visit->store->phone ?? 'Tidak ada data telepon' }}</span>
                            @if($visit->store->phone)
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $visit->store->phone) }}" target="_blank" class="min-h-[48px] px-3.5 py-2 bg-emerald-50 text-emerald-700 text-xs rounded-xl font-bold hover:bg-emerald-100 flex items-center">
                                    Hubungi WA
                                </a>
                            @endif
                        </div>
                    </div>

                    <div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1">Alamat Lengkap</span>
                        <div class="p-3.5 bg-slate-50 border border-slate-100 rounded-xl text-slate-700 text-xs leading-relaxed">
                            {{ $visit->store->address }}
                        </div>
                    </div>

                    <div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1">Koordinat Lokasi Toko</span>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100 min-h-[48px]">
                            <span class="font-mono text-xs text-slate-700">
                                Lat: {{ $visit->store->latitude ?? '-' }}, Lng: {{ $visit->store->longitude ?? '-' }}
                            </span>
                            @if($visit->store->latitude && $visit->store->longitude)
                                <a href="https://www.google.com/maps?q={{ $visit->store->latitude }},{{ $visit->store->longitude }}" target="_blank" class="min-h-[48px] px-3 py-2 text-xs font-bold text-blue-600 hover:underline flex items-center">
                                    Buka Peta &rarr;
                                </a>
                            @endif
                        </div>
                    </div>

                    @if($visit->notes)
                        <div>
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1">Catatan Khusus dari Admin/Jadwal</span>
                            <div class="p-3 bg-amber-50/70 border border-amber-100 rounded-xl text-amber-900 text-xs">
                                "{{ $visit->notes }}"
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Riwayat Kunjungan Sebelumnya -->
            @if($previousVisits->isNotEmpty())
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <h3 class="font-bold text-slate-900 text-sm mb-3">Riwayat Kunjungan Toko Ini Sebelumnya</h3>
                    <div class="space-y-2.5">
                        @foreach($previousVisits as $prev)
                            <div class="p-3 rounded-xl border border-slate-100 text-xs flex items-center justify-between">
                                <div>
                                    <div class="font-semibold text-slate-800">{{ \Carbon\Carbon::parse($prev->visit_date)->translatedFormat('d M Y') }}</div>
                                    <div class="text-slate-400">Oleh: {{ $prev->user->name ?? '-' }}</div>
                                </div>
                                <div class="text-right">
                                    <x-badge :status="$prev->result_status ?? $prev->status" />
                                    @if($prev->result_status === 'Berhasil')
                                        <div class="font-bold text-emerald-600 mt-1">Rp {{ number_format($prev->order_value, 0, ',', '.') }}</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Kolom Kanan (Formulir Check-in Kunjungan / Hasil Selesai) -->
        <div class="lg:col-span-7 space-y-6">
            @if($visit->status === 'Selesai')
                <!-- HASIL KUNJUNGAN SELESAI (KUNCI DATA / READ-ONLY SESUAI ATURAN IMUTABILITAS SRS I.2) -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">
                    <!-- Banner Kunci Data Imutabilitas -->
                    <div class="mb-6 p-4 rounded-xl bg-slate-50 border border-slate-200 flex items-center gap-3">
                        <span class="text-2xl">🔒</span>
                        <div>
                            <p class="text-xs font-bold text-slate-800 uppercase tracking-wider">Data Terkunci (Read-Only)</p>
                            <p class="text-xs text-slate-600 mt-0.5">
                                Formulir kunjungan ini telah dikunci untuk menjaga integritas data pengawasan (SRS I.2).
                                @if(auth()->check() && auth()->user()->isAdmin())
                                    <span class="text-blue-600 font-semibold block mt-0.5">Anda login sebagai Admin: memiliki wewenang untuk meninjau riwayat dan memperbarui data.</span>
                                @else
                                    <span class="text-slate-500 block mt-0.5">Data tidak dapat diubah kembali oleh Sales.</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pb-5 border-b border-slate-100">
                        <div class="w-12 h-12 rounded-2xl {{ $visit->result_status === 'Berhasil' ? 'bg-emerald-50 text-emerald-600' : ($visit->result_status === 'Tutup' ? 'bg-amber-50 text-amber-600' : 'bg-rose-50 text-rose-600') }} flex items-center justify-center font-bold text-xl">
                            @if($visit->result_status === 'Berhasil') ✓ @elseif($visit->result_status === 'Tutup') ! @else ✕ @endif
                        </div>
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Hasil Check-in Kunjungan</span>
                            <h2 class="text-xl font-bold text-slate-900">Status: {{ $visit->result_status }}</h2>
                        </div>
                    </div>

                    <div class="mt-6 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-xs text-slate-400 font-medium block">Waktu Kunjungan (Server Timestamp)</span>
                                <span class="text-sm font-bold text-slate-800 mt-1 block">
                                    {{ $visit->check_in_time ? \Carbon\Carbon::parse($visit->check_in_time)->translatedFormat('d M Y, H:i:s') . ' WIB' : '-' }}
                                </span>
                            </div>

                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-xs text-slate-400 font-medium block">Nilai Pesanan</span>
                                @if($visit->result_status === 'Berhasil')
                                    <span class="text-lg font-extrabold text-emerald-600 mt-0.5 block">
                                        Rp {{ number_format($visit->order_value, 0, ',', '.') }}
                                    </span>
                                @else
                                    <span class="text-sm font-semibold text-slate-400 mt-1 block">
                                        Rp 0 (Tutup / Ditolak)
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if($visit->fail_reason)
                            <div class="p-4 rounded-xl bg-rose-50/60 border border-rose-100">
                                <span class="text-xs font-bold text-rose-800 uppercase tracking-wider block mb-1">Alasan Gagal / Penolakan</span>
                                <p class="text-sm text-rose-900 font-medium">
                                    {{ $visit->fail_reason }}
                                </p>
                            </div>
                        @endif

                        @if($visit->notes)
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">Catatan Kunjungan &amp; Riwayat Log</span>
                                <p class="text-sm text-slate-700 whitespace-pre-line">
                                    {{ $visit->notes }}
                                </p>
                            </div>
                        @endif

                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">Koordinat Check-in Sales</span>
                            <div class="flex items-center justify-between text-xs font-mono text-slate-700">
                                <span>Lat: {{ $visit->simulated_latitude ?? '-' }}, Lng: {{ $visit->simulated_longitude ?? '-' }}</span>
                                @if($visit->simulated_latitude && $visit->simulated_longitude)
                                    <a href="https://www.google.com/maps?q={{ $visit->simulated_latitude }},{{ $visit->simulated_longitude }}" target="_blank" class="text-blue-600 font-sans font-bold hover:underline min-h-[36px] flex items-center">
                                        Lihat Posisi Check-in &rarr;
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-5 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-xs text-slate-400">Data tersimpan aman di sistem pengawasan.</span>
                        <a href="{{ route('rute.index') }}" class="min-h-[48px] px-5 py-2.5 bg-slate-900 text-white rounded-xl text-xs font-semibold hover:bg-slate-800 transition-colors flex items-center justify-center">
                            Kembali ke Daftar Rute
                        </a>
                    </div>
                </div>
            @else
                <!-- FORMULIR CHECK-IN KUNJUNGAN (MOBILE-FIRST: TOUCH TARGET >= 48PX - SDS II.3) -->
                <div class="bg-white rounded-2xl border border-blue-100 shadow-sm p-6 sm:p-8 relative"
                     x-data="{
                        resultStatus: '{{ old('result_status', old('status', 'Berhasil')) }}',
                        simLat: '{{ old('simulated_latitude', $visit->store->latitude ?? '-6.2088000') }}',
                        simLng: '{{ old('simulated_longitude', $visit->store->longitude ?? '106.8456000') }}',
                        setStoreCoords() {
                            this.simLat = '{{ $visit->store->latitude ?? '-6.2088000' }}';
                            this.simLng = '{{ $visit->store->longitude ?? '106.8456000' }}';
                        }
                     }">

                    <div class="flex items-center gap-3 pb-5 border-b border-slate-100">
                        <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-blue-600">Alur Transaksi &amp; Pelaporan</span>
                            <h2 class="text-xl font-bold text-slate-900">Formulir Check-in Kunjungan</h2>
                        </div>
                    </div>

                    <p class="text-xs text-slate-500 mt-3 mb-6">
                        Isi form ini ketika telah tiba di toko. Waktu kunjungan akan otomatis dicatat oleh server timestamp saat formulir dikirimkan (SRS I.3).
                    </p>

                    <!-- Formulir Check-in Kunjungan -->
                    <form action="{{ route('rute.checkin', $visit->id) }}" method="POST" id="checkinForm">
                        @csrf

                        <div class="space-y-5">
                            <!-- 1. Pilihan Status Hasil Kunjungan (Touch Target Min-Height 48px) -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    Status Kunjungan <span class="text-rose-500">*</span>
                                </label>
                                <div class="grid grid-cols-3 gap-3">
                                    <label class="relative flex flex-col items-center justify-center min-h-[56px] p-3 rounded-xl border-2 cursor-pointer transition-all"
                                           :class="resultStatus === 'Berhasil' ? 'border-emerald-500 bg-emerald-50/40 text-emerald-900 font-bold' : 'border-slate-200 hover:border-slate-300 text-slate-700'">
                                        <input type="radio" name="result_status" value="Berhasil" x-model="resultStatus" class="sr-only">
                                        <span class="text-base mb-0.5">🎉</span>
                                        <span class="text-xs">Berhasil</span>
                                    </label>

                                    <label class="relative flex flex-col items-center justify-center min-h-[56px] p-3 rounded-xl border-2 cursor-pointer transition-all"
                                           :class="resultStatus === 'Tutup' ? 'border-amber-500 bg-amber-50/40 text-amber-900 font-bold' : 'border-slate-200 hover:border-slate-300 text-slate-700'">
                                        <input type="radio" name="result_status" value="Tutup" x-model="resultStatus" class="sr-only">
                                        <span class="text-base mb-0.5">🔒</span>
                                        <span class="text-xs">Tutup</span>
                                    </label>

                                    <label class="relative flex flex-col items-center justify-center min-h-[56px] p-3 rounded-xl border-2 cursor-pointer transition-all"
                                           :class="resultStatus === 'Ditolak' ? 'border-rose-500 bg-rose-50/40 text-rose-900 font-bold' : 'border-slate-200 hover:border-slate-300 text-slate-700'">
                                        <input type="radio" name="result_status" value="Ditolak" x-model="resultStatus" class="sr-only">
                                        <span class="text-base mb-0.5">🚫</span>
                                        <span class="text-xs">Ditolak</span>
                                    </label>
                                </div>
                                @error('result_status')
                                    <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                                @enderror
                                @error('status')
                                    <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 2. Nilai Pesanan (Wajib Jika Hasil Berhasil - SRS I.3) (Touch Target Min-Height 48px) -->
                            <div x-show="resultStatus === 'Berhasil'" x-transition class="p-4 rounded-xl bg-emerald-50/50 border border-emerald-100">
                                <label for="order_value" class="block text-xs font-bold text-emerald-900 uppercase tracking-wider mb-2">
                                    Nilai Pesanan (Rp) <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-sm font-bold text-emerald-700 pointer-events-none">
                                        Rp
                                    </span>
                                    <input type="number" step="1000" min="0" name="order_value" id="order_value" 
                                           value="{{ old('order_value', old('nilai_pesanan', '1500000')) }}"
                                           placeholder="Contoh: 1500000"
                                           class="w-full min-h-[48px] pl-12 pr-3.5 py-3 rounded-xl border {{ $errors->has('order_value') || $errors->has('nilai_pesanan') ? 'border-rose-400 bg-rose-50/30' : 'border-emerald-200' }} text-base sm:text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                                </div>
                                <p class="text-[11px] text-emerald-700 mt-1.5">Wajib bernilai positif (>= 0) jika kunjungan Berhasil.</p>
                                @error('order_value')
                                    <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                                @enderror
                                @error('nilai_pesanan')
                                    <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 3. Catatan / Alasan (Wajib Jika Tutup atau Ditolak - SRS I.3) -->
                            <div x-show="resultStatus === 'Tutup' || resultStatus === 'Ditolak'" x-transition class="p-4 rounded-xl bg-amber-50/50 border border-amber-100">
                                <label for="fail_reason" class="block text-xs font-bold text-amber-900 uppercase tracking-wider mb-2">
                                    Alasan Kunjungan Tutup / Ditolak <span class="text-rose-500">*</span>
                                </label>
                                <textarea name="fail_reason" id="fail_reason" rows="3"
                                          placeholder="Jelaskan alasan mengapa toko tutup atau pesanan ditolak pemilik..."
                                          class="w-full min-h-[72px] px-3.5 py-3 rounded-xl border {{ $errors->has('fail_reason') || $errors->has('catatan') ? 'border-rose-400 bg-rose-50/30' : 'border-amber-200' }} text-base sm:text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">{{ old('fail_reason', old('catatan')) }}</textarea>
                                <p class="text-[11px] text-amber-700 mt-1.5">Wajib diisi sebagai catatan alasan kegagalan kunjungan.</p>
                                @error('fail_reason')
                                    <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                                @enderror
                                @error('catatan')
                                    <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 4. Catatan Sales Tambahan (Opsional jika Berhasil) -->
                            <div x-show="resultStatus === 'Berhasil'">
                                <label for="checkin_notes" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    Catatan Kunjungan (Opsional)
                                </label>
                                <textarea name="notes" id="checkin_notes" rows="2"
                                          placeholder="Catatan tambahan hasil obrolan dengan pemilik toko..."
                                          class="w-full min-h-[60px] px-3.5 py-2.5 rounded-xl border border-slate-200 text-base sm:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">{{ old('notes') }}</textarea>
                            </div>

                            <!-- 5. Simulasi Koordinat Sales Saat Check-in -->
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                        Koordinat Lokasi Check-in
                                    </label>
                                    <button type="button" @click="setStoreCoords()" class="min-h-[40px] px-2 text-xs font-semibold text-blue-600 hover:underline flex items-center">
                                        Gunakan Posisi Toko
                                    </button>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <input type="text" name="simulated_latitude" x-model="simLat" placeholder="Latitude"
                                               class="w-full min-h-[48px] px-3 py-2.5 rounded-lg border border-slate-200 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <input type="text" name="simulated_longitude" x-model="simLng" placeholder="Longitude"
                                               class="w-full min-h-[48px] px-3 py-2.5 rounded-lg border border-slate-200 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                                    </div>
                                </div>
                            </div>

                            <!-- Tombol Kirim Check-In (Touch Target Min-Height 48px - SDS II.3) -->
                            <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-3">
                                <a href="{{ route('rute.index') }}" class="min-h-[48px] px-5 py-3 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors flex items-center justify-center">
                                    Batal
                                </a>
                                <button type="submit" class="min-h-[48px] px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-base sm:text-sm font-semibold shadow-md shadow-blue-600/30 transition-all flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Simpan &amp; Selesaikan Kunjungan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-layout>

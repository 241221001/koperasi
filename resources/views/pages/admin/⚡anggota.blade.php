<?php

use Livewire\Component;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public $penggunaId = null; 
    public $detailPenggota = null;
    public $input_nomor_anggota; 
    public $search = ''; 

    
    public function updatedSearch()
    {
        $this->resetDetail();
    }

    
    public function getAnggotaPendingProperty()
    {
        $query = DB::table('pengguna')
            ->leftJoin('anggota', 'pengguna.id', '=', 'anggota.user_id')
            ->where('pengguna.peran', 'anggota');

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('pengguna.nama', 'like', '%' . $this->search . '%')
                  ->orWhere('anggota.nama', 'like', '%' . $this->search . '%')
                  ->orWhere('anggota.nik', 'like', '%' . $this->search . '%')
                  ->orWhere('pengguna.email', 'like', '%' . $this->search . '%');
            });
        }

        return $query->select(
                'pengguna.id as id_pengguna', 
                'pengguna.email', 
                'pengguna.nomor_anggota as nomor_aktif_pengguna', 
                'pengguna.nama as nama_akun', 
                'anggota.nama as nama_anggota', 
                'anggota.nik',
                'anggota.status as status_anggota' 
            )
            ->orderBy('pengguna.created_at', 'desc')
            ->get();
    }

    // Ambil data gabungan saat klik periksa
    public function lihatDetail($id)
    {
        $this->penggunaId = $id;
        $this->refreshDetailData($id);
    }

    // Helper fungsi untuk mengambil data detail pendaftar terbaru secara realtime
    private function refreshDetailData($id)
    {
        $data = DB::table('pengguna')
            ->leftJoin('anggota', 'pengguna.id', '=', 'anggota.user_id')
            ->where('pengguna.id', $id)
            ->select(
                'pengguna.*', 
                'anggota.*', 
                'pengguna.id as id_pengguna', 
                'pengguna.nama as nama_akun', 
                'anggota.nama as nama_anggota',
                'anggota.status as status_anggota' 
            )
            ->first();
            
        if ($data) {
            $this->detailPenggota = $data;
            
            if (!empty($data->nomor_anggota)) {
                $this->input_nomor_anggota = $data->nomor_anggota;
            } else {
                $tahun = date('Y');
                $totalAktiv = DB::table('pengguna')->whereNotNull('nomor_anggota')->count() + 1;
                $this->input_nomor_anggota = 'KOP-' . $tahun . '-' . str_pad($totalAktiv, 4, '0', STR_PAD_LEFT);
            }
        }
    }

    // Proses Aktivasi Berkas Pendaftaran
    public function aktivasiAnggota($id_pengguna)
    {
        $this->validate([
            'input_nomor_anggota' => 'required'
        ]);

        // 🌟 PERBAIKAN LOGIKA: Simpan nomor ke tabel 'pengguna' dan ubah status di tabel 'anggota' pakai 'user_id'
        DB::transaction(function() use ($id_pengguna) {
            DB::table('pengguna')
                ->where('id', $id_pengguna)
                ->update([
                    'nomor_anggota' => $this->input_nomor_anggota
                ]);

            DB::table('anggota') 
                ->where('user_id', $id_pengguna) // 🛠️ Diperbaiki dari id_pengguna ke user_id
                ->update([
                    'nomor_anggota' => $this->input_nomor_anggota,
                    'status' => 'aktif' 
                ]);
        });

        // 🌟 Sinkronisasi data kanan & kiri secara realtime tanpa refresh page
        $this->refreshDetailData($id_pengguna);

        session()->flash('sukses_anggota', 'Akun anggota berhasil diaktivasi!');
    }

    // Tolak / Hapus Akun Anggota Sepenuhnya
    public function hapusAnggota($id)
    {
        DB::transaction(function () use ($id) {
            DB::table('simpanan')->whereIn('anggota_id', function($query) use ($id) {
                $query->select('id')->from('anggota')->where('user_id', $id);
            })->delete();

            DB::table('anggota')->where('user_id', $id)->delete();
            DB::table('pengguna')->where('id', $id)->delete();
        });

        session()->flash('error_anggota', "Data akun anggota berhasil dihapus permanen dari sistem.");
        $this->resetDetail();
    }

    public function resetDetail()
    {
        $this->detailPenggota = null;
        $this->penggunaId = null;
        $this->input_nomor_anggota = '';
    }
}; 
?>

<div class="space-y-6">
    
    @if (session()->has('sukses_anggota'))
        <div class="p-4 text-sm text-green-700 bg-green-50 rounded-xl font-medium border border-green-100 shadow-sm">
            ✨ {{ session('sukses_anggota') }}
        </div>
    @endif

    @if (session()->has('error_anggota'))
        <div class="p-4 text-sm text-rose-700 bg-rose-50 rounded-xl font-medium border border-rose-100 shadow-sm">
            🗑️ {{ session('error_anggota') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        
        <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm lg:col-span-2">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5 border-b border-gray-50 pb-4">
                <div>
                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wide">📋 Daftar & Verifikasi Anggota</h3>
                    <p class="text-xs text-gray-400 font-medium">Memantau seluruh akun pendaftar anggota beserta status verifikasinya.</p>
                </div>
                
                <div class="relative w-full sm:w-64">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 text-xs">🔍</span>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama, NIK, atau email..." class="w-full pl-8 pr-3 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 font-medium text-gray-700 transition-all shadow-sm">
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg border border-gray-100">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead>
                        <tr class="bg-gray-50/70 border-b border-gray-100 text-gray-400 text-xs font-bold uppercase tracking-wider">
                            <th class="p-4">Nama Pendaftar</th>
                            <th class="p-4">NIK / KTP</th>
                            <th class="p-4">Email Akun</th>
                            <th class="p-4 text-center w-40">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($this->anggotaPending as $item)
                            <tr class="hover:bg-gray-50/50 {{ $this->penggunaId == $item->id_pengguna ? 'bg-indigo-50/30' : '' }} transition-colors duration-150">
                                <td class="p-4 font-bold text-gray-800">
                                    <div class="flex flex-col">
                                        <span>{{ $item->nama_anggota ?? $item->nama_akun ?? 'Tanpa Nama' }}</span>
                                        @if(($item->status_anggota ?? 'nonaktif') === 'aktif')
                                            <span class="text-[10px] text-emerald-600 font-mono font-bold">✓ Terverifikasi Aktif</span>
                                        @else
                                            <span class="text-[10px] text-amber-500 font-bold">⏳ Menunggu Verifikasi</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="p-4 font-mono text-xs font-semibold text-gray-500">
                                    {{ $item->nik ?? 'Belum Isi Profil' }}
                                </td>
                                <td class="p-4 font-medium text-gray-500 text-xs">{{ $item->email }}</td>
                                <td class="p-4">
                                    <div class="flex items-center gap-1.5 justify-center">
                                        <button wire:click="lihatDetail({{ $item->id_pengguna }})" class="px-3 py-1.5 text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-md text-xs font-bold transition-all duration-150 active:scale-95">
                                            🔍 Periksa
                                        </button>

                                        @if(($item->status_anggota ?? 'nonaktif') === 'aktif')
                                            <span class="px-2 py-1 bg-emerald-50 text-emerald-700 border border-emerald-100 rounded-md text-[10px] font-bold">
                                                Verified
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-12 text-gray-400 text-xs font-semibold">
                                    🔍 Data pencarian tidak ditemukan / belum ada anggota.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm sticky top-6">
            <div class="mb-4 border-b border-gray-100 pb-3">
                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wide">🪪 Berkas Profil Anggota</h3>
                <p class="text-xs text-gray-400 font-medium">Validasi kecocokan data fisik dari tabel anggota.</p>
            </div>

            @if($this->penggunaId && $this->detailPenggota)
                <div class="space-y-3 text-sm">
                    <div>
                        <span class="block text-[10px] uppercase font-bold text-gray-400 tracking-wider">Nomor NIK / KTP</span>
                        <p class="font-mono text-sm font-bold text-gray-800 bg-gray-50 p-2 rounded-lg border border-gray-100">{{ $detailPenggota->nik ?? 'Belum diisi' }}</p>
                    </div>

                    <div>
                        <span class="block text-[10px] uppercase font-bold text-gray-400 tracking-wider">Nama Lengkap</span>
                        <p class="font-bold text-gray-800 text-base">{{ $detailPenggota->nama_anggota ?? $detailPenggota->nama_akun }}</p>
                    </div>

                    <div>
                        <span class="block text-[10px] uppercase font-bold text-gray-400 tracking-wider">No. Telepon / WA</span>
                        <p class="font-mono text-xs text-indigo-600 font-bold">{{ $detailPenggota->telepon ?? '-' }}</p>
                    </div>

                    <div>
                        <span class="block text-[10px] uppercase font-bold text-gray-400 tracking-wider">Alamat Lengkap</span>
                        <p class="text-xs text-gray-600 font-medium leading-relaxed bg-gray-50/50 p-2.5 rounded-lg border border-gray-100/50">
                            {{ $detailPenggota->alamat ?? 'Belum diisi' }}
                        </p>
                    </div>

                    <div>
                        <span class="block text-[10px] uppercase font-bold text-gray-400 tracking-wider mb-1.5">Lampiran Foto KTP</span>
                        @if(!empty($detailPenggota->foto_ktp))
                            <div class="relative overflow-hidden rounded-lg border border-gray-100 bg-gray-50 group">
                                <img src="{{ asset('storage/' . $detailPenggota->foto_ktp) }}" 
                                     alt="Foto KTP Anggota" 
                                     class="w-full h-40 object-cover object-center transition-transform duration-200 group-hover:scale-105">
                                
                                <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                    <a href="{{ asset('storage/' . $detailPenggota->foto_ktp) }}" 
                                       target="_blank" 
                                       class="bg-white text-gray-800 px-3 py-1.5 rounded-md text-xs font-bold shadow-sm flex items-center gap-1">
                                        🔍 Perbesar Foto
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="py-6 text-center border border-dashed border-gray-200 rounded-lg text-gray-400 text-xs font-semibold bg-gray-50/50">
                                🚫 Tidak ada lampiran foto KTP
                            </div>
                        @endif
                    </div>

                    <hr class="border-gray-100 my-1">

                    <div>
                        <label class="block text-[11px] font-bold text-amber-700 bg-amber-50/50 border border-amber-100 px-2 py-1 rounded mb-1.5 uppercase tracking-wider">
                            🔑 Nomor Anggota Koperasi
                        </label>
                        <input type="text" wire:model="input_nomor_anggota" {{ ($detailPenggota->status_anggota ?? 'nonaktif') === 'aktif' ? 'disabled' : '' }} class="w-full px-3 py-1.5 border border-gray-200 disabled:bg-gray-100 disabled:text-gray-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm font-mono font-bold text-gray-800 transition-all">
                        @error('input_nomor_anggota') <span class="text-xs text-rose-500 mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-1 flex flex-col gap-2">
                        @if(($detailPenggota->status_anggota ?? 'nonaktif') !== 'aktif')
                            <div class="flex gap-2">
                                <button onclick="confirm('Tolak dan hapus pendaftaran ini?') || event.stopImmediatePropagation()" wire:click="hapusAnggota({{ $detailPenggota->id_pengguna }})" class="w-1/3 bg-rose-50 hover:bg-rose-100 text-rose-600 py-2 rounded-lg text-xs font-bold transition-all duration-150 active:scale-95">
                                    ❌ Tolak
                                </button>
                                <button wire:click="aktivasiAnggota({{ $detailPenggota->id_pengguna }})" class="w-2/3 bg-emerald-600 hover:bg-emerald-700 text-white py-2 rounded-lg text-xs font-bold transition-all duration-150 shadow-sm shadow-emerald-100 active:scale-95 text-center">
                                    ✓ Cocok & Aktifkan
                                </button>
                            </div>
                        @else
                            <div class="flex flex-col gap-2">
                                <div class="w-full bg-emerald-50 border border-emerald-200 text-emerald-800 py-2 rounded-lg text-xs font-black text-center">
                                    🛡️ Akun Ini Sudah Terverifikasi Aktif
                                </div>
                                <button onclick="confirm('PERINGATAN: Hapus permanen akun anggota ini beserta semua riwayat saldo simpanannya?') || event.stopImmediatePropagation()" wire:click="hapusAnggota({{ $detailPenggota->id_pengguna }})" class="w-full bg-rose-600 hover:bg-rose-700 text-white py-2 rounded-lg text-xs font-bold transition-all duration-150 active:scale-95 text-center shadow-sm shadow-rose-100">
                                    🗑️ Hapus Akun dari Sistem
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="py-12 text-center">
                    <span class="text-3xl block mb-2">🔍</span>
                    <p class="text-xs font-semibold text-gray-400">Klik tombol <span class="text-indigo-600">Periksa</span> di tabel kiri untuk memuat berkas pendaftaran anggota secara realtime.</p>
                </div>
            @endif
        </div>

    </div>
</div>
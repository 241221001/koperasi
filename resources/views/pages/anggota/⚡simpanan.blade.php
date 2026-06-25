<?php

namespace App\views\Pages\Anggota;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Simpanan;
use App\Models\Anggota;
use App\Models\KategoriSimpanan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    use WithPagination;

    // Filter properties
    public $tahun;
    public $bulan;
    public $kategori_simpanan_id = 'semua';
    public $jenis_transaksi = 'semua';
    public $search = '';
    public $sortBy = 'tanggal_transaksi';
    public $sortDirection = 'desc';
    public $perPage = 10;

    // Form properties
    public $anggota_id;
    public $kategori_id;
    public $jenis_transaksi_form;
    public $jumlah;
    public $tanggal_transaksi;

    protected $queryString = [
        'tahun',
        'bulan',
        'kategori_simpanan_id',
        'jenis_transaksi',
        'search',
        'sortBy',
        'sortDirection',
        'page' => ['except' => 1],
    ];

    protected $rules = [
        'anggota_id' => 'required|exists:anggota,id',
        'kategori_id' => 'required|exists:kategori_simpanan,id',
        'jenis_transaksi_form' => 'required|in:setor,tarik',
        'jumlah' => 'required|numeric|min:1000',
        'tanggal_transaksi' => 'required|date',
    ];

    protected $messages = [
        'anggota_id.required' => 'Anggota harus dipilih',
        'anggota_id.exists' => 'Anggota tidak ditemukan',
        'kategori_id.required' => 'Kategori simpanan harus dipilih',
        'kategori_id.exists' => 'Kategori simpanan tidak ditemukan',
        'jenis_transaksi_form.required' => 'Jenis transaksi harus dipilih',
        'jenis_transaksi_form.in' => 'Jenis transaksi tidak valid',
        'jumlah.required' => 'Jumlah harus diisi',
        'jumlah.numeric' => 'Jumlah harus berupa angka',
        'jumlah.min' => 'Jumlah minimal Rp 1.000',
        'tanggal_transaksi.required' => 'Tanggal transaksi harus diisi',
        'tanggal_transaksi.date' => 'Format tanggal tidak valid',
    ];

    public function mount()
    {
        $this->tahun = $this->tahun ?? Carbon::now()->year;
        $this->bulan = $this->bulan ?? Carbon::now()->month;
        $this->tanggal_transaksi = Carbon::now()->format('Y-m-d');

        // Set anggota_id jika user login sebagai anggota
        if (Auth::check() && $this->isAnggota()) {
            $anggota = Anggota::where('user_id', Auth::id())->first();
            if ($anggota) {
                $this->anggota_id = $anggota->id;
            }
        }
    }

    // Computed properties untuk Statistik
    public function getTotalSimpananProperty()
    {
        $query = Simpanan::where('jenis_transaksi', 'setor');

        if (Auth::check() && $this->isAnggota()) {
            $anggota = Anggota::where('user_id', Auth::id())->first();
            if ($anggota) {
                $query->where('anggota_id', $anggota->id);
            }
        }

        return $query->when($this->kategori_simpanan_id !== 'semua', function ($q) {
            return $q->where('kategori_simpanan_id', $this->kategori_simpanan_id);
        })->sum('jumlah');
    }

    public function getTotalPenarikanProperty()
    {
        $query = Simpanan::where('jenis_transaksi', 'tarik');

        if (Auth::check() && $this->isAnggota()) {
            $anggota = Anggota::where('user_id', Auth::id())->first();
            if ($anggota) {
                $query->where('anggota_id', $anggota->id);
            }
        }

        return $query->when($this->kategori_simpanan_id !== 'semua', function ($q) {
            return $q->where('kategori_simpanan_id', $this->kategori_simpanan_id);
        })->sum('jumlah');
    }

    public function getSaldoSimpananProperty()
    {
        return $this->total_simpanan - $this->total_penarikan;
    }

    public function getTotalSimpananBulanIniProperty()
    {
        $query = Simpanan::whereMonth('tanggal_transaksi', $this->bulan)
            ->whereYear('tanggal_transaksi', $this->tahun)
            ->where('jenis_transaksi', 'setor');

        if (Auth::check() && $this->isAnggota()) {
            $anggota = Anggota::where('user_id', Auth::id())->first();
            if ($anggota) {
                $query->where('anggota_id', $anggota->id);
            }
        }

        return $query->when($this->kategori_simpanan_id !== 'semua', function ($q) {
            return $q->where('kategori_simpanan_id', $this->kategori_simpanan_id);
        })->sum('jumlah');
    }

    public function getDataSimpananProperty()
    {
        $query = Simpanan::with(['anggota', 'kategoriSimpanan']);

        if (Auth::check() && $this->isAnggota()) {
            $anggota = Anggota::where('user_id', Auth::id())->first();
            if ($anggota) {
                $query->where('anggota_id', $anggota->id);
            }
        }

        return $query->when($this->kategori_simpanan_id !== 'semua', function ($q) {
            return $q->where('kategori_simpanan_id', $this->kategori_simpanan_id);
        })
        ->when($this->jenis_transaksi !== 'semua', function ($q) {
            return $q->where('jenis_transaksi', $this->jenis_transaksi);
        })
        ->when($this->search, function ($q) {
            return $q->whereHas('anggota', function ($sub) {
                $sub->where('nama', 'like', '%' . $this->search . '%')
                    ->orWhere('nik', 'like', '%' . $this->search . '%');
            })->orWhere('jumlah', 'like', '%' . $this->search . '%');
        })
        ->orderBy($this->sortBy, $this->sortDirection)
        ->paginate($this->perPage);
    }

    public function getKategoriSimpananListProperty()
    {
        return KategoriSimpanan::all();
    }

    public function getAnggotaListProperty()
    {
        if (Auth::check() && $this->isAnggota()) {
            $anggota = Anggota::where('user_id', Auth::id())->first();
            return $anggota ? collect([$anggota]) : collect();
        }
        return Anggota::where('status', 'aktif')->get();
    }

    private function isAnggota()
    {
        $user = Auth::user();
        return $user && $user->role === 'anggota';
    }

    private function isAdmin()
    {
        $user = Auth::user();
        return $user && $user->role === 'admin';
    }

    // Methods untuk sorting
    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    // Reset filter
    public function resetFilter()
    {
        $this->reset(['search', 'kategori_simpanan_id', 'jenis_transaksi', 'sortBy', 'sortDirection']);
        $this->tahun = Carbon::now()->year;
        $this->bulan = Carbon::now()->month;
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingKategoriSimpananId()
    {
        $this->resetPage();
    }

    public function updatingJenisTransaksi()
    {
        $this->resetPage();
    }

    // Reset form
    public function resetForm()
    {
        $this->reset([
            'kategori_id',
            'jenis_transaksi_form',
            'jumlah',
            'tanggal_transaksi'
        ]);
        $this->tanggal_transaksi = Carbon::now()->format('Y-m-d');
        $this->resetValidation();

        if (Auth::check() && $this->isAnggota()) {
            $anggota = Anggota::where('user_id', Auth::id())->first();
            if ($anggota) {
                $this->anggota_id = $anggota->id;
            }
        }
    }

    // Simpan data
    public function simpan()
    {
        // Validasi
        $validated = $this->validate();

        // Cek apakah kategori simpanan ada
        $kategori = KategoriSimpanan::find($this->kategori_id);
        if (!$kategori) {
            $this->addError('kategori_id', 'Kategori simpanan tidak ditemukan');
            return;
        }

        // Cek apakah anggota ada
        $anggota = Anggota::find($this->anggota_id);
        if (!$anggota) {
            $this->addError('anggota_id', 'Anggota tidak ditemukan');
            return;
        }

        // Cek saldo untuk penarikan
        if ($this->jenis_transaksi_form === 'tarik') {
            $saldo = $this->getSaldoAnggota($this->anggota_id);
            if ($saldo < $this->jumlah) {
                $this->addError('jumlah', 'Saldo tidak mencukupi. Saldo saat ini: Rp ' . number_format($saldo, 0, ',', '.'));
                return;
            }
        }

        try {
            Simpanan::create([
                'anggota_id' => $this->anggota_id,
                'kategori_simpanan_id' => $this->kategori_id,
                'jenis_transaksi' => $this->jenis_transaksi_form,
                'jumlah' => $this->jumlah,
                'tanggal_transaksi' => $this->tanggal_transaksi,
            ]);

            session()->flash('success', 'Transaksi simpanan berhasil ' . ($this->jenis_transaksi_form === 'setor' ? 'disetor' : 'ditarik'));

            $this->resetForm();
            $this->dispatch('refreshData');

        } catch (\Exception $e) {
            // Tangkap error dan tampilkan pesan yang lebih jelas
            if (str_contains($e->getMessage(), 'foreign key constraint')) {
                $this->addError('kategori_id', 'Kategori simpanan tidak valid. Silakan pilih kategori yang tersedia.');
            } else {
                $this->addError('form', 'Terjadi kesalahan: ' . $e->getMessage());
            }
        }
    }

    // Get saldo anggota
    private function getSaldoAnggota($anggota_id)
    {
        $setor = Simpanan::where('anggota_id', $anggota_id)
            ->where('jenis_transaksi', 'setor')
            ->sum('jumlah');

        $tarik = Simpanan::where('anggota_id', $anggota_id)
            ->where('jenis_transaksi', 'tarik')
            ->sum('jumlah');

        return $setor - $tarik;
    }

    public function render()
    {
        return view('pages.anggota.simpanan');
    }
}
?>
<div class="container mx-auto px-4 py-8">

    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight">
                {{ Auth::check() && $this->isAnggota() ? 'Simpanan Saya' : 'Manajemen Simpanan' }}
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                {{ Auth::check() && $this->isAnggota() ? 'Lihat dan kelola simpanan Anda' : 'Kelola data simpanan anggota koperasi' }}
            </p>
        </div>
        <div class="flex gap-2">
            <button wire:click="resetFilter" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-xl text-sm transition active:scale-[0.99]">
                Reset Filter
            </button>
            <a href="{{ route('anggota.dashboard') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-xl text-sm transition active:scale-[0.99]">
                ← Kembali
            </a>
        </div>
    </div>

    <!-- Error Message -->
    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl text-xs font-bold mb-6">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Success Message -->
    @if (session()->has('success'))
        <div class="bg-emerald-50 border border-emerald-100 text-emerald-700 px-4 py-3 rounded-2xl text-xs font-bold mb-6">
            {{ session('success') }}
        </div>
    @endif

    <!-- Form Tambah Simpanan (Hanya untuk Admin) -->
    @if(Auth::check() && $this->isAdmin())
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6 mb-6">
        <h2 class="text-lg font-black text-slate-800 mb-4">Tambah Transaksi Simpanan</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Anggota -->
            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Anggota <span class="text-red-500">*</span></label>
                <select wire:model="anggota_id" class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                    <option value="">-- pilih anggota --</option>
                    @foreach($this->anggota_list as $anggota)
                        <option value="{{ $anggota->id }}">{{ $anggota->nama }} ({{ $anggota->nik }})</option>
                    @endforeach
                </select>
                @error('anggota_id') <p class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Kategori Simpanan -->
            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Kategori Simpanan <span class="text-red-500">*</span></label>
                <select wire:model="kategori_id" class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                    <option value="">-- pilih kategori --</option>
                    @foreach($this->kategori_simpanan_list as $kategori)
                        <option value="{{ $kategori->id }}">{{ $kategori->nama }}</option>
                    @endforeach
                </select>
                @error('kategori_id') <p class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Jenis Transaksi -->
            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Jenis Transaksi <span class="text-red-500">*</span></label>
                <select wire:model="jenis_transaksi_form" class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                    <option value="">-- pilih jenis --</option>
                    <option value="setor">Setor</option>
                    <option value="tarik">Tarik</option>
                </select>
                @error('jenis_transaksi_form') <p class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Jumlah -->
            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Jumlah (Rp) <span class="text-red-500">*</span></label>
                <input type="number" wire:model="jumlah" placeholder="Masukkan nominal" min="1000" class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                @error('jumlah') <p class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Tanggal -->
            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tanggal Transaksi <span class="text-red-500">*</span></label>
                <input type="date" wire:model="tanggal_transaksi" class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                @error('tanggal_transaksi') <p class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Tombol -->
            <div class="flex items-end space-x-2">
                <button wire:click="simpan" class="flex-1 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-sm py-3 rounded-2xl shadow-lg shadow-blue-500/20 transition active:scale-[0.99]">
                    Simpan Transaksi
                </button>
                <button wire:click="resetForm" type="button" class="px-4 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold text-sm rounded-2xl transition active:scale-[0.99]">
                    Reset
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Filter Section -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tahun</label>
                <select wire:model.live="tahun" class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                    @for($i = Carbon\Carbon::now()->year; $i >= 2020; $i--)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
            </div>
            
            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Bulan</label>
                <select wire:model.live="bulan" class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                    @foreach(range(1, 12) as $month)
                        <option value="{{ $month }}">{{ Carbon\Carbon::create()->month($month)->format('F') }}</option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Kategori Simpanan</label>
                <select wire:model.live="kategori_simpanan_id" class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                    <option value="semua">Semua Kategori</option>
                    @foreach($this->kategori_simpanan_list as $kategori)
                        <option value="{{ $kategori->id }}">{{ $kategori->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Jenis Transaksi</label>
                <select wire:model.live="jenis_transaksi" class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                    <option value="semua">Semua</option>
                    <option value="setor">Setor</option>
                    <option value="tarik">Tarik</option>
                </select>
            </div>

            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Cari</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari anggota atau nominal..." class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
            </div>
        </div>
    </div>

    <!-- Statistik Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Card Total Simpanan -->
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-3xl border border-blue-200/60 shadow-sm p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-[10px] uppercase tracking-wider text-blue-600 font-bold">Total Setoran</h3>
                    <p class="text-xl font-black text-blue-700">Rp {{ number_format($this->total_simpanan, 0, ',', '.') }}</p>
                    <p class="text-[10px] text-blue-500 mt-1">Bulan ini: Rp {{ number_format($this->total_simpanan_bulan_ini, 0, ',', '.') }}</p>
                </div>
                <div class="bg-blue-500 rounded-full p-3">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Card Total Penarikan -->
        <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-3xl border border-red-200/60 shadow-sm p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-[10px] uppercase tracking-wider text-red-600 font-bold">Total Penarikan</h3>
                    <p class="text-xl font-black text-red-700">Rp {{ number_format($this->total_penarikan, 0, ',', '.') }}</p>
                </div>
                <div class="bg-red-500 rounded-full p-3">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Card Saldo -->
        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-3xl border border-green-200/60 shadow-sm p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-[10px] uppercase tracking-wider text-green-600 font-bold">Saldo Simpanan</h3>
                    <p class="text-xl font-black text-green-700">Rp {{ number_format($this->saldo_simpanan, 0, ',', '.') }}</p>
                    <p class="text-[10px] text-green-500 mt-1">Total keseluruhan</p>
                </div>
                <div class="bg-green-500 rounded-full p-3">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Card Total Transaksi -->
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-3xl border border-purple-200/60 shadow-sm p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-[10px] uppercase tracking-wider text-purple-600 font-bold">Total Transaksi</h3>
                    <p class="text-xl font-black text-purple-700">{{ number_format($this->data_simpanan->total()) }}</p>
                    <p class="text-[10px] text-purple-500 mt-1">Data ditampilkan</p>
                </div>
                <div class="bg-purple-500 rounded-full p-3">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Data Simpanan -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-black text-slate-800">Data Simpanan</h2>
            <div class="flex items-center space-x-2">
                <span class="text-xs text-slate-500 font-bold">Per Page:</span>
                <select wire:model.live="perPage" class="px-3 py-2 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50 rounded-2xl">
                    <tr>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider cursor-pointer hover:bg-slate-100 transition rounded-l-2xl" wire:click="sortBy('tanggal_transaksi')">
                            Tanggal
                            @if($sortBy === 'tanggal_transaksi')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider cursor-pointer hover:bg-slate-100 transition" wire:click="sortBy('anggota.nama')">
                            Anggota
                            @if($sortBy === 'anggota.nama')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">NIK</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Kategori</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Jenis</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider cursor-pointer hover:bg-slate-100 transition rounded-r-2xl" wire:click="sortBy('jumlah')">
                            Jumlah
                            @if($sortBy === 'jumlah')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    @forelse($this->data_simpanan as $simpanan)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600">{{ Carbon\Carbon::parse($simpanan->tanggal_transaksi)->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs font-bold text-slate-800">{{ $simpanan->anggota->nama }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600">{{ $simpanan->anggota->nik }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600">{{ $simpanan->kategoriSimpanan->nama ?? '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-[10px] leading-5 font-bold rounded-full {{ $simpanan->jenis_transaksi === 'setor' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ ucfirst($simpanan->jenis_transaksi) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs font-bold {{ $simpanan->jenis_transaksi === 'setor' ? 'text-green-600' : 'text-red-600' }}">
                                Rp {{ number_format($simpanan->jumlah, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-3 text-center text-xs text-slate-500">Tidak ada data simpanan</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            {{ $this->data_simpanan->links() }}
        </div>
    </div>

</div>
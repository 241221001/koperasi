<?php

namespace App\views\Pages\Anggota;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ArusKas;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    use WithPagination;

    // Filter properties
    public $tahun;
    public $bulan;
    public $jenis_kas = 'semua';
    public $search = '';
    public $sortBy = 'tanggal_transaksi';
    public $sortDirection = 'desc';
    public $perPage = 10;

    // Form properties
    public $jenis_kas_form;
    public $jumlah;
    public $keterangan;
    public $tanggal_transaksi;

    protected $queryString = [
        'tahun',
        'bulan',
        'jenis_kas',
        'search',
        'sortBy',
        'sortDirection',
        'page' => ['except' => 1],
    ];

    protected $rules = [
        'jenis_kas_form' => 'required|in:masuk,keluar',
        'jumlah' => 'required|numeric|min:1000',
        'keterangan' => 'required|string|min:3|max:255',
        'tanggal_transaksi' => 'required|date',
    ];

    protected $messages = [
        'jenis_kas_form.required' => 'Jenis kas harus dipilih',
        'jenis_kas_form.in' => 'Jenis kas tidak valid',
        'jumlah.required' => 'Jumlah harus diisi',
        'jumlah.numeric' => 'Jumlah harus berupa angka',
        'jumlah.min' => 'Jumlah minimal Rp 1.000',
        'keterangan.required' => 'Keterangan harus diisi',
        'keterangan.min' => 'Keterangan minimal 3 karakter',
        'keterangan.max' => 'Keterangan maksimal 255 karakter',
        'tanggal_transaksi.required' => 'Tanggal transaksi harus diisi',
        'tanggal_transaksi.date' => 'Format tanggal tidak valid',
    ];

    public function mount()
    {
        $this->tahun = $this->tahun ?? Carbon::now()->year;
        $this->bulan = $this->bulan ?? Carbon::now()->month;
        $this->tanggal_transaksi = Carbon::now()->format('Y-m-d');
        $this->jenis_kas_form = 'masuk';
    }

    // Computed properties untuk Statistik
    public function getTotalKasMasukProperty()
    {
        return ArusKas::where('jenis_kas', 'masuk')
            ->when($this->jenis_kas !== 'semua', function ($query) {
                return $query->where('jenis_kas', $this->jenis_kas);
            })
            ->sum('jumlah');
    }

    public function getTotalKasKeluarProperty()
    {
        return ArusKas::where('jenis_kas', 'keluar')
            ->when($this->jenis_kas !== 'semua', function ($query) {
                return $query->where('jenis_kas', $this->jenis_kas);
            })
            ->sum('jumlah');
    }

    public function getSaldoKasProperty()
    {
        return $this->total_kas_masuk - $this->total_kas_keluar;
    }

    public function getKasMasukBulanIniProperty()
    {
        return ArusKas::where('jenis_kas', 'masuk')
            ->whereMonth('tanggal_transaksi', $this->bulan)
            ->whereYear('tanggal_transaksi', $this->tahun)
            ->sum('jumlah');
    }

    public function getKasKeluarBulanIniProperty()
    {
        return ArusKas::where('jenis_kas', 'keluar')
            ->whereMonth('tanggal_transaksi', $this->bulan)
            ->whereYear('tanggal_transaksi', $this->tahun)
            ->sum('jumlah');
    }

    public function getTotalTransaksiBulanIniProperty()
    {
        return ArusKas::whereMonth('tanggal_transaksi', $this->bulan)
            ->whereYear('tanggal_transaksi', $this->tahun)
            ->count();
    }

    public function getDataArusKasProperty()
    {
        return ArusKas::with('user')
            ->when($this->jenis_kas !== 'semua', function ($query) {
                return $query->where('jenis_kas', $this->jenis_kas);
            })
            ->when($this->search, function ($query) {
                return $query->where('keterangan', 'like', '%' . $this->search . '%')
                    ->orWhere('jumlah', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function getGrafikBulananProperty()
    {
        $data = [];
        for ($i = 1; $i <= 12; $i++) {
            $kas_masuk = ArusKas::where('jenis_kas', 'masuk')
                ->whereMonth('tanggal_transaksi', $i)
                ->whereYear('tanggal_transaksi', $this->tahun)
                ->sum('jumlah');
            
            $kas_keluar = ArusKas::where('jenis_kas', 'keluar')
                ->whereMonth('tanggal_transaksi', $i)
                ->whereYear('tanggal_transaksi', $this->tahun)
                ->sum('jumlah');
            
            $data[] = [
                'bulan' => Carbon::create()->month($i)->format('M'),
                'kas_masuk' => $kas_masuk,
                'kas_keluar' => $kas_keluar,
                'saldo' => $kas_masuk - $kas_keluar
            ];
        }
        return $data;
    }

    public function getSaldoKasHarianProperty()
    {
        $today = Carbon::now()->format('Y-m-d');
        $masuk_hari_ini = ArusKas::where('jenis_kas', 'masuk')
            ->whereDate('tanggal_transaksi', $today)
            ->sum('jumlah');
        
        $keluar_hari_ini = ArusKas::where('jenis_kas', 'keluar')
            ->whereDate('tanggal_transaksi', $today)
            ->sum('jumlah');

        return [
            'masuk' => $masuk_hari_ini,
            'keluar' => $keluar_hari_ini,
            'saldo' => $masuk_hari_ini - $keluar_hari_ini
        ];
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
        $this->reset(['search', 'jenis_kas', 'sortBy', 'sortDirection']);
        $this->tahun = Carbon::now()->year;
        $this->bulan = Carbon::now()->month;
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingJenisKas()
    {
        $this->resetPage();
    }

    // Reset form
    public function resetForm()
    {
        $this->reset([
            'jenis_kas_form',
            'jumlah',
            'keterangan',
            'tanggal_transaksi'
        ]);
        $this->jenis_kas_form = 'masuk';
        $this->tanggal_transaksi = Carbon::now()->format('Y-m-d');
        $this->resetValidation();
    }

    // Simpan data
    public function simpan()
    {
        $this->validate();

        // Cek saldo untuk kas keluar
        if ($this->jenis_kas_form === 'keluar') {
            $saldo = $this->saldo_kas;
            if ($saldo < $this->jumlah) {
                $this->addError('jumlah', 'Saldo kas tidak mencukupi. Saldo saat ini: Rp ' . number_format($saldo, 0, ',', '.'));
                return;
            }
        }

        try {
            ArusKas::create([
                'jenis_kas' => $this->jenis_kas_form,
                'jumlah' => $this->jumlah,
                'keterangan' => $this->keterangan,
                'tanggal_transaksi' => $this->tanggal_transaksi,
                'user_id' => Auth::id(),
            ]);

            $message = $this->jenis_kas_form === 'masuk' ? 'Kas masuk berhasil dicatat' : 'Kas keluar berhasil dicatat';
            session()->flash('success', $message);

            $this->resetForm();
            $this->dispatch('refreshData');

        } catch (\Exception $e) {
            $this->addError('form', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('pages.anggota.arus-kas');
    }
}
?>
<div class="container mx-auto px-4 py-8">

    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight">Manajemen Arus Kas</h1>
            <p class="text-xs text-slate-400 mt-1">Kelola data pemasukan dan pengeluaran kas koperasi</p>
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

    <!-- Form Tambah Arus Kas (Hanya untuk Admin) -->
    @if($this->isAdmin())
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6 mb-6">
        <h2 class="text-lg font-black text-slate-800 mb-4">Tambah Transaksi Kas</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Jenis Kas -->
            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Jenis Kas <span class="text-red-500">*</span></label>
                <select wire:model="jenis_kas_form" class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                    <option value="masuk">Kas Masuk</option>
                    <option value="keluar">Kas Keluar</option>
                </select>
                @error('jenis_kas_form') <p class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Jumlah -->
            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Jumlah (Rp) <span class="text-red-500">*</span></label>
                <input type="number" wire:model="jumlah" placeholder="Masukkan nominal" min="1000" class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                @error('jumlah') <p class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Keterangan -->
            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Keterangan <span class="text-red-500">*</span></label>
                <input type="text" wire:model="keterangan" placeholder="Contoh: Pembayaran listrik" class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                @error('keterangan') <p class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Tanggal -->
            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tanggal Transaksi <span class="text-red-500">*</span></label>
                <input type="date" wire:model="tanggal_transaksi" class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                @error('tanggal_transaksi') <p class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Tombol -->
            <div class="flex items-end space-x-2 md:col-span-2 lg:col-span-4">
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
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
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
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Jenis Kas</label>
                <select wire:model.live="jenis_kas" class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                    <option value="semua">Semua</option>
                    <option value="masuk">Kas Masuk</option>
                    <option value="keluar">Kas Keluar</option>
                </select>
            </div>

            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Cari</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari keterangan atau nominal..." class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
            </div>
        </div>
    </div>

    <!-- Statistik Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Card Kas Masuk -->
        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-3xl border border-green-200/60 shadow-sm p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-[10px] uppercase tracking-wider text-green-600 font-bold">Total Kas Masuk</h3>
                    <p class="text-xl font-black text-green-700">Rp {{ number_format($this->total_kas_masuk, 0, ',', '.') }}</p>
                    <p class="text-[10px] text-green-500 mt-1">Bulan ini: Rp {{ number_format($this->kas_masuk_bulan_ini, 0, ',', '.') }}</p>
                </div>
                <div class="bg-green-500 rounded-full p-3">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Card Kas Keluar -->
        <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-3xl border border-red-200/60 shadow-sm p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-[10px] uppercase tracking-wider text-red-600 font-bold">Total Kas Keluar</h3>
                    <p class="text-xl font-black text-red-700">Rp {{ number_format($this->total_kas_keluar, 0, ',', '.') }}</p>
                    <p class="text-[10px] text-red-500 mt-1">Bulan ini: Rp {{ number_format($this->kas_keluar_bulan_ini, 0, ',', '.') }}</p>
                </div>
                <div class="bg-red-500 rounded-full p-3">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Card Saldo Kas -->
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-3xl border border-purple-200/60 shadow-sm p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-[10px] uppercase tracking-wider text-purple-600 font-bold">Saldo Kas</h3>
                    <p class="text-xl font-black {{ $this->saldo_kas >= 0 ? 'text-purple-700' : 'text-red-700' }}">
                        Rp {{ number_format($this->saldo_kas, 0, ',', '.') }}
                    </p>
                    <p class="text-[10px] text-purple-500 mt-1">Total keseluruhan</p>
                </div>
                <div class="bg-purple-500 rounded-full p-3">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Card Total Transaksi -->
        <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-3xl border border-orange-200/60 shadow-sm p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-[10px] uppercase tracking-wider text-orange-600 font-bold">Total Transaksi</h3>
                    <p class="text-xl font-black text-orange-700">{{ number_format($this->data_arus_kas->total()) }}</p>
                    <p class="text-[10px] text-orange-500 mt-1">Bulan ini: {{ number_format($this->total_transaksi_bulan_ini) }}</p>
                </div>
                <div class="bg-orange-500 rounded-full p-3">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Saldo Hari Ini -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-4 mb-6">
        <div class="grid grid-cols-3 gap-4">
            <div>
                <h4 class="text-[10px] uppercase tracking-wider text-slate-500 font-bold">Kas Masuk Hari Ini</h4>
                <p class="text-lg font-black text-green-600">Rp {{ number_format($this->saldo_kas_harian['masuk'], 0, ',', '.') }}</p>
                <p class="text-[10px] text-slate-400">{{ Carbon::now()->format('d M Y') }}</p>
            </div>
            <div>
                <h4 class="text-[10px] uppercase tracking-wider text-slate-500 font-bold">Kas Keluar Hari Ini</h4>
                <p class="text-lg font-black text-red-600">Rp {{ number_format($this->saldo_kas_harian['keluar'], 0, ',', '.') }}</p>
                <p class="text-[10px] text-slate-400">{{ Carbon::now()->format('d M Y') }}</p>
            </div>
            <div>
                <h4 class="text-[10px] uppercase tracking-wider text-slate-500 font-bold">Saldo Hari Ini</h4>
                <p class="text-lg font-black {{ $this->saldo_kas_harian['saldo'] >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                    Rp {{ number_format($this->saldo_kas_harian['saldo'], 0, ',', '.') }}
                </p>
                <p class="text-[10px] text-slate-400">{{ Carbon::now()->format('d M Y') }}</p>
            </div>
        </div>
    </div>

    <!-- Grafik Arus Kas -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-4 mb-6">
        <h2 class="text-lg font-black text-slate-800 mb-4">Grafik Arus Kas {{ $tahun }}</h2>
        <div class="h-72">
            <canvas id="kasChart" wire:ignore></canvas>
        </div>
    </div>

    <!-- Tabel Data Arus Kas -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm p-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-black text-slate-800">Data Arus Kas</h2>
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
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Jenis Kas</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider cursor-pointer hover:bg-slate-100 transition" wire:click="sortBy('keterangan')">
                            Keterangan
                            @if($sortBy === 'keterangan')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider cursor-pointer hover:bg-slate-100 transition" wire:click="sortBy('jumlah')">
                            Jumlah
                            @if($sortBy === 'jumlah')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider rounded-r-2xl">User</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    @forelse($this->data_arus_kas as $arus)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600">{{ Carbon\Carbon::parse($arus->tanggal_transaksi)->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-[10px] leading-5 font-bold rounded-full {{ $arus->jenis_kas === 'masuk' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ ucfirst($arus->jenis_kas) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600">{{ $arus->keterangan }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs font-bold {{ $arus->jenis_kas === 'masuk' ? 'text-green-600' : 'text-red-600' }}">
                                Rp {{ number_format($arus->jumlah, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600">{{ $arus->user->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-3 text-center text-xs text-slate-500">Tidak ada data arus kas</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            {{ $this->data_arus_kas->links() }}
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('livewire:init', function() {
            let chart = null;
            
            function initChart() {
                const ctx = document.getElementById('kasChart');
                if (!ctx) return;
                
                const data = @json($this->grafik_bulanan);
                
                if (chart) {
                    chart.destroy();
                }
                
                chart = new Chart(ctx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: data.map(item => item.bulan),
                        datasets: [
                            {
                                label: 'Kas Masuk',
                                data: data.map(item => item.kas_masuk),
                                backgroundColor: 'rgba(34, 197, 94, 0.6)',
                                borderColor: 'rgb(34, 197, 94)',
                                borderWidth: 2,
                                borderRadius: 4
                            },
                            {
                                label: 'Kas Keluar',
                                data: data.map(item => item.kas_keluar),
                                backgroundColor: 'rgba(239, 68, 68, 0.6)',
                                borderColor: 'rgb(239, 68, 68)',
                                borderWidth: 2,
                                borderRadius: 4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    font: {
                                        size: 11,
                                        weight: 'bold'
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'Rp ' + value.toLocaleString('id-ID');
                                    }
                                }
                            }
                        }
                    }
                });
            }
            
            // Init chart
            initChart();
            
            // Reinit chart when data changes
            Livewire.on('render', function() {
                setTimeout(initChart, 100);
            });
        });
    </script>
    @endpush
</div>
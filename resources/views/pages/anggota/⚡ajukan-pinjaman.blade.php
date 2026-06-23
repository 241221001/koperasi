<?php
namespace App\views\Pages\Anggota;

use Livewire\Component;
use App\Models\Pinjaman;
use App\Models\Anggota;
use App\Models\KategoriPinjaman;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public $kategoriPinjaman = [];

    public $kategori_pinjaman_id;
    public $jumlah_pokok;
    public $tenor_bulan;

    public $kategori;
    public $tenor_maksimal_bulan = 0;
    public $persentase_bunga = 0;

    public $total_bunga = 0;
    public $total_pinjaman = 0;
    public $cicilan_per_bulan = 0;

    public function mount()
    {
        $this->kategoriPinjaman = KategoriPinjaman::all();
    }

    public function updatedKategoriPinjamanId($value)
    {
        $this->kategori = KategoriPinjaman::find($value);

        $this->reset([
            'tenor_bulan',
            'total_bunga',
            'total_pinjaman',
            'cicilan_per_bulan',
        ]);

        if ($this->kategori) {
            $this->tenor_maksimal_bulan = $this->kategori->tenor_maksimal_bulan;
            $this->persentase_bunga = $this->kategori->persentase_bunga;
        }
    }

    public function updated($property)
    {
        if (
            in_array($property, [
                'kategori_pinjaman_id',
                'jumlah_pokok',
                'tenor_bulan'
            ])
        ) {
            $this->hitung();
        }
    }

    public function hitung()
    {
        if (
            !$this->kategori_pinjaman_id ||
            !$this->jumlah_pokok ||
            !$this->tenor_bulan
        ) {
            return;
        }

        $kategori = KategoriPinjaman::find($this->kategori_pinjaman_id);

        if (!$kategori) return;

        if ($this->tenor_bulan > $kategori->tenor_maksimal_bulan) {
            $this->addError(
                'tenor_bulan',
                'Tenor melebihi batas maksimal ' . $kategori->tenor_maksimal_bulan . ' bulan'
            );
            return;
        }

        $pokok = (float) $this->jumlah_pokok;
        $tenor = (int) $this->tenor_bulan;
        $bunga = (float) $kategori->persentase_bunga;

        $this->total_bunga = $pokok * ($bunga / 100) * $tenor;
        $this->total_pinjaman = $pokok + $this->total_bunga;
        $this->cicilan_per_bulan = $this->total_pinjaman / $tenor;
    }

    public function simpan()
    {
        $this->validate([
            'kategori_pinjaman_id' => 'required|exists:kategori_pinjaman,id',
            'jumlah_pokok' => 'required|numeric|min:1000',
            'tenor_bulan' => 'required|numeric|min:1',
        ]);

        $kategori = KategoriPinjaman::findOrFail($this->kategori_pinjaman_id);

        if ($this->tenor_bulan > $kategori->tenor_maksimal_bulan) {
            $this->addError(
                'tenor_bulan',
                'Tenor tidak boleh lebih dari ' . $kategori->tenor_maksimal_bulan
            );
            return;
        }

        $anggota = Anggota::where('user_id', Auth::id())->first();

        $pokok = (float) $this->jumlah_pokok;
        $tenor = (int) $this->tenor_bulan;
        $bunga = (float) $kategori->persentase_bunga;

        $totalBunga = $pokok * ($bunga / 100) * $tenor;

        Pinjaman::create([
            'anggota_id' => $anggota->id,
            'kategori_pinjaman_id' => $kategori->id,
            'jumlah_pokok' => $pokok,
            'total_pinjaman' => $pokok + $totalBunga,
            'tenor_bulan' => $tenor,
            'status' => 'diajukan',
        ]);

        $this->reset([
            'kategori_pinjaman_id',
            'jumlah_pokok',
            'tenor_bulan',
            'tenor_maksimal_bulan',
            'persentase_bunga',
            'total_bunga',
            'total_pinjaman',
            'cicilan_per_bulan',
        ]);

        session()->flash('success', 'Pinjaman berhasil diajukan.');
    }

    
}
?>
<div class="max-w-3xl mx-auto bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6 space-y-3 margin-top-50px">


<!-- Header -->
 <div>
        <a href="{{ route('anggota.dashboard') }}"
           class="inline-block px-4 py-2 bg-red-600 text-white rounded hover:bg-gray-700">
            ← Kembali
        </a>
</div>

<div class="space-y-1 margin-50px">
    <h2 class="text-xl font-black text-slate-800 tracking-tight">
        Ajukan Pinjaman
    </h2>
    <p class="text-xs text-slate-400">
        Pilih kategori dan tentukan tenor sesuai ketentuan koperasi
    </p>
</div>

<!-- Success Message -->
@if (session()->has('success'))
    <div class="bg-emerald-50 border border-emerald-100 text-emerald-700 px-4 py-3 rounded-2xl text-xs font-bold">
        {{ session('success') }}
    </div>
@endif

<!-- Kategori -->
<div class="space-y-2">
    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">
        Kategori Pinjaman
    </label>

    <select
        wire:model.live="kategori_pinjaman_id"
        class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition"
    >
        <option value="">-- pilih kategori --</option>
        @foreach ($kategoriPinjaman as $k)
            <option value="{{ $k->id }}">
                {{ $k->nama_pinjaman }}
            </option>
        @endforeach
    </select>
</div>

<!-- Jumlah Pinjaman -->
<div class="space-y-2">
    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">
        Jumlah Pinjaman
    </label>

    <input
        type="number"
        wire:model.live="jumlah_pokok"
        placeholder="Masukkan nominal pinjaman"
        class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition"
    >
</div>

<!-- Tenor -->
<div class="space-y-2">
    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">
        Tenor (maks: {{ $tenor_maksimal_bulan }} bulan)
    </label>

    <select
        wire:model.live="tenor_bulan"
        class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition"
    >
        <option value="">-- pilih tenor --</option>

        @for ($i = 1; $i <= $tenor_maksimal_bulan; $i++)
            <option value="{{ $i }}">{{ $i }} bulan</option>
        @endfor
    </select>

    @error('tenor_bulan')
        <p class="text-xs text-rose-500 font-semibold">
            {{ $message }}
        </p>
    @enderror
</div>

<!-- Divider -->
<div class="border-t border-slate-100"></div>

<!-- Summary -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-3">

    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
        <p class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">
            Total Bunga
        </p>
        <p class="text-lg font-black text-slate-800">
            Rp {{ number_format($total_bunga, 0, ',', '.') }}
        </p>
    </div>

    <div class="bg-indigo-50 rounded-2xl p-4 border border-indigo-100">
        <p class="text-[10px] uppercase tracking-wider text-indigo-400 font-bold">
            Total Pinjaman
        </p>
        <p class="text-lg font-black text-indigo-700">
            Rp {{ number_format($total_pinjaman, 0, ',', '.') }}
        </p>
    </div>

    <div class="bg-emerald-50 rounded-2xl p-4 border border-emerald-100">
        <p class="text-[10px] uppercase tracking-wider text-emerald-400 font-bold">
            Cicilan / Bulan
        </p>
        <p class="text-lg font-black text-emerald-700">
            Rp {{ number_format($cicilan_per_bulan, 0, ',', '.') }}
        </p>
    </div>

</div>

<!-- Button -->
<button
    wire:click="simpan"
    class="w-full bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white font-bold text-sm py-3.5 rounded-2xl shadow-lg shadow-indigo-500/20 transition active:scale-[0.99]"
>
    Ajukan Pinjaman
</button>


</div>

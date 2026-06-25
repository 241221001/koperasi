<?php
// app/Livewire/TarikTunai.php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Simpanan;
use App\Models\Anggota;
use App\Models\KategoriSimpanan;
use Illuminate\Support\Facades\DB;

class TarikTunai extends Component
{
    use WithPagination;

    public $transaksiId;
    public $anggota_id;
    public $kategori_simpanan_id;
    public $jumlah;
    public $tanggal_transaksi;
    public $isEdit = false;
    public $search = '';
    public $filterTanggal = '';
    public $totalTarik;

    public function mount()
    {
        $this->tanggal_transaksi = date('Y-m-d');
        $this->hitungTotalTarik();
    }

    public function resetForm()
    {
        $this->transaksiId = null;
        $this->anggota_id = null;
        $this->kategori_simpanan_id = null;
        $this->jumlah = null;
        $this->tanggal_transaksi = date('Y-m-d');
        $this->isEdit = false;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function simpanTarik()
    {
        $this->validate([
            'anggota_id' => 'required|exists:anggota,id',
            'kategori_simpanan_id' => 'required|exists:kategori_simpanan,id',
            'jumlah' => 'required|numeric|min:1000',
            'tanggal_transaksi' => 'required|date|before_or_equal:today',
        ]);

        $saldo = $this->getSaldoAnggota($this->anggota_id, $this->kategori_simpanan_id);
        if ($saldo < $this->jumlah) {
            $this->addError('jumlah', 'Saldo tidak mencukupi! Saldo: Rp ' . number_format($saldo, 0, ',', '.'));
            return;
        }

        DB::transaction(function () {
            if ($this->isEdit) {
                Simpanan::find($this->transaksiId)->update([
                    'anggota_id' => $this->anggota_id,
                    'kategori_simpanan_id' => $this->kategori_simpanan_id,
                    'jenis_transaksi' => 'tarik',
                    'jumlah' => $this->jumlah,
                    'tanggal_transaksi' => $this->tanggal_transaksi,
                ]);
                session()->flash('sukses_tarik', 'Penarikan berhasil diperbarui!');
            } else {
                Simpanan::create([
                    'anggota_id' => $this->anggota_id,
                    'kategori_simpanan_id' => $this->kategori_simpanan_id,
                    'jenis_transaksi' => 'tarik',
                    'jumlah' => $this->jumlah,
                    'tanggal_transaksi' => $this->tanggal_transaksi,
                ]);
                session()->flash('sukses_tarik', 'Penarikan berhasil ditambahkan!');
            }
        });

        $this->resetForm();
        $this->hitungTotalTarik();
    }

    public function editTarik($id)
    {
        $transaksi = Simpanan::where('jenis_transaksi', 'tarik')->findOrFail($id);
        $this->transaksiId = $transaksi->id;
        $this->anggota_id = $transaksi->anggota_id;
        $this->kategori_simpanan_id = $transaksi->kategori_simpanan_id;
        $this->jumlah = $transaksi->jumlah;
        $this->tanggal_transaksi = $transaksi->tanggal_transaksi->format('Y-m-d');
        $this->isEdit = true;
    }

    public function hapusTarik($id)
    {
        DB::transaction(function () use ($id) {
            Simpanan::where('jenis_transaksi', 'tarik')->findOrFail($id)->delete();
        });
        session()->flash('sukses_tarik', 'Penarikan berhasil dihapus!');
        $this->hitungTotalTarik();
    }

    public function hitungTotalTarik()
    {
        $this->totalTarik = Simpanan::where('jenis_transaksi', 'tarik')->sum('jumlah');
    }

    private function getSaldoAnggota($anggotaId, $kategoriId)
    {
        $totalSetor = Simpanan::where('anggota_id', $anggotaId)
            ->where('kategori_simpanan_id', $kategoriId)
            ->where('jenis_transaksi', 'setor')
            ->sum('jumlah');

        $totalTarik = Simpanan::where('anggota_id', $anggotaId)
            ->where('kategori_simpanan_id', $kategoriId)
            ->where('jenis_transaksi', 'tarik')
            ->sum('jumlah');

        return $totalSetor - $totalTarik;
    }

    public function getPenarikanProperty()
    {
        $query = Simpanan::with(['anggota', 'kategoriSimpanan'])
            ->where('jenis_transaksi', 'tarik');

        if ($this->search) {
            $query->whereHas('anggota', function ($q) {
                $q->where('nama', 'like', '%' . $this->search . '%')
                    ->orWhere('nik', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterTanggal) {
            $query->whereDate('tanggal_transaksi', $this->filterTanggal);
        }

        return $query->orderBy('created_at', 'desc')->paginate(10);
    }

    public function getAnggotaListProperty()
    {
        return Anggota::orderBy('nama')->get();
    }

    public function getKategoriListProperty()
    {
        return KategoriSimpanan::orderBy('nama_simpanan')->get();
    }

    public function render()
    {
        return <<<'HTML'
        <div class="max-w-7xl mx-auto bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <a href="{{ route('anggota.dashboard') }}" wire:navigate class="inline-block px-4 py-2 bg-slate-600 text-white rounded-xl hover:bg-slate-700 text-sm font-bold transition">← Kembali</a>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Penarikan</p>
                    <p class="text-2xl font-black text-amber-600">Rp {{ number_format($this->totalTarik, 0, ',', '.') }}</p>
                </div>
            </div>

            <div class="space-y-1">
                <h2 class="text-xl font-black text-slate-800 tracking-tight">💳 Tarik Tunai</h2>
                <p class="text-xs text-slate-400">Lakukan penarikan tunai anggota</p>
            </div>

            @if (session()->has('sukses_tarik'))
                <div class="bg-amber-50 border border-amber-100 text-amber-700 px-4 py-3 rounded-2xl text-xs font-bold">🎉 {{ session('sukses_tarik') }}</div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-1 space-y-4">
                    <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200/60">
                        <h3 class="font-bold text-slate-700 text-sm mb-4">{{ $this->isEdit ? '✏️ Edit Penarikan' : '📤 Form Penarikan' }}</h3>
                        <form wire:submit.prevent="simpanTarik" class="space-y-4">
                            <div>
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Anggota</label>
                                <select wire:model="anggota_id" class="w-full mt-1 px-4 py-3 rounded-2xl bg-white border border-slate-200 text-slate-700 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition">
                                    <option value="">-- pilih anggota --</option>
                                    @foreach($this->anggotaList as $anggota)
                                        <option value="{{ $anggota->id }}">{{ $anggota->nama }} - {{ $anggota->nik }}</option>
                                    @endforeach
                                </select>
                                @error('anggota_id') <p class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Kategori</label>
                                <select wire:model="kategori_simpanan_id" class="w-full mt-1 px-4 py-3 rounded-2xl bg-white border border-slate-200 text-slate-700 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition">
                                    <option value="">-- pilih kategori --</option>
                                    @foreach($this->kategoriList as $kategori)
                                        <option value="{{ $kategori->id }}">{{ $kategori->nama_simpanan }}</option>
                                    @endforeach
                                </select>
                                @error('kategori_simpanan_id') <p class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Jumlah (Rp)</label>
                                <input type="number" wire:model="jumlah" min="1000" step="1000" placeholder="Masukkan nominal" class="w-full mt-1 px-4 py-3 rounded-2xl bg-white border border-slate-200 text-slate-700 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition">
                                @error('jumlah') <p class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</p> @enderror
                                <p class="text-[10px] text-slate-400 mt-1">* Pastikan saldo mencukupi</p>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tanggal</label>
                                <input type="date" wire:model="tanggal_transaksi" class="w-full mt-1 px-4 py-3 rounded-2xl bg-white border border-slate-200 text-slate-700 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition">
                                @error('tanggal_transaksi') <p class="text-xs text-rose-500 font-semibold mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex gap-2 pt-2">
                                <button type="submit" class="flex-1 bg-amber-600 hover:bg-amber-700 text-white font-bold text-sm py-3.5 rounded-2xl shadow-lg shadow-amber-500/20 transition active:scale-[0.99]">
                                    {{ $this->isEdit ? 'Update' : 'Tarik Sekarang' }}
                                </button>
                                @if($this->isEdit)
                                    <button type="button" wire:click="resetForm" class="px-4 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-sm py-3.5 rounded-2xl transition">Batal</button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>

                <div class="lg:col-span-2 space-y-4">
                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/60">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Cari</label>
                                <input type="text" wire:model.live="search" placeholder="Nama atau NIK..." class="w-full mt-1 px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition">
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Filter Tanggal</label>
                                <input type="date" wire:model.live="filterTanggal" class="w-full mt-1 px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition">
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200/60 overflow-hidden">
                        <div class="px-5 py-3 border-b border-slate-200/60 bg-slate-50/50">
                            <h3 class="font-bold text-slate-700 text-sm">📋 Riwayat Penarikan</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-sm">
                                <thead>
                                    <tr class="bg-slate-50/80 text-xs text-slate-400 font-bold uppercase tracking-wider border-b">
                                        <th class="px-4 py-3 w-12 text-center">No</th>
                                        <th class="px-4 py-3">Anggota</th>
                                        <th class="px-4 py-3">Kategori</th>
                                        <th class="px-4 py-3 text-right">Jumlah</th>
                                        <th class="px-4 py-3">Tanggal</th>
                                        <th class="px-4 py-3 w-32 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse($this->penarikan as $index => $item)
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-4 py-3 text-center font-mono font-bold text-slate-400 text-xs">{{ ($this->penarikan->currentPage() - 1) * $this->penarikan->perPage() + $index + 1 }}</td>
                                            <td class="px-4 py-3">
                                                <div class="font-bold text-slate-800 text-sm">{{ $item->anggota->nama }}</div>
                                                <div class="text-xs text-slate-400">{{ $item->anggota->nik }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-slate-600 text-xs">{{ $item->kategoriSimpanan->nama_simpanan }}</td>
                                            <td class="px-4 py-3 text-right font-mono font-bold text-amber-600 text-sm">Rp {{ number_format($item->jumlah, 0, ',', '.') }}</td>
                                            <td class="px-4 py-3 text-slate-500 text-xs">{{ $item->tanggal_transaksi->format('d/m/Y') }}</td>
                                            <td class="px-4 py-3">
                                                <div class="flex justify-center gap-1.5">
                                                    <button wire:click="editTarik({{ $item->id }})" class="text-amber-600 bg-amber-50 hover:bg-amber-100 px-2.5 py-1.5 rounded-lg text-xs font-bold transition">✏️ Edit</button>
                                                    <button onclick="confirm('Hapus penarikan ini?') || event.stopImmediatePropagation()" wire:click="hapusTarik({{ $item->id }})" class="text-rose-600 bg-rose-50 hover:bg-rose-100 px-2.5 py-1.5 rounded-lg text-xs font-bold transition">🗑️ Hapus</button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center py-10 text-slate-400 text-xs font-semibold">📂 Belum ada data penarikan.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="px-5 py-3 border-t border-slate-200/60 bg-slate-50/30">{{ $this->penarikan->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }
}
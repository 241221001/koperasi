<?php

use Livewire\Component;
use App\Models\Pinjaman;

new class extends Component
{
    // Properti untuk filter status di dashboard admin
    public $filterStatus = 'diajukan'; 

    public function setujui($id)
    {
        $pinjaman = Pinjaman::findOrFail($id);
        $namaAnggota = $pinjaman->anggota->nama ?? 'Anggota';

        $pinjaman->update([
            'status' => 'disetujui'
        ]);

        session()->flash('success', 'Pinjaman atas nama ' . $namaAnggota . ' berhasil disetujui.');
    }

    public function tolak($id)
    {
        $pinjaman = Pinjaman::findOrFail($id);
        $namaAnggota = $pinjaman->anggota->nama ?? 'Anggota';

        $pinjaman->update([
            'status' => 'ditolak'
        ]);

        session()->flash('error', 'Pinjaman atas nama ' . $namaAnggota . ' telah ditolak.');
    }

    // Menggunakan gaya Computed Property sesuai contohmu
    public function getDaftarPinjamanProperty()
    {
        return Pinjaman::with(['anggota.pengguna', 'kategoriPinjaman'])
            ->when($this->filterStatus, function($query) {
                return $query->where('status', $this->filterStatus);
            })
            ->latest()
            ->get();
    }
}; 
?>

<div class="max-w-6xl mx-auto bg-white rounded-3xl border border-slate-200/60 shadow-sm p-6 space-y-6 my-10">
    
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-100 pb-5">
        <div>
            <h2 class="text-xl font-black text-slate-800 tracking-tight">
                Persetujuan Pinjaman Anggota
            </h2>
            <p class="text-xs text-slate-400">
                Kelola, setujui, atau tolak pengajuan pinjaman dari anggota koperasi
            </p>
        </div>
        
        <div class="flex bg-slate-100 p-1 rounded-xl self-start md:self-center">
            <button wire:click="$set('filterStatus', 'diajukan')" 
                class="px-4 py-2 text-xs font-bold rounded-lg transition {{ $filterStatus == 'diajukan' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                Diajukan
            </button>
            <button wire:click="$set('filterStatus', 'disetujui')" 
                class="px-4 py-2 text-xs font-bold rounded-lg transition {{ $filterStatus == 'disetujui' ? 'bg-white text-emerald-600 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                Disetujui
            </button>
            <button wire:click="$set('filterStatus', 'ditolak')" 
                class="px-4 py-2 text-xs font-bold rounded-lg transition {{ $filterStatus == 'ditolak' ? 'bg-white text-rose-600 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                Ditolak
            </button>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="bg-emerald-50 border border-emerald-100 text-emerald-700 px-4 py-3 rounded-2xl text-xs font-bold">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-rose-50 border border-rose-100 text-rose-700 px-4 py-3 rounded-2xl text-xs font-bold">
            {{ session('error') }}
        </div>
    @endif

    <div class="overflow-x-auto rounded-2xl border border-slate-100">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100 text-[10px] uppercase tracking-wider text-slate-400 font-bold">
                    <th class="p-4">Nama Anggota</th>
                    <th class="p-4">Kategori &amp; Tenor</th>
                    <th class="p-4">Jumlah Pokok</th>
                    <th class="p-4">Total + Bunga</th>
                    <th class="p-4 text-center">Status</th>
                    @if($filterStatus == 'diajukan')
                        <th class="p-4 text-right">Tindakan</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm text-slate-700 font-medium">
                @forelse ($this->daftarPinjaman as $p)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="p-4">
                            <div class="font-bold text-slate-800">{{ $p->anggota->nama ?? 'N/A' }}</div>
                            <div class="text-[11px] text-slate-400">ID Anggota: #{{ $p->anggota_id }}</div>
                        </td>
                        
                        <td class="p-4">
                            <span class="px-2 py-0.5 text-[11px] font-semibold bg-slate-100 text-slate-600 rounded-md">
                                {{ $p->kategoriPinjaman->nama_pinjaman ?? 'Kategori Terhapus' }}
                            </span>
                            <div class="text-xs text-slate-500 mt-1">{{ $p->tenor_bulan }} Bulan</div>
                        </td>
                        
                        <td class="p-4 font-semibold text-slate-600">
                            Rp {{ number_format($p->jumlah_pokok, 0, ',', '.') }}
                        </td>
                        
                        <td class="p-4 font-bold text-indigo-600">
                            Rp {{ number_format($p->total_pinjaman, 0, ',', '.') }}
                            <div class="text-[10px] text-slate-400 font-normal">
                                Cicilan: Rp {{ number_format($p->total_pinjaman / $p->tenor_bulan, 0, ',', '.') }}/bln
                            </div>
                        </td>
                        
                        <td class="p-4 text-center">
                            @if($p->status == 'diajukan')
                                <span class="px-2.5 py-1 text-xs font-bold bg-amber-50 text-amber-600 border border-amber-100 rounded-full">Diajukan</span>
                            @elseif($p->status == 'disetujui')
                                <span class="px-2.5 py-1 text-xs font-bold bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-full">Disetujui</span>
                            @else
                                <span class="px-2.5 py-1 text-xs font-bold bg-rose-50 text-rose-600 border border-rose-100 rounded-full">Ditolak</span>
                            @endif
                        </td>

                        @if($filterStatus == 'diajukan')
                            <td class="p-4 text-right space-x-1 whitespace-nowrap">
                                <button 
                                    wire:click="setujui({{ $p->id }})"
                                    wire:confirm="Apakah Anda yakin ingin menyetujui pinjaman ini?"
                                    class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm shadow-emerald-600/10 transition"
                                >
                                    Setujui
                                </button>
                                <button 
                                    wire:click="tolak({{ $p->id }})"
                                    wire:confirm="Apakah Anda yakin ingin menolak pinjaman ini?"
                                    class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-sm shadow-rose-600/10 transition"
                                >
                                    Tolak
                                </button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-xs text-slate-400 font-bold uppercase tracking-wider">
                            Tidak ada pengajuan pinjaman dengan status ini
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
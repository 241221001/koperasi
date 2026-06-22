<?php

use Livewire\Component;
use App\Models\KategoriPinjaman as ModelPinjaman;

new class extends Component
{
    public $kategoriId;
    public $nama_pinjaman; 
    public $bunga_persen = 0; 
    public $tenor_maksimal = 12;
    public $isEdit = false;

    public function resetForm() {
        $this->nama_pinjaman = '';
        $this->bunga_persen = 0;
        $this->tenor_maksimal = 12;
        $this->kategoriId = null;
        $this->isEdit = false;
    }

    public function simpanKategori() {
        $this->validate([
            'nama_pinjaman' => 'required|string|min:3',
            'bunga_persen' => 'required|numeric|min:0|max:100',
            'tenor_maksimal' => 'required|integer|min:1',
        ]);

        if ($this->isEdit) {
            ModelPinjaman::find($this->kategoriId)->update([
                'nama_pinjaman' => $this->nama_pinjaman,
                'persentase_bunga' => $this->bunga_persen, 
                'tenor_maksimal_bulan' => $this->tenor_maksimal,
            ]);
            session()->flash('sukses_pinjaman', 'Kategori pinjaman berhasil diperbarui!');
        } else {
            ModelPinjaman::create([
                'nama_pinjaman' => $this->nama_pinjaman,
                'persentase_bunga' => $this->bunga_persen,
                'tenor_maksimal_bulan' => $this->tenor_maksimal,
            ]);
            session()->flash('sukses_pinjaman', 'Kategori pinjaman baru berhasil ditambahkan!');
        }
        $this->resetForm();
    }

    public function editKategori($id) {
        $kategori = ModelPinjaman::findOrFail($id);
        $this->kategoriId = $kategori->id;
        $this->nama_pinjaman = $kategori->nama_pinjaman;
        $this->bunga_persen = $kategori->persentase_bunga; 
        $this->tenor_maksimal = $kategori->tenor_maksimal_bulan;
        $this->isEdit = true;
    }

    public function hapusKategori($id) {
        ModelPinjaman::destroy($id);
        session()->flash('sukses_pinjaman', 'Kategori pinjaman berhasil dihapus!');
        $this->resetForm();
    }

    public function getKategoriProperty()
    {
        return ModelPinjaman::latest()->get();
    }
}; 
?>
<div class="space-y-6">
    
    @if (session()->has('sukses_pinjaman'))
        <div class="p-4 text-sm text-green-700 bg-green-50 rounded-xl font-medium border border-green-100 shadow-sm transition-all duration-300">
            ✨ {{ session('sukses_pinjaman') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        
        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm sticky top-6">
            <div class="mb-4">
                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wide">
                    {{ $this->isEdit ? '✏️ Edit Kategori' : '➕ Tambah Kategori' }}
                </h3>
                <p class="text-xs text-gray-400 font-medium">Kelola parameter produk pinjaman.</p>
            </div>
            
            <form wire:submit="simpanKategori" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1 uppercase tracking-wider">Nama Pinjaman</label>
                    <input type="text" wire:model="nama_pinjaman" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm placeholder-gray-400 font-medium transition-all" placeholder="Contoh: Pinjaman Darurat">
                    @error('nama_pinjaman') <span class="text-xs text-rose-500 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1 uppercase tracking-wider">Bunga (% / Bulan)</label>
                    <input type="number" step="0.01" wire:model="bunga_persen" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm font-medium transition-all">
                    @error('bunga_persen') <span class="text-xs text-rose-500 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1 uppercase tracking-wider">Tenor Maksimal (Bulan)</label>
                    <input type="number" wire:model="tenor_maksimal" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm font-medium transition-all">
                    @error('tenor_maksimal') <span class="text-xs text-rose-500 mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div class="flex flex-col gap-2 pt-2">
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded-lg text-sm font-bold shadow-sm shadow-indigo-100 transition-all duration-200 active:scale-95">
                        {{ $this->isEdit ? '💾 Perbarui Data' : '➕ Simpan Kategori' }}
                    </button>
                    @if($this->isEdit)
                        <button type="button" wire:click="resetForm" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-600 py-2 rounded-lg text-sm font-bold transition-all duration-200 active:scale-95 text-center">Batal</button>
                    @endif
                </div>
            </form>
        </div>

        <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm lg:col-span-2">
            <div class="mb-4">
                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wide">📋 Daftar Kategori Pinjaman</h3>
                <p class="text-xs text-gray-400 font-medium">Seluruh skema pinjaman aktif di koperasi.</p>
            </div>

            <div class="overflow-x-auto rounded-lg border border-gray-100">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead>
                        <tr class="bg-gray-50/70 border-b border-gray-100 text-gray-400 text-xs font-bold uppercase tracking-wider">
                            <th class="p-4 text-center w-16">ID</th>
                            <th class="p-4">Jenis Pinjaman</th>
                            <th class="p-4">Bunga</th>
                            <th class="p-4">Tenor Maks</th>
                            <th class="p-4 text-center w-36">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($this->kategori as $item)
                            <tr class="hover:bg-gray-50/50 transition-colors duration-150">
                                <td class="p-4 text-center font-mono font-bold text-gray-400">{{ $item->id }}</td>
                                <td class="p-4 font-bold text-gray-800">{{ $item->nama_pinjaman }}</td>
                                <td class="p-4">
                                    <span class="px-2 py-1 text-xs font-mono font-bold text-emerald-700 bg-emerald-50 rounded-md">
                                        {{ $item->persentase_bunga }}%
                                    </span>
                                </td>
                                <td class="p-4 font-semibold text-gray-600">{{ $item->tenor_maksimal_bulan }} Bln</td>
                                <td class="p-4 flex justify-center gap-1.5">
                                    <button wire:click="editKategori({{ $item->id }})" class="text-indigo-600 bg-indigo-50 hover:bg-indigo-100 px-2 py-1 rounded-md text-xs font-bold transition-all duration-150 active:scale-95">✏️ Edit</button>
                                    <button onclick="confirm('Hapus kategori pinjaman ini?') || event.stopImmediatePropagation()" wire:click="hapusKategori({{ $item->id }})" class="text-rose-600 bg-rose-50 hover:bg-rose-100 px-2 py-1 rounded-md text-xs font-bold transition-all duration-150 active:scale-95">🗑️ Hapus</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-12 text-gray-400 text-xs font-semibold">
                                    📂 Belum ada data kategori pinjaman.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
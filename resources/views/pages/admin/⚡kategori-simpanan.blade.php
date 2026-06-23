<?php

use Livewire\Component;
use App\Models\KategoriSimpanan as ModelSimpanan;

new class extends Component
{
    public $kategoriId;
    public $nama_simpanan; 
    public $nominal_default = 0; 
    public $isEdit = false;

    public function resetForm() {
        $this->nama_simpanan = '';
        $this->nominal_default = 0;
        $this->kategoriId = null;
        $this->isEdit = false;
    }

    public function simpanKategori() {
        $this->validate([
            'nama_simpanan' => 'required|string|min:3|unique:kategori_simpanan,nama_simpanan,' . $this->kategoriId,
            'nominal_default' => 'required|numeric|min:0',
        ]);

        if ($this->isEdit) {
            ModelSimpanan::find($this->kategoriId)->update([
                'nama_simpanan' => $this->nama_simpanan,
                'nominal_default' => $this->nominal_default,
            ]);
            session()->flash('sukses_simpanan', 'Kategori simpanan berhasil diperbarui!');
        } else {
            ModelSimpanan::create([
                'nama_simpanan' => $this->nama_simpanan,
                'nominal_default' => $this->nominal_default,
            ]);
            session()->flash('sukses_simpanan', 'Kategori simpanan baru berhasil ditambahkan!');
        }
        $this->resetForm();
    }

    public function editKategori($id) {
        $kategori = ModelSimpanan::findOrFail($id);
        $this->kategoriId = $kategori->id;
        $this->nama_simpanan = $kategori->nama_simpanan;
        $this->nominal_default = $kategori->nominal_default;
        $this->isEdit = true;
    }

    public function hapusKategori($id) {
        ModelSimpanan::destroy($id);
        session()->flash('sukses_simpanan', 'Kategori simpanan berhasil dihapus!');
        $this->resetForm();
    }

    
    public function getKategoriProperty()
    {
        return ModelSimpanan::latest()->get();
    }
}; 
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm h-fit space-y-4">
        <h3 class="font-bold text-gray-800 text-base pb-2 border-b">
            {{ $isEdit ? '✏️ Edit Kategori Simpanan' : '✨ Tambah Kategori Simpanan' }}
        </h3>
        
        @if (session()->has('sukses_simpanan'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-600 px-3 py-2 rounded-xl text-xs font-bold shadow-sm">
                🎉 {{ session('sukses_simpanan') }}
            </div>
        @endif

        <form wire:submit.prevent="simpanKategori" class="space-y-4">
            <div>
                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Nama Jenis Simpanan</label>
                <input type="text" wire:model="nama_simpanan" placeholder="Contoh: Pokok, Wajib, Sukarela" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 text-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm shadow-sm">
                @error('nama_simpanan') <span class="text-rose-500 text-xs mt-1 block font-medium">⚠️ {{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Nominal Default (Rp)</label>
                <input type="number" wire:model="nominal_default" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 text-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm shadow-sm">
                @error('nominal_default') <span class="text-rose-500 text-xs mt-1 block font-medium">⚠️ {{ $message }}</span> @enderror
            </div>

            <div class="flex gap-2 pt-2">
                <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 rounded-xl font-bold text-xs tracking-wide shadow-md shadow-indigo-500/10 transition">
                    {{ $isEdit ? 'Simpan' : 'Tambahkan' }}
                </button>
                @if($isEdit)
                    <button type="button" wire:click="resetForm" class="bg-gray-100 text-gray-600 px-4 py-2.5 rounded-xl font-bold text-xs hover:bg-gray-200 transition">
                        Batal
                    </button>
                @endif
            </div>
        </form>
    </div>

    <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b bg-gray-50/50">
            <h3 class="font-bold text-gray-700 text-sm">Daftar Master Kategori Simpanan</h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm text-gray-600">
                <thead>
                    <tr class="bg-gray-50 text-xs text-gray-400 font-bold uppercase tracking-wider border-b">
                        <th class="p-4 w-12 text-center">ID</th>
                        <th class="p-4">Nama Simpanan</th>
                        <th class="p-4">Nominal Default</th>
                        <th class="p-4 w-32 text-center">Aksi</th>
                    </tr>
                </thead>
<tbody class="divide-y divide-gray-100">
    @forelse($this->kategori as $item)
        <tr class="hover:bg-gray-50/50 transition-colors">
            <td class="p-4 text-center font-mono font-bold text-gray-400">{{ $item->id }}</td>
            <td class="p-4 font-bold text-gray-800">{{ $item->nama_simpanan }}</td>
            <td class="p-4 font-mono text-xs text-indigo-600 font-bold">
                Rp {{ number_format($item->nominal_default, 0, ',', '.') }}
            </td>
            <td class="p-4 flex justify-center gap-2">
                <button wire:click="editKategori({{ $item->id }})" class="text-indigo-600 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1.5 rounded-lg text-xs font-bold transition">✏️ Edit</button>
                <button onclick="confirm('Hapus kategori simpanan ini?') || event.stopImmediatePropagation()" wire:click="hapusKategori({{ $item->id }})" class="text-rose-600 bg-rose-50 hover:bg-rose-100 px-2.5 py-1.5 rounded-lg text-xs font-bold transition">🗑️ Hapus</button>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="4" class="text-center py-10 text-gray-400 text-xs font-semibold">
                📂 Belum ada data kategori simpanan.
            </td>
        </tr>
    @endforelse
</tbody>
            </table>
        </div>
    </div>
</div>
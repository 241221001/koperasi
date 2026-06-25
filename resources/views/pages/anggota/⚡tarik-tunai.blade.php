<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use App\Models\Simpanan;
use App\Models\KategoriSimpanan;

new class extends Component {
    public $user;
    public $jumlah;
    public $tanggal_transaksi;
    public $keterangan = '';
    public $password_confirm;

    public function mount()
    {
        $this->user = Auth::user();
        $this->tanggal_transaksi = date('Y-m-d');

        // Cek autentikasi
        if (!$this->user || $this->user->peran !== 'anggota') {
            return redirect()->to('/login');
        }

        // Cek kelengkapan profil
        if (!$this->user->anggota) {
            session()->flash('error', 'Silakan lengkapi profil Anda terlebih dahulu.');
            return redirect()->to('/dashboard');
        }

        if ($this->user->anggota->status !== 'aktif') {
            session()->flash('error', 'Akun Anda belum aktif. Tunggu verifikasi dari admin.');
            return redirect()->to('/dashboard');
        }
    }

    #[Computed]
    public function profilAnggota()
    {
        return $this->user->anggota;
    }

    #[Computed]
    public function totalSaldo()
    {
        if (!$this->profilAnggota) {
            return 0;
        }

        $totalSetor = $this->profilAnggota->simpanan()->where('jenis_transaksi', 'setor')->sum('jumlah');
        $totalTarik = $this->profilAnggota->simpanan()->where('jenis_transaksi', 'tarik')->sum('jumlah');

        return $totalSetor - $totalTarik;
    }

    #[Computed]
    public function kategoriSimpanan()
    {
        return KategoriSimpanan::all();
    }

    public function prosesPenarikan()
    {
        $this->validate([
            'jumlah' => 'required|numeric|min:10000',
            'tanggal_transaksi' => 'required|date',
            'keterangan' => 'nullable|string|max:255',
            'password_confirm' => 'required|string'
        ]);

        // Verifikasi password (sederhana - bisa diganti dengan verifikasi PIN)
        if (!Auth::validate(['email' => $this->user->email, 'password' => $this->password_confirm])) {
            $this->addError('password_confirm', 'Password yang Anda masukkan salah.');
            return;
        }

        // Cek saldo cukup
        if ($this->jumlah > $this->totalSaldo) {
            $this->addError('jumlah', 'Saldo Anda tidak mencukupi untuk penarikan sebesar Rp ' . number_format($this->jumlah, 0, ',', '.'));
            return;
        }

        try {
            Simpanan::create([
                'anggota_id' => $this->profilAnggota->id,
                'kategori_simpanan_id' => 1, // Default kategori simpanan wajib
                'jenis_transaksi' => 'tarik',
                'jumlah' => $this->jumlah,
                'tanggal_transaksi' => $this->tanggal_transaksi,
                'keterangan' => $this->keterangan,
            ]);

            session()->flash('sukses', 'Penarikan tunai sebesar Rp ' . number_format($this->jumlah, 0, ',', '.') . ' berhasil diproses!');
            $this->reset('jumlah', 'keterangan', 'password_confirm');
            $this->tanggal_transaksi = date('Y-m-d');

        } catch (\Exception $e) {
            session()->flash('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect()->to('/login');
    }

    public function kembali()
    {
        return redirect()->to('/dashboard');
    }
};
?>
<div class="min-h-screen bg-slate-50 font-sans antialiased text-slate-800">
    <nav class="bg-white/80 backdrop-blur-md sticky top-0 z-50 border-b border-slate-200/80 px-6 py-4 flex justify-between items-center shadow-sm">
        <div class="flex items-center space-x-3">
            <div class="bg-gradient-to-tr from-blue-600 to-indigo-600 p-2 rounded-xl text-white shadow-md shadow-blue-500/20">
                <span class="text-xl font-bold tracking-wider">⚡</span>
            </div>
            <span class="text-lg font-black tracking-tight bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">KOP-ID MEMBER</span>
        </div>
        <button wire:click="logout" class="bg-rose-50 text-rose-600 px-4 py-2 rounded-xl font-bold text-sm hover:bg-rose-100/80 transition-all duration-200 border border-rose-100 active:scale-95 flex items-center space-x-1">
            <span>Keluar</span>
            <span>➔</span>
        </button>
    </nav>

    <div class="max-w-2xl mx-auto p-6">
        @if (session()->has('sukses'))
            <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm font-medium flex items-center gap-2 animate-fade-in">
                <span class="text-xl">✅</span>
                <span>{{ session('sukses') }}</span>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-4 bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl text-sm font-medium flex items-center gap-2 animate-fade-in">
                <span class="text-xl">⚠️</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-rose-600 to-pink-600 px-6 py-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-black text-white tracking-tight">💸 Tarik Tunai</h1>
                        <p class="text-rose-100 text-sm mt-0.5">Tarik saldo tabungan Anda dengan aman</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-2 text-white border border-white/10">
                        <p class="text-xs font-medium text-rose-100">Saldo Saat Ini</p>
                        <p class="text-lg font-black">Rp {{ number_format($this->totalSaldo, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            <!-- Form Tarik -->
            <div class="p-6">
                <form wire:submit="prosesPenarikan" class="space-y-5">
                    <!-- Jumlah Penarikan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                            Jumlah Penarikan
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-lg">Rp</span>
                            <input type="number" 
                                   wire:model="jumlah" 
                                   placeholder="0" 
                                   class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 text-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all text-lg font-bold shadow-sm">
                        </div>
                        @error('jumlah') 
                            <span class="text-rose-500 text-xs mt-1 block font-medium">⚠️ {{ $message }}</span> 
                        @enderror
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button type="button" wire:click="$set('jumlah', 50000)" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-lg transition-all">Rp 50.000</button>
                            <button type="button" wire:click="$set('jumlah', 100000)" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-lg transition-all">Rp 100.000</button>
                            <button type="button" wire:click="$set('jumlah', 250000)" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-lg transition-all">Rp 250.000</button>
                            <button type="button" wire:click="$set('jumlah', 500000)" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-lg transition-all">Rp 500.000</button>
                        </div>
                        <div class="mt-2 text-xs text-slate-400">
                            💡 Saldo tersedia: Rp {{ number_format($this->totalSaldo, 0, ',', '.') }}
                        </div>
                    </div>

                    <!-- Tanggal Transaksi -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                            Tanggal Transaksi
                        </label>
                        <input type="date" 
                               wire:model="tanggal_transaksi" 
                               class="w-full px-4 py-3 bg-slate-50 border border-slate-200 text-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all shadow-sm">
                        @error('tanggal_transaksi') 
                            <span class="text-rose-500 text-xs mt-1 block font-medium">⚠️ {{ $message }}</span> 
                        @enderror
                    </div>

                    <!-- Keterangan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                            Keterangan (Opsional)
                        </label>
                        <textarea wire:model="keterangan" 
                                  rows="2" 
                                  placeholder="Catatan tambahan untuk penarikan ini..." 
                                  class="w-full px-4 py-3 bg-slate-50 border border-slate-200 text-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all shadow-sm resize-none"></textarea>
                        @error('keterangan') 
                            <span class="text-rose-500 text-xs mt-1 block font-medium">⚠️ {{ $message }}</span> 
                        @enderror
                    </div>

                    <!-- Konfirmasi Password -->
                    <div class="border-t border-slate-100 pt-4">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                            Konfirmasi Password
                        </label>
                        <div class="relative">
                            <input type="password" 
                                   wire:model="password_confirm" 
                                   placeholder="Masukkan password Anda untuk konfirmasi" 
                                   class="w-full px-4 py-3 bg-slate-50 border border-slate-200 text-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all shadow-sm">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">🔒</span>
                        </div>
                        @error('password_confirm') 
                            <span class="text-rose-500 text-xs mt-1 block font-medium">⚠️ {{ $message }}</span> 
                        @enderror
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="flex gap-3 pt-2">
                        <button type="button" 
                                wire:click="kembali"
                                class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-600 py-3.5 rounded-xl font-bold text-sm transition-all active:scale-95">
                            ← Kembali
                        </button>
                        <button type="submit" 
                                class="flex-[2] bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-700 hover:to-pink-700 text-white py-3.5 rounded-xl font-bold text-sm tracking-wide shadow-lg shadow-rose-500/20 transition-all duration-150 transform active:scale-[0.99]">
                            💳 Proses Penarikan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Info Tambahan -->
        <div class="mt-6 bg-white/70 backdrop-blur-sm border border-slate-200/60 rounded-2xl p-4 shadow-sm">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 bg-rose-50 rounded-lg flex items-center justify-center text-rose-600 text-sm flex-shrink-0">ℹ️</div>
                <div>
                    <p class="text-xs font-bold text-slate-700">Informasi Penarikan</p>
                    <p class="text-xs text-slate-400 mt-0.5 leading-relaxed">
                        • Minimal penarikan Rp 10.000<br>
                        • Pastikan saldo Anda mencukupi sebelum melakukan penarikan<br>
                        • Transaksi akan tercatat dalam riwayat aktivitas rekening<br>
                        • Untuk keamanan, konfirmasi password diperlukan
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
use Livewire\Component;
use App\Models\Pengguna;
use Illuminate\Support\Facades\Hash; // Panggil library enkripsi bawaan Laravel

new class extends Component {
    public $nama;
    public $email;
    public $password;

    public function daftar()
    {
        // Validasi input
        $this->validate([
            'nama' => 'required|string|max:255',
            'email' => 'required|email|unique:pengguna,email',
            'password' => 'required|min:6',
        ]);

        // Generate Nomor Anggota otomatis
        $totalAnggota = Pengguna::where('peran', 'anggota')->count() + 1;
        $nomorAnggota = 'KOP-' . date('Y') . '-' . str_pad($totalAnggota, 4, '0', STR_PAD_LEFT);

        // Simpan ke database dengan password yang sudah DIENKRIPSI
        Pengguna::create([
            'nama' => $this->nama,
            'email' => $this->email,
            'password' => Hash::make($this->password), // <--- PROSES ENKRIPSI AMAN DI SINI
            'peran' => 'anggota',
            'nomor_anggota' => $nomorAnggota,
        ]);

        // Kirim notifikasi sukses ke halaman login
        session()->flash('sukses_daftar', 'Akun Anggota berhasil dibuat! Silakan login menggunakan Email dan Password Anda.');
        
        return redirect()->to('/login');
    }
};
?><div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-900 via-indigo-850 to-slate-900 px-4 py-12 relative overflow-hidden">
    <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="bg-white/95 backdrop-blur-md p-8 md:p-10 rounded-3xl shadow-2xl shadow-indigo-950/40 w-full max-w-md border border-white/20 transition-all duration-300 hover:scale-[1.01]">
        
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-500 text-white text-2xl shadow-md shadow-indigo-700/30 animate-bounce mb-3">
                📝
            </div>
            <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Gabung Anggota Koperasi</h2>
            <p class="text-xs text-slate-400 font-medium mt-1.5 max-w-xs mx-auto">Mulai langkah suksesmu, buat akun baru untuk mengakses seluruh layanan ekosistem koperasi.</p>
        </div>

        <form wire:submit="daftar" class="space-y-5">
            
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-600 uppercase tracking-wider block">Nama Lengkap</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 text-sm pointer-events-none">👤</span>
                    <input type="text" wire:model="nama" placeholder="Contoh: Ahmad Subarjo" class="w-full pl-10 pr-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white transition-all duration-200">
                </div>
                @error('nama') 
                    <span class="text-rose-500 text-xs font-semibold flex items-center gap-1 mt-1 animate-pulse">⚠️ {{ $message }}</span> 
                @enderror
            </div>

            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-600 uppercase tracking-wider block">Alamat Email</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 text-sm pointer-events-none">✉️</span>
                    <input type="email" wire:model="email" placeholder="nama@email.com" class="w-full pl-10 pr-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white transition-all duration-200">
                </div>
                @error('email') 
                    <span class="text-rose-500 text-xs font-semibold flex items-center gap-1 mt-1 animate-pulse">⚠️ {{ $message }}</span> 
                @enderror
            </div>

            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-600 uppercase tracking-wider block">Password Keamanan</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 text-sm pointer-events-none">🔒</span>
                    <input type="password" wire:model="password" placeholder="Minimal 6 karakter" class="w-full pl-10 pr-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm font-medium text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white transition-all duration-200">
                </div>
                @error('password') 
                    <span class="text-rose-500 text-xs font-semibold flex items-center gap-1 mt-1 animate-pulse">⚠️ {{ $message }}</span> 
                @enderror
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-3 rounded-xl font-bold text-sm tracking-wide hover:from-indigo-700 hover:to-purple-700 focus:ring-4 focus:ring-indigo-500/20 transition-all duration-200 transform active:scale-[0.98] shadow-lg shadow-indigo-800/30 flex items-center justify-center gap-2 group">
                    <span>Daftar Sekarang Berkas</span>
                    <span class="group-hover:translate-x-1 transition-transform duration-150">🚀</span>
                </button>
            </div>
        </form>

        <div class="mt-8 text-center text-xs text-slate-400 border-t border-slate-100 pt-5 font-medium">
            Sudah bergabung sebelumnya? <a href="/login" wire:navigate class="text-indigo-600 font-bold hover:text-indigo-700 hover:underline transition-colors duration-150">Masuk Akun</a>
        </div>
    </div>
</div>
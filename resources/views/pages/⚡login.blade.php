<?php
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $email;
    public $password;

    public function login()
    {
        // 1. Validasi input wajib berformat email
        $credentials = $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // 2. Proses Otentikasi Laravel (Otomatis mencari ke tabel 'pengguna' & mencocokkan password terenkripsi)
        if (Auth::attempt($credentials)) {
            session()->regenerate();
            
            $user = Auth::user();

            // 3. Bagi arah dashboard berdasarkan isi kolom 'peran' di tabel pengguna
            if ($user->peran === 'admin') {
                return redirect()->to('/admin/dashboard-admin');
            } elseif ($user->peran === 'petugas') {
                return redirect()->to('/petugas/dashboard');
            } elseif ($user->peran === 'anggota') {
                // Simpan ID pengguna di session untuk keperluan data tabungan/pinjaman nanti
                session(['anggota_id' => $user->id]);
                return redirect()->to('/anggota/dashboard');
            }
        }

        // 4. Jika email tidak terdaftar atau password salah
        $this->addError('email', 'Email atau password yang Anda masukkan salah.');
    }

public $lihatPassword = false;

public function togglePassword()
{
    $this->lihatPassword = !$this->lihatPassword;
}
};
?>
<div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-indigo-900 via-indigo-850 to-slate-900 px-4 py-12 relative overflow-hidden">
    <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md relative z-10">
        
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/10 backdrop-blur-md rounded-full border border-white/10 shadow-inner mb-3">
                <span class="text-sm">⚡</span>
                <span class="text-xs font-black tracking-widest text-indigo-100 uppercase">KOP-ID System</span>
            </div>
            <p class="text-indigo-100/70 text-[11px] uppercase tracking-widest font-bold">Koperasi Simpan Pinjam Terkini</p>
        </div>

        <div class="bg-white/95 backdrop-blur-md p-8 md:p-10 rounded-3xl shadow-2xl shadow-indigo-950/40 border border-white/20 transition-all duration-300 hover:scale-[1.01]">
            <div class="mb-6">
                <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Selamat Datang</h2>
                <p class="text-xs text-slate-400 font-medium mt-1">Silakan masuk menggunakan akun koperasi yang sudah terverifikasi.</p>
            </div>

            <form wire:submit="login" class="space-y-5">
                
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider">Alamat Email</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 text-sm pointer-events-none">
                            ✉️
                        </span>
                        <input type="email" wire:model="email" placeholder="nama@koperasi.com" 
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-50/50 border border-slate-200 text-slate-700 placeholder-slate-400 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white transition-all duration-200 text-sm shadow-sm font-medium">
                    </div>
                    @error('email') <span class="text-rose-500 text-xs mt-1 block font-semibold animate-pulse">⚠️ {{ $message }}</span> @enderror
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider">Kata Sandi</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 text-sm pointer-events-none">
                            🔒
                        </span>
                        
                        <input type="{{ $lihatPassword ? 'text' : 'password' }}" wire:model="password" placeholder="••••••••" 
                            class="w-full pl-10 pr-12 py-2.5 bg-slate-50/50 border border-slate-200 text-slate-700 placeholder-slate-400 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white transition-all duration-200 text-sm shadow-sm font-mono font-bold">
                        
                        <button type="button" wire:click="togglePassword" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-indigo-600 transition-colors focus:outline-none text-xs font-bold">
                            @if($lihatPassword)
                                🙈 <span class="ml-1 text-[10px] text-slate-400 uppercase tracking-wider">Sembunyikan</span>
                            @else
                                👁️ <span class="ml-1 text-[10px] text-slate-400 uppercase tracking-wider">Lihat</span>
                            @endif
                        </button>
                    </div>
                    @error('password') <span class="text-rose-500 text-xs mt-1 block font-semibold animate-pulse">⚠️ {{ $message }}</span> @enderror
                </div>

                <div class="flex items-center justify-between text-xs pt-1 font-medium">
                    <label class="flex items-center space-x-2 text-slate-500 cursor-pointer select-none group">
                        <input type="checkbox" class="w-4 h-4 text-indigo-600 border-slate-200 rounded focus:ring-indigo-500/20 focus:ring-offset-0 accent-indigo-600 transition-all">
                        <span class="group-hover:text-slate-700 transition-colors">Ingat saya</span>
                    </label>
                    <a href="register" wire:navigate class="text-indigo-600 hover:text-indigo-700 hover:underline font-bold transition-colors">Belum Punya Akun?</a>
                </div>

                <div class="pt-2">
                    <button type="submit" 
                        class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-3 rounded-xl font-bold text-sm tracking-wide hover:from-indigo-700 hover:to-purple-700 focus:ring-4 focus:ring-indigo-500/20 transition-all duration-200 transform active:scale-[0.98] shadow-lg shadow-indigo-800/30 flex justify-center items-center gap-2 group">
                        <span>Masuk ke Sistem</span>
                        <span class="group-hover:translate-x-1 transition-transform duration-150">➔</span>
                    </button>
                </div>
            </form>
        </div>

        <p class="text-center text-xs text-indigo-100/50 mt-8 font-medium tracking-wide">
            &copy; 2026 KOP-ID System. All rights reserved.
        </p>
    </div>
</div>
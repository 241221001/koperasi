<?php
use Livewire\Component;
use App\Models\Anggota;
use App\Models\Pinjaman; // 🌟 Tambahkan import model Pinjaman di sini
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $halamanAktif = 'dashboard'; 

    // 🌟 PROTEKSI ROLE DI SINI MANG
    public function mount()
    {
        // 1. Cek apakah user sudah login atau belum
        if (!Auth::check()) {
            return redirect()->to('/login');
        }

        // 2. Ambil data user yang sedang login
        $user = Auth::user();

        // 3. Cek Role (Sesuaikan nama kolom di database lu, misal: 'peran' atau 'role')
        if ($user->peran !== 'admin' && $user->peran !== 'petugas') {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();
            
            session()->flash('error_auth', 'Waduh mang, halaman ini khusus untuk Admin & Petugas saja!');
            return redirect()->to('/login');
        }
    }

    public function gantiHalaman($namaHalaman)
    {
        $this->halamanAktif = $namaHalaman;
    }

    public function totalAnggota()
    {
        return Anggota::where('status', 'aktif')->count();
    }

    public function totalKasMasuk()
    {
        // Sementara 0 dulu karena modul simpanan belum dibuat penuh
        return 0; 
    }

    public function totalKasKeluar()
    {
        // 🌟 REVISI: Kas keluar dihitung dari total pokok pinjaman yang sudah DISETUJUI oleh admin
        return Pinjaman::where('status', 'disetujui')->sum('jumlah_pokok');
    }

    public function arusKasTerbaru()
    {
        // 🌟 REVISI: Mengambil 5 data pinjaman disetujui terbaru untuk dimasukkan ke Buku Besar Dashboard
        return Pinjaman::with('anggota')
            ->where('status', 'disetujui')
            ->latest('updated_at')
            ->take(5)
            ->get()
            ->map(function ($pinjaman) {
                // Kita samakan struktur object-nya dengan properti tabel di Blade biar tidak error
                return (object) [
                    'tanggal_transaksi' => $pinjaman->updated_at->format('d M Y'),
                    'keterangan' => 'Pencairan Pinjaman: ' . ($pinjaman->anggota->nama ?? 'Anggota'),
                    'jenis_kas' => 'keluar',
                    'jumlah' => $pinjaman->jumlah_pokok
                ];
            });
    }

    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect()->to('/login');
    }
};
?>

<div class="min-h-screen bg-gray-50 flex" style="min-height: 100vh;">
    <div class="w-64 bg-indigo-900 text-white p-6 flex flex-col justify-between shadow-xl flex-shrink-0">
        <div class="space-y-6">
            <div class="flex items-center space-x-2">
                <span class="text-2xl font-black tracking-wider text-yellow-400">⚡ KOP-ID</span>
            </div>
            <div class="text-xs bg-indigo-950 p-3 rounded-lg border border-indigo-800">
                <p class="text-indigo-300">Login sebagai:</p>
                <p class="font-bold text-white text-sm truncate">{{ Auth::user()->nama }}</p>
                <span class="inline-block mt-1 px-2 py-0.5 text-[10px] uppercase font-bold bg-green-500 text-white rounded">
                    {{ Auth::user()->peran }}
                </span>
            </div>
            
            <nav class="space-y-1">
                <button wire:click="gantiHalaman('dashboard')" 
                    class="w-full flex items-center space-x-3 p-2.5 rounded-lg text-left transition font-semibold {{ $halamanAktif === 'dashboard' ? 'bg-indigo-700 text-white' : 'text-indigo-200 hover:bg-indigo-800 hover:text-white' }}">
                    <span>📊 Dashboard</span>
                </button>
                
                <button wire:click="gantiHalaman('kategoris')" 
                    class="w-full flex items-center space-x-3 p-2.5 rounded-lg text-left transition font-semibold {{ $halamanAktif === 'kategoris' ? 'bg-indigo-700 text-white' : 'text-indigo-200 hover:bg-indigo-800 hover:text-white' }}">
                    <span>⚙️ Kategori Simpanan</span>
                </button>

                <button wire:click="gantiHalaman('kategorip')" 
                    class="w-full flex items-center space-x-3 p-2.5 rounded-lg text-left transition font-semibold {{ $halamanAktif === 'kategorip' ? 'bg-indigo-700 text-white' : 'text-indigo-200 hover:bg-indigo-800 hover:text-white' }}">
                    <span>⚙️ Kategori Pinjaman</span>
                </button>

                <button wire:click="gantiHalaman('anggota')" 
                    class="w-full flex items-center space-x-3 p-2.5 rounded-lg text-left transition font-semibold {{ $halamanAktif === 'anggota' ? 'bg-indigo-700 text-white' : 'text-indigo-200 hover:bg-indigo-800 hover:text-white' }}">
                    <span>👥 Anggota</span>
                </button>
                
                <button wire:click="gantiHalaman('simpanan')" 
                    class="w-full flex items-center space-x-3 p-2.5 rounded-lg text-left transition font-semibold {{ $halamanAktif === 'simpanan' ? 'bg-indigo-700 text-white' : 'text-indigo-200 hover:bg-indigo-800 hover:text-white' }}">
                    <span>💰 Simpanan</span>
                </button>
                
                <button wire:click="gantiHalaman('pinjaman')" 
                    class="w-full flex items-center space-x-3 p-2.5 rounded-lg text-left transition font-semibold {{ $halamanAktif === 'pinjaman' ? 'bg-indigo-700 text-white' : 'text-indigo-200 hover:bg-indigo-800 hover:text-white' }}">
                    <span>💳 Pinjaman</span>
                                    <button wire:click="gantiHalaman('arus-kas')" 
                    class="w-full flex items-center space-x-3 p-2.5 rounded-lg text-left transition font-semibold {{ $halamanAktif === 'pinjaman' ? 'bg-indigo-700 text-white' : 'text-indigo-200 hover:bg-indigo-800 hover:text-white' }}">
                    <span>💳 Arus-kas</span>
                </button>
            </nav>
        </div>
        
        <button wire:click="logout" class="w-full bg-red-600 hover:bg-red-700 p-2.5 rounded-lg font-bold text-sm transition shadow">
            🚪 Keluar (Logout)
        </button>
    </div>

    <div class="flex-1 p-8 overflow-y-auto">
        
        @if($halamanAktif === 'dashboard')
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-3xl font-extrabold text-gray-800 tracking-tight">Ringkasan Finansial</h2>
                    <p class="text-sm text-gray-500 mt-1">Pantau perkembangan data anggota dan arus kas koperasi secara real-time.</p>
                </div>
                <div class="text-sm bg-white px-4 py-2 rounded-lg shadow-sm font-medium text-gray-600 border">
                    📅 {{ date('d F Y') }}
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-xs font-bold uppercase tracking-wider">Total Anggota Aktif</p>
                        <p class="text-4xl font-black text-gray-800 mt-1">{{ $this->totalAnggota() }}</p>
                    </div>
                    <div class="p-4 bg-blue-50 text-blue-600 rounded-xl text-2xl">👥</div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-xs font-bold uppercase tracking-wider">Total Kas Masuk</p>
                        <p class="text-2xl font-black text-green-600 mt-2">Rp {{ number_format($this->totalKasMasuk(), 0, ',', '.') }}</p>
                    </div>
                    <div class="p-4 bg-green-50 text-green-600 rounded-xl text-2xl">📈</div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-xs font-bold uppercase tracking-wider">Total Kas Keluar (Pinjaman)</p>
                        <p class="text-2xl font-black text-red-600 mt-2">Rp {{ number_format($this->totalKasKeluar(), 0, ',', '.') }}</p>
                    </div>
                    <div class="p-4 bg-red-50 text-red-600 rounded-xl text-2xl">📉</div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-800">Aliran Dana Terakhir (Buku Besar)</h3>
                    <span class="text-xs bg-indigo-50 text-indigo-600 px-2.5 py-1 rounded-full font-semibold">Live System</span>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 text-xs font-bold uppercase tracking-wider border-b">
                                <th class="p-3">Tanggal</th>
                                <th class="p-3">Keterangan Transaksi</th>
                                <th class="p-3">Tipe</th>
                                <th class="p-3 text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($this->arusKasTerbaru() as $kas)
                                <tr class="hover:bg-gray-50 transition text-sm">
                                    <td class="p-3 text-gray-400">{{ $kas->tanggal_transaksi }}</td>
                                    <td class="p-3 font-semibold text-gray-700">{{ $kas->keterangan }}</td>
                                    <td class="p-3">
                                        <span class="px-2 py-0.5 text-xs font-bold rounded-full {{ $kas->jenis_kas == 'masuk' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ ucfirst($kas->jenis_kas) }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-right font-bold {{ $kas->jenis_kas == 'masuk' ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $kas->jenis_kas == 'masuk' ? '+' : '-' }} Rp {{ number_format($kas->jumlah, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="p-8 text-center text-gray-400">Belum ada aktivitas finansial yang tercatat hari ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        @else
            <div class="mb-6">
                <h2 class="text-3xl font-extrabold text-gray-800 tracking-tight">
                    {{ $halamanAktif === 'kategoris' ? 'Manajemen Kategori Simpanan' : ($halamanAktif === 'kategorip' ? 'Manajemen Kategori Pinjaman' : ($halamanAktif === 'anggota' ? 'Daftar Anggota Koperasi' : '')) }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Kelola data sistem koperasi secara real-time.</p>
            </div>

            @if($halamanAktif === 'kategoris')
                <livewire:pages::admin.kategori-simpanan />
                
            @elseif($halamanAktif === 'kategorip')
                <livewire:pages::admin.kategori-pinjaman  />
                
            @elseif($halamanAktif === 'anggota')
                <livewire:pages::admin.anggota/>
                
            @elseif($halamanAktif === 'simpanan')
                <div class="p-8 text-center text-gray-400 bg-white rounded-xl border">Halaman Transaksi Simpanan Belum Dibuat.</div>
            @elseif($halamanAktif === 'pinjaman')
                <livewire:pages::admin.manage-pinjaman />
            @elseif($halamanAktif === 'arus-kas')
                <livewire:pages::admin.arus-kas />
                
            @endif
        @endif

    </div>
</div>
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
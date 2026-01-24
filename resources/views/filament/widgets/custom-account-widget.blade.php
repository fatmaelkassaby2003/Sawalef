<x-filament::section class="fi-section-rounded h-full flex flex-col justify-center">
    <div class="flex items-center justify-between w-full h-full p-4">
        <div class="flex items-center gap-x-4">
            <!-- Avatar Circle -->
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gray-950 text-white font-bold text-lg dark:bg-white dark:text-gray-950">
                {{ substr($this->getUser()->name ?? 'A', 0, 1) }}
            </div>
            
            <div class="flex flex-col">
                <h2 class="text-xl font-bold text-gray-950 dark:text-white">
                    مرحباً
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $this->getUser()->name }}
                </p>
            </div>
        </div>

        <form action="{{ route('filament.admin.auth.logout') }}" method="post">
            @csrf
            <button type="submit" class="flex items-center gap-x-2 px-4 py-2 text-sm font-medium text-gray-500 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 transition-colors">
                <span>تسجيل الخروج</span>
                <x-heroicon-m-arrow-right-start-on-rectangle class="w-4 h-4 rtl:rotate-180" />
            </button>
        </form>
    </div>
</x-filament::section>

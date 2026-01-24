<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="h-16 w-16 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700 shadow-sm border border-gray-100 dark:border-gray-600">
                    @if(auth()->user()->profile_image)
                        <img src="{{ auth()->user()->profile_image }}" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center text-2xl font-bold text-primary-600 dark:text-primary-400">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                    @endif
                </div>
                
                <div>
                    <h2 class="text-lg font-bold tracking-tight text-gray-900 dark:text-white">مرحباً</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ auth()->user()->name }}</p>
                </div>
            </div>

            <div>
                <form action="{{ route('filament.admin.auth.logout') }}" method="post" class="inline">
                    @csrf
                    <x-filament::button
                        type="submit"
                        color="gray"
                        icon="heroicon-m-arrow-left-on-rectangle"
                        size="sm"
                        outlined
                    >
                        تسجيل الخروج
                    </x-filament::button>
                </form>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

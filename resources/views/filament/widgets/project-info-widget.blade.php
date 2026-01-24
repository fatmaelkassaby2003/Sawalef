<x-filament::section class="fi-section-rounded">
    <div class="flex items-center justify-between py-2">
        <!-- Logo Container (Right in RTL) -->
        <div class="flex-shrink-0">
            <img src="{{ asset('img/sawalef_logo.png') }}" alt="Sawalef Logo" class="h-16 w-auto object-contain">
        </div>

        <!-- Text Content (Left in RTL) -->
        <div class="flex flex-col gap-y-1 text-left" dir="ltr"> <!-- Force LTR for text block to align left? No, just text-left -->
             <!-- Actually better to just let flex do it, but align text itself -->
            <div class="flex flex-col items-end"> 
                <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                    سوالف
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">
                    لوحة التحكم
                </p>
            </div>
        </div>
    </div>
</x-filament::section>

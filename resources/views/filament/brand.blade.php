<div class="flex items-center gap-3" style="width: 100%;">
    @if(request()->routeIs('filament.admin.auth.login'))
        {{-- في صفحة تسجيل الدخول: الأيقونة فقط --}}
        <img src="{{ asset('img/sawalef_logo.png') }}" alt="Logo" class="brand-logo" style="height: 6rem; width: auto; margin: 0 auto;" />
    @else
        {{-- في السايد بار: الأيقونة + الاسم (مزاح لليسار) --}}
        <div class="flex items-center gap-3" style="margin-right: 55px;">
            <!-- <img src="{{ asset('img/sawalef_logo.png') }}" alt="Logo" class="brand-logo" style="height: 2.8rem; width: auto;" /> -->
            <div class="flex items-center font-black brand-text whitespace-nowrap" style="font-size: 2.2rem; line-height: 1;">
                <span style="color: #ec4899 !important;">سو</span><span style="color: #ffffff !important;">الف</span>
            </div>
        </div>
    @endif
</div>

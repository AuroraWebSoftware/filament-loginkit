@php
    use Filament\Support\Enums\Width;

    $livewire ??= null;

    $renderHookScopes = $livewire?->getRenderHookScopes();
    $maxContentWidth ??= (filament()->getSimplePageMaxContentWidth() ?? Width::Large);

    if (is_string($maxContentWidth)) {
        $maxContentWidth = Width::tryFrom($maxContentWidth) ?? $maxContentWidth;
    }
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    @props([
        'after' => null,
        'heading' => null,
        'subheading' => null,
    ])

    <div class="fi-simple-layout min-h-screen flex flex-col">
        {{ \Filament\Support\Facades\FilamentView::renderHook(
            \Filament\View\PanelsRenderHook::SIMPLE_LAYOUT_START,
            scopes: $renderHookScopes
        ) }}

        @if (($hasTopbar ?? true) && filament()->auth()->check())
            <div class="fi-simple-layout-header">
                @if (filament()->hasDatabaseNotifications())
                    @livewire(Filament\Livewire\DatabaseNotifications::class, [
                        'lazy' => filament()->hasLazyLoadedDatabaseNotifications(),
                    ])
                @endif

                @if (filament()->hasUserMenu())
                    @livewire(Filament\Livewire\SimpleUserMenu::class)
                @endif
            </div>
        @endif

        {{-- Ana içerik ortalama --}}
        <div class="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8 py-8">
            <main
                class="w-full max-w-2xl sm:max-w-3xl lg:max-w-4xl"
            >
                {{ $slot }}
            </main>
        </div>

        {{ \Filament\Support\Facades\FilamentView::renderHook(
            \Filament\View\PanelsRenderHook::FOOTER,
            scopes: $renderHookScopes
        ) }}

        {{ \Filament\Support\Facades\FilamentView::renderHook(
            \Filament\View\PanelsRenderHook::SIMPLE_LAYOUT_END,
            scopes: $renderHookScopes
        ) }}
    </div>
</x-filament-panels::layout.base>

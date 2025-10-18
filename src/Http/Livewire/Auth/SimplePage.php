<?php

namespace AuroraWebSoftware\FilamentLoginKit\Http\Livewire\Auth;

use Filament\Facades\Filament;
use Filament\Pages\BasePage;
use Filament\Pages\Concerns;

abstract class SimplePage extends BasePage
{
    use Concerns\HasMaxWidth;
    use Concerns\HasTopbar;

    protected string $view = 'filament-loginkit::auth.login';

    protected static string $layout = 'filament-panels::components.layout.simple';

    public function render(): \Illuminate\Contracts\View\View
    {
        app()->setLocale(session('locale', config('app.locale', 'tr')));

        $panelId = session('flk_panel_id')
            ?? Filament::getDefaultPanel()?->getId()
            ?? 'default';

        $panel = Filament::getPanel($panelId);
        Filament::setCurrentPanel($panel);

        $panelPrimary = $panel?->getColors()['primary'] ?? \Filament\Support\Colors\Color::Amber;
        \Filament\Support\Facades\FilamentColor::register([
            'default' => is_string($panelPrimary) ? \Filament\Support\Colors\Color::hex($panelPrimary) : $panelPrimary,
            'primary' => is_string($panelPrimary) ? \Filament\Support\Colors\Color::hex($panelPrimary) : $panelPrimary,
        ]);

        return view($this->view)
            ->layout(static::$layout)
            ->layoutData($this->getLayoutData());
    }

    protected function getLayoutData(): array
    {
        return [
            'hasTopbar' => $this->hasTopbar(),
            'maxContentWidth' => $maxContentWidth = $this->getMaxWidth() ?? $this->getMaxContentWidth(),
            'maxWidth' => $maxContentWidth,
        ];
    }

    public function hasLogo(): bool
    {
        return true;
    }
}

<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class LanguageSwitcher extends Component
{
    public string $currentLocale = 'ru';

    /**
     * @var array<string, string>
     */
    public array $locales = [];

    public function mount(): void
    {
        $this->locales = (array) config('localization.supported', ['ru' => 'RU', 'en' => 'EN']);
        $this->currentLocale = app()->getLocale();
    }

    public function setLocale(string $locale): mixed
    {
        if (! array_key_exists($locale, $this->locales)) {
            return null;
        }

        session(['app.locale' => $locale]);
        app()->setLocale($locale);
        $this->currentLocale = $locale;

        return redirect(request()->header('Referer') ?: url()->current());
    }

    public function render(): View
    {
        return view('livewire.language-switcher');
    }
}

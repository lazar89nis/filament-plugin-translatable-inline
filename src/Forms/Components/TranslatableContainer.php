<?php

declare(strict_types=1);

namespace Mvenghaus\FilamentPluginTranslatableInline\Forms\Components;

use Filament\Forms\Components\Component;
use Filament\Forms\ComponentContainer;
use Illuminate\Support\Collection;
use Closure;
use Filament\Forms\Components\Actions\Action;
use Illuminate\Support\Facades\Http;

class TranslatableContainer extends Component
{
    protected string $view = 'filament-plugin-translatable-inline::forms.components.translatable-container';

    protected Component $baseComponent;

    protected bool | Closure $onlyMainIsRequired = false;
    protected bool | Closure $required = false;
    protected array $requiredLocales = [];
    protected bool | Closure $showCopyButton = false;
    protected bool | Closure $showTranslateButton = false;

    final public function __construct(array $schema = [])
    {
        $this->schema($schema);

        $this->baseComponent = collect($schema)->first();
        $this->statePath($this->baseComponent->getName());
    }

    public static function make(Component $component): static
    {
        $static = app(static::class, [
            'schema' => [$component]
        ]);
        $static->configure();

        return $static;
    }

    public function getName(): string
    {
        return $this->baseComponent->getName();
    }

    public function getLabel(): string
    {
        return $this->baseComponent->getLabel();
    }

    public function getChildComponentContainers(bool $withHidden = false): array
    {
        $locales = $this->getTranslatableLocales();

        $containers = [];

        $containers['main'] = ComponentContainer::make($this->getLivewire())
            ->parentComponent($this)
            ->components([
                $this->cloneComponent($this->baseComponent, $locales->first())
                    ->required($this->isLocaleRequired($locales->first()))
            ]);

        $containers['additional'] = ComponentContainer::make($this->getLivewire())
            ->parentComponent($this)
            ->components(
                $locales
                    ->filter(fn(string $locale, int $index) => $index !== 0)
                    ->map(
                        fn(string $locale): Component => $this->cloneComponent($this->baseComponent, $locale)
                            ->required($this->isLocaleRequired($locale))
                    )
                    ->all()
            );

        return $containers;
    }

    public function cloneComponent(Component $component, string $locale): Component
    {
        $clonedComponent = $component
            ->getClone()
            ->meta('locale', $locale)
            ->label("{$component->getLabel()} ({$locale})")
            ->statePath($locale);

        // Add translate action if enabled and this is not the main locale
        if ($this->shouldShowTranslateButton() ) {
            $clonedComponent->hintActions([
                Action::make('translate_from_' . $locale)
                    ->label('Translate')
                    ->icon('heroicon-m-language')
                    ->color('info')
                    ->size('sm')
                    ->tooltip('Translate all empty fields using ' . $locale . ' as source')
                    ->action(function () use ($locale) {
                        $this->translateFromLocale($locale);
                    })
            ]);
        }

        return $clonedComponent;
    }

    public function getTranslatableLocales(): Collection
    {
        $resourceLocales = null;
        if (method_exists($this->getLivewire(), 'getResource') &&
            method_exists($this->getLivewire()::getResource(), 'getTranslatableLocales')
        ) {
            $resourceLocales = $this->getLivewire()::getResource()::getTranslatableLocales();
        }

        return collect($resourceLocales ?? filament('spatie-laravel-translatable')->getDefaultLocales());
    }

    public function isLocaleStateEmpty(string $locale): bool
    {
        return empty($this->getState()[$locale]);
    }

    public function onlyMainLocaleRequired(bool | Closure $condition = true): self
    {
        $this->onlyMainIsRequired = $condition;

        return $this;
    }

    public function localesRequired(bool | Closure $condition = true): self
    {
        $this->required = $condition;

        return $this;
    }

    public function requiredLocales(array $locales): self
    {
        $this->requiredLocales = $locales;

        return $this;
    }

    public function showCopyButton(bool | Closure $condition = true): self
    {
        $this->showCopyButton = $condition;

        return $this;
    }

    public function showTranslateButton(bool | Closure $condition = true): self
    {
        $this->showTranslateButton = $condition;

        return $this;
    }

    private function isLocaleRequired(string $locale): bool
    {
        if ($this->isOnlyMainLocaleRequiredLocal()) {
            return ($locale === $this->getTranslatableLocales()->first());
        }

        if (in_array($locale, $this->requiredLocales)) {
            return true;
        }

        if (empty($this->requiredLocales) && $this->isRequiredLocal()) {
            return true;
        }

        return false;
    }

    public function isRequiredLocal(): bool
    {
        return (bool) $this->evaluate($this->required);
    }

    public function isOnlyMainLocaleRequiredLocal(): bool
    {
        return (bool) $this->evaluate($this->onlyMainIsRequired);
    }

    public function shouldShowCopyButton(): bool
    {
        return (bool) $this->evaluate($this->showCopyButton);
    }

    public function shouldShowTranslateButton(): bool
    {
        return (bool) $this->evaluate($this->showTranslateButton);
    }


    public function translateFromLocale(string $sourceLocale): void
    {
        // Get the source text from the current locale
        $sourceText = $this->getState()[$sourceLocale] ?? '';
        
        if (empty($sourceText)) {
            return;
        }

        // Get all target locales (excluding the source)
        $targetLocales = $this->getTranslatableLocales()
            ->filter(fn($locale) => $locale !== $sourceLocale)
            ->filter(fn($locale) => empty($this->getState()[$locale] ?? ''))
            ->values();

        if ($targetLocales->isEmpty()) {
            return;
        }

        // Get Google Translate API key from config
        $apiKey = config('services.google.translate_key');

        
        if (empty($apiKey)) {
            return;
        }

        // Translate to each target locale
        foreach ($targetLocales as $targetLocale) {
            try {
                $translatedText = $this->translateWithGoogle($sourceText, $sourceLocale, $targetLocale, $apiKey);
                
                if ($translatedText) {
                    // Update the state for this locale
                    $currentState = $this->getState();
                    $currentState[$targetLocale] = $translatedText;
                    $this->state($currentState);
                }
            } catch (\Exception $e) {
                // Log error or handle gracefully
                continue;
            }
        }
    }

    private function translateWithGoogle(string $text, string $sourceLocale, string $targetLocale, string $apiKey): ?string
    {
        $url = 'https://translation.googleapis.com/language/translate/v2';
        
        $response = Http::get($url, [
            'q' => $text,
            'source' => $sourceLocale,
            'target' => $targetLocale,
            'key' => $apiKey,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['data']['translations'][0]['translatedText'] ?? null;
        } 

        return null;
    }

}

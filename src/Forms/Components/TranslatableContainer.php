<?php

declare(strict_types=1);

namespace Mvenghaus\FilamentPluginTranslatableInline\Forms\Components;

use Filament\Forms\Components\Component;
use Filament\Forms\ComponentContainer;
use Illuminate\Support\Collection;
use Closure;

class TranslatableContainer extends Component
{
    protected string $view = 'filament-plugin-translatable-inline::forms.components.translatable-container';

    protected Component $baseComponent;

    protected bool | Closure $onlyMainIsRequired = false;
    protected bool | Closure $required = false;
    protected array $requiredLocales = [];
    protected bool | Closure $showCopyButton = false;

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
        return $component
            ->getClone()
            ->meta('locale', $locale)
            ->label("{$component->getLabel()} ({$locale})")
            ->statePath($locale);
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
}

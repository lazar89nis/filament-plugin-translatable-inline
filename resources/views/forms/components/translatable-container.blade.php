<div>
    <div
            x-data="{
                open: false,
                init() {
                    document.addEventListener('livewire:initialized', () => {
                        this.open = Boolean($refs.additionalContainer.querySelector(':invalid'));
                    })
                },
                handleOpenState() {
                    this.open = !this.open;
                    if (!this.open) {
                        this.open = Boolean($refs.additionalContainer.querySelector(':invalid'));
                    }
                },
                copyToAllLocales() {
                
                    const locales = @js($getTranslatableLocales()->toArray());
                    const mainLocale = locales[0];
                    const statePath = '{{ $getStatePath() }}';
                    
                    // Get the main locale value
                    const mainValue = $wire.get(statePath + '.' + mainLocale);
                    if (mainValue && mainValue !== '') {
                        // Copy to all other locales
                        locales.forEach(locale => {
                            if (locale !== mainLocale) {
                                $wire.set(statePath + '.' + locale, mainValue);
                            }
                        });
                        
                        // Update form inputs to reflect the changes
                        locales.forEach(locale => {
                            if (locale !== mainLocale) {
                                const fullPath = statePath + '.' + locale;
                                const inputs = document.querySelectorAll('[name*=\'' + fullPath + '\'], [wire\\:model*=\'' + fullPath + '\']');
                                
                                inputs.forEach(input => {
                                    if (input.type === 'text' || input.type === 'textarea' || input.tagName === 'TEXTAREA') {
                                        input.value = mainValue;
                                        input.dispatchEvent(new Event('input', { bubbles: true }));
                                        input.dispatchEvent(new Event('change', { bubbles: true }));
                                    }
                                });
                            }
                        });
                    }
                },
                

        }"
            @form-validation-error.window="
                $nextTick(() => {
                    if ($refs.additionalContainer.querySelector('[data-validation-error]')) {
                        open = true;
                    }
                });
        "
    >
        <div class="relative">
            {{ $getChildComponentContainer('main') }}
        </div>

        <div class="flex items-center justify-between my-2">
            <div class="flex items-center gap-1.5 cursor-pointer select-none"
                 @click="handleOpenState()"
            >
                <div x-show="!open">
                    <x-filament::icon icon="heroicon-c-chevron-right" class="h-5 w-5 text-gray-500 dark:text-gray-400"/>
                </div>

                <div x-show="open">
                    <x-filament::icon icon="heroicon-c-chevron-down" class="h-5 w-5 text-gray-500 dark:text-gray-400"/>
                </div>

                @foreach($getTranslatableLocales() as $locale)
                    <div class="text-xs rounded-full p-1 shadow-sm ring-2 ring-inset ring-gray-950/10 dark:ring-white/20"
                         @if (!$isLocaleStateEmpty($locale))
                             style="border: 1px forestgreen solid"
                            @endif
                    >
                        <div class="px-1">{{ $locale }}</div>
                    </div>
                @endforeach
            </div>

            @if($shouldShowCopyButton())
                <div class="flex-shrink-0">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center w-8 h-8 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md transition-colors duration-200"
                        title="Copy to all languages"
                        @click.stop="copyToAllLocales()"
                    >
                        <x-filament::icon icon="heroicon-m-document-duplicate" class="h-4 w-4"/>
                    </button>
                </div>
            @endif
        </div>

        <div x-ref="additionalContainer"
             x-show="open"
        >
            <div class="p-4">
                {{ $getChildComponentContainer('additional') }}
            </div>
        </div>
    </div>
</div>
# Filament Plugin - Translatable Inline

This is an addon to [Spatie Translatable](https://filamentphp.com/plugins/filament-spatie-translatable) that allows you to edit your translation directly below the field. 

This approach offers several advantages:

- Faster editing of your translations
- Detecting fields that can be translated is much easier to see
- You can quickly see which translations are missing

## Screenshots

![Screenshot](https://raw.githubusercontent.com/mvenghaus/filament-plugin-translatable-inline/main/docs/images/screenshot.png)

## Requirements

You need the latest version of Filament v3.

This package is based on:
- [Spatie Laravel Translatable](https://github.com/spatie/laravel-translatable)
- [Filament Spatie Translatable Plugin](https://github.com/filamentphp/spatie-laravel-translatable-plugin)

You don't need to install them separately, it's handled via dependencies. 

## Installation

Install the package via composer:

```bash
composer require mvenghaus/filament-plugin-translatable-inline:"^3.0"
```

### Configuration

Since it is based on the Spatie plugin, it must be registered as described in the [documentation](https://github.com/filamentphp/spatie-laravel-translatable-plugin).

> **_NOTE:_** It is important that you don't add the traits and the header action to your form resource pages, or it won't work! Only the trait "Translatable" in your resource is required!

Instead of having a locale switcher in a dropdown above, you add a container for each translatable field.

**Before**
```php
<?php

...

    public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->maxLength(255)
                        ->required()
                    ,

...
```

**After**
```php
<?php

...

use Mvenghaus\FilamentPluginTranslatableInline\Forms\Components\TranslatableContainer;

...

    public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    TranslatableContainer::make(
                        Forms\Components\TextInput::make('title')
                            ->maxLength(255)
                            ->required()
                    )
                       ->onlyMainLocaleRequired() // optional
                       ->requiredLocales(['en', 'es']) // optional
                    ,

...
```

For each field that can be translated, simply repeat this process, and you'll be done.

> **_NOTE:_** You don't have to globally choose between inline or dropdown. Instead, you can choose an option on each page. For instance, it makes sense to have the dropdown in the list view and then use the inline version when editing.

### Options

#### onlyMainLocaleRequired

Sometimes you might want the field to be required, but only for the primary language. For example, if you set the TextInput to 'required,' it applies to all language variants. This is where this option comes into play. It removes the 'required' validation for all other languages except the primary one.

#### requireLocales

If you have more than one required locales you can pass an array to this method.

#### showCopyButton

Enable copy buttons that copy the current language value to all other languages:

```php
TranslatableContainer::make(
    Forms\Components\TextInput::make('title')
)
    ->showCopyButton()  // Copy only to empty fields
    ->showCopyButton(true, true)  // Copy to all fields (overwrite existing)
```

**Parameters:**
- **First parameter**: Enable/disable copy functionality (default: `true`)
- **Second parameter**: `overwriteFilled` - if `true`, overwrites existing content; if `false`, only copies to empty fields (default: `false`)

#### showTranslateButton

Enable automatic translation using Google Translate API. This adds translate buttons to each language field:

```php
TranslatableContainer::make(
    Forms\Components\TextInput::make('title')
)
    ->showTranslateButton()           // Translate only to empty fields
    ->showTranslateButton(true, true) // Translate to all fields (overwrite existing)
```

**Parameters:**
- **First parameter**: Enable/disable translation functionality (default: `true`)
- **Second parameter**: `overwriteFilled` - if `true`, translates to all languages (overwrites existing); if `false`, only translates to empty languages (default: `false`)

**Configuration:**

The plugin uses Laravel's standard services configuration. Add to your `.env` file:
```env
GOOGLE_TRANSLATE_API_KEY=your_google_translate_api_key_here
```

Or add to your `config/services.php`:
```php
'google' => [
    'translate_key' => env('GOOGLE_TRANSLATE_API_KEY'),
],
```

Get your Google Translate API key from [Google Cloud Console](https://console.cloud.google.com/apis/credentials)

**How it works:**
- Each language field gets a "Translate" button in the hint area
- Clicking the button translates the current field's text to other languages
- Behavior depends on the `overwriteFilled` parameter:
  - `false` (default): Only translates to empty languages
  - `true`: Translates to all languages (overwrites existing content)
- Uses Google Translate API for high-quality translations

**Component Behavior:**
- Main component (first language) retains its helper text and hints
- Additional language components have helper text and hints removed for cleaner UI
- All actions (copy/translate) appear as hint actions below each field

## Tipps & Hints

### Validation

If all of your locales are required and if your values do not pass the JS validation, then the variants will remain automatically expanded.

### afterStateUpdated

If you want to use "afterStateUpdated", you have to consider that the state path shifts by one level.
n addition, one must specify the locale which is located in the component's meta under the key "locale".

**Before**
```php
->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),
```

**After**
```php
->afterStateUpdated(fn (Set $set, Component $component, ?string $state) => $set('../slug.' . $component->getMeta('locale'), Str::slug($state))),
```

### Empty translations

![Screenshot](https://raw.githubusercontent.com/mvenghaus/filament-plugin-translatable-inline/main/docs/images/screenshot.png)

As you can see in the screenshot, the "nl" is not filled and therefore not marked.

# Contact
If you any questions or you find a bug, please [contact me via email](mailto:support@inklammern.de).
<?php

namespace App\Traits;

use Illuminate\Support\Str;
use Spatie\Translatable\Events\TranslationHasBeenSet;
use Spatie\Translatable\HasTranslations;

trait HasTranslationsFixed
{
    use HasTranslations {
        HasTranslations::setTranslation as faultySetTranslation;
    }

    public function setTranslation(string $key, string $locale, $value): self
    {
        $this->guardAgainstNonTranslatableAttribute($key);

        $translations = $this->getTranslations($key);
        $oldValue = $translations[$locale] ?? '';

        if ($this->hasSetMutator($key)) {
            $method = 'set'.Str::studly($key).'Attribute';

            $this->{$method}($value, $locale);

            $value = $this->attributes[$key];
        }

        $translations[$locale] = $value;

        // fixes https://github.com/spatie/laravel-translatable/discussions/290
        $translations = array_filter($translations, function ($value) {
            return $value !== null;
        });

        // fixes https://github.com/spatie/laravel-translatable/discussions/273
        $this->attributes[$key] = json_encode($translations, JSON_UNESCAPED_UNICODE);
        // $this->attributes[$key] = $this->asJson($translations);

        event(new TranslationHasBeenSet($this, $key, $locale, $oldValue, $value));

        return $this;
    }
}

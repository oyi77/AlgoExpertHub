<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class LanguageTranslationRepository implements LanguageTranslationRepositoryInterface
{
    /**
     * Get translations for a language
     */
    public function getTranslations(string $languageCode): array
    {
        return DB::table('language_translations')
            ->where('language_code', $languageCode)
            ->get()
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Update a translation
     */
    public function updateTranslation(string $languageCode, string $key, string $value): bool
    {
        return DB::table('language_translations')
            ->where('language_code', $languageCode)
            ->where('key', $key)
            ->update([
                'value' => $value,
                'updated_at' => now()
            ]) > 0;
    }

    /**
     * Bulk update translations
     */
    public function bulkUpdateTranslations(string $languageCode, array $translations): int
    {
        $updated = 0;
        foreach ($translations as $translation) {
            if (isset($translation['key'], $translation['value'])) {
                $result = $this->updateTranslation($languageCode, $translation['key'], $translation['value']);
                if ($result) {
                    $updated++;
                }
            }
        }
        return $updated;
    }

    /**
     * Delete a translation key
     */
    public function deleteKey(string $languageCode, string $key): bool
    {
        return DB::table('language_translations')
            ->where('language_code', $languageCode)
            ->where('key', $key)
            ->delete() > 0;
    }

    /**
     * Get translation settings
     */
    public function getSettings(): ?object
    {
        return DB::table('translation_settings')->first();
    }

    /**
     * Update translation settings
     */
    public function updateSettings(array $data): bool
    {
        $existing = $this->getSettings();
        
        if ($existing) {
            return DB::table('translation_settings')
                ->where('id', $existing->id)
                ->update(array_merge($data, ['updated_at' => now()])) > 0;
        } else {
            return DB::table('translation_settings')->insert(
                array_merge($data, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    /**
     * Get translations for auto-translate
     */
    public function getTranslationsForAutoTranslate(string $languageCode, ?array $keys = null): Collection
    {
        $query = DB::table('language_translations')
            ->where('language_code', $languageCode);

        if ($keys !== null && !empty($keys)) {
            $query->whereIn('key', $keys);
        }

        return $query->get();
    }
}


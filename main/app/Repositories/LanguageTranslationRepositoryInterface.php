<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Collection;

interface LanguageTranslationRepositoryInterface
{
    /**
     * Get translations for a language
     *
     * @param string $languageCode
     * @return array<string, string>
     */
    public function getTranslations(string $languageCode): array;

    /**
     * Update a translation
     *
     * @param string $languageCode
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function updateTranslation(string $languageCode, string $key, string $value): bool;

    /**
     * Bulk update translations
     *
     * @param string $languageCode
     * @param array<string, string> $translations
     * @return int Number of updated translations
     */
    public function bulkUpdateTranslations(string $languageCode, array $translations): int;

    /**
     * Delete a translation key
     *
     * @param string $languageCode
     * @param string $key
     * @return bool
     */
    public function deleteKey(string $languageCode, string $key): bool;

    /**
     * Get translation settings
     *
     * @return object|null
     */
    public function getSettings(): ?object;

    /**
     * Update translation settings
     *
     * @param array<string, mixed> $data
     * @return bool
     */
    public function updateSettings(array $data): bool;

    /**
     * Get translations for auto-translate
     *
     * @param string $languageCode
     * @param array<string>|null $keys
     * @return Collection
     */
    public function getTranslationsForAutoTranslate(string $languageCode, ?array $keys = null): Collection;
}


<?php

declare(strict_types=1);

namespace Paysera\CheckoutSdk\Service;

use Paysera\CheckoutSdk\Entity\Collection\TranslationCollection;
use Paysera\CheckoutSdk\Entity\Translation;

class Translator
{
    public const DEFAULT_LANGUAGE = 'lt';

    public function getTitle(TranslatableTitleInterface $entity, string $language): string
    {
        return $this->translate($entity->getTitleTranslations(), $language)
            ?? $this->translate($entity->getTitleTranslations(), self::DEFAULT_LANGUAGE)
            ?? $entity->getFallbackTitle();
    }

    public function getLogo(TranslatableLogoInterface $entity, string $language): ?string
    {
        return $this->translate($entity->getLogos(), $language)
            ?? $this->translate($entity->getLogos(), self::DEFAULT_LANGUAGE);
    }

    /**
     * Get translation from collection. Uses specified language or fallback one.
     * @param TranslationCollection<Translation> $collection
     * @param string $language
     * @return string|null
     */
    public function translate(
        TranslationCollection $collection,
        string $language
    ): ?string {
        $translation = $this->getTranslationByLanguage($collection, $language);

        if ($translation === null) {
            return null;
        }

        return $translation->getValue();
    }

    /**
     * @param TranslationCollection<Translation> $collection
     * @param string $language
     * @return Translation|null
     */
    protected function getTranslationByLanguage(
        TranslationCollection $collection,
        string $language
    ): ?Translation {
        return $collection->filter(
            static fn (Translation $translation) => $translation->getLanguage() === $language
        )->get();
    }
}

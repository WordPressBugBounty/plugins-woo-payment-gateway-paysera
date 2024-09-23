<?php

declare(strict_types=1);

namespace Paysera\Helper;

class PostDataHelper
{
    public function normalizeDecimalSeparator(
        array $postData,
        array $decimalFields,
        string $separatorForNormalization
    ): array {
        foreach ($decimalFields as $field) {
            if (isset($postData[$field])) {
                $postData[$field] = str_replace($separatorForNormalization, '.', $postData[$field]);
            }
        }

        return $postData;
    }

    public function trimPostDataKeysPrefix(array $postData, string $prefix): array
    {
        $trimmedPostData = [];

        foreach ($postData as $key => $value) {
            $trimmedPostData[str_replace($prefix, '', $key)] = $value;
        }

        return $trimmedPostData;
    }
}

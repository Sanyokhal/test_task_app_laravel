<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CustomRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value->isValid()) {
            $fail("Помилка при ввантаженні");
            return;
        }

        if ($value->getSize() > $this->getMaxBytes()) {
            $fail("Файл завеликий.");
        }
    }

    private function getMaxBytes()
    {
        $uploadMax = $this->convertToBytes(ini_get('upload_max_filesize'));
        $postMax = $this->convertToBytes(ini_get('post_max_size'));

        return min($uploadMax, $postMax);
    }

    protected function convertToBytes(string $value): int
    {
        $unit = strtolower(substr($value, -1));
        $bytes = (int)$value;

        switch ($unit) {
            case 'g':
                $bytes *= 1024;
            case 'm':
                $bytes *= 1024;
            case 'k':
                $bytes *= 1024;
        }

        return $bytes;
    }
}

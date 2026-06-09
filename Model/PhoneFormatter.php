<?php
/**
 * MageShop | Entrega99
 *
 * @category MageShop
 * @package  Entrega99
 */

declare(strict_types=1);

namespace MageShop\Entrega99\Model;

/**
 * Brazilian phone formatter.
 * 99Entrega accepts phone in numeric form. We strip non-digits and, for BR,
 * ensure the country code (+55) is included when not present.
 */
class PhoneFormatter
{
    /**
     * Returns a digits-only phone string with country code prefix.
     * Examples:
     *   "(41) 99911-3080"  → "5541999113080"
     *   "+55 41 99911-3080" → "5541999113080"
     *   "11 99999-9999" (BR) → "5511999999999"
     *   "11999999999"      → "5511999999999"
     */
    public function format(?string $phone, string $countryId = 'BR'): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }

        if ($countryId === 'BR') {
            // 10 = landline w/ DDD (11 3333 4444), 11 = mobile w/ DDD (11 99999 9999)
            if (in_array(strlen($digits), [10, 11], true)) {
                return '55' . $digits;
            }
            // Already has country code: 13 digits typical, accept as is
            if (strlen($digits) >= 12 && str_starts_with($digits, '55')) {
                return $digits;
            }
        }

        return $digits;
    }

    /**
     * Strict digits-only (no country code), useful for fields that ask for
     * "DDD + number" without the +55.
     */
    public function digits(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        return $digits === '' ? null : $digits;
    }
}

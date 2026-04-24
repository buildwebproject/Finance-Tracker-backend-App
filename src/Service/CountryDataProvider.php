<?php

declare(strict_types=1);

namespace App\Service;

use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Intl\Countries;

class CountryDataProvider
{
    /**
     * @return array<int, array{name: string, iso2Code: string, iso3Code: ?string, dialCode: string, flagEmoji: ?string, currencyCode: ?string, currencyIcon: ?string}>
     */
    public function getAllCountryData(string $locale = 'en'): array
    {
        $names = Countries::getNames($locale);
        $phoneNumberUtil = PhoneNumberUtil::getInstance();

        $countries = [];
        foreach ($names as $iso2Code => $name) {
            $iso2Code = strtoupper((string) $iso2Code);
            if (!Countries::exists($iso2Code)) {
                continue;
            }

            $countryCode = $phoneNumberUtil->getCountryCodeForRegion($iso2Code);
            if ($countryCode <= 0) {
                continue;
            }

            $currencyCode = $this->resolveCurrencyCode($iso2Code);
            $countries[] = [
                'name' => trim((string) $name),
                'iso2Code' => $iso2Code,
                'iso3Code' => $this->resolveIso3Code($iso2Code),
                'dialCode' => '+'.$countryCode,
                'flagEmoji' => self::generateFlagEmoji($iso2Code),
                'currencyCode' => $currencyCode,
                'currencyIcon' => $this->resolveCurrencyIcon($currencyCode, $locale),
            ];
        }

        usort(
            $countries,
            static fn (array $left, array $right): int => [$left['name'], $left['iso2Code']] <=> [$right['name'], $right['iso2Code']]
        );

        return $countries;
    }

    public static function generateFlagEmoji(string $iso2Code): ?string
    {
        $iso2Code = strtoupper(trim($iso2Code));
        if (2 !== strlen($iso2Code) || 1 !== preg_match('/^[A-Z]{2}$/', $iso2Code)) {
            return null;
        }

        $base = 127397;

        return mb_chr(ord($iso2Code[0]) + $base).mb_chr(ord($iso2Code[1]) + $base);
    }

    private function resolveIso3Code(string $iso2Code): ?string
    {
        try {
            $iso3Code = Countries::getAlpha3Code($iso2Code);
        } catch (\Throwable) {
            return null;
        }

        $iso3Code = strtoupper(trim((string) $iso3Code));

        return '' === $iso3Code ? null : $iso3Code;
    }

    private function resolveCurrencyCode(string $iso2Code): ?string
    {
        try {
            $currencies = Currencies::forCountry($iso2Code, true, true);
        } catch (\Throwable) {
            return null;
        }

        if ([] === $currencies) {
            return null;
        }

        $currencyCode = strtoupper(trim((string) $currencies[0]));

        return '' === $currencyCode ? null : $currencyCode;
    }

    private function resolveCurrencyIcon(?string $currencyCode, string $locale): ?string
    {
        if (null === $currencyCode) {
            return null;
        }

        try {
            $symbol = trim(Currencies::getSymbol($currencyCode, $locale));
        } catch (\Throwable) {
            return null;
        }

        if ('' === $symbol || strtoupper($symbol) === $currencyCode) {
            return null;
        }

        return $symbol;
    }
}

<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Country;
use App\Repository\CountryRepository;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/countries')]
final class CountryController extends AbstractController
{
    public function __construct(
        private readonly CountryRepository $countryRepository,
        #[Autowire(service: 'cache.api_responses')]
        private readonly TagAwareAdapterInterface $apiResponseCache,
    ) {
    }

    #[Route('', name: 'api_countries_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $search = $this->normalizeNullableString($request->query->get('q'));
        $activeOnly = $this->normalizeBool($request->query->get('active_only'), true);
        $limit = $this->normalizeInt($request->query->get('limit'), 300, 1, 500);

        $cacheKey = sprintf('api.countries.%s', sha1(json_encode([
            'q' => $search,
            'active_only' => $activeOnly,
            'limit' => $limit,
        ], JSON_THROW_ON_ERROR)));

        try {
            $payload = $this->apiResponseCache->get($cacheKey, function (CacheItem $item) use ($search, $activeOnly, $limit): array {
                $item->tag(['api.countries']);
                $item->expiresAfter(600);

                $countries = $this->countryRepository->findForApi($search, $activeOnly, $limit);

                return [
                    'countries' => array_map(
                        static fn (Country $country): array => [
                            'id' => $country->getId(),
                            'name' => $country->getName(),
                            'iso2_code' => $country->getIso2Code(),
                            'iso3_code' => $country->getIso3Code(),
                            'dial_code' => $country->getDialCode(),
                            'flag_emoji' => $country->getFlagEmoji(),
                            'currency_code' => $country->getCurrencyCode(),
                            'currency_icon' => $country->getCurrencyIcon(),
                            'is_active' => $country->isActive(),
                        ],
                        $countries
                    ),
                    'meta' => [
                        'count' => count($countries),
                        'search' => $search,
                        'active_only' => $activeOnly,
                        'limit' => $limit,
                    ],
                ];
            });
        } catch (\Throwable) {
            $countries = $this->countryRepository->findForApi($search, $activeOnly, $limit);

            $payload = [
                'countries' => array_map(
                    static fn (Country $country): array => [
                        'id' => $country->getId(),
                        'name' => $country->getName(),
                        'iso2_code' => $country->getIso2Code(),
                        'iso3_code' => $country->getIso3Code(),
                        'dial_code' => $country->getDialCode(),
                        'flag_emoji' => $country->getFlagEmoji(),
                        'currency_code' => $country->getCurrencyCode(),
                        'currency_icon' => $country->getCurrencyIcon(),
                        'is_active' => $country->isActive(),
                    ],
                    $countries
                ),
                'meta' => [
                    'count' => count($countries),
                    'search' => $search,
                    'active_only' => $activeOnly,
                    'limit' => $limit,
                ],
            ];
        }

        return $this->json($payload);
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (null === $value || !\is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return '' === $value ? null : $value;
    }

    private function normalizeBool(mixed $value, bool $default): bool
    {
        if (null === $value) {
            return $default;
        }

        if (\is_bool($value)) {
            return $value;
        }

        if (!\is_scalar($value)) {
            return $default;
        }

        $value = strtolower(trim((string) $value));

        if (in_array($value, ['1', 'true', 'yes'], true)) {
            return true;
        }

        if (in_array($value, ['0', 'false', 'no'], true)) {
            return false;
        }

        return $default;
    }

    private function normalizeInt(mixed $value, int $default, int $min, int $max): int
    {
        if (null === $value) {
            return $default;
        }

        if (\is_int($value)) {
            return max($min, min($max, $value));
        }

        if (!\is_scalar($value) || !is_numeric((string) $value)) {
            return $default;
        }

        return max($min, min($max, (int) $value));
    }
}

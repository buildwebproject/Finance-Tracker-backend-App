<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\FinanceCategory;
use App\Repository\FinanceCategoryRepository;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/categories')]
final class CategoryController extends AbstractController
{
    use ApiResponseTrait;

    public function __construct(
        private readonly FinanceCategoryRepository $categoryRepository,
        #[Autowire(service: 'cache.api_responses')]
        private readonly TagAwareAdapterInterface $apiResponseCache,
    ) {
    }

    #[Route('', name: 'api_category_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $search = $this->normalizeQueryString($request->query->get('q'));
        $activeOnly = $this->normalizeQueryBool($request->query->get('active_only'), true);
        $limit = min(500, max(1, $this->normalizeQueryInt($request->query->get('limit')) ?? 300));

        $cacheKey = sprintf('api.categories.%s', sha1(json_encode([
            'q' => $search,
            'active_only' => $activeOnly,
            'limit' => $limit,
        ], JSON_THROW_ON_ERROR)));

        try {
            $payload = $this->apiResponseCache->get($cacheKey, function (CacheItem $item) use ($search, $activeOnly, $limit): array {
                $item->tag(['api.categories']);
                $item->expiresAfter(600);

                $categories = $this->categoryRepository->findForApi($search, $activeOnly, $limit);

                return [
                    'items' => array_map(fn (FinanceCategory $category): array => $this->serializeCategory($category), $categories),
                    'meta' => [
                        'count' => count($categories),
                        'search' => $search,
                        'active_only' => $activeOnly,
                        'limit' => $limit,
                    ],
                ];
            });
        } catch (\Throwable) {
            $categories = $this->categoryRepository->findForApi($search, $activeOnly, $limit);
            $payload = [
                'items' => array_map(fn (FinanceCategory $category): array => $this->serializeCategory($category), $categories),
                'meta' => [
                    'count' => count($categories),
                    'search' => $search,
                    'active_only' => $activeOnly,
                    'limit' => $limit,
                ],
            ];
        }

        return $this->successResponse('Categories fetched successfully.', $payload);
    }

    public function serializeCategory(FinanceCategory $category): array
    {
        return [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'icon' => $category->getIconName(),
            'icon_url' => null !== $category->getIconName() ? '/uploads/categories/'.$category->getIconName() : null,
            'is_active' => $category->isActive(),
        ];
    }

    private function normalizeQueryBool(mixed $value, bool $default): bool
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
}

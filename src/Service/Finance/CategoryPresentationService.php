<?php

declare(strict_types=1);

namespace App\Service\Finance;

final class CategoryPresentationService
{
    /**
     * @var array<string, array{icon: string, color: string, title: string}>
     */
    private const CATEGORY_MAP = [
        'food' => ['icon' => 'restaurant', 'color' => '#E67E22', 'title' => 'Food'],
        'transport' => ['icon' => 'directions_car', 'color' => '#3498DB', 'title' => 'Transport'],
        'shopping' => ['icon' => 'shopping_bag', 'color' => '#9B59B6', 'title' => 'Shopping'],
        'health' => ['icon' => 'favorite', 'color' => '#E74C3C', 'title' => 'Health'],
        'salary' => ['icon' => 'payments', 'color' => '#27AE60', 'title' => 'Salary'],
        'freelance' => ['icon' => 'work', 'color' => '#16A085', 'title' => 'Freelance'],
        'bills' => ['icon' => 'receipt_long', 'color' => '#F39C12', 'title' => 'Bills'],
        'entertainment' => ['icon' => 'movie', 'color' => '#8E44AD', 'title' => 'Entertainment'],
        'education' => ['icon' => 'school', 'color' => '#2980B9', 'title' => 'Education'],
        'gift' => ['icon' => 'card_giftcard', 'color' => '#C0392B', 'title' => 'Gift'],
    ];

    public function getCategoryPresentation(?string $category): array
    {
        $normalized = mb_strtolower(trim((string) $category));
        if (isset(self::CATEGORY_MAP[$normalized])) {
            return self::CATEGORY_MAP[$normalized];
        }

        return [
            'icon' => 'account_balance_wallet',
            'color' => '#7F8C8D',
            'title' => '' !== $normalized ? ucwords($normalized) : 'Transaction',
        ];
    }

    /**
     * @return list<string>
     */
    public function defaultCategories(): array
    {
        return array_values(array_unique(array_map(
            static fn (string $key): string => self::CATEGORY_MAP[$key]['title'],
            array_keys(self::CATEGORY_MAP)
        )));
    }
}


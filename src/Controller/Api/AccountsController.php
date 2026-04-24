<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\Finance\SummaryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/accounts')]
final class AccountsController extends AbstractController
{
    use ApiResponseTrait;

    public function __construct(
        private readonly SummaryService $summaryService,
    ) {
    }

    #[Route('/overview', name: 'api_accounts_overview', methods: ['GET'])]
    public function overview(): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        return $this->successResponse('Accounts overview fetched successfully.', $this->summaryService->buildAccountsOverview($user));
    }
}


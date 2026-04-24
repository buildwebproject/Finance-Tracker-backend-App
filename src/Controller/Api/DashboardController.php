<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\Finance\SummaryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dashboard')]
final class DashboardController extends AbstractController
{
    use ApiResponseTrait;

    public function __construct(
        private readonly SummaryService $summaryService,
    ) {
    }

    #[Route('/summary', name: 'api_dashboard_summary', methods: ['GET'])]
    public function summary(): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        return $this->successResponse('Dashboard summary fetched successfully.', $this->summaryService->buildDashboardSummary($user));
    }
}


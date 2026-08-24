<?php

namespace App\Http\Controllers;

use App\Http\Requests\DistributeLeadsRequest;
use App\Services\LeadDistributionService;
use Illuminate\Http\JsonResponse;

class LeadDistributionController extends Controller
{
    public function __construct(
        private readonly LeadDistributionService $distributionService,
    ) {
    }

    public function __invoke(DistributeLeadsRequest $request): JsonResponse
    {
        $distributed = $this->distributionService->distribute();

        return response()->json([
            'distributed' => $distributed,
        ]);
    }
}

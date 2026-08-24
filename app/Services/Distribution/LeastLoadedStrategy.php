<?php

namespace App\Services\Distribution;

use App\Contracts\DistributionStrategy;
use App\DTO\LeadDistribution;
use App\Models\Lead;
use App\Models\Manager;
use Illuminate\Support\Collection;

class LeastLoadedStrategy implements DistributionStrategy
{
    /**
     * @param Collection<int, Manager> $managers
     * @param array<int, int> $loads
     * @param Collection<int, Lead> $leads
     * @return Collection<int, LeadDistribution>
     */
    public function distribute(
        Collection $managers,
        array $loads,
        Collection $leads,
    ): Collection {
        if ($managers->isEmpty() || $leads->isEmpty()) {
            return collect();
        }

        $distributions = collect();

        foreach ($leads as $lead) {
            $managerId = array_key_first(
                array_filter(
                    $loads,
                    fn (int $load) => $load === min($loads)
                )
            );

            $manager = $managers->firstWhere('id', $managerId);

            $distributions->push(
                new LeadDistribution(
                    lead: $lead,
                    manager: $manager,
                )
            );

            $loads[$managerId]++;
        }

        return $distributions;
    }
}

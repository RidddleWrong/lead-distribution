<?php

namespace App\Contracts;

use App\DTO\LeadDistribution;
use App\Models\Lead;
use App\Models\Manager;
use Illuminate\Support\Collection;

interface DistributionStrategy
{
    /**
     * @param Collection<int, Manager> $managers
     * @param array<int, int> $loads
     * @param Collection<int, Lead> $leads
     *
     * @return Collection<int, LeadDistribution>
     */
    public function distribute(
        Collection $managers,
        array $loads,
        Collection $leads,
    ): Collection;
}

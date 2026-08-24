<?php

namespace App\Services;

use App\Events\LeadDistributed;
use App\Contracts\DistributionStrategy;
use App\DTO\LeadDistribution;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\Manager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeadDistributionService
{
    public function __construct(
        private readonly DistributionStrategy $strategy,
    ) {
    }

    /**
     * @return Collection<int, LeadDistribution>
     */
    public function createDistributionPlan(Collection $leads): Collection
    {
        $managers = Manager::query()
            ->where('is_active', true)
            ->withOpenLeadsCount()
            ->get();

        $loads = $managers
            ->mapWithKeys(
                fn (Manager $manager) => [
                    $manager->id => $manager->open_leads_count,
                ]
            )
            ->all();

        return $this->strategy->distribute(
            managers: $managers,
            loads: $loads,
            leads: $leads,
        );
    }

    public function distribute(): int
    {
        return DB::transaction(function (): int {
            $leads = Lead::query()
                ->where('status', LeadStatus::NEW)
                ->lockForUpdate()
                ->get();

            if ($leads->isEmpty()) {
                return 0;
            }

            $plan = $this->createDistributionPlan($leads);

            foreach ($plan as $distribution) {
                $this->applyDistribution($distribution);
            }

            return $plan->count();
        });
    }

    private function applyDistribution(LeadDistribution $distribution): void
    {
        $lead = $distribution->lead;
        $manager = $distribution->manager;

        $oldStatus = $lead->status;

        $lead->manager_id = $manager->id;
        $lead->status = LeadStatus::IN_PROGRESS;
        $lead->save();

        $lead->statusHistories()->create([
            'from_status' => $oldStatus,
            'to_status' => LeadStatus::IN_PROGRESS,
        ]);

        LeadDistributed::dispatch(
            $lead,
            $manager,
        );
    }

}

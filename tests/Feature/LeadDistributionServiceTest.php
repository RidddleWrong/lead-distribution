<?php

namespace Tests\Feature;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\Manager;
use App\Services\LeadDistributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Jobs\ProcessDistributedLead;
use Illuminate\Support\Facades\Queue;


class LeadDistributionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_creates_distribution_plan_for_new_leads(): void
    {
        $managerA = Manager::factory()->create([
            'is_active' => true,
        ]);

        $managerB = Manager::factory()->create([
            'is_active' => true,
        ]);

        $managerC = Manager::factory()->create([
            'is_active' => true,
        ]);

        Lead::factory()->count(12)->create([
            'manager_id' => $managerA->id,
            'status' => LeadStatus::IN_PROGRESS,
        ]);

        Lead::factory()->count(4)->create([
            'manager_id' => $managerB->id,
            'status' => LeadStatus::IN_PROGRESS,
        ]);

        Lead::factory()->count(8)->create([
            'manager_id' => $managerC->id,
            'status' => LeadStatus::IN_PROGRESS,
        ]);


        $leads = Lead::factory()->count(9)->create([
            'status' => LeadStatus::NEW,
        ]);

        $plan = app(LeadDistributionService::class)
            ->createDistributionPlan($leads);

        $result = $plan
            ->groupBy(fn ($distribution) => $distribution->manager->id)
            ->map(fn ($items) => $items->count());

        $this->assertSame(7, $result[$managerB->id]);
        $this->assertSame(2, $result[$managerC->id]);
        $this->assertArrayNotHasKey(
            $managerA->id,
            $result->all()
        );
    }

    public function test_service_distributes_leads_and_creates_status_history(): void
    {
        $managerA = Manager::factory()->create([
            'is_active' => true,
        ]);

        $managerB = Manager::factory()->create([
            'is_active' => true,
        ]);

        $leads = Lead::factory()->count(5)->create([
            'status' => LeadStatus::NEW,
            'manager_id' => null,
        ]);

        $distributed = app(LeadDistributionService::class)->distribute();

        $this->assertSame(5, $distributed);

        $leads->each(function (Lead $lead) {
            $lead->refresh();

            $this->assertNotNull($lead->manager_id);
            $this->assertSame(
                LeadStatus::IN_PROGRESS,
                $lead->status
            );

            $this->assertDatabaseHas('lead_status_histories', [
                'lead_id' => $lead->id,
                'from_status' => LeadStatus::NEW->value,
                'to_status' => LeadStatus::IN_PROGRESS->value,
            ]);
        });
    }

    public function test_distributed_lead_is_sent_to_queue(): void
    {
        Queue::fake();

        $manager = Manager::factory()->create([
            'is_active' => true,
        ]);

        $lead = Lead::factory()->create([
            'status' => LeadStatus::NEW,
            'manager_id' => null,
        ]);

        app(LeadDistributionService::class)->distribute();

        Queue::assertPushed(
            ProcessDistributedLead::class,
            function (ProcessDistributedLead $job) use ($lead): bool {
                return $job->lead->id === $lead->id;
            }
        );
    }

    public function test_distribution_returns_zero_when_there_are_no_new_leads(): void
    {
        Manager::factory()->create([
            'is_active' => true,
        ]);

        $distributed = app(LeadDistributionService::class)->distribute();

        $this->assertSame(0, $distributed);
    }

    public function test_new_leads_remain_undistributed_when_there_are_no_active_managers(): void
    {
        Lead::factory()->count(3)->create([
            'status' => LeadStatus::NEW,
            'manager_id' => null,
        ]);

        $distributed = app(LeadDistributionService::class)->distribute();

        $this->assertSame(0, $distributed);

        $this->assertDatabaseCount('lead_status_histories', 0);

        $this->assertSame(
            3,
            Lead::query()
                ->where('status', LeadStatus::NEW)
                ->count()
        );
    }
}

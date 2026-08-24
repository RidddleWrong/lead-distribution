<?php

namespace Tests\Feature;

use App\Enums\LeadStatus;
use App\Jobs\ProcessDistributedLead;
use App\Models\Lead;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LeadDistributionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_distributes_new_leads(): void
    {
        Queue::fake();

        $managerA = Manager::factory()->create([
            'is_active' => true,
        ]);

        $managerB = Manager::factory()->create([
            'is_active' => true,
        ]);

        Lead::factory()->count(5)->create([
            'manager_id' => $managerA->id,
            'status' => LeadStatus::IN_PROGRESS,
        ]);

        Lead::factory()->count(2)->create([
            'manager_id' => $managerB->id,
            'status' => LeadStatus::IN_PROGRESS,
        ]);

        Lead::factory()->count(3)->create([
            'status' => LeadStatus::NEW,
            'manager_id' => null,
        ]);

        $response = $this->postJson('/api/leads/distribute');

        $response
            ->assertOk()
            ->assertJson([
                'distributed' => 3,
            ]);

        $this->assertDatabaseCount('lead_status_histories', 3);

        $this->assertDatabaseMissing('leads', [
            'status' => LeadStatus::NEW->value,
        ]);

        Queue::assertPushed(ProcessDistributedLead::class, 3);
    }

    public function test_repeated_endpoint_call_does_not_redistribute_leads(): void
    {
        Queue::fake();

        Manager::factory()->create([
            'is_active' => true,
        ]);

        Lead::factory()->count(3)->create([
            'status' => LeadStatus::NEW,
            'manager_id' => null,
        ]);

        $firstResponse = $this->postJson('/api/leads/distribute');

        $firstResponse
            ->assertOk()
            ->assertJson([
                'distributed' => 3,
            ]);

        $secondResponse = $this->postJson('/api/leads/distribute');

        $secondResponse
            ->assertOk()
            ->assertJson([
                'distributed' => 0,
            ]);

        $this->assertDatabaseCount('lead_status_histories', 3);

        Queue::assertPushed(ProcessDistributedLead::class, 3);
    }

    public function test_inactive_managers_do_not_receive_new_leads(): void
    {
        Queue::fake();

        $inactiveManager = Manager::factory()->create([
            'is_active' => false,
        ]);

        $activeManager = Manager::factory()->create([
            'is_active' => true,
        ]);

        Lead::factory()->count(5)->create([
            'manager_id' => $inactiveManager->id,
            'status' => LeadStatus::IN_PROGRESS,
        ]);

        $newLeads = Lead::factory()->count(3)->create([
            'status' => LeadStatus::NEW,
            'manager_id' => null,
        ]);

        $response = $this->postJson('/api/leads/distribute');

        $response
            ->assertOk()
            ->assertJson([
                'distributed' => 3,
            ]);

        foreach ($newLeads as $lead) {
            $lead->refresh();

            $this->assertSame(
                $activeManager->id,
                $lead->manager_id
            );
        }

        $this->assertDatabaseHas('managers', [
            'id' => $inactiveManager->id,
            'is_active' => false,
        ]);
    }

    public function test_endpoint_returns_zero_when_there_are_no_active_managers(): void
    {
        Lead::factory()->count(3)->create([
            'status' => LeadStatus::NEW,
            'manager_id' => null,
        ]);

        $response = $this->postJson('/api/leads/distribute');

        $response
            ->assertOk()
            ->assertJson([
                'distributed' => 0,
            ]);

        $this->assertDatabaseCount('lead_status_histories', 0);
    }
}

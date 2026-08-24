<?php

namespace Tests\Feature;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerOpenLeadsCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_open_leads_count_includes_only_open_leads(): void
    {
        $manager = Manager::factory()->create();

        Lead::factory()->create([
            'manager_id' => $manager->id,
            'status' => LeadStatus::NEW,
        ]);

        Lead::factory()->create([
            'manager_id' => $manager->id,
            'status' => LeadStatus::IN_PROGRESS,
        ]);

        Lead::factory()->create([
            'manager_id' => $manager->id,
            'status' => LeadStatus::DONE,
        ]);

        Lead::factory()->create([
            'manager_id' => $manager->id,
            'status' => LeadStatus::CANCELED,
        ]);

        $manager = Manager::withOpenLeadsCount()
            ->findOrFail($manager->id);

        $this->assertSame(2, $manager->open_leads_count);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\Manager;
use App\Models\LeadStatusHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ManagerStatisticsSqlTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_statistics_sql_returns_expected_data(): void
    {
        $manager = Manager::factory()->create();

        $openLead = Lead::factory()->create([
            'manager_id' => $manager->id,
            'status' => LeadStatus::IN_PROGRESS,
        ]);

        LeadStatusHistory::create([
            'lead_id' => $openLead->id,
            'from_status' => LeadStatus::NEW,
            'to_status' => LeadStatus::IN_PROGRESS,
            'created_at' => now()->subHours(2),
        ]);

        $doneLead = Lead::factory()->create([
            'manager_id' => $manager->id,
            'status' => LeadStatus::DONE,
        ]);

        LeadStatusHistory::create([
            'lead_id' => $doneLead->id,
            'from_status' => LeadStatus::NEW,
            'to_status' => LeadStatus::IN_PROGRESS,
            'created_at' => now()->subHours(5),
        ]);

        LeadStatusHistory::create([
            'lead_id' => $doneLead->id,
            'from_status' => LeadStatus::IN_PROGRESS,
            'to_status' => LeadStatus::DONE,
            'created_at' => now()->subHours(2),
        ]);

        $sql = file_get_contents(
            database_path('sql/manager_statistics.sql')
        );

        $result = DB::select($sql);

        $managerResult = collect($result)
            ->firstWhere('id', $manager->id);

        $this->assertNotNull($managerResult);

        $this->assertEquals(
            1,
            $managerResult->open_leads_count
        );

        $this->assertEquals(
            1,
            $managerResult->completed_leads_last_30_days
        );
    }
}

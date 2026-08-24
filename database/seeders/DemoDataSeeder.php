<?php

namespace Database\Seeders;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\Manager;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $managers = Manager::factory()->count(3)->create([
            'is_active' => true,
        ]);

        Lead::factory()->count(5)->create([
            'status' => LeadStatus::IN_PROGRESS,
            'manager_id' => $managers[0]->id,
        ]);

        Lead::factory()->count(3)->create([
            'status' => LeadStatus::IN_PROGRESS,
            'manager_id' => $managers[1]->id,
        ]);

        Lead::factory()->count(2)->create([
            'status' => LeadStatus::IN_PROGRESS,
            'manager_id' => $managers[2]->id,
        ]);

        Lead::factory()->count(9)->create([
            'status' => LeadStatus::NEW,
            'manager_id' => null,
        ]);
    }
}

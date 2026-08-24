<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Manager;
use App\Services\Distribution\LeastLoadedStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeastLoadedStrategyTest extends TestCase
{
    use RefreshDatabase;

    public function test_least_loaded_strategy_distributes_leads_evenly(): void
    {
        $managers = Manager::factory()->count(3)->create();

        $leads = Lead::factory()->count(9)->create();

        $loads = [
            $managers[0]->id => 12,
            $managers[1]->id => 4,
            $managers[2]->id => 8,
        ];

        $strategy = new LeastLoadedStrategy();

        $distributions = $strategy->distribute(
            managers: $managers,
            loads: $loads,
            leads: $leads,
        );

        $result = $distributions
            ->groupBy(fn ($distribution) => $distribution->manager->id)
            ->map(fn ($items) => $items->count());

        $this->assertSame(7, $result[$managers[1]->id]);
        $this->assertSame(2, $result[$managers[2]->id]);
        $this->assertArrayNotHasKey($managers[0]->id, $result->all());
    }

    public function test_least_loaded_strategy_distributes_between_managers_with_equal_load(): void
    {
        $managers = Manager::factory()->count(3)->create();

        $leads = Lead::factory()->count(6)->create();

        $loads = [
            $managers[0]->id => 5,
            $managers[1]->id => 5,
            $managers[2]->id => 10,
        ];

        $strategy = new LeastLoadedStrategy();

        $distributions = $strategy->distribute(
            managers: $managers,
            loads: $loads,
            leads: $leads,
        );

        $result = $distributions
            ->groupBy(fn ($distribution) => $distribution->manager->id)
            ->map(fn ($items) => $items->count());

        $this->assertSame(3, $result[$managers[0]->id]);
        $this->assertSame(3, $result[$managers[1]->id]);
        $this->assertArrayNotHasKey($managers[2]->id, $result->all());
    }

    public function test_least_loaded_strategy_returns_empty_collection_when_there_are_no_leads(): void
    {
        $managers = Manager::factory()->count(3)->create();

        $strategy = new LeastLoadedStrategy();

        $result = $strategy->distribute(
            managers: $managers,
            loads: [
                $managers[0]->id => 5,
                $managers[1]->id => 3,
                $managers[2]->id => 7,
            ],
            leads: collect(),
        );

        $this->assertEmpty($result);
    }

}

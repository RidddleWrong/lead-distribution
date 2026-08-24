<?php

namespace App\Providers;

use App\Contracts\DistributionStrategy;
use App\Services\Distribution\LeastLoadedStrategy;
use Illuminate\Support\ServiceProvider;

class DistributionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            DistributionStrategy::class,
            function () {
                return match (config('distribution.strategy')) {
                    'least_loaded' => app(LeastLoadedStrategy::class),

                    default => throw new \InvalidArgumentException(
                        'Unknown distribution strategy: ' . config('distribution.strategy')
                    ),
                };
            }
        );
    }

    public function boot(): void
    {
        //
    }
}

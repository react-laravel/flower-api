<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set default database query timeout (in seconds)
        // Prevents slow queries from hanging indefinitely
        $queryTimeout = (int) env('DB_QUERY_TIMEOUT', 10);

        DB::connection()->getPdo()?->setAttribute(\PDO::ATTR_TIMEOUT, $queryTimeout);

        // Log slow queries (> 1 second) for monitoring
        DB::listen(function (QueryExecuted $query) use ($queryTimeout) {
            if ($query->time > 1000) { // > 1 second
                Log::warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time_ms' => $query->time,
                ]);
            }
        });
    }
}

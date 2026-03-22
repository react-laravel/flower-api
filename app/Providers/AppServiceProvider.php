<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Flower;
use App\Models\Knowledge;
use App\Models\SiteSetting;
use App\Policies\CategoryPolicy;
use App\Policies\FlowerPolicy;
use App\Policies\KnowledgePolicy;
use App\Policies\SiteSettingPolicy;
use App\Policies\UploadPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        Flower::class => FlowerPolicy::class,
        Category::class => CategoryPolicy::class,
        Knowledge::class => KnowledgePolicy::class,
        SiteSetting::class => SiteSettingPolicy::class,
    ];

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
        // Register policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Register UploadPolicy for non-model authorization
        Gate::define('upload', [UploadPolicy::class, 'create']);
        Gate::define('upload.delete', [UploadPolicy::class, 'delete']);
    }
}

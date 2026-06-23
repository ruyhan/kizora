<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Observers\AuditObserver;
use App\Support\PageContent;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PageContent::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Auto-audit key models (extend by adding more ::observe calls).
        foreach ([Product::class, Category::class, Order::class, User::class, Role::class] as $model) {
            $model::observe(AuditObserver::class);
        }
    }
}

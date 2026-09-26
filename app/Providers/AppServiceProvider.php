<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\EmailLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Observers\AuditObserver;
use App\Services\Email\EmailSettingService;
use App\Support\PageContent;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
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

        // Apply admin-managed mail settings (SMTP + branding) over runtime config.
        // Guarded: the settings table may not exist yet during a fresh migrate.
        try {
            app(EmailSettingService::class)->apply();
        } catch (\Throwable) {
            // ignore — falls back to .env mail config
        }

        // A Privilege Card issued to this email may be waiting to be activated
        // (covers accounts that already existed when the admin created the card).
        Event::listen(\Illuminate\Auth\Events\Login::class, function ($event) {
            rescue(fn () => app(\App\Services\PrivilegeClaimService::class)->attempt($event->user), report: true);
        });

        // Throttle outgoing transactional email (queue middleware: RateLimited('emails')).
        RateLimiter::for('emails', fn () => Limit::perMinute(30));

        // Flip an email log to "sent" once the message is actually dispatched.
        Event::listen(MessageSent::class, function (MessageSent $event) {
            $header = $event->message->getHeaders()->get('X-Kizora-Log');
            if (! $header) {
                return;
            }
            $id = (int) $header->getBodyAsString();
            if ($id) {
                EmailLog::where('id', $id)->where('status', 'queued')
                    ->update(['status' => 'sent', 'sent_at' => now()]);
            }
        });
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\Cart;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Review;
use App\Models\Wishlist;
use App\Services\Recaptcha\RecaptchaService;
use App\Support\CatalogCache;
use App\Support\ChatbotSettings;
use App\Support\DeliverySettings;
use App\Support\PageContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $cartCount = 0;
        $wishlistCount = 0;

        if (Auth::check()) {
            $cartCount = Cart::where('user_id', Auth::id())->sum('quantity');
            $wishlistCount = Wishlist::where('user_id', Auth::id())->count();
        } else {
            $cartCount = Cart::where('session_id', $request->session()->getId())->sum('quantity');
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => Auth::check() ? (function () {
                    $u = Auth::user();
                    $staff = $u->canAccessAdmin();
                    $privilegeStatus = $u->privilegeMembership()->value('status');
                    return [
                        'id' => $u->id,
                        'name' => $u->name,
                        'email' => $u->email,
                        'role' => $u->role?->slug,
                        'role_name' => $u->role?->name,
                        'avatar' => $u->avatar,
                        'is_super' => $u->isSuperAdmin(),
                        'is_staff' => $staff,
                        'permissions' => $staff ? $u->permissionSlugs() : [],
                        // pending | approved | rejected | revoked | null — drives the account "Privilege Member" link
                        'privilege_status' => $privilegeStatus,
                        // % off shown on product prices for approved members (0 = regular prices)
                        'member_discount' => $privilegeStatus === 'approved'
                            ? app(\App\Support\PrivilegeCardSettings::class)->discountPercent()
                            : 0,
                        // Reward-point balance for customers without a Privilege Card (null = hide the badge)
                        'reward_points' => $privilegeStatus !== 'approved'
                            && app(\App\Support\PointSettings::class)->get()['enabled']
                            ? (int) $u->reward_points
                            : null,
                    ];
                })() : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'invite_link' => $request->session()->get('invite_link'),
            ],
            'cart_count' => $cartCount,
            'wishlist_count' => $wishlistCount,
            // Sidebar notification badges — staff/admins only
            'newOrdersCount'    => (Auth::check() && Auth::user()->isAdmin()) ? Order::web()->where('status', 'pending')->count() : 0,
            'unreadMessages'    => (Auth::check() && Auth::user()->isAdmin()) ? ContactMessage::where('is_read', false)->count() : 0,
            'newChatbotOrders'  => (Auth::check() && Auth::user()->isAdmin()) ? Order::chatbot()->where('status', 'pending')->count() : 0,
            'newReviewsCount'   => (Auth::check() && Auth::user()->isAdmin()) ? Review::whereNull('seen_at')->count() : 0,
            'pendingMemberships' => (Auth::check() && Auth::user()->isAdmin() && Auth::user()->hasPermission('membership.view'))
                ? \App\Models\PrivilegeMembership::where('status', 'pending')->count()
                : 0,
            'lowStockCount'     => (Auth::check() && Auth::user()->isAdmin())
                ? \App\Models\Product::where('is_active', true)
                    ->where('stock', '<=', max(1, (int) data_get(app(PageContent::class)->get('settings'), 'low_stock_threshold', 10)))
                    ->count()
                : 0,
            'recaptcha' => app(RecaptchaService::class)->publicConfig(),
            // Editable content (JSON CMS) shared site-wide
            'site' => app(PageContent::class)->get('settings'),
            'footer' => app(PageContent::class)->get('footer'),
            'header' => app(PageContent::class)->get('header'),
            'megamenu' => app(PageContent::class)->get('megamenu'),
            'productSettings' => app(PageContent::class)->get('product'),
            'delivery' => app(DeliverySettings::class)->publicConfig(),
            'chatbot' => app(ChatbotSettings::class)->publicConfig(),
            // Category hierarchy for mega menu / mobile drawer / shop facet
            'categoryTree' => app(CatalogCache::class)->remember('nav:tree', fn () => Category::navTree()),
        ]);
    }
}

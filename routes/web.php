<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\PaymentCallbackController;
use App\Http\Controllers\Admin\PaymentGatewayController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\MessageController as AdminMessageController;
use App\Http\Controllers\Admin\DeliveryAreaController as AdminDeliveryAreaController;
use App\Http\Controllers\Admin\DeliveryCityController as AdminDeliveryCityController;
use App\Http\Controllers\Admin\RecaptchaController as AdminRecaptchaController;
use App\Http\Controllers\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Admin\ContentController as AdminContentController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\InvitationController as AdminInvitationController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\InventoryController as AdminInventoryController;
use App\Http\Controllers\Admin\PosController as AdminPosController;
use App\Http\Controllers\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Admin\InvoiceSettingsController as AdminInvoiceSettingsController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Admin\ChatbotController as AdminChatbotController;
use App\Http\Controllers\Admin\ChatbotSettingsController as AdminChatbotSettingsController;

// PWA service worker — served from the site root so its scope covers the whole app
Route::get('/sw.js', [\App\Http\Controllers\PwaController::class, 'serviceWorker']);

// Sitemap
Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [HomeController::class, 'about'])->name('about');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');
Route::get('/faq', [HomeController::class, 'faq'])->name('faq');
Route::get('/privacy-policy', [HomeController::class, 'privacy'])->name('privacy');
Route::get('/terms-conditions', [HomeController::class, 'terms'])->name('terms');

// Offers page (redirects to shop — keeps Navbar route('offers') from throwing a Ziggy error)
Route::get('/offers', fn () => redirect()->route('shop.index'))->name('offers');

// Shop routes
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/shop/{slug}', [ShopController::class, 'show'])->name('shop.show');
Route::get('/search', [ShopController::class, 'search'])->name('search');
Route::get('/products/{slug}/quickview', [ShopController::class, 'quickview'])->name('products.quickview');

// Product review
Route::middleware('auth')->post('/products/{slug}/reviews', [ReviewController::class, 'store'])->name('review.store');

// Cart routes
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/{cart}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{cart}', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon');
Route::get('/api/cart', [CartController::class, 'getCartJson'])->name('cart.json');

// Checkout routes
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/order/success', [CheckoutController::class, 'success'])->name('order.success');

// Payment gateway callbacks (public — no auth, called by payment gateways)
Route::prefix('payment')->name('payment.')->group(function () {
    // bKash
    Route::get('bkash/callback',       [PaymentCallbackController::class, 'bkashCallback'])->name('bkash.callback');
    // Nagad
    Route::get('nagad/callback',        [PaymentCallbackController::class, 'nagadCallback'])->name('nagad.callback');
    // SSLCommerz
    Route::post('sslcommerz/success',  [PaymentCallbackController::class, 'sslcommerzSuccess'])->name('sslcommerz.success');
    Route::post('sslcommerz/fail',     [PaymentCallbackController::class, 'sslcommerzFail'])->name('sslcommerz.fail');
    Route::post('sslcommerz/cancel',   [PaymentCallbackController::class, 'sslcommerzCancel'])->name('sslcommerz.cancel');
    Route::post('sslcommerz/ipn',      [PaymentCallbackController::class, 'sslcommerzIpn'])->name('sslcommerz.ipn');
});

// Order tracking
Route::get('/track-order', [OrderController::class, 'track'])->name('order.track');

// Contact
Route::post('/contact', [ContactController::class, 'sendMessage'])->name('contact.send');

// AI Chatbot (public widget) — rate-limited
Route::post('/chat/message', [ChatController::class, 'message'])->middleware('throttle:30,1')->name('chat.message');
Route::post('/chat/event',   [ChatController::class, 'event'])->middleware('throttle:60,1')->name('chat.event');
Route::post('/chat/order',   [ChatController::class, 'placeOrder'])->middleware('throttle:10,1')->name('chat.order');
Route::get('/chat/delivery-options', [ChatController::class, 'deliveryOptions'])->middleware('throttle:60,1')->name('chat.delivery-options');

// Auth routes (guests only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');

    // Team invitation acceptance (invitee sets their own password)
    Route::get('/invite/{token}', [AuthController::class, 'showInviteAccept'])->name('invite.accept');
    Route::post('/invite/{token}', [AuthController::class, 'acceptInvite'])->name('invite.complete');
});

// Auth routes (authenticated only)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Account
    Route::get('/account', [AccountController::class, 'dashboard'])->name('account.dashboard');
    Route::get('/account/profile', [AccountController::class, 'profile'])->name('account.profile');
    Route::post('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::post('/account/password', [AccountController::class, 'updatePassword'])->name('account.password');

    // Orders
    Route::get('/account/orders', [OrderController::class, 'index'])->name('account.orders');
    Route::get('/account/orders/{order}', [OrderController::class, 'show'])->name('account.orders.show');

    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::delete('/wishlist/{wishlist}', [WishlistController::class, 'remove'])->name('wishlist.remove');
});

// Admin routes — every route is permission-gated (Super Admin bypasses).
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->middleware('permission:dashboard.view')->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('permission:dashboard.view')->name('dashboard.alt');

    // Products
    Route::get('products/{product}/gallery', [AdminProductController::class, 'galleryData'])->middleware('permission:products.view')->name('products.gallery');
    Route::get('products', [AdminProductController::class, 'index'])->middleware('permission:products.view')->name('products.index');
    Route::get('products/create', [AdminProductController::class, 'create'])->middleware('permission:products.create')->name('products.create');
    Route::post('products', [AdminProductController::class, 'store'])->middleware('permission:products.create')->name('products.store');
    Route::get('products/{product}/edit', [AdminProductController::class, 'edit'])->middleware('permission:products.edit')->name('products.edit');
    Route::match(['put', 'patch'], 'products/{product}', [AdminProductController::class, 'update'])->middleware('permission:products.edit')->name('products.update');
    Route::post('products/bulk-delete', [AdminProductController::class, 'bulkDestroy'])->middleware('permission:products.delete')->name('products.bulk-destroy');
    Route::delete('products/{product}', [AdminProductController::class, 'destroy'])->middleware('permission:products.delete')->name('products.destroy');

    // Orders
    Route::get('/orders', [AdminOrderController::class, 'index'])->middleware('permission:orders.view')->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->middleware('permission:orders.view')->name('orders.show');
    Route::get('/orders/{order}/json', [AdminOrderController::class, 'data'])->middleware('permission:orders.view')->name('orders.data');
    Route::get('/orders-products/search', [AdminOrderController::class, 'productsSearch'])->middleware('permission:orders.add_products')->name('orders.products-search');
    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->middleware('permission:orders.update_status')->name('orders.status');
    Route::put('/orders/{order}', [AdminOrderController::class, 'update'])->middleware('permission:orders.edit')->name('orders.update');
    Route::delete('/orders/{order}', [AdminOrderController::class, 'destroy'])->middleware('permission:orders.delete')->name('orders.destroy');
    Route::post('/orders/bulk-delete', [AdminOrderController::class, 'bulkDestroy'])->middleware('permission:orders.delete')->name('orders.bulk-destroy');

    // Categories
    Route::get('categories', [AdminCategoryController::class, 'index'])->middleware('permission:categories.view')->name('categories.index');
    Route::post('categories', [AdminCategoryController::class, 'store'])->middleware('permission:categories.create')->name('categories.store');
    Route::match(['put', 'patch'], 'categories/{category}', [AdminCategoryController::class, 'update'])->middleware('permission:categories.edit')->name('categories.update');
    Route::post('categories/bulk-delete', [AdminCategoryController::class, 'bulkDestroy'])->middleware('permission:categories.delete')->name('categories.bulk-destroy');
    Route::delete('categories/{category}', [AdminCategoryController::class, 'destroy'])->middleware('permission:categories.delete')->name('categories.destroy');

    // Coupons (Marketing)
    Route::get('coupons', [AdminCouponController::class, 'index'])->middleware('permission:marketing.view')->name('coupons.index');
    Route::post('coupons', [AdminCouponController::class, 'store'])->middleware('permission:marketing.create')->name('coupons.store');
    Route::match(['put', 'patch'], 'coupons/{coupon}', [AdminCouponController::class, 'update'])->middleware('permission:marketing.edit')->name('coupons.update');
    Route::post('coupons/bulk-delete', [AdminCouponController::class, 'bulkDestroy'])->middleware('permission:marketing.delete')->name('coupons.bulk-destroy');
    Route::delete('coupons/{coupon}', [AdminCouponController::class, 'destroy'])->middleware('permission:marketing.delete')->name('coupons.destroy');

    // Users (staff)
    Route::get('/users/export', [AdminUserController::class, 'export'])->middleware('permission:users.view')->name('users.export');
    Route::get('/users', [AdminUserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
    Route::post('/users', [AdminUserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->middleware('permission:users.edit')->name('users.update');
    Route::patch('/users/{user}/status', [AdminUserController::class, 'updateStatus'])->middleware('permission:users.edit')->name('users.status');
    Route::patch('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->middleware('permission:users.edit')->name('users.reset-password');
    Route::post('/users/bulk', [AdminUserController::class, 'bulk'])->middleware('permission:users.edit')->name('users.bulk');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->middleware('permission:users.delete')->name('users.destroy');

    // Invitations
    Route::post('/invitations', [AdminInvitationController::class, 'store'])->middleware('permission:users.create')->name('invitations.store');
    Route::delete('/invitations/{invitation}', [AdminInvitationController::class, 'destroy'])->middleware('permission:users.create')->name('invitations.destroy');

    // Customers (consumers)
    Route::get('/customers', [AdminCustomerController::class, 'index'])->middleware('permission:customers.view')->name('customers.index');
    Route::get('/customers/{customer}/orders', [AdminCustomerController::class, 'orders'])->middleware('permission:customers.view_orders')->name('customers.orders');
    Route::get('/customers/{customer}/orders-data', [AdminCustomerController::class, 'ordersData'])->middleware('permission:customers.view_orders')->name('customers.orders-data');
    Route::patch('/customers/{customer}', [AdminCustomerController::class, 'update'])->middleware('permission:customers.edit')->name('customers.update');
    Route::patch('/customers/{customer}/reset-password', [AdminCustomerController::class, 'resetPassword'])->middleware('permission:customers.edit')->name('customers.reset-password');

    // Roles & Permissions
    Route::get('/roles', [AdminRoleController::class, 'index'])->middleware('permission:roles.view')->name('roles.index');
    Route::post('/roles', [AdminRoleController::class, 'store'])->middleware('permission:roles.create')->name('roles.store');
    Route::put('/roles/{role}', [AdminRoleController::class, 'update'])->middleware('permission:roles.edit')->name('roles.update');
    Route::post('/roles/{role}/duplicate', [AdminRoleController::class, 'duplicate'])->middleware('permission:roles.create')->name('roles.duplicate');
    Route::delete('/roles/{role}', [AdminRoleController::class, 'destroy'])->middleware('permission:roles.delete')->name('roles.destroy');

    // Inventory
    Route::get('/inventory', [AdminInventoryController::class, 'index'])->middleware('permission:inventory.view')->name('inventory.index');
    Route::get('/inventory/export', [AdminInventoryController::class, 'export'])->middleware('permission:inventory.export')->name('inventory.export');
    Route::get('/inventory/logs', [AdminInventoryController::class, 'logs'])->middleware('permission:inventory.logs')->name('inventory.logs');
    Route::get('/inventory/analytics', [AdminInventoryController::class, 'analytics'])->middleware('permission:inventory.analytics')->name('inventory.analytics');
    Route::patch('/inventory/{product}/stock', [AdminInventoryController::class, 'updateStock'])->middleware('permission:inventory.update')->name('inventory.stock');

    // POS
    Route::get('/pos/create', [AdminPosController::class, 'create'])->middleware('permission:pos.create')->name('pos.create');
    Route::post('/pos', [AdminPosController::class, 'store'])->middleware('permission:pos.create')->name('pos.store');
    Route::get('/pos/orders', [AdminPosController::class, 'orders'])->middleware('permission:pos.orders')->name('pos.orders');

    // Invoices (works for website + POS orders)
    Route::get('/invoices/{order}', [AdminInvoiceController::class, 'show'])->middleware('permission:invoice.view')->name('invoices.show');
    Route::get('/invoices/{order}/print', [AdminInvoiceController::class, 'print'])->middleware('permission:invoice.print')->name('invoices.print');
    Route::get('/invoices/{order}/download', [AdminInvoiceController::class, 'download'])->middleware('permission:invoice.download')->name('invoices.download');
    Route::post('/invoices/{order}/regenerate', [AdminInvoiceController::class, 'regenerate'])->middleware('permission:invoice.regenerate')->name('invoices.regenerate');

    // Invoice Settings (Settings tab)
    Route::get('/invoice-settings', [AdminInvoiceSettingsController::class, 'index'])->middleware('permission:invoice_settings.view')->name('invoice-settings.index');
    Route::post('/invoice-settings', [AdminInvoiceSettingsController::class, 'update'])->middleware('permission:invoice_settings.edit')->name('invoice-settings.update');

    // Chatbot Settings (Settings tab)
    Route::get('/chatbot-settings', [AdminChatbotSettingsController::class, 'index'])->middleware('permission:chatbot.settings.view')->name('chatbot-settings.index');
    Route::post('/chatbot-settings', [AdminChatbotSettingsController::class, 'update'])->middleware('permission:chatbot.settings.edit')->name('chatbot-settings.update');
    Route::post('/chatbot-settings/test', [AdminChatbotSettingsController::class, 'test'])->middleware('permission:chatbot.settings.view')->name('chatbot-settings.test');

    // AI Chatbot orders + analytics
    Route::get('/chatbot/orders', [AdminChatbotController::class, 'orders'])->middleware('permission:chatbot.orders.view')->name('chatbot.orders');
    Route::get('/chatbot/analytics', [AdminChatbotController::class, 'analytics'])->middleware('permission:chatbot.analytics.view')->name('chatbot.analytics');
    Route::get('/chatbot/conversations', [AdminChatbotController::class, 'conversations'])->middleware('permission:chatbot.analytics.view')->name('chatbot.conversations');

    // Reports
    Route::get('/reports', [AdminReportController::class, 'index'])->middleware('permission:reports.view')->name('reports.index');
    Route::get('/sales-analysis', [AdminReportController::class, 'salesAnalysis'])->middleware('permission:reports.view')->name('reports.sales');
    Route::get('/sales-analysis/export', [AdminReportController::class, 'salesExport'])->middleware('permission:reports.export')->name('reports.sales-export');
    Route::get('/reports/export', [AdminReportController::class, 'export'])->middleware('permission:reports.export')->name('reports.export');

    // Audit logs
    Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->middleware('permission:reports.view')->name('audit-logs.index');
    Route::delete('/audit-logs', [AdminAuditLogController::class, 'clear'])->middleware('permission:reports.view')->name('audit-logs.clear');

    // Messages / Leads (customer inquiries)
    Route::get('/messages/contacts', [AdminMessageController::class, 'contacts'])->middleware('permission:customers.view')->name('messages.contacts');
    Route::patch('/messages/contacts/{message}/read', [AdminMessageController::class, 'markContactRead'])->middleware('permission:customers.view')->name('messages.contacts.read');
    Route::patch('/messages/contacts/{message}/status', [AdminMessageController::class, 'updateStatus'])->middleware('permission:customers.view')->name('messages.contacts.status');
    Route::delete('/messages/contacts/{message}', [AdminMessageController::class, 'destroy'])->middleware('permission:customers.delete')->name('messages.contacts.destroy');
    Route::post('/messages/contacts/bulk-delete', [AdminMessageController::class, 'bulkDestroy'])->middleware('permission:customers.delete')->name('messages.contacts.bulk-destroy');

    // Product Reviews (manage / moderate)
    Route::get('reviews',                  [\App\Http\Controllers\Admin\ReviewController::class, 'index'])->middleware('permission:products.view')->name('reviews.index');
    Route::put('reviews/{review}',         [\App\Http\Controllers\Admin\ReviewController::class, 'update'])->middleware('permission:products.edit')->name('reviews.update');
    Route::patch('reviews/{review}/toggle', [\App\Http\Controllers\Admin\ReviewController::class, 'toggle'])->middleware('permission:products.edit')->name('reviews.toggle');
    Route::post('reviews/bulk-delete',     [\App\Http\Controllers\Admin\ReviewController::class, 'bulkDestroy'])->middleware('permission:products.edit')->name('reviews.bulk-destroy');
    Route::delete('reviews/{review}',      [\App\Http\Controllers\Admin\ReviewController::class, 'destroy'])->middleware('permission:products.edit')->name('reviews.destroy');

    // Security / reCAPTCHA (Settings)
    Route::get('/recaptcha',  [AdminRecaptchaController::class, 'index'])->middleware('permission:settings.view')->name('recaptcha.index');
    Route::post('/recaptcha', [AdminRecaptchaController::class, 'update'])->middleware('permission:settings.edit')->name('recaptcha.update');

    // Accounts (accounting module)
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\Accounts\DashboardController::class, 'index'])->middleware('permission:accounting.view')->name('dashboard');

        Route::get('transactions',        [\App\Http\Controllers\Admin\Accounts\TransactionController::class, 'index'])->middleware('permission:accounting.view')->name('transactions');
        Route::post('transactions',       [\App\Http\Controllers\Admin\Accounts\TransactionController::class, 'store'])->middleware('permission:accounting.manage')->name('transactions.store');
        Route::get('transactions/export', [\App\Http\Controllers\Admin\Accounts\TransactionController::class, 'export'])->middleware('permission:accounting.view')->name('transactions.export');
        Route::put('transactions/{transaction}',    [\App\Http\Controllers\Admin\Accounts\TransactionController::class, 'update'])->middleware('permission:accounting.manage')->name('transactions.update');
        Route::delete('transactions/{transaction}', [\App\Http\Controllers\Admin\Accounts\TransactionController::class, 'destroy'])->middleware('permission:accounting.manage')->name('transactions.destroy');
        Route::post('transactions/bulk-delete',     [\App\Http\Controllers\Admin\Accounts\TransactionController::class, 'bulkDestroy'])->middleware('permission:accounting.manage')->name('transactions.bulk-destroy');

        Route::get('monthly',         [\App\Http\Controllers\Admin\Accounts\SummaryController::class, 'monthly'])->middleware('permission:accounting.view')->name('monthly');
        Route::get('partner-capital', [\App\Http\Controllers\Admin\Accounts\SummaryController::class, 'partnerCapital'])->middleware('permission:accounting.view')->name('partner-capital');
        Route::get('cash-bank',       [\App\Http\Controllers\Admin\Accounts\SummaryController::class, 'cashBank'])->middleware('permission:accounting.view')->name('cash-bank');
        Route::get('ledger',          [\App\Http\Controllers\Admin\Accounts\SummaryController::class, 'ledger'])->middleware('permission:accounting.view')->name('ledger');
        Route::get('ledger-detail',   [\App\Http\Controllers\Admin\Accounts\SummaryController::class, 'ledgerHead'])->middleware('permission:accounting.view')->name('ledger.head');

        Route::get('settings',  [\App\Http\Controllers\Admin\Accounts\SettingsController::class, 'index'])->middleware('permission:accounting.manage')->name('settings');
        Route::post('settings', [\App\Http\Controllers\Admin\Accounts\SettingsController::class, 'update'])->middleware('permission:accounting.manage')->name('settings.update');

        Route::get('import',          [\App\Http\Controllers\Admin\Accounts\ImportController::class, 'index'])->middleware('permission:accounting.import')->name('import');
        Route::post('import/preview', [\App\Http\Controllers\Admin\Accounts\ImportController::class, 'preview'])->middleware('permission:accounting.import')->name('import.preview');
        Route::post('import/commit',  [\App\Http\Controllers\Admin\Accounts\ImportController::class, 'commit'])->middleware('permission:accounting.import')->name('import.commit');
        Route::post('import/{batch}/rollback', [\App\Http\Controllers\Admin\Accounts\ImportController::class, 'rollback'])->middleware('permission:accounting.import')->name('import.rollback');
    });

    // Email Configuration (Settings → SMTP · Branding · Templates · Logs)
    Route::prefix('email')->name('email.')->group(function () {
        Route::get('smtp',         [\App\Http\Controllers\Admin\EmailSettingsController::class, 'smtp'])->middleware('permission:settings.view')->name('smtp');
        Route::post('smtp',        [\App\Http\Controllers\Admin\EmailSettingsController::class, 'updateSmtp'])->middleware('permission:settings.edit')->name('smtp.update');
        Route::post('smtp/verify', [\App\Http\Controllers\Admin\EmailSettingsController::class, 'verifySmtp'])->middleware('permission:settings.edit')->name('smtp.verify');
        Route::post('smtp/test',   [\App\Http\Controllers\Admin\EmailSettingsController::class, 'testEmail'])->middleware('permission:settings.edit')->name('smtp.test');

        Route::get('brand',          [\App\Http\Controllers\Admin\EmailSettingsController::class, 'brand'])->middleware('permission:settings.view')->name('brand');
        Route::post('brand',         [\App\Http\Controllers\Admin\EmailSettingsController::class, 'updateBrand'])->middleware('permission:settings.edit')->name('brand.update');
        Route::post('brand/preview', [\App\Http\Controllers\Admin\EmailSettingsController::class, 'brandPreview'])->middleware('permission:settings.view')->name('brand.preview');

        Route::get('templates',                       [\App\Http\Controllers\Admin\EmailTemplateController::class, 'index'])->middleware('permission:settings.view')->name('templates');
        Route::get('templates/{template}',            [\App\Http\Controllers\Admin\EmailTemplateController::class, 'edit'])->middleware('permission:settings.view')->name('templates.edit');
        Route::post('templates/{template}',           [\App\Http\Controllers\Admin\EmailTemplateController::class, 'update'])->middleware('permission:settings.edit')->name('templates.update');
        Route::post('templates/{template}/preview',   [\App\Http\Controllers\Admin\EmailTemplateController::class, 'preview'])->middleware('permission:settings.view')->name('templates.preview');
        Route::post('templates/{template}/test',      [\App\Http\Controllers\Admin\EmailTemplateController::class, 'test'])->middleware('permission:settings.edit')->name('templates.test');
        Route::post('templates/{template}/reset',     [\App\Http\Controllers\Admin\EmailTemplateController::class, 'reset'])->middleware('permission:settings.edit')->name('templates.reset');
        Route::post('templates/{template}/duplicate', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'duplicate'])->middleware('permission:settings.edit')->name('templates.duplicate');
        Route::patch('templates/{template}/toggle',   [\App\Http\Controllers\Admin\EmailTemplateController::class, 'toggle'])->middleware('permission:settings.edit')->name('templates.toggle');
        Route::delete('templates/{template}',         [\App\Http\Controllers\Admin\EmailTemplateController::class, 'destroy'])->middleware('permission:settings.edit')->name('templates.destroy');

        Route::get('logs',               [\App\Http\Controllers\Admin\EmailLogController::class, 'index'])->middleware('permission:settings.view')->name('logs');
        Route::post('logs/{log}/resend', [\App\Http\Controllers\Admin\EmailLogController::class, 'resend'])->middleware('permission:settings.edit')->name('logs.resend');
        Route::delete('logs/clear',      [\App\Http\Controllers\Admin\EmailLogController::class, 'clear'])->middleware('permission:settings.edit')->name('logs.clear');
    });

    // Media Library
    Route::get('/media',                 [AdminMediaController::class, 'index'])->middleware('permission:media.view')->name('media.index');
    Route::get('/media/list',            [AdminMediaController::class, 'list'])->middleware('permission:media.view')->name('media.list');
    Route::post('/media',                [AdminMediaController::class, 'store'])->middleware('permission:media.upload')->name('media.store');
    Route::post('/media/upload',         [AdminMediaController::class, 'upload'])->middleware('permission:media.upload')->name('media.upload');
    Route::get('/media/{media}/usage',   [AdminMediaController::class, 'usage'])->middleware('permission:media.view')->name('media.usage');
    Route::delete('/media/{media}',      [AdminMediaController::class, 'destroy'])->middleware('permission:media.delete')->name('media.destroy');

    // Delivery Cities (Settings)
    Route::get('/delivery-cities',                 [AdminDeliveryCityController::class, 'index'])->middleware('permission:settings.view')->name('delivery-cities.index');
    Route::patch('/delivery-cities/{deliveryCity}',[AdminDeliveryCityController::class, 'update'])->middleware('permission:settings.edit')->name('delivery-cities.update');

    // Delivery Areas (Settings)
    Route::get('/delivery-areas',                  [AdminDeliveryAreaController::class, 'index'])->middleware('permission:settings.view')->name('delivery-areas.index');
    Route::patch('/delivery-areas/{deliveryArea}', [AdminDeliveryAreaController::class, 'update'])->middleware('permission:settings.edit')->name('delivery-areas.update');

    // Payment Gateways (Settings)
    Route::get('/payments',                   [PaymentGatewayController::class, 'index'])->middleware('permission:settings.view')->name('payments.index');
    Route::post('/payments/{gateway}/update', [PaymentGatewayController::class, 'update'])->middleware('permission:settings.edit')->name('payments.update');
    Route::post('/payments/{gateway}/logo',   [PaymentGatewayController::class, 'uploadLogo'])->middleware('permission:settings.edit')->name('payments.logo.upload');
    Route::delete('/payments/{gateway}/logo', [PaymentGatewayController::class, 'deleteLogo'])->middleware('permission:settings.edit')->name('payments.logo.delete');

    // Content / CMS + Settings tabs (dual-purpose controller → allow either family)
    Route::get('/content',          [AdminContentController::class, 'index'])->middleware('permission:cms.view')->name('content.index');
    Route::get('/content/{group}',  [AdminContentController::class, 'edit'])->middleware('permission:settings.view,cms.view')->name('content.edit');
    Route::post('/content/{group}', [AdminContentController::class, 'update'])->middleware('permission:settings.edit,cms.edit')->name('content.update');
});

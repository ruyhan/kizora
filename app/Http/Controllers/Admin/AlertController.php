<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Product;
use App\Support\PageContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    /** Live counters for the admin sidebar badges — each count is permission-gated. */
    public function counts(Request $request, PageContent $content): JsonResponse
    {
        $user = $request->user();
        $counts = [];

        if ($user->hasPermission('orders.view')) {
            $counts['pending_orders'] = Order::web()->where('status', 'pending')->count();
        }
        if ($user->hasPermission('customers.view')) {
            $counts['unread_messages'] = ContactMessage::where('is_read', false)->count();
        }
        if ($user->hasPermission('inventory.view')) {
            $threshold = (int) data_get($content->get('settings'), 'low_stock_threshold', 10);
            $counts['low_stock'] = Product::where('is_active', true)
                ->where('stock', '<=', max(0, $threshold))->count();
        }

        return response()->json($counts);
    }
}

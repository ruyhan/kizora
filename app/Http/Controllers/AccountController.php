<?php

namespace App\Http\Controllers;

use App\Models\DeliveryArea;
use App\Models\DeliveryCity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function dashboard(): Response
    {
        $user = Auth::user();
        $recentOrders = $user->orders()->latest()->take(5)->get()->map(fn($o) => [
            'id' => $o->id,
            'order_number' => $o->order_number,
            'status' => $o->status,
            'status_label' => $o->status_label,
            'total' => $o->total,
            'created_at' => $o->created_at->format('d M Y'),
        ]);

        return Inertia::render('Account/Dashboard', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar,
            ],
            'stats' => [
                'total_orders' => $user->orders()->count(),
                'completed_orders' => $user->orders()->where('status', 'completed')->count(),
                'wishlist_count' => $user->wishlists()->count(),
            ],
            'recentOrders' => $recentOrders,
        ]);
    }

    public function profile(): Response
    {
        $user = Auth::user();
        return Inertia::render('Account/Profile', [
            'user' => [
                'name'             => $user->name,
                'email'            => $user->email,
                'phone'            => $user->phone,
                'avatar'           => $user->avatar,
                'delivery_area_id' => $user->delivery_area_id,
                'delivery_address' => $user->delivery_address,
            ],
            // Users can choose ANY city/area (active or not)
            'deliveryCities' => DeliveryCity::orderBy('name')->get(['id', 'name', 'is_active']),
            'deliveryAreas'  => DeliveryArea::orderBy('name')->get(['id', 'delivery_city_id', 'name', 'postal_code']),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'name'             => 'required|string|max:255',
            'phone'            => 'nullable|string|max:20',
            'email'            => 'required|email|unique:users,email,' . $user->id,
            // Any existing area is allowed — availability is not enforced on profile save
            'delivery_area_id' => ['nullable', Rule::exists('delivery_areas', 'id')],
            'delivery_address' => 'nullable|string|max:500',
        ]);

        $user->update($request->only('name', 'email', 'phone', 'delivery_area_id', 'delivery_address'));
        return back()->with('success', 'Profile updated successfully!');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->update(['password' => Hash::make($request->password)]);
        return back()->with('success', 'Password changed successfully!');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryCity;
use Illuminate\Http\Request;

/**
 * City-wise delivery charges, managed from within the Delivery Settings page.
 * Charges live on the existing delivery_cities table (delivery_charge column)
 * — no separate table. "Configured" = a city whose delivery_charge is not null;
 * deleting a charge nulls the column. Each action redirects back so the
 * Delivery Settings editor re-renders with fresh city data.
 */
class DeliveryChargeController extends Controller
{
    /**
     * Assign a charge to a set of cities. Handles both:
     *  - Add: set `delivery_charge` on the selected cities.
     *  - Edit a charge group: also null the charge for cities removed from it
     *    (passed in `remove_ids`).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'city_ids'        => 'required|array|min:1',
            'city_ids.*'      => 'integer|exists:delivery_cities,id',
            'delivery_charge' => 'required|numeric|min:0|max:1000000',
            'remove_ids'      => 'nullable|array',
            'remove_ids.*'    => 'integer|exists:delivery_cities,id',
        ]);

        $count = DeliveryCity::whereIn('id', $data['city_ids'])
            ->update(['delivery_charge' => $data['delivery_charge']]);

        if (! empty($data['remove_ids'])) {
            DeliveryCity::whereIn('id', $data['remove_ids'])->update(['delivery_charge' => null]);
        }

        return back()->with('success', "Delivery charge set for {$count} " . ($count === 1 ? 'city' : 'cities') . '.');
    }

    /** Remove a whole charge group — nulls the charge for every listed city. */
    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'city_ids'   => 'required|array|min:1',
            'city_ids.*' => 'integer|exists:delivery_cities,id',
        ]);

        $count = DeliveryCity::whereIn('id', $data['city_ids'])->update(['delivery_charge' => null]);

        return back()->with('success', "Delivery charge removed from {$count} " . ($count === 1 ? 'city' : 'cities') . '.');
    }
}

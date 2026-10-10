<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryCity;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeliveryCityController extends Controller
{
    public function index(Request $request)
    {
        $query = DeliveryCity::withCount('areas')->orderBy('name');

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        return Inertia::render('Admin/DeliveryCities/Index', [
            'cities'  => $query->get(),
            'filters' => ['search' => $search ?? ''],
            'stats'   => [
                'total'  => DeliveryCity::count(),
                'active' => DeliveryCity::where('is_active', true)->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:255|unique:delivery_cities,name',
            'delivery_charge' => 'nullable|numeric|min:0|max:1000000',
            'is_active'       => 'boolean',
        ]);

        $city = DeliveryCity::create($data);

        return back()->with('success', "{$city->name} added.");
    }

    public function update(Request $request, DeliveryCity $deliveryCity)
    {
        $data = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $deliveryCity->update($data);

        return back()->with('success', "{$deliveryCity->name} delivery " . ($data['is_active'] ? 'enabled' : 'disabled') . '.');
    }
}

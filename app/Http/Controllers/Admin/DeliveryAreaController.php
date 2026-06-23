<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryArea;
use App\Models\DeliveryCity;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeliveryAreaController extends Controller
{
    public function index(Request $request)
    {
        $query = DeliveryArea::with('city')
            ->join('delivery_cities', 'delivery_areas.delivery_city_id', '=', 'delivery_cities.id')
            ->orderBy('delivery_cities.name')
            ->orderBy('delivery_areas.name')
            ->select('delivery_areas.*');

        if ($cityId = $request->get('city_id')) {
            $query->where('delivery_areas.delivery_city_id', $cityId);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('delivery_areas.name', 'like', "%{$search}%")
                  ->orWhere('delivery_areas.postal_code', 'like', "%{$search}%");
            });
        }

        $areas = $query->paginate(30)->withQueryString()->through(fn($a) => [
            'id'          => $a->id,
            'name'        => $a->name,
            'postal_code' => $a->postal_code,
            'city'        => ['id' => $a->city->id, 'name' => $a->city->name, 'is_active' => $a->city->is_active],
        ]);

        return Inertia::render('Admin/DeliveryAreas/Index', [
            'areas'   => $areas,
            'cities'  => DeliveryCity::orderBy('name')->get(['id', 'name']),
            'filters' => ['search' => $search ?? '', 'city_id' => $cityId ?? ''],
        ]);
    }

    public function update(Request $request, DeliveryArea $deliveryArea)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:20',
        ]);

        $deliveryArea->update($data);

        return back()->with('success', 'Area updated.');
    }
}

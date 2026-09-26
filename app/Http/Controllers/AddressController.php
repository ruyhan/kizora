<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    private function rules(): array
    {
        return [
            'label'        => 'nullable|string|max:50',
            'name'         => 'required|string|max:255',
            'phone'        => ['required', new \App\Rules\BdPhone],
            'address_line' => 'required|string|max:500',
            'city'         => 'nullable|string|max:100',
            'postal_code'  => 'nullable|string|max:20',
            'is_default'   => 'nullable|boolean',
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data['label'] = $data['label'] ?: 'Address ' . (Auth::user()->addresses()->count() + 1);

        if (! empty($data['is_default']) || ! Auth::user()->addresses()->exists()) {
            Auth::user()->addresses()->update(['is_default' => false]);
            $data['is_default'] = true;
        }

        Auth::user()->addresses()->create($data);

        return back()->with('success', 'Address saved.');
    }

    public function update(Request $request, Address $address)
    {
        abort_unless($address->user_id === Auth::id(), 403);

        $data = $request->validate($this->rules());

        if (! empty($data['is_default'])) {
            Auth::user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
            $data['is_default'] = true;
        }

        $address->update($data);

        return back()->with('success', 'Address updated.');
    }

    public function destroy(Address $address)
    {
        abort_unless($address->user_id === Auth::id(), 403);
        $wasDefault = $address->is_default;
        $address->delete();

        // Keep exactly one default when possible
        if ($wasDefault && ($next = Auth::user()->addresses()->latest()->first())) {
            $next->update(['is_default' => true]);
        }

        return back()->with('success', 'Address removed.');
    }
}

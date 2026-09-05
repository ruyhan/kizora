<?php

namespace App\Support;

use App\Models\DeliveryArea;
use App\Models\DeliveryCity;

/**
 * Single source of truth for delivery charges. Free-delivery rules live in the
 * admin-managed "delivery" settings group; the actual charge is now stored
 * per-city on `delivery_cities.delivery_charge` (managed under
 * Settings → Delivery Charges). Nothing about delivery cost is hardcoded.
 */
class DeliverySettings
{
    public function __construct(private readonly PageContent $content) {}

    private function data(): array
    {
        return $this->content->get('delivery');
    }

    public function freeEnabled(): bool
    {
        return (bool) ($this->data()['free_delivery_enabled'] ?? false);
    }

    /** 'all' = free on everything · 'minimum' = free above a purchase amount. */
    public function type(): string
    {
        $t = $this->data()['free_delivery_type'] ?? 'all';
        return in_array($t, ['all', 'minimum'], true) ? $t : 'all';
    }

    public function minimumAmount(): float
    {
        return (float) ($this->data()['free_delivery_minimum_amount'] ?? 0);
    }

    /**
     * "Delivery charge based on location" — no charge is computed at checkout;
     * staff enter the exact amount later (admin order edit) or while creating a
     * POS order. Takes precedence over the per-city charge table.
     */
    public function locationBased(): bool
    {
        return (bool) ($this->data()['location_charge_enabled'] ?? false);
    }

    /** Whether the per-city charge table is applied at all. */
    public function chargesEnabled(): bool
    {
        return (bool) ($this->data()['delivery_charges_enabled'] ?? true);
    }

    /** Green checkout note shown while location-based charging is on. */
    public function locationMessage(): string
    {
        $m = trim((string) ($this->data()['location_charge_message'] ?? ''));
        return $m !== '' ? $m : 'Delivery charge will be added based on your location.';
    }

    /**
     * True when the charge is not decided at checkout time and is added by staff
     * afterwards — i.e. location-based mode (free delivery still wins).
     */
    public function manualCharge(float $subtotal = 0): bool
    {
        return $this->locationBased() && ! $this->qualifiesFree($subtotal);
    }

    /**
     * True when an order's delivery charge has not been decided yet: location-based
     * mode is on, free delivery does not apply and no amount has been entered by
     * staff. The invoice + order emails show the location note instead of a value.
     */
    public function chargePending(float $subtotal = 0, ?float $charge = 0): bool
    {
        return $this->manualCharge($subtotal) && (float) $charge <= 0.0;
    }

    /** Message shown when a selected city has no delivery charge configured. */
    public function noChargeMessage(): string
    {
        $m = trim((string) ($this->data()['no_charge_message'] ?? ''));
        return $m !== '' ? $m
            : 'Delivery is not available for the selected city yet. Please choose another city or contact us.';
    }

    /** Whether the given subtotal currently qualifies for free delivery. */
    public function qualifiesFree(float $subtotal = 0): bool
    {
        if (! $this->freeEnabled()) return false;
        return $this->type() === 'all' || $subtotal >= $this->minimumAmount();
    }

    /**
     * Delivery charge for a city + subtotal.
     *  - Free delivery applies   → 0.0
     *  - Location-based mode     → 0.0   (added manually by staff later)
     *  - City charges disabled   → 0.0
     *  - City missing            → null   (location unknown)
     *  - City has no charge set  → null   (checkout should block)
     *  - Otherwise               → the city's configured charge
     */
    public function chargeForCity(?DeliveryCity $city, float $subtotal = 0): ?float
    {
        if ($this->qualifiesFree($subtotal)) return 0.0;
        if ($this->locationBased() || ! $this->chargesEnabled()) return 0.0;
        if (! $city || $city->delivery_charge === null) return null;
        return (float) $city->delivery_charge;
    }

    /** Delivery charge for a delivery area (resolves to its parent city). */
    public function chargeFor(?DeliveryArea $area, float $subtotal = 0): ?float
    {
        return $this->chargeForCity($area?->city, $subtotal);
    }

    /** Charge resolved from a snapshot city name (used when editing an order). */
    public function chargeForCityName(?string $name, float $subtotal = 0): ?float
    {
        if ($this->qualifiesFree($subtotal)) return 0.0;
        if ($this->locationBased() || ! $this->chargesEnabled()) return 0.0;
        if (! $name) return null;
        $city = DeliveryCity::whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])->first();
        return $city ? $this->chargeForCity($city, $subtotal) : null;
    }

    /** Whether a delivery area's city has a charge configured (for checkout validation). */
    public function hasChargeFor(?DeliveryArea $area): bool
    {
        if ($this->locationBased() || ! $this->chargesEnabled()) return true;
        return $area?->city && $area->city->delivery_charge !== null;
    }

    /**
     * Best-effort estimate when the location is unknown (e.g. the cart page):
     * free → 0, otherwise the lowest configured city charge.
     */
    public function estimate(float $subtotal = 0): float
    {
        if ($this->qualifiesFree($subtotal)) return 0.0;
        if ($this->locationBased() || ! $this->chargesEnabled()) return 0.0;
        return (float) (DeliveryCity::active()->whereNotNull('delivery_charge')->min('delivery_charge') ?? 0);
    }

    /** Human-readable delivery-charge summary for the chatbot / messaging. */
    public function chargeSummary(): string
    {
        if ($this->locationBased()) {
            return $this->freeEnabled() && $this->type() === 'all'
                ? 'Free delivery on all orders.'
                : $this->locationMessage();
        }
        if (! $this->chargesEnabled()) {
            return 'No delivery charge is applied.';
        }
        if ($this->freeEnabled()) {
            return $this->type() === 'all'
                ? 'Free delivery on all orders.'
                : 'Free delivery on orders above ৳' . (int) $this->minimumAmount() . '.';
        }
        $min = DeliveryCity::active()->whereNotNull('delivery_charge')->min('delivery_charge');
        $max = DeliveryCity::active()->whereNotNull('delivery_charge')->max('delivery_charge');
        if ($min === null) return 'Delivery charges vary by city.';
        return ((int) $min === (int) $max)
            ? 'Delivery charge is ৳' . (int) $min . '.'
            : 'Delivery charges range from ৳' . (int) $min . ' to ৳' . (int) $max . ', depending on your city.';
    }

    /** Shared with the frontend so messaging/estimates stay in sync. */
    public function publicConfig(): array
    {
        return [
            'free_enabled'      => $this->freeEnabled(),
            'type'              => $this->type(),
            'minimum'           => $this->minimumAmount(),
            'no_charge_message' => $this->noChargeMessage(),
            // Location-based mode: no charge at checkout, staff add it later.
            'location_based'    => $this->locationBased(),
            'location_message'  => $this->locationMessage(),
            'charges_enabled'   => $this->chargesEnabled(),
            // Lowest configured city charge — the mini-cart's estimate before a city is chosen.
            'estimate'          => ($this->locationBased() || ! $this->chargesEnabled())
                ? 0.0
                : (float) (DeliveryCity::active()->whereNotNull('delivery_charge')->min('delivery_charge') ?? 0),
        ];
    }
}

<?php

namespace App\Support;

use App\Models\DeliveryArea;

/**
 * Single source of truth for delivery charges. Reads the admin-managed
 * "delivery" settings group — nothing about delivery cost is hardcoded
 * elsewhere in the app.
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

    public function inside(): float
    {
        return (float) ($this->data()['inside_dhaka_delivery_charge'] ?? 80);
    }

    public function outside(): float
    {
        return (float) ($this->data()['outside_dhaka_delivery_charge'] ?? 150);
    }

    public function isInsideDhaka(?DeliveryArea $area): bool
    {
        $city = $area?->city?->name;
        return $city !== null && strcasecmp(trim($city), 'Dhaka') === 0;
    }

    /**
     * Delivery charge for a given area + cart subtotal.
     *  - Free (all)      → always 0
     *  - Free (minimum)  → 0 when subtotal ≥ minimum, otherwise the location charge
     *  - Free off        → the location charge
     * With no area (cart page) the Inside-Dhaka rate is the estimate; checkout
     * resolves the exact charge by location.
     */
    public function chargeFor(?DeliveryArea $area, float $subtotal = 0): float
    {
        if ($this->freeEnabled()) {
            if ($this->type() === 'all' || $subtotal >= $this->minimumAmount()) {
                return 0.0;
            }
        }
        if (! $area) {
            return $this->inside();
        }
        return $this->isInsideDhaka($area) ? $this->inside() : $this->outside();
    }

    /** Charge by Inside/Outside Dhaka boolean (used where no specific area is picked, e.g. chatbot). */
    public function chargeForInside(bool $inside, float $subtotal = 0): float
    {
        if ($this->freeEnabled() && ($this->type() === 'all' || $subtotal >= $this->minimumAmount())) {
            return 0.0;
        }
        return $inside ? $this->inside() : $this->outside();
    }

    /** Shared with the frontend so messaging/estimates stay in sync. */
    public function publicConfig(): array
    {
        return [
            'free_enabled' => $this->freeEnabled(),
            'type'         => $this->type(),
            'minimum'      => $this->minimumAmount(),
            'inside'       => $this->inside(),
            'outside'      => $this->outside(),
        ];
    }
}

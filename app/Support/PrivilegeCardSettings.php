<?php

namespace App\Support;

use App\Models\WebsiteSetting;

/**
 * Editable Privilege Card content (front/back text, card-number scheme and the
 * customer-facing program copy). Stored as one JSON blob in website_settings
 * and merged over these defaults so a missing key never breaks the card.
 */
class PrivilegeCardSettings
{
    public const KEY = 'privilege_card';

    /** Random part of a card number — the last group on the artwork ("2606 1510 •••••••"). */
    public const RANDOM_DIGITS = 7;

    public function defaults(): array
    {
        return [
            // Front
            'tier_line_1'   => 'PRIVILEGE',
            'tier_line_2'   => 'CARD',
            // Back
            'terms_heading' => 'TERMS & CONDITIONS',
            'terms'         => [
                'Valid only for the registered cardholder and non-transferable.',
                'Present the card or Card No. for eligible online and offline purchases.',
                'Benefits cannot be combined with other offers unless stated.',
                'Delivery charges and applicable fees are excluded.',
                'Lost or misused cards may be cancelled.',
                'Kizora reserves the right to modify or terminate any privilege without prior notice.',
            ],
            // random:     random_prefix + 7 random digits ("26061510" + "4829173") — can't be guessed
            // sequential: number_prefix + zero-padded serial ("2606151021" + "00001")
            'number_mode'   => 'random',
            'random_prefix' => '26061510',
            'number_prefix' => '2606151021',
            'serial_digits' => 5,
            'serial_start'  => 1,
            // Customer-facing program copy
            'accepting_applications' => true,
            'program_title' => 'Kizora Privilege Membership',
            'program_intro' => 'Join our exclusive circle of members and enjoy special pricing, early access to new arrivals and priority care — online and in store.',
            'benefits'      => [
                'Exclusive member-only prices and offers',
                'Early access to new arrivals and limited drops',
                'Priority customer support',
                'Use your card online and at the Kizora store',
            ],
            // Customers may apply only after one completed order of at least this much (0 = no requirement)
            'min_order_to_apply' => 5000,
            // Member discount — % off the order subtotal (delivery excluded). 0 = off.
            'member_discount_percent'     => 0,
            // Guests can type a card number at checkout to claim the discount.
            'checkout_card_field'         => true,
            // false = only the bigger of coupon / member discount applies (per the card terms).
            'discount_stacks_with_coupon' => false,
        ];
    }

    public function get(): array
    {
        $saved = json_decode((string) WebsiteSetting::get(self::KEY, ''), true);
        return array_merge($this->defaults(), is_array($saved) ? $saved : []);
    }

    public function save(array $data): void
    {
        $merged = array_merge($this->get(), $data);
        WebsiteSetting::set(self::KEY, json_encode($merged, JSON_UNESCAPED_UNICODE));
    }

    public function minOrderToApply(): float
    {
        return max(0, (float) $this->get()['min_order_to_apply']);
    }

    public function discountPercent(): float
    {
        return max(0, min(100, (float) $this->get()['member_discount_percent']));
    }

    /** Guest card-number field on checkout — only meaningful while a discount is set. */
    public function guestFieldEnabled(): bool
    {
        return $this->discountPercent() > 0 && (bool) $this->get()['checkout_card_field'];
    }

    /** Only the fields the card artwork renders. */
    public function cardContent(): array
    {
        $s = $this->get();
        return [
            'tier_line_1'   => $s['tier_line_1'],
            'tier_line_2'   => $s['tier_line_2'],
            'terms_heading' => $s['terms_heading'],
            'terms'         => array_values($s['terms']),
        ];
    }
}

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
            // Card number = prefix + zero-padded serial ("2606151021" + "00001")
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

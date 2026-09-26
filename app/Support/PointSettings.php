<?php

namespace App\Support;

use App\Models\WebsiteSetting;

/**
 * Reward-point rules, editable from Admin → Privilege Members → Points.
 * Stored as one JSON blob in website_settings, merged over these defaults.
 */
class PointSettings
{
    public const KEY = 'reward_points';

    public function defaults(): array
    {
        return [
            'enabled'            => true,
            // Earning: `earn_points` for every full `earn_amount` ৳ spent (৳100 → 1 point)
            'earn_amount'        => 100,
            'earn_points'        => 1,
            // Redeeming: 1 point = `point_value` ৳, only once the balance reaches `min_redeem`
            'point_value'        => 1,
            'min_redeem'         => 50,
            // Points can cover at most this % of the product amount of one order
            'max_redeem_percent' => 100,
        ];
    }

    public function get(): array
    {
        $saved = json_decode((string) WebsiteSetting::get(self::KEY, ''), true);
        return array_merge($this->defaults(), is_array($saved) ? $saved : []);
    }

    public function save(array $data): void
    {
        WebsiteSetting::set(self::KEY, json_encode(array_merge($this->get(), $data), JSON_UNESCAPED_UNICODE));
    }

    /** The rules the storefront needs (checkout + account badge). */
    public function publicRules(): array
    {
        $s = $this->get();
        return [
            'earn_amount' => (float) $s['earn_amount'],
            'earn_points' => (int) $s['earn_points'],
            'point_value' => (float) $s['point_value'],
            'min_redeem'  => (int) $s['min_redeem'],
        ];
    }
}

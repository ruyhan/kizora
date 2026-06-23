<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentGatewaySettingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentGatewayController extends Controller
{
    public function __construct(
        private readonly PaymentGatewaySettingService $gatewaySettings
    ) {}

    public function index()
    {
        $settings = $this->gatewaySettings->getAll();

        // Mask sensitive fields before sending to frontend
        foreach (['bkash', 'nagad', 'sslcommerz'] as $gateway) {
            foreach ($settings[$gateway] as $key => $value) {
                if ($value && $this->isSensitive($gateway, $key)) {
                    $settings[$gateway][$key] = '••••••••';
                }
            }
            // Attach the logo URL for display
            $settings[$gateway]['logo_url'] = $this->gatewaySettings->getLogoUrl($gateway);
        }

        return Inertia::render('Admin/Payments/Index', [
            'settings' => $settings,
        ]);
    }

    /** Save credential settings (no file upload) */
    public function update(Request $request, string $gateway)
    {
        if (! in_array($gateway, ['bkash', 'nagad', 'sslcommerz'], true)) {
            abort(404);
        }

        $data = $request->validate($this->validationRules($gateway));

        // Preserve sensitive fields if the mask placeholder is sent
        foreach ($data as $field => $value) {
            if ($value === '••••••••' || $value === '') {
                $data[$field] = null;
            }
        }

        $this->gatewaySettings->saveGateway($gateway, $data);

        return back()->with('success', ucfirst($gateway) . ' settings saved.');
    }

    /** Set a gateway logo from a Media Library image (URL) */
    public function uploadLogo(Request $request, string $gateway)
    {
        if (! in_array($gateway, ['bkash', 'nagad', 'sslcommerz'], true)) {
            abort(404);
        }

        $data = $request->validate([
            'logo_url' => 'required|string|max:1000',
        ]);

        $this->gatewaySettings->setLogoPath($gateway, $data['logo_url']);

        return back()->with('success', 'Logo updated.');
    }

    /** Clear the logo for a gateway (the Media Library keeps the file) */
    public function deleteLogo(string $gateway)
    {
        if (! in_array($gateway, ['bkash', 'nagad', 'sslcommerz'], true)) {
            abort(404);
        }

        $this->gatewaySettings->clearLogoPath($gateway);

        return back()->with('success', 'Logo removed.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function isSensitive(string $gateway, string $field): bool
    {
        $sensitive = [
            'bkash'      => ['app_secret', 'password'],
            'nagad'      => ['private_key'],
            'sslcommerz' => ['store_password'],
        ];
        return in_array($field, $sensitive[$gateway] ?? [], true);
    }

    private function validationRules(string $gateway): array
    {
        $base = [
            'enabled' => 'required|in:0,1',
            'mode'    => 'required|in:sandbox,live',
        ];

        return match ($gateway) {
            'bkash' => $base + [
                'app_key'      => 'nullable|string|max:200',
                'app_secret'   => 'nullable|string|max:500',
                'username'     => 'nullable|string|max:200',
                'password'     => 'nullable|string|max:500',
                'callback_url' => 'nullable|url|max:500',
            ],
            'nagad' => $base + [
                'merchant_id'     => 'nullable|string|max:100',
                'merchant_number' => 'nullable|string|max:20',
                'public_key'      => 'nullable|string|max:2000',
                'private_key'     => 'nullable|string|max:2000',
                'callback_url'    => 'nullable|url|max:500',
            ],
            'sslcommerz' => $base + [
                'store_id'       => 'nullable|string|max:200',
                'store_password' => 'nullable|string|max:500',
                'success_url'    => 'nullable|url|max:500',
                'fail_url'       => 'nullable|url|max:500',
                'cancel_url'     => 'nullable|url|max:500',
                'ipn_url'        => 'nullable|url|max:500',
            ],
        };
    }
}

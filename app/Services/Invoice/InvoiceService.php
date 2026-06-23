<?php

namespace App\Services\Invoice;

use App\Models\Invoice;
use App\Models\Order;
use App\Support\PageContent;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

/**
 * Single reusable invoice engine for website + POS orders.
 * All branding/content comes from the admin "invoice" settings — nothing hardcoded.
 */
class InvoiceService
{
    public function __construct(private readonly PageContent $content) {}

    public function settings(): array
    {
        return $this->content->get('invoice');
    }

    public function ensureFor(Order $order): Invoice
    {
        return Invoice::forOrder($order);
    }

    /**
     * Regenerate the PDF. The number is the order number and never changes —
     * this simply re-syncs and re-renders the latest order contents.
     */
    public function regenerate(Order $order): Invoice
    {
        return Invoice::forOrder($order);
    }

    public function filename(Order $order): string
    {
        // Required naming convention: INV-{OrderNumber}.pdf
        return 'INV-' . $order->order_number . '.pdf';
    }

    /** Assemble everything the invoice template needs. */
    public function data(Order $order, bool $print = false): array
    {
        $order->loadMissing('items', 'user');
        $settings = $this->settings();

        return [
            'settings' => $settings,
            'logoSrc'  => $this->logoSrc($settings['company_logo'] ?? ''),
            'invoice'  => $this->ensureFor($order),
            'order'    => $order,
            'print'    => $print,
            'customer' => [
                'name'    => $order->user?->name ?? $order->guest_name ?? 'Customer',
                'phone'   => $order->user?->phone ?? $order->guest_phone,
                'email'   => $order->user?->email ?? $order->guest_email,
                'address' => collect([$order->delivery_address, $order->delivery_area, $order->delivery_city])
                    ->filter()->implode(', '),
            ],
        ];
    }

    public function view(Order $order, bool $print = false)
    {
        return view('invoices.invoice', $this->data($order, $print));
    }

    public function pdf(Order $order)
    {
        return Pdf::loadView('invoices.invoice', $this->data($order))->setPaper('a4');
    }

    /**
     * Resolve the logo to a base64 data URI so it renders reliably in both the
     * browser preview and the dompdf-generated PDF (dompdf can't fetch remote URLs).
     */
    private function logoSrc(string $logo): ?string
    {
        if (! $logo) {
            return null;
        }

        $rel = null;
        if (str_contains($logo, '/storage/')) {
            $rel = substr($logo, strpos($logo, '/storage/') + 1); // "storage/..."
        } elseif (str_starts_with($logo, 'storage/')) {
            $rel = $logo;
        }

        if ($rel) {
            $path = public_path($rel);
            if (is_file($path)) {
                $mime = function_exists('mime_content_type') ? (mime_content_type($path) ?: 'image/png') : 'image/png';
                return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path));
            }
        }

        return $logo; // fallback (browser can still load a URL)
    }
}

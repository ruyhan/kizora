<?php

namespace App\Http\Controllers\Admin\Accounts;

use App\Http\Controllers\Controller;
use App\Models\AccountImportBatch;
use App\Models\AccountTransaction;
use App\Services\Accounting\AccountingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportController extends Controller
{
    /** System field => human label, for the column-mapping UI. */
    private const FIELDS = [
        'date' => 'Date', 'voucher_no' => 'Voucher No.', 'type' => 'Transaction Type',
        'account_head' => 'Account Head', 'party' => 'Party', 'description' => 'Description',
        'payment_account' => 'Payment Account', 'partner' => 'Partner', 'cash_in' => 'Cash In',
        'cash_out' => 'Cash Out', 'vat_tax' => 'VAT/Tax', 'discount' => 'Discount',
        'status' => 'Status', 'remarks' => 'Remarks',
    ];

    public function __construct(private readonly AccountingService $acc) {}

    public function index(Request $request): Response
    {
        $settings = $this->acc->settings();

        return Inertia::render('Admin/Accounts/Import', [
            'fields' => self::FIELDS,
            'lists'  => [
                'transaction_types' => $settings['transaction_types'],
                'account_heads' => $settings['account_heads'],
                'payment_accounts' => $settings['payment_accounts'],
                'statuses' => $settings['statuses'],
            ],
            'batches' => AccountImportBatch::latest()->take(20)->get()->map(fn ($b) => [
                'id' => $b->id,
                'filename' => $b->filename,
                'total_rows' => $b->total_rows,
                'imported' => $b->imported,
                'skipped' => $b->skipped,
                'duplicates' => $b->duplicates,
                'user_name' => $b->user_name,
                'created_at' => $b->created_at->format('d M Y, h:i A'),
            ]),
        ]);
    }

    /** Step 1–2: parse the file and return headers, raw rows and a suggested mapping. */
    public function preview(Request $request)
    {
        $request->validate(['file' => 'required|file|max:10240|mimes:xlsx,xls,csv,txt']);

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
            $data = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Could not read the file: ' . $e->getMessage()], 422);
        }

        // Drop fully-empty rows.
        $data = array_values(array_filter($data, fn ($r) => count(array_filter($r, fn ($c) => $c !== null && $c !== '')) > 0));
        if (count($data) < 2) {
            return response()->json(['error' => 'The file has no data rows.'], 422);
        }

        $headers = array_map(fn ($h) => trim((string) $h), $data[0]);
        $rows = array_slice($data, 1);
        if (count($rows) > 5000) {
            return response()->json(['error' => 'Too many rows (' . count($rows) . '). Max 5,000 per import.'], 422);
        }

        $mapping = [];
        foreach (self::FIELDS as $key => $label) {
            $mapping[$key] = $this->guessColumn($headers, $key, $label);
        }

        return response()->json([
            'headers' => $headers,
            'rows' => $rows,
            'mapping' => $mapping,
            'fields' => self::FIELDS,
            'total' => count($rows),
        ]);
    }

    private function guessColumn(array $headers, string $key, string $label): ?int
    {
        $aliases = [
            'date' => ['date'], 'voucher_no' => ['voucher', 'voucher no', 'voucher no.', 'vno'],
            'type' => ['transaction type', 'type'], 'account_head' => ['account head', 'head', 'account'],
            'party' => ['party', 'customer', 'supplier', 'party / customer / supplier'],
            'description' => ['description', 'details', 'narration'], 'payment_account' => ['payment account', 'payment', 'account paid'],
            'partner' => ['partner'], 'cash_in' => ['cash in', 'debit', 'in', 'received'],
            'cash_out' => ['cash out', 'credit', 'out', 'paid'], 'vat_tax' => ['vat/tax', 'vat', 'tax'],
            'discount' => ['discount'], 'status' => ['status'], 'remarks' => ['remarks', 'note', 'notes'],
        ];
        $want = $aliases[$key] ?? [strtolower($label)];
        foreach ($headers as $i => $h) {
            if (in_array(strtolower(trim($h)), $want, true)) {
                return $i;
            }
        }
        return null;
    }

    /** Step 4: validate + insert the mapped rows as a batch. */
    public function commit(Request $request)
    {
        $data = $request->validate([
            'filename'    => 'nullable|string|max:255',
            'rows'        => 'required|array|max:5000',
            'rows.*'      => 'array',
            'overwrite'   => 'boolean',
        ]);

        $settings = $this->acc->settings();
        $statuses = $settings['statuses'];
        $overwrite = (bool) ($data['overwrite'] ?? false);

        $batch = AccountImportBatch::create([
            'filename'   => $data['filename'] ?? 'import',
            'total_rows' => count($data['rows']),
            'user_name'  => $request->user()->name,
        ]);

        $imported = 0;
        $skipped = 0;
        $duplicates = 0;
        $reasons = [];

        foreach ($data['rows'] as $i => $r) {
            $date = $this->toDate($r['date'] ?? null);
            $in   = is_numeric($r['cash_in'] ?? null) ? round((float) $r['cash_in'], 2) : 0.0;
            $out  = is_numeric($r['cash_out'] ?? null) ? round((float) $r['cash_out'], 2) : 0.0;
            $type = trim((string) ($r['type'] ?? ''));

            if (! $date) { $skipped++; $reasons[] = 'Row ' . ($i + 1) . ': invalid date'; continue; }
            if ($type === '') { $skipped++; $reasons[] = 'Row ' . ($i + 1) . ': missing type'; continue; }
            if ($in > 0 && $out > 0) { $skipped++; $reasons[] = 'Row ' . ($i + 1) . ': both Cash In and Cash Out'; continue; }
            if ($in <= 0 && $out <= 0) { $skipped++; $reasons[] = 'Row ' . ($i + 1) . ': no amount'; continue; }

            $status = in_array($r['status'] ?? null, $statuses, true) ? $r['status'] : 'Draft';
            $desc = (string) ($r['description'] ?? '');

            // Duplicate = same Date + Amount + Type + Description.
            $dupe = AccountTransaction::whereDate('date', $date)->where('type', $type)
                ->where('description', $desc)->where('cash_in', $in)->where('cash_out', $out)->first();

            $payload = [
                'date' => $date, 'type' => $type,
                'account_head' => $r['account_head'] ?? null, 'party' => $r['party'] ?? null,
                'description' => $desc ?: null, 'payment_account' => $r['payment_account'] ?? null,
                'partner' => $r['partner'] ?? null, 'cash_in' => $in, 'cash_out' => $out,
                'vat_tax' => is_numeric($r['vat_tax'] ?? null) ? (float) $r['vat_tax'] : 0,
                'discount' => is_numeric($r['discount'] ?? null) ? (float) $r['discount'] : 0,
                'status' => $status, 'remarks' => $r['remarks'] ?? null,
                'import_batch_id' => $batch->id,
            ];

            if ($dupe) {
                $duplicates++;
                if (! $overwrite) { $skipped++; $reasons[] = 'Row ' . ($i + 1) . ': duplicate skipped'; continue; }
                $dupe->update($payload);
                $imported++;
                continue;
            }

            $voucher = trim((string) ($r['voucher_no'] ?? ''));
            if ($voucher === '' || AccountTransaction::where('voucher_no', $voucher)->exists()) {
                $voucher = $this->acc->nextVoucherNo();
            }
            $payload['voucher_no'] = $voucher;
            AccountTransaction::create($payload);
            $imported++;
        }

        $batch->update(['imported' => $imported, 'skipped' => $skipped, 'duplicates' => $duplicates]);

        return response()->json([
            'batch_id' => $batch->id,
            'processed' => count($data['rows']),
            'imported' => $imported,
            'skipped' => $skipped,
            'duplicates' => $duplicates,
            'reasons' => array_slice($reasons, 0, 50),
        ]);
    }

    public function rollback(AccountImportBatch $batch)
    {
        $batch->transactions()->delete();
        $batch->delete();

        return back()->with('success', 'Import batch rolled back and its transactions removed.');
    }

    /** Excel serial number or date string → Y-m-d (null if unparseable). */
    private function toDate($v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v)) {
            // Excel serial: days since 1899-12-30 (handles the 1900 leap-year bug for serials ≥ 60).
            return Carbon::create(1899, 12, 30)->addDays((int) round((float) $v))->format('Y-m-d');
        }
        try {
            return Carbon::parse($v)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}

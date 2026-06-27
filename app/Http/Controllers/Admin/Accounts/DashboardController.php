<?php

namespace App\Http\Controllers\Admin\Accounts;

use App\Http\Controllers\Controller;
use App\Services\Accounting\AccountingService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly AccountingService $acc) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Accounts/Dashboard', [
            'data'     => $this->acc->dashboard(),
            'currency' => $this->acc->settings()['currency'],
        ]);
    }
}

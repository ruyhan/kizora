<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class PwaController extends Controller
{
    /** Serve the built service worker from the site root so its scope is "/". */
    public function serviceWorker(): Response
    {
        $path = public_path('build/sw.js');
        abort_unless(is_file($path), 404);

        return response((string) file_get_contents($path), 200, [
            'Content-Type'           => 'application/javascript',
            'Service-Worker-Allowed' => '/',
            'Cache-Control'          => 'no-cache',
        ]);
    }
}

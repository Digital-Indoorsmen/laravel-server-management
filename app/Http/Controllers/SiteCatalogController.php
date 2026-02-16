<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\Site;
use Inertia\Inertia;
use Inertia\Response;

class SiteCatalogController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Sites/Catalog', [
            'sites' => Site::query()->with('server')->latest()->get(),
            'servers' => Server::query()->latest()->get(),
        ]);
    }
}

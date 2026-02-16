<?php

namespace App\Http\Controllers;

use App\Models\Database;
use Inertia\Inertia;
use Inertia\Response;

class DatabaseController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Databases/Index', [
            'databases' => Database::query()->with(['site', 'server'])->latest()->get(),
        ]);
    }
}

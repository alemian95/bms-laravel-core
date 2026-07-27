<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardStats;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardStats $stats): Response
    {
        return Inertia::render('dashboard', $stats->for($request->user()));
    }
}

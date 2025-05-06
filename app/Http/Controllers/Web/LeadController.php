<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class LeadController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Leads/Index');
    }
}

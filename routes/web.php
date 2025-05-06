<?php

declare(strict_types=1);

use App\Http\Controllers\Webhook\WebhookController;
 use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn() => Inertia::render('welcome'))->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', fn() => Inertia::render('dashboard'))->name('dashboard');
});

Route::post('/webhook', [WebhookController::class, 'handle']);

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';

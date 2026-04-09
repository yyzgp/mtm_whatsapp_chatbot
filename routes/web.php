<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('dashboard'));

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', App\Livewire\Dashboard\DashboardPage::class)->name('dashboard');

    Route::get('/customers', App\Livewire\Customers\CustomerPage::class)->name('customers.index');
    Route::get('/customers/{customer}', App\Livewire\Customers\CustomerProfile::class)->name('customers.show');

    Route::get('/chat', App\Livewire\Chat\ChatInterface::class)->name('chat.index');

    Route::get('/users', App\Livewire\Users\UserPage::class)->name('users.index');
    Route::get('/roles', App\Livewire\Roles\RolePage::class)->name('roles.index');
    Route::get('/teams', App\Livewire\Teams\TeamPage::class)->name('teams.index');

    Route::get('/reports', App\Livewire\Reports\ReportPage::class)->name('reports.index');

    Route::get('/settings', App\Livewire\Settings\SettingsPage::class)->name('settings.index');
    Route::get('/workflows', App\Livewire\Workflows\WorkflowPage::class)->name('workflows.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/fcm-token', function (\Illuminate\Http\Request $request) {
        $request->validate(['token' => 'required|string']);
        $request->user()->update(['fcm_token' => $request->token]);
        return response()->json(['ok' => true]);
    })->name('fcm-token.store');
});

require __DIR__.'/auth.php';

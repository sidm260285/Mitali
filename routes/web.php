<?php

use App\Http\Controllers\Admin\AccountHeadController;
use App\Http\Controllers\Admin\BankController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\BankFlow\BankFlowController;
use App\Http\Controllers\BankFlow\InflowController as BankInflowController;
use App\Http\Controllers\BankFlow\OutflowController as BankOutflowController;
use App\Http\Controllers\CashFlow\InflowController;
use App\Http\Controllers\CashFlow\OutflowController;
use App\Http\Controllers\CashFlow\TransactionController;
use App\Http\Controllers\CashFlow\TransferController;
use App\Http\Controllers\Admin\ExecutiveController;
use App\Http\Controllers\Admin\PasswordController as AdminPasswordController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\TrainerController;
use App\Http\Controllers\Admin\TrainerDocumentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Bank\DashboardController as BankDashboardController;
use App\Http\Controllers\Executive\DashboardController as ExecutiveDashboardController;
use App\Http\Controllers\Executive\PasswordController as ExecutivePasswordController;
use App\Http\Controllers\Executive\ProfileController as ExecutiveProfileController;
use App\Http\Controllers\ForcedPasswordChangeController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->to(auth()->user()->dashboardRoute());
    }

    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::get('/password/force-change', [ForcedPasswordChangeController::class, 'show'])
        ->name('password.force-change');
    Route::put('/password/force-change', [ForcedPasswordChangeController::class, 'update'])
        ->name('password.force-change.update');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

Route::bind('executive', function (string $value) {
    return User::executives()->findOrFail($value);
});

Route::bind('bank', function (string $value) {
    return User::banks()->findOrFail($value);
});

Route::prefix('admin')
    ->middleware(['auth', 'admin', 'password.changed'])
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/profile', [AdminProfileController::class, 'show'])->name('profile.show');
        Route::get('/profile/edit', [AdminProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [AdminProfileController::class, 'update'])->name('profile.update');

        Route::get('/change-password', [AdminPasswordController::class, 'edit'])->name('password.edit');
        Route::put('/change-password', [AdminPasswordController::class, 'update'])->name('password.update');

        Route::get('/executives', [ExecutiveController::class, 'index'])->name('executives.index');
        Route::get('/executives/create', [ExecutiveController::class, 'create'])->name('executives.create');
        Route::post('/executives', [ExecutiveController::class, 'store'])->name('executives.store');
        Route::get('/executives/{executive}', [ExecutiveController::class, 'show'])->name('executives.show');
        Route::get('/executives/{executive}/edit', [ExecutiveController::class, 'edit'])->name('executives.edit');
        Route::put('/executives/{executive}', [ExecutiveController::class, 'update'])->name('executives.update');
        Route::patch('/executives/{executive}/deactivate', [ExecutiveController::class, 'deactivate'])->name('executives.deactivate');
        Route::patch('/executives/{executive}/activate', [ExecutiveController::class, 'activate'])->name('executives.activate');
        Route::post('/executives/{executive}/reset-password', [ExecutiveController::class, 'resetPassword'])->name('executives.reset-password');
        Route::get('/executives/{executive}/reset-password/reveal', [ExecutiveController::class, 'revealResetPassword'])->name('executives.reset-password.reveal');

        Route::get('/banks', [BankController::class, 'index'])->name('banks.index');
        Route::get('/banks/create', [BankController::class, 'create'])->name('banks.create');
        Route::post('/banks', [BankController::class, 'store'])->name('banks.store');
        Route::get('/banks/{bank}', [BankController::class, 'show'])->name('banks.show');
        Route::get('/banks/{bank}/edit', [BankController::class, 'edit'])->name('banks.edit');
        Route::put('/banks/{bank}', [BankController::class, 'update'])->name('banks.update');
        Route::patch('/banks/{bank}/deactivate', [BankController::class, 'deactivate'])->name('banks.deactivate');
        Route::patch('/banks/{bank}/activate', [BankController::class, 'activate'])->name('banks.activate');
        Route::post('/banks/{bank}/reset-password', [BankController::class, 'resetPassword'])->name('banks.reset-password');
        Route::get('/banks/{bank}/reset-password/reveal', [BankController::class, 'revealResetPassword'])->name('banks.reset-password.reveal');

        Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
        Route::get('/sessions/create', [SessionController::class, 'create'])->name('sessions.create');
        Route::post('/sessions', [SessionController::class, 'store'])->name('sessions.store');
        Route::get('/sessions/{session}/edit', [SessionController::class, 'edit'])->name('sessions.edit');
        Route::put('/sessions/{session}', [SessionController::class, 'update'])->name('sessions.update');
        Route::delete('/sessions/{session}', [SessionController::class, 'destroy'])->name('sessions.destroy');

        Route::get('/trainers', [TrainerController::class, 'index'])->name('trainers.index');
        Route::get('/trainers/create', [TrainerController::class, 'create'])->name('trainers.create');
        Route::post('/trainers', [TrainerController::class, 'store'])->name('trainers.store');
        Route::get('/trainers/{trainer}', [TrainerController::class, 'show'])->name('trainers.show');
        Route::get('/trainers/{trainer}/edit', [TrainerController::class, 'edit'])->name('trainers.edit');
        Route::put('/trainers/{trainer}', [TrainerController::class, 'update'])->name('trainers.update');
        Route::patch('/trainers/{trainer}/deactivate', [TrainerController::class, 'deactivate'])->name('trainers.deactivate');
        Route::patch('/trainers/{trainer}/activate', [TrainerController::class, 'activate'])->name('trainers.activate');

        Route::get('/trainers/{trainer}/documents', [TrainerDocumentController::class, 'index'])->name('trainers.documents.index');
        Route::post('/trainers/{trainer}/documents', [TrainerDocumentController::class, 'store'])->name('trainers.documents.store');
        Route::get('/trainers/{trainer}/documents/{document}', [TrainerDocumentController::class, 'show'])->name('trainers.documents.show');
        Route::delete('/trainers/{trainer}/documents/{document}', [TrainerDocumentController::class, 'destroy'])->name('trainers.documents.destroy');

        Route::get('/account-heads', [AccountHeadController::class, 'index'])->name('account-heads.index');
        Route::get('/account-heads/create', [AccountHeadController::class, 'create'])->name('account-heads.create');
        Route::post('/account-heads', [AccountHeadController::class, 'store'])->name('account-heads.store');
        Route::get('/account-heads/{account_head}/edit', [AccountHeadController::class, 'edit'])->name('account-heads.edit');
        Route::put('/account-heads/{account_head}', [AccountHeadController::class, 'update'])->name('account-heads.update');
        Route::delete('/account-heads/{account_head}', [AccountHeadController::class, 'destroy'])->name('account-heads.destroy');

        Route::prefix('cash-flow')->name('cash-flow.')->group(function () {
            Route::get('/inflow', [InflowController::class, 'create'])->name('inflow.create');
            Route::post('/inflow', [InflowController::class, 'store'])->name('inflow.store');
            Route::get('/outflow', [OutflowController::class, 'create'])->name('outflow.create');
            Route::post('/outflow', [OutflowController::class, 'store'])->name('outflow.store');
            Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
            Route::get('/executive-transactions', [TransactionController::class, 'executiveIndex'])->name('executive-transactions.index');
            Route::get('/transfer-to-executive', [TransferController::class, 'createAdminTransfer'])->name('transfer-to-executive.create');
            Route::post('/transfer-to-executive', [TransferController::class, 'storeAdminTransfer'])->name('transfer-to-executive.store');
            Route::get('/transactions/{cash_transaction}', [TransactionController::class, 'show'])
                ->whereNumber('cash_transaction')
                ->name('transactions.show');
        });

        Route::prefix('bank-flow')->name('bank-flow.')->group(function () {
            Route::get('/inflow', [BankInflowController::class, 'create'])->name('inflow.create');
            Route::post('/inflow', [BankInflowController::class, 'store'])->name('inflow.store');
            Route::get('/outflow', [BankOutflowController::class, 'create'])->name('outflow.create');
            Route::post('/outflow', [BankOutflowController::class, 'store'])->name('outflow.store');
            Route::get('/banks/{bank}/balance', [BankFlowController::class, 'bankBalance'])->name('bank-balance');
        });
    });

Route::prefix('executive')
    ->middleware(['auth', 'executive', 'password.changed'])
    ->name('executive.')
    ->group(function () {
        Route::get('/dashboard', [ExecutiveDashboardController::class, 'index'])->name('dashboard');

        Route::get('/profile', [ExecutiveProfileController::class, 'show'])->name('profile.show');
        Route::get('/profile/edit', [ExecutiveProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ExecutiveProfileController::class, 'update'])->name('profile.update');

        Route::get('/change-password', [ExecutivePasswordController::class, 'edit'])->name('password.edit');
        Route::put('/change-password', [ExecutivePasswordController::class, 'update'])->name('password.update');

        Route::prefix('cash-flow')->name('cash-flow.')->group(function () {
            Route::get('/inflow', [InflowController::class, 'create'])->name('inflow.create');
            Route::post('/inflow', [InflowController::class, 'store'])->name('inflow.store');
            Route::get('/outflow', [OutflowController::class, 'create'])->name('outflow.create');
            Route::post('/outflow', [OutflowController::class, 'store'])->name('outflow.store');
            Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
            Route::get('/transfer-to-executive', [TransferController::class, 'createExecutiveTransfer'])->name('transfer-to-executive.create');
            Route::post('/transfer-to-executive', [TransferController::class, 'storeExecutiveTransfer'])->name('transfer-to-executive.store');
            Route::get('/transfer-to-admin', [TransferController::class, 'createExecutiveToAdmin'])->name('transfer-to-admin.create');
            Route::post('/transfer-to-admin', [TransferController::class, 'storeExecutiveToAdmin'])->name('transfer-to-admin.store');
            Route::get('/transactions/{cash_transaction}', [TransactionController::class, 'show'])
                ->whereNumber('cash_transaction')
                ->name('transactions.show');
        });

        Route::prefix('bank-flow')->name('bank-flow.')->group(function () {
            Route::get('/inflow', [BankInflowController::class, 'create'])->name('inflow.create');
            Route::post('/inflow', [BankInflowController::class, 'store'])->name('inflow.store');
            Route::get('/outflow', [BankOutflowController::class, 'create'])->name('outflow.create');
            Route::post('/outflow', [BankOutflowController::class, 'store'])->name('outflow.store');
            Route::get('/banks/{bank}/balance', [BankFlowController::class, 'bankBalance'])->name('bank-balance');
        });
    });

Route::prefix('bank')
    ->middleware(['auth', 'bank', 'password.changed'])
    ->name('bank.')
    ->group(function () {
        Route::get('/dashboard', [BankDashboardController::class, 'index'])->name('dashboard');
    });

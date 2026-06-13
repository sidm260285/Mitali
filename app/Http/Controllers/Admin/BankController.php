<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBankRequest;
use App\Http\Requests\UpdateBankRequest;
use App\Models\User;
use App\Support\PasswordGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BankController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::banks()->orderBy('name');

        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->string('name').'%');
        }

        if ($request->filled('account_no')) {
            $query->where('account_no', 'like', '%'.$request->string('account_no').'%');
        }

        $banks = $query->paginate(25)->withQueryString();

        return view('admin.banks.index', compact('banks'));
    }

    public function create(): View
    {
        return view('admin.banks.create', [
            'defaultPassword' => PasswordGenerator::random(),
            'accountTypeOptions' => User::accountTypeOptions(),
        ]);
    }

    public function store(StoreBankRequest $request): RedirectResponse
    {
        User::create([
            ...$request->validated(),
            'role' => User::ROLE_BANK,
            'is_active' => true,
            'must_change_password' => false,
        ]);

        return redirect()->route('admin.banks.index')
            ->with('success', 'Bank created successfully.');
    }

    public function show(User $bank): View
    {
        $this->ensureBank($bank);

        return view('admin.banks.show', compact('bank'));
    }

    public function edit(User $bank): View
    {
        $this->ensureBank($bank);

        return view('admin.banks.edit', [
            'bank' => $bank,
            'accountTypeOptions' => User::accountTypeOptions(),
        ]);
    }

    public function update(UpdateBankRequest $request, User $bank): RedirectResponse
    {
        $this->ensureBank($bank);

        $bank->update($request->validated());

        return redirect()->route('admin.banks.index')
            ->with('success', 'Bank updated successfully.');
    }

    public function deactivate(User $bank): RedirectResponse
    {
        $this->ensureBank($bank);

        $bank->update(['is_active' => false]);

        return redirect()->route('admin.banks.index')
            ->with('success', 'Bank deactivated successfully.');
    }

    public function activate(User $bank): RedirectResponse
    {
        $this->ensureBank($bank);

        $bank->update(['is_active' => true]);

        return redirect()->route('admin.banks.index')
            ->with('success', 'Bank activated successfully.');
    }

    public function resetPassword(User $bank): RedirectResponse
    {
        $this->ensureBank($bank);

        $newPassword = PasswordGenerator::random();

        $bank->update([
            'password' => $newPassword,
            'must_change_password' => true,
        ]);

        return redirect()
            ->route('admin.banks.reset-password.reveal', $bank)
            ->with('reset_password', $newPassword)
            ->with('success', 'Password reset successfully. Copy the new password now.');
    }

    public function revealResetPassword(User $bank): View|RedirectResponse
    {
        $this->ensureBank($bank);

        $password = session('reset_password');

        if (! $password) {
            return redirect()->route('admin.banks.index')
                ->with('error', 'Password reveal is no longer available. Please reset the password again.');
        }

        session()->forget('reset_password');

        return view('admin.banks.reset-password-reveal', [
            'bank' => $bank,
            'password' => $password,
        ]);
    }

    private function ensureBank(User $bank): void
    {
        if (! $bank->isBank()) {
            abort(404);
        }
    }
}

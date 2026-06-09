<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountHeadRequest;
use App\Http\Requests\UpdateAccountHeadRequest;
use App\Models\AccountHead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountHeadController extends Controller
{
    public function index(Request $request): View
    {
        $query = AccountHead::query()->userManaged()->orderBy('name');

        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->string('name').'%');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        $accountHeads = $query->paginate(25)->withQueryString();

        return view('admin.account-heads.index', compact('accountHeads'));
    }

    public function create(): View
    {
        return view('admin.account-heads.create');
    }

    public function store(StoreAccountHeadRequest $request): RedirectResponse
    {
        AccountHead::create([
            ...$request->validated(),
            'is_system' => false,
        ]);

        return redirect()
            ->route('admin.account-heads.index')
            ->with('success', 'Accounts head created successfully.');
    }

    public function edit(AccountHead $accountHead): View|RedirectResponse
    {
        if ($accountHead->is_system) {
            abort(404);
        }

        return view('admin.account-heads.edit', compact('accountHead'));
    }

    public function update(UpdateAccountHeadRequest $request, AccountHead $accountHead): RedirectResponse
    {
        if ($accountHead->is_system || $accountHead->isInUse()) {
            return redirect()
                ->route('admin.account-heads.index')
                ->with('error', 'This accounts head cannot be edited because it is in use.');
        }

        $accountHead->update($request->validated());

        return redirect()
            ->route('admin.account-heads.index')
            ->with('success', 'Accounts head updated successfully.');
    }

    public function destroy(AccountHead $accountHead): RedirectResponse
    {
        if ($accountHead->is_system) {
            abort(404);
        }

        if ($accountHead->isInUse()) {
            return redirect()
                ->route('admin.account-heads.index')
                ->with('error', 'This accounts head cannot be deleted because it is in use.');
        }

        $accountHead->delete();

        return redirect()
            ->route('admin.account-heads.index')
            ->with('success', 'Accounts head deleted successfully.');
    }
}

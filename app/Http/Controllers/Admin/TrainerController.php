<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrainerRequest;
use App\Http\Requests\UpdateTrainerRequest;
use App\Models\Trainer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainerController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status', 'active')->toString();

        if (! in_array($status, ['active', 'inactive', 'all'], true)) {
            $status = 'active';
        }

        $query = Trainer::query()->orderByDesc('created_at');

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->string('name').'%');
        }

        if ($request->filled('phone')) {
            $query->where('mobile', 'like', '%'.$request->string('phone').'%');
        }

        $trainers = $query->paginate(25)->withQueryString();

        return view('admin.trainers.index', compact('trainers', 'status'));
    }

    public function create(): View
    {
        return view('admin.trainers.create');
    }

    public function store(StoreTrainerRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['bank_account_holder_name'] = $data['name'];
        $data['is_active'] = true;

        $trainer = Trainer::create($data);

        return redirect()
            ->route('admin.trainers.show', $trainer)
            ->with('success', 'Trainer created successfully. You can add documents when ready.');
    }

    public function show(Trainer $trainer): View
    {
        $trainer->load(['documents', 'profileImage']);

        return view('admin.trainers.show', compact('trainer'));
    }

    public function edit(Trainer $trainer): View|RedirectResponse
    {
        if (! $trainer->isActive()) {
            return redirect()
                ->route('admin.trainers.show', $trainer)
                ->with('error', 'Inactive trainers cannot be edited.');
        }

        return view('admin.trainers.edit', compact('trainer'));
    }

    public function update(UpdateTrainerRequest $request, Trainer $trainer): RedirectResponse
    {
        $data = $request->validated();
        $data['bank_account_holder_name'] = $data['name'];

        $trainer->update($data);

        return redirect()
            ->route('admin.trainers.show', $trainer)
            ->with('success', 'Trainer updated successfully.');
    }

    public function deactivate(Trainer $trainer): RedirectResponse
    {
        $trainer->update(['is_active' => false]);

        return redirect()
            ->route('admin.trainers.index', ['status' => 'inactive'])
            ->with('success', 'Trainer deactivated successfully.');
    }

    public function activate(Trainer $trainer): RedirectResponse
    {
        $trainer->update(['is_active' => true]);

        return redirect()
            ->route('admin.trainers.index')
            ->with('success', 'Trainer activated successfully.');
    }
}

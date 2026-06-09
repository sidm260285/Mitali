<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrainerDocumentRequest;
use App\Models\Trainer;
use App\Models\TrainerDocument;
use App\Services\TrainerDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrainerDocumentController extends Controller
{
    public function __construct(
        private TrainerDocumentService $documentService,
    ) {}

    public function index(Trainer $trainer): View|RedirectResponse
    {
        if (! $trainer->isActive()) {
            return redirect()
                ->route('admin.trainers.show', $trainer)
                ->with('error', 'Documents cannot be managed while the trainer is inactive.');
        }

        $trainer->load('documents');

        return view('admin.trainers.documents', [
            'trainer' => $trainer,
            'documentsGrouped' => $trainer->documentsGroupedByType(),
            'hasProfileImage' => $trainer->profileImage()->exists(),
        ]);
    }

    public function store(StoreTrainerDocumentRequest $request, Trainer $trainer): RedirectResponse
    {
        $this->documentService->store(
            $trainer,
            $request->string('type')->toString(),
            $request->file('file'),
        );

        return redirect()
            ->route('admin.trainers.documents.index', $trainer)
            ->with('success', 'Document uploaded successfully.');
    }

    public function destroy(Trainer $trainer, TrainerDocument $document): RedirectResponse
    {
        $this->ensureDocumentBelongsToTrainer($trainer, $document);
        $this->documentService->delete($document);

        return redirect()
            ->route('admin.trainers.documents.index', $trainer)
            ->with('success', 'Document deleted successfully.');
    }

    public function show(Trainer $trainer, TrainerDocument $document): Response|StreamedResponse
    {
        $this->ensureDocumentBelongsToTrainer($trainer, $document);

        if (! $document->fileExists()) {
            abort(404, 'File not found.');
        }

        $disk = Storage::disk($document->disk());

        if ($document->isImage() || $document->isPdf()) {
            return response($disk->get($document->file_path), 200, [
                'Content-Type' => $document->mime_type,
                'Content-Disposition' => 'inline; filename="'.$document->original_name.'"',
            ]);
        }

        return $disk->download($document->file_path, $document->original_name);
    }

    private function ensureDocumentBelongsToTrainer(Trainer $trainer, TrainerDocument $document): void
    {
        if ($document->trainer_id !== $trainer->id) {
            abort(404);
        }
    }
}

<?php

namespace App\Http\Controllers\Admission;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\AdmissionDocument;
use App\Services\AdmissionDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdmissionDocumentController extends Controller
{
    public function __construct(
        private AdmissionDocumentService $documentService,
    ) {}

    public function index(Admission $admission): View
    {
        $admission->load('documents');

        $grouped = [
            AdmissionDocument::TYPE_PHOTO => $admission->documents->where('type', AdmissionDocument::TYPE_PHOTO),
            AdmissionDocument::TYPE_ID_PROOF => $admission->documents->where('type', AdmissionDocument::TYPE_ID_PROOF),
            AdmissionDocument::TYPE_FITNESS => $admission->documents->where('type', AdmissionDocument::TYPE_FITNESS),
        ];

        return view('admission.documents', [
            'admission' => $admission,
            'grouped' => $grouped,
        ]);
    }

    public function store(Request $request, Admission $admission): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:'.(config('admission.max_upload_size_mb') * 1024)],
            'type' => ['required', 'string'],
        ]);

        $document = $this->documentService->store(
            $admission,
            $request->input('type'),
            $request->file('file'),
        );

        return response()->json([
            'success' => true,
            'document' => [
                'id' => $document->id,
                'type' => $document->type,
                'original_name' => $document->original_name,
                'file_size' => $document->file_size,
            ],
            'is_document_complete' => $admission->fresh()->is_document_complete,
        ]);
    }

    public function show(Admission $admission, AdmissionDocument $document): StreamedResponse
    {
        if ($document->admission_id !== $admission->id) {
            abort(404);
        }

        $disk = (string) config('admission.storage_disk');

        if (! Storage::disk($disk)->exists($document->file_path)) {
            abort(404);
        }

        return Storage::disk($disk)->download($document->file_path, $document->original_name);
    }

    public function destroy(Admission $admission, AdmissionDocument $document): JsonResponse
    {
        if ($document->admission_id !== $admission->id) {
            abort(404);
        }

        $this->documentService->delete($document);

        return response()->json([
            'success' => true,
            'is_document_complete' => $admission->fresh()->is_document_complete,
        ]);
    }
}

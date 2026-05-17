<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;

class ExportController extends Controller
{
    public function pdf($documentId)
    {
        $document = Document::findOrFail($documentId);
        
        // ❌ Hapus/Komentar baris ini dulu
        // Gate::authorize('view', $document);
        
        // ✅ Atau ganti dengan cek login biasa
        if (!auth()->check()) {
            abort(403, 'Must be logged in');
        }
        
        // Convert HTML ke PDF
        $pdf = Pdf::loadView('exports.document-pdf', [
            'document' => $document,
            'content' => $document->content
        ]);
        
        // Download dengan nama file
        $filename = 'document-' . $document->id . '-' . $this->sanitizeFilename($document->title ?? 'untitled') . '.pdf';
        
        return $pdf->download($filename);
    }
    
    private function sanitizeFilename($filename) {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
    }
}
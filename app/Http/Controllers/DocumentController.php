<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Events\DocumentUpdated; 

class DocumentController extends Controller
{
 public function index()
{
    $userId = auth()->id();

    // Logic Baru: 
    // 1. Ambil dokumen yang user_id-nya = ID user login (Dokumen sendiri)
    // 2. ATAU (orWhereHas) ambil dokumen yang ada relasi 'sharedUsers' untuk user login
    $documents = Document::where('user_id', $userId)
        ->orWhereHas('sharedUsers', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->with('user') // Optional: memuat info pemilik dokumen
        ->latest()
        ->get();

    return Inertia::render('Documents/Index', [
        'documents' => $documents
    ]);
}

    public function create()
    {
        return Inertia::render('Documents/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
    'title' => 'required|string|max:255',
    'content' => 'nullable|string',
]);

    $document = Document::create([
    'title' => $request->title,
    'content' => $request->content ?? '',
    'user_id' => auth()->id(),
]);
        return redirect()->route('documents.show', $document);
    }
public function show(Document $document)
{
    // Cek apakah user ini pemilik dokumen ATAU punya akses share
    if ($document->user_id !== auth()->id()) {
        $isShared = $document->sharedUsers()
            ->where('user_id', auth()->id())
            ->exists();
            
        if (!$isShared) {
            abort(403, 'Unauthorized action.');
        }
    }
    
    return Inertia::render('Documents/Show', [
        'document' => $document,
        'userName' => auth()->user()->name,
        'userColor' => null
    ]);
}

public function update(Request $request, Document $document)
{
    $request->validate([
        'content' => 'required|string'
    ]);

    // Cek akses
    if ($document->user_id !== auth()->id()) {
        $access = $document->sharedUsers()
            ->where('user_id', auth()->id())
            ->first();
            
        if (!$access || $access->role === 'viewer') {
            abort(403, 'You do not have permission to edit this document.');
        }
    }

    // Update dokumen
    $document->update([
        'content' => $request->content,
        'last_edited_at' => now()
    ]);

    // ✅ PENTING: Broadcast ke semua user di channel ini
    event(new \App\Events\DocumentUpdated($document->id, $request->content));

    return response()->json([
        'message' => 'Document updated successfully',
        'content' => $document->content
    ]);
}
}  // ← HANYA INI SATU-SATUNYA PENUTUP METHOD
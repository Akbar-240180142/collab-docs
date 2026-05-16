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
    // Ambil dokumen yang:
    // 1. Dimiliki user ini, ATAU
    // 2. Di-share ke user ini (via pivot table)
    
    $documents = Document::where('user_id', auth()->id())
        ->orWhereHas('users', function($query) {
            $query->where('user_id', auth()->id());
        })
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
    // Cek apakah user punya akses via pivot table
    $access = $document->users()->where('user_id', auth()->id())->first();
    
    // Kalau bukan owner dan tidak ada di pivot table, tolak
    if ($document->user_id !== auth()->id() && !$access) {
        abort(403, 'You do not have permission to view this document.');
    }

    return Inertia::render('Documents/Show', [
        'document' => $document,
        'userName' => auth()->user()->name,
        'userColor' => '#' . substr(md5(auth()->id()), 0, 6),
    ]);
}

public function update(Request $request, Document $document)
{
    \Log::info('=== UPDATE DIPANGGIL ===');
    
    $access = $document->users()->where('user_id', auth()->id())->first();
    
    if ($document->user_id !== auth()->id() && (!$access || $access->pivot->role === 'viewer')) {
        abort(403, 'You do not have permission to edit this document.');
    }

    $document->update([
        'content' => $request->content,
        'last_edited_at' => now(),
    ]);

    // Broadcast event
    broadcast(new \App\Events\DocumentUpdated(
        $document->id,
        $request->content,
        auth()->id(),
        auth()->user()->name
    ));

    return back();
}
}  // ← HANYA INI SATU-SATUNYA PENUTUP METHOD
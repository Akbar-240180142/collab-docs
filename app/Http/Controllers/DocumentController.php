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
        $documents = auth()->user()->documents()->latest()->get();
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
            'title' => 'required|string|max:255'
        ]);

        $document = auth()->user()->documents()->create([
            'title' => $request->title,
            'content' => '<p>Mulai mengetik di sini...</p>'
        ]);

        return redirect()->route('documents.show', $document);
    }

    public function show(Document $document)
{
    // COMMENT OUT atau HAPUS baris ini:
    // if ($document->user_id !== auth()->id()) {
    //     abort(403);
    // }
    
    return Inertia::render('Documents/Show', [
        'document' => $document,
        'userName' => auth()->user()->name,
        'userColor' => '#' . substr(md5(auth()->id()), 0, 6),
    ]);
}

public function update(Request $request, Document $document)
{
    $document->update([
        'content' => $request->content,
        'last_edited_at' => now(),
    ]);

    // Debug log
    \Log::info('Broadcasting update for document: ' . $document->id);

    broadcast(new DocumentUpdated(
        $document->id,
        $request->content,
        auth()->id(),
        auth()->user()->name
    ));

    return back();
}
}
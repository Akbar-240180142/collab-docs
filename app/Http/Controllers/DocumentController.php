<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Events\DocumentUpdated;
use App\Events\UserTyping;

class DocumentController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $documents = Document::where('user_id', $userId)
            ->orWhereHas('sharedUsers', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with('user')
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

        if ($document->user_id !== auth()->id()) {
            $access = $document->sharedUsers()
                ->where('user_id', auth()->id())
                ->first();
                
            if (!$access || $access->role === 'viewer') {
                abort(403, 'You do not have permission to edit this document.');
            }
        }

        $document->update([
            'content' => $request->content,
            'last_edited_at' => now()
        ]);

        event(new DocumentUpdated($document->id, $request->content));

        return response()->json([
            'message' => 'Document updated successfully',
            'content' => $document->content
        ]);
    }

    public function typing(Request $request, Document $document)
    {
        $request->validate([
            'isTyping' => 'boolean'
        ]);

        event(new UserTyping(
            $document->id,
            auth()->user()->name
        ));

        return response()->json(['message' => 'Typing event broadcasted']);
    }

    /**
     * ✅ Get version history
     */
    public function versions(Document $document)
    {
        if ($document->user_id !== auth()->id()) {
            $isShared = $document->sharedUsers()
                ->where('user_id', auth()->id())
                ->exists();
            if (!$isShared) {
                abort(403, 'Unauthorized action.');
            }
        }

        $versions = $document->versions()
            ->with('user:id,name')
            ->limit(20)
            ->get()
            ->map(function ($version) {
                return [
                    'id' => $version->id,
                    'version_name' => $version->version_name ?? 'Version ' . $version->id,
                    'created_at' => $version->created_at->diffForHumans(),
                    'created_at_full' => $version->created_at->format('d M Y H:i'),
                    'user_name' => $version->user->name,
                    'word_count' => $version->word_count,
                    'content_preview' => mb_strimwidth(strip_tags($version->content), 0, 100, '...'),
                ];
            });

        return response()->json($versions);
    }

    /**
     * ✅ Save new version
     */
    public function saveVersion(Request $request, Document $document)
    {
        $request->validate([
            'content' => 'required|string',
            'version_name' => 'nullable|string|max:255',
            'word_count' => 'nullable|integer'
        ]);

        if ($document->user_id !== auth()->id()) {
            $access = $document->sharedUsers()
                ->where('user_id', auth()->id())
                ->first();
            if (!$access) {
                abort(403, 'Unauthorized action.');
            }
        }

        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'user_id' => auth()->id(),
            'content' => $request->content,
            'version_name' => $request->version_name,
            'word_count' => $request->word_count ?? str_word_count(strip_tags($request->content)),
        ]);

        return response()->json(['message' => 'Version saved', 'version' => $version]);
    }

    /**
     * ✅ Restore to specific version
     */
    public function restoreVersion(Request $request, Document $document, $versionId)
    {
        if ($document->user_id !== auth()->id()) {
            $access = $document->sharedUsers()
                ->where('user_id', auth()->id())
                ->first();
            if (!$access || $access->role === 'viewer') {
                abort(403, 'You do not have permission to restore this document.');
            }
        }

        $version = $document->versions()->findOrFail($versionId);

        $document->update([
            'content' => $version->content,
            'last_edited_at' => now()
        ]);

        DocumentVersion::create([
            'document_id' => $document->id,
            'user_id' => auth()->id(),
            'content' => $version->content,
            'version_name' => 'Restored from Version #' . $versionId,
            'word_count' => str_word_count(strip_tags($version->content)),
        ]);

        event(new DocumentUpdated($document->id, $version->content));

        return response()->json([
            'message' => 'Document restored successfully',
            'content' => $document->content
        ]);
    }

    public function destroy(Document $document)
    {
        if ($document->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $document->delete();
        return redirect()->route('documents.index');
    }
    /**
 * Get specific version content for preview
 */
public function getVersion(Document $document, $versionId)
{
    // Cek akses
    if ($document->user_id !== auth()->id()) {
        $isShared = $document->sharedUsers()
            ->where('user_id', auth()->id())
            ->exists();
        if (!$isShared) {
            abort(403, 'Unauthorized action.');
        }
    }

    $version = $document->versions()->findOrFail($versionId);

    return response()->json([
        'id' => $version->id,
        'content' => $version->content,
        'version_name' => $version->version_name,
        'created_at' => $version->created_at->diffForHumans(),
        'user_name' => $version->user->name,
        'word_count' => $version->word_count,
    ]);
}
}
<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ShareDocumentController extends Controller
{
    // Dapatkan semua user yang punya akses ke dokumen ini
public function index(Document $document)
{
    // Gate::authorize('view', $document);  <-- KASI SLASH DI DEPANNYA
    
    $sharedUsers = $document->sharedUsers()->with('user')->get();
    return response()->json($sharedUsers);
}
    
    // Tambah user ke dokumen
   public function store(Request $request, Document $document)
{
    // Gate::authorize('view', $document);  <-- KASI SLASH DI DEPANNYA
    
    $request->validate([
        'email' => 'required|email|exists:users,email',
        'role' => 'required|in:viewer,editor'
    ]);
    
    // ... sisa kode tetap sama ...
        
        $user = User::where('email', $request->email)->first();
        
        // Cek apakah user sudah ada
        $existing = DocumentUser::where('document_id', $document->id)
            ->where('user_id', $user->id)
            ->first();
        
        if ($existing) {
            return response()->json(['message' => 'User already has access'], 422);
        }
        
        // Tambah user
        DocumentUser::create([
            'document_id' => $document->id,
            'user_id' => $user->id,
            'role' => $request->role
        ]);
        
        return response()->json(['message' => 'User added successfully']);
    }
    
    // Update role user
    public function update(Request $request, Document $document, $documentUserId)
    {
        Gate::authorize('view', $document);
        
        $request->validate([
            'role' => 'required|in:viewer,editor'
        ]);
        
        $documentUser = DocumentUser::findOrFail($documentUserId);
        $documentUser->update(['role' => $request->role]);
        
        return response()->json(['message' => 'Role updated successfully']);
    }
    
    // Hapus akses user
    public function destroy(Document $document, $documentUserId)
    {
        Gate::authorize('view', $document);
        
        $documentUser = DocumentUser::findOrFail($documentUserId);
        $documentUser->delete();
        
        return response()->json(['message' => 'Access removed successfully']);
    }
}
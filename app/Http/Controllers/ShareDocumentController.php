<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShareDocumentController extends Controller
{
    public function store(Request $request, Document $document)
    {
        // Hanya owner yang bisa share
        if ($document->user_id !== auth()->id()) {
            abort(403);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'role' => 'required|in:editor,viewer',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $user = User::where('email', $request->email)->first();

        // Jangan share ke diri sendiri
        if ($user->id === auth()->id()) {
            return back()->withErrors(['email' => 'Cannot share to yourself']);
        }

        // Attach user ke document dengan role
        $document->users()->syncWithoutDetaching([
            $user->id => ['role' => $request->role]
        ]);

        return back()->with('success', "Document shared with {$user->name} as {$request->role}");
    }

    public function destroy(Document $document, User $user)
    {
        // Hanya owner yang bisa revoke access
        if ($document->user_id !== auth()->id()) {
            abort(403);
        }

        $document->users()->detach($user->id);

        return back()->with('success', "Access revoked for {$user->name}");
    } 
}
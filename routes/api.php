use Illuminate\Support\Facades\Route;
use App\Models\Document;

Route::middleware('auth')->get('/documents/{document}/users', function (Document $document) {
    // Cek akses dulu
    if ($document->user_id !== auth()->id()) {
        $access = $document->users()->where('user_id', auth()->id())->first();
        if (!$access) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    }
    
    return $document->users()->withPivot('role')->get();
});
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'content',
        'last_edited_at',
    ];

    protected $casts = [
        'last_edited_at' => 'datetime',
    ];

    // Relasi ke User (Pemilik Dokumen)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ✅ TAMBAHKAN FUNGSI INI (Relasi ke tabel document_users)
    public function sharedUsers(): HasMany
    {
        return $this->hasMany(DocumentUser::class);
    }
    // Tambahkan di dalam class Document
public function versions()
{
    return $this->hasMany(DocumentVersion::class)->latest();
}
}
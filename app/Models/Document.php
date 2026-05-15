<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Document extends Model
{
    use HasFactory;

    // INI WAJIB ADA AGAR BISA MENYIMPAN DATA DARI CONTROLLER
    protected $fillable = [
        'title',
        'content',
        'user_id',
        'last_edited_at'
    ];

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
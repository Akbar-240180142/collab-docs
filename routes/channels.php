<?php

use Illuminate\Support\Facades\Broadcast;

// IZINKAN SEMUA USER YANG LOGIN MASUK (TESTING DULU)
Broadcast::channel('document.{documentId}', function ($user, $documentId) {
    return true; 
});
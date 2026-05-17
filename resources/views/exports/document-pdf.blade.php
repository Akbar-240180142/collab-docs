<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $document->title ?? 'Document' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 40px;
        }
        h1 { font-size: 24px; margin-bottom: 10px; }
        h2 { font-size: 20px; margin-bottom: 8px; }
        h3 { font-size: 16px; margin-bottom: 6px; }
        p { margin-bottom: 10px; }
        ul, ol { margin-bottom: 10px; padding-left: 20px; }
        .metadata {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ccc;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Render HTML content -->
    {!! $content !!}
    
    <!-- Metadata -->
    <div class="metadata">
        <p><strong>Document ID:</strong> {{ $document->id }}</p>
        <p><strong>Created:</strong> 
            @if($document->created_at)
                {{ \Carbon\Carbon::parse($document->created_at)->format('d M Y, H:i') }}
            @else
                -
            @endif
        </p>
        <p><strong>Last Edited:</strong> 
            @if($document->last_edited_at)
                {{ \Carbon\Carbon::parse($document->last_edited_at)->format('d M Y, H:i') }}
            @else
                -
            @endif
        </p>
        <p><strong>Owner:</strong> {{ $document->user->name ?? 'Unknown' }}</p>
    </div>
</body>
</html>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    @include('dev.principal.pdf-styles')
</head>
<body class="ks-pdf-document">
@foreach($documents as $document)
    @include('dev.principal.pdf-sheet', ['document' => $document])
@endforeach
</body>
</html>

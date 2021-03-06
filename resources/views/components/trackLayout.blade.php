<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    @if ($gallery->count() > 0)
        <script src="https://unpkg.com/swiper@6.8.4/swiper-bundle.min.js" defer></script>
        <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
        <link rel="stylesheet" href="https://unpkg.com/swiper@6.8.4/swiper-bundle.min.css" />
    @endif
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/icons/webmapp-icons/style.css') }}" />
    
    <title>Geohub -  {{$track->name}}</title>
</head>
<body class="font-body">
    {{$slot}}
</body>
</html>
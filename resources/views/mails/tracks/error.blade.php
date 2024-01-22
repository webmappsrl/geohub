<h1>Tracks not imported</h1>
<ul>
    @foreach ($error_not_created as $error)
        <li><strong>{{ $error[0] }}</strong>: {{ $error[1] }}</li>
    @endforeach
</ul>
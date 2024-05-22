<!-- resources/views/mails/UpdateTrackFromOsmEmail.blade.php -->

<!DOCTYPE html>
<html>

<head>
    <title>Error Logs</title>
</head>

<body>
    <h1>Error Logs</h1>
    <ul>
        @foreach ($errorLogs as $errorLog)
        <li>{{ is_array($errorLog) ? implode(', ', $errorLog) : $errorLog }}</li>

        @endforeach
    </ul>
</body>

</html>
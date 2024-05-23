<!-- resources/views/emails/ranking_increased.blade.php -->
<!DOCTYPE html>
<html>

<head>
    <title>Ranking Increased</title>
</head>

<body>
    <h1>Your Ranking Has Increased now you are in {{$position}} position</h1>
    <p>Dear {{ $user->name }},</p>
    <p>Your ranking has increased due to the new media added!</p>
    <p>Keep up the good work!</p>
    <p>Check out the top ten users <a href="{{ url('/top-ten/'.$app->id) }}">here</a>.</p>
    <p>View your detailed user ranking <a href="{{ url('/user-ranking/'.$app->id.'/'.$user->id) }}">here</a>.</p>

</body>

</html>
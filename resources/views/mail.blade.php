<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Broadcast Email</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
    </style>
</head>

<body>
    <h1>Hello, {{ $target->name }}</h1>
    <p>{{ $broadcastMessage }}</p>

    <p>Regards,<br> Your Company Name</p>
</body>

</html>

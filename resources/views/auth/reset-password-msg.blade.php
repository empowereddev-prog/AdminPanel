<!-- resources/views/reset-password-error.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
</head>
<body>
    <div class="container">
        <h1>Reset Password</h1>
        @if (session('invalid'))
            <p style="color: red;">{{ session('invalid') }}</p>
        @elseif (session('expired'))
            <p style="color: red;">{{ session('expired') }}</p>
        @elseif (session('success'))
            <p style="color: green;">{{ session('success') }}</p>
        @else
            <p style="color: red;">The reset password link is invalid or has expired.</p>
        @endif
    </div>
</body>
</html>

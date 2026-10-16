<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ \App\Support\Brand::NAME }} | Email verification</title>
  <style>
    body { margin: 0; background: #eef2f7; font-family: Arial, Helvetica, sans-serif; color: #4a5b6d; }
    .wrap { max-width: 520px; margin: 48px auto; padding: 0 16px; }
    .card { background: #ffffff; border: 1px solid #d9e2ef; border-radius: 12px; overflow: hidden; }
    .head { background: #1a5edb; padding: 24px 32px; text-align: center; color: #ffffff; font-size: 22px; font-weight: 700; }
    .content { padding: 32px; text-align: center; }
    .content h1 { margin: 0 0 12px; font-size: 18px; color: #1c2a3a; }
    .content p { margin: 0; font-size: 15px; line-height: 1.6; }
    .success h1 { color: #1e7a45; }
    .error h1 { color: #c0392b; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <div class="head">{{ \App\Support\Brand::NAME }}</div>
      <div class="content {{ !empty($success) ? 'success' : (!empty($error) ? 'error' : '') }}">
        @if (!empty($success))
          <h1>Email verified</h1>
          <p>{{ $success }} You can now return to the app and sign in.</p>
        @elseif (!empty($message))
          <h1>{{ $message }}</h1>
          <p>You can return to the app and sign in.</p>
        @elseif (!empty($error))
          <h1>We could not verify your email</h1>
          <p>{{ $error }}</p>
        @endif
      </div>
    </div>
  </div>
</body>
</html>

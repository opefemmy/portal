<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password Reset</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 10px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Password Reset Request</h2>

        <p>Hello {{ $user->name }},</p>

        <p>We received a request to reset your password. Click the button below to create a new password:</p>

        <a href="{{ url('/password/reset/' . $token . '?email=' . urlencode($user->email)) }}" class="btn">
            Reset Password
        </a>

        <p>If you didn't request a password reset, please ignore this email or contact support if you have concerns.</p>

        <div class="footer">
            <p>This link will expire in 1 hour.</p>
            <p>If the button doesn't work, copy and paste this link into your browser:</p>
            <p>{{ url('/password/reset/' . $token . '?email=' . urlencode($user->email)) }}</p>
        </div>
    </div>
</body>
</html>

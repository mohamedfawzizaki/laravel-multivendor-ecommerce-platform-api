<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Email Verification</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        padding: 20px;
        text-align: center;
    }

    .container {
        background-color: #ffffff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        max-width: 500px;
        margin: auto;
    }

    h2 {
        color: #333;
    }

    p {
        color: #666;
        font-size: 16px;
    }

    .btn {
        display: inline-block;
        background-color: #28a745;
        color: #fff;
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: bold;
        margin-top: 15px;
    }

    .footer {
        margin-top: 20px;
        font-size: 12px;
        color: #999;
    }
    </style>
</head>

<body>
    <div class="container">
        <h2>Verify Your Email</h2>
        <p>Hello, <strong>{{ $userName }}</strong></p>
        <p>Thank you for registering with us! To complete your registration, please verify your email by clicking the
            button below:</p>

        <a href="{{ $verificationUrl }}" class="btn">Verify Email</a>

        <p>If you didn’t request this, you can ignore this email.</p>

        <div class="footer">
            <p>&copy; {{ date('Y') }} YourAppName. All Rights Reserved.</p>
        </div>
    </div>
</body>

</html>
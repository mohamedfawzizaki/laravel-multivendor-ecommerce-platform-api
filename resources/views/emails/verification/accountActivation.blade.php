<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Activation</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        padding: 20px;
    }

    .container {
        background: #ffffff;
        max-width: 600px;
        margin: 0 auto;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        text-align: center;
    }

    h2 {
        color: #333;
    }

    p {
        font-size: 16px;
        color: #555;
        line-height: 1.6;
    }

    .btn {
        display: inline-block;
        padding: 12px 20px;
        margin: 20px 0;
        color: white;
        background-color: #007bff;
        text-decoration: none;
        font-size: 16px;
        border-radius: 5px;
    }

    .btn:hover {
        background-color: #0056b3;
    }

    .footer {
        font-size: 12px;
        color: #999;
        margin-top: 20px;
    }
    </style>
</head>

<body>

    <div class="container">
        <h2>Activate Your Account</h2>
        <p>Hello, {{ $userName }}!</p>
        <p>Thank you for signing up. Please click the button below to activate your account:</p>
        <p>If you did not request this, please ignore this email.</p>
        <p class="footer">This email was sent by {{ config('app.name') }}.</p>
    </div>

</body>

</html>
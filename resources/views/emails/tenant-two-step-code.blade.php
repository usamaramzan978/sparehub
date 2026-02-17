<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Verification Code') }}</title>
</head>

<body style="font-family: Arial, sans-serif; color: #111827;">
    <p>{{ __('Your verification code is:') }}</p>
    <p style="font-size: 24px; font-weight: 700; letter-spacing: 4px;">{{ $code }}</p>
    <p>{{ __('This code expires in 10 minutes.') }}</p>
</body>

</html>

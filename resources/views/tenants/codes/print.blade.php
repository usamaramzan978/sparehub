<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print Code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 24px;
            text-align: center;
        }

        .label {
            margin-top: 8px;
            color: #555;
            font-size: 14px;
        }
    </style>
</head>

<body onload="window.print()">
    <img src="{{ $previewUrl }}" alt="Code">
    <div class="label">{{ $payload['label'] ?? $payload['value'] ?? '' }}</div>
</body>

</html>

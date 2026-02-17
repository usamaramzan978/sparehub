<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'POS') }} - Print</title>
        <style>
            :root {
                color-scheme: light;
            }
            body {
                font-family: "Helvetica Neue", Arial, sans-serif;
                margin: 0;
                padding: 24px;
                color: #0b0f14;
                background: #f6f7fb;
            }
            .toolbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                margin-bottom: 16px;
            }
            .btn {
                border: 1px solid #0d6efd;
                background: #0d6efd;
                color: #fff;
                padding: 8px 14px;
                border-radius: 8px;
                text-decoration: none;
                font-size: 14px;
            }
            .btn-outline {
                background: transparent;
                color: #0d6efd;
            }
            .sheet {
                background: #fff;
                border-radius: 16px;
                padding: 20px;
                box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 16px;
            }
            .label {
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                padding: 12px;
                text-align: center;
                display: flex;
                flex-direction: column;
                gap: 6px;
            }
            .label img {
                max-width: 100%;
                height: auto;
                display: block;
                margin: 0 auto;
            }
            .label-title {
                font-weight: 600;
                font-size: 12px;
            }
            .label-meta {
                font-size: 11px;
                color: #64748b;
                word-break: break-all;
            }

            @media print {
                body {
                    background: #fff;
                    padding: 0;
                }
                .toolbar {
                    display: none;
                }
                .sheet {
                    box-shadow: none;
                    border-radius: 0;
                    padding: 0;
                }
                .label {
                    break-inside: avoid;
                }
            }
        </style>
    </head>
    <body>
        @yield('content')
    </body>
</html>

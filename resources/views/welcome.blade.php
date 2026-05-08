<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'BSR') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Gradient background */
        .hero {
            background: linear-gradient(180deg, #fad793 0%, #be5633 50%, #46291d 100%);
            width: 100%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* Logo container */
        .logo-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .logo-container img {
            max-width: 500px;
            width: 100%;
            height: auto;
            display: block;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .logo-container {
                padding: 20px;
            }
            .logo-container img {
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="hero">
        <div class="logo-container">
            <img src="{{ asset('img/BSR_Logo_1.png') }}" alt="BSR Logo">
        </div>
    </div>
</body>
</html>
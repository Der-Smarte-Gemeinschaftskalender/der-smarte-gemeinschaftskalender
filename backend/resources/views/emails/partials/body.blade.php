<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Mail-Adresse bestätigen</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f4fdfb;
            margin: 0;
            padding: 0;
            color: #3b3f44;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }

        .header {
            padding: 20px 15px;
            text-align: center;
            background-color: #f4fdfb;
        }

        .content {
            padding: 20px 15px;
        }

        .btn {
            display: inline-block;
            padding: 12px 20px;
            background-color: #2b2c6a;
            color: #ffffff;
            text-decoration: none;
            border-radius: 4px;
            margin: 15px 0;
        }

        .btn-outline {
            background-color: #ffffff;
            color: #2b2c6a;
            border: 1px solid #2b2c6a;
        }

        .footer {
            padding: 20px 15px;
            background-color: #f4fdfb;
            font-size: 14px;
            text-align: center;
        }

        .footer-links {
            margin: 15px 0;
        }

        .footer-links a {
            color: #2b2c6a;
            margin: 0 8px;
            text-decoration: underline;
        }

        .event-link {
            text-decoration: none;
            color: inherit;
        }

        h2 {
            color: #2b2c6a;
            font-size: 24px;
            margin: 0 0 15px 0;
        }

        h4 {
            font-size: 14px;
            font-weight: normal;
            line-height: 1.5;
            margin: 0 0 15px 0;
        }

        .highlight {
            color: #2b2c6a;
            font-weight: bold;
        }

        .event {
            border-left: 4px solid #2b2c6a;
            padding: 15px;
            margin: 15px 0;
            background-color: #f9f9f9;
        }

        .event-title {
            font-weight: bold;
            color: #2b2c6a;
            margin-bottom: 5px;
        }

        .event-date {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="container">

        @yield('content')

    </div>
</body>

</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Login - Mitali SP')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #e6ecf3;
            min-height: 100vh;
        }
        .brand-title {
            font-weight: 700;
            font-size: 2rem;
            color: #0f2744;
            letter-spacing: 0.5px;
        }
        .auth-card {
            background: #ffffff;
            border: 1px solid #d5dee8;
            box-shadow: 0 8px 24px rgba(15, 39, 68, 0.1);
        }
    </style>
</head>
<body class="d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="text-center mb-4">
                    <span class="brand-title">Mitali SP</span>
                </div>
                @include('partials.flash')
                @yield('content')
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

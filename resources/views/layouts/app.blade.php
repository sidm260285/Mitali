<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Mitali SP')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --nav-bg: #0f2744;
            --nav-bg-end: #163a5f;
            --nav-text: #e8eef5;
            --nav-text-muted: #b8c7d9;
            --nav-active: #7dd3fc;
            --page-bg: #e6ecf3;
            --surface-bg: #ffffff;
            --surface-muted: #f3f7fb;
            --border-soft: #d5dee8;
        }

        body {
            background-color: var(--page-bg);
            color: #1e293b;
        }

        .content-wrapper {
            min-height: calc(100vh - 72px);
        }

        .content-wrapper > .container {
            background-color: var(--surface-bg);
            border: 1px solid var(--border-soft);
            border-radius: 0.75rem;
            box-shadow: 0 2px 12px rgba(15, 39, 68, 0.06);
            padding: 1.5rem;
        }

        .navbar-app {
            background: linear-gradient(135deg, var(--nav-bg) 0%, var(--nav-bg-end) 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 4px 14px rgba(15, 39, 68, 0.18);
        }

        .brand-title {
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: 0.5px;
            color: #ffffff;
            text-decoration: none;
        }

        .brand-title:hover {
            color: var(--nav-active);
        }

        .main-nav .nav-link {
            font-weight: 500;
            color: var(--nav-text-muted) !important;
            padding-inline: 1rem;
            transition: color 0.2s ease;
        }

        .main-nav .nav-link:hover {
            color: var(--nav-text) !important;
        }

        .main-nav .nav-link.active {
            color: var(--nav-active) !important;
            position: relative;
        }

        .main-nav .nav-link.active::after {
            content: '';
            position: absolute;
            left: 1rem;
            right: 1rem;
            bottom: 0.2rem;
            height: 2px;
            background: var(--nav-active);
            border-radius: 2px;
        }

        .navbar-app .navbar-toggler {
            border-color: rgba(255, 255, 255, 0.35);
        }

        .navbar-app .navbar-toggler-icon {
            filter: invert(1) grayscale(1) brightness(2);
        }

        .user-menu-btn {
            color: var(--nav-text);
            border-color: rgba(255, 255, 255, 0.35);
            background: rgba(255, 255, 255, 0.06);
        }

        .user-menu-btn:hover,
        .user-menu-btn:focus,
        .user-menu-btn.show {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .card {
            background-color: var(--surface-bg);
            border: 1px solid var(--border-soft) !important;
            box-shadow: 0 2px 8px rgba(15, 39, 68, 0.05);
        }

        .card-header {
            background-color: var(--surface-muted) !important;
            border-bottom: 1px solid var(--border-soft) !important;
        }

        .table {
            --bs-table-bg: var(--surface-bg);
        }

        .nav-tabs .nav-link {
            color: #475569;
        }

        .nav-tabs .nav-link.active {
            background-color: var(--surface-bg);
            border-color: var(--border-soft) var(--border-soft) var(--surface-bg);
            color: #0f2744;
            font-weight: 600;
        }

        .table-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            justify-content: flex-end;
            align-items: center;
        }

        .table-actions form {
            margin: 0;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.8125rem;
            font-weight: 500;
            line-height: 1.2;
            padding: 0.4rem 0.7rem;
            border-radius: 0.5rem;
            border: none;
            transition: all 0.15s ease;
            white-space: nowrap;
        }

        .btn-action i {
            font-size: 0.95rem;
        }

        .btn-action-info {
            background: #e0f2fe;
            color: #0369a1;
        }
        .btn-action-info:hover {
            background: #bae6fd;
            color: #0c4a6e;
        }

        .btn-action-neutral {
            background: #f1f5f9;
            color: #475569;
        }
        .btn-action-neutral:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        .btn-action-warning {
            background: #fef3c7;
            color: #b45309;
        }
        .btn-action-warning:hover {
            background: #fde68a;
            color: #92400e;
        }

        .btn-action-danger {
            background: #fee2e2;
            color: #b91c1c;
        }
        .btn-action-danger:hover {
            background: #fecaca;
            color: #991b1b;
        }

        .btn-action-success {
            background: #dcfce7;
            color: #15803d;
        }
        .btn-action-success:hover {
            background: #bbf7d0;
            color: #166534;
        }

        .btn-form {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-weight: 500;
            border: none;
            border-radius: 0.5rem;
            transition: all 0.15s ease;
            text-decoration: none;
        }

        .btn-form i {
            font-size: 1rem;
        }

        .btn-form-primary {
            background: linear-gradient(135deg, var(--nav-bg) 0%, var(--nav-bg-end) 100%);
            color: #ffffff;
            padding: 0.65rem 1.35rem;
            box-shadow: 0 2px 8px rgba(15, 39, 68, 0.2);
        }

        .btn-form-primary:hover {
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(15, 39, 68, 0.28);
            transform: translateY(-1px);
        }

        .btn-form-secondary {
            background: #f1f5f9;
            color: #475569;
            padding: 0.5rem 1rem;
        }

        .btn-form-secondary:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        .btn-form-add {
            background: #e0f2fe;
            color: #0369a1;
            padding: 0.4rem 0.85rem;
            font-size: 0.8125rem;
        }

        .btn-form-add:hover {
            background: #bae6fd;
            color: #0c4a6e;
        }

        .btn-form-remove {
            background: #fee2e2;
            color: #b91c1c;
            padding: 0.35rem 0.7rem;
            font-size: 0.8125rem;
        }

        .btn-form-remove:hover {
            background: #fecaca;
            color: #991b1b;
        }

        .form-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
            padding-top: 0.5rem;
        }

        .session-list-cell {
            min-width: 10rem;
            line-height: 1.5;
            vertical-align: top;
        }
    </style>
    @stack('styles')
</head>
<body>
    @auth
        <nav class="navbar navbar-expand-lg navbar-dark navbar-app">
            <div class="container">
                <a href="{{ auth()->user()->dashboardRoute() }}" class="brand-title">Mitali SP</a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="mainNavbar">
                    <ul class="navbar-nav mx-auto main-nav">
                        @if(auth()->user()->isAdmin())
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.executives.*') ? 'active' : '' }}"
                                   href="{{ route('admin.executives.index') }}">Executive</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.banks.*') ? 'active' : '' }}"
                                   href="{{ route('admin.banks.index') }}">Bank</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.sessions.*') ? 'active' : '' }}"
                                   href="{{ route('admin.sessions.index') }}">Session Master</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.trainers.*') ? 'active' : '' }}"
                                   href="{{ route('admin.trainers.index') }}">Trainer</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.account-heads.*') ? 'active' : '' }}"
                                   href="{{ route('admin.account-heads.index') }}">Accounts Head</a>
                            </li>
                            @include('partials.cash-flow-nav')
                            @include('partials.bank-flow-nav')
                            @include('partials.salary-payment-nav')
                            @include('partials.admission-nav')
                        @else
                            @include('partials.cash-flow-nav')
                            @include('partials.bank-flow-nav')
                            @include('partials.admission-nav')
                        @endif
                    </ul>

                    <div class="dropdown">
                        <a class="btn user-menu-btn dropdown-toggle" href="#" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            {{ auth()->user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li>
                                <a class="dropdown-item"
                                   href="{{ auth()->user()->isAdmin() ? route('admin.profile.show') : route('executive.profile.show') }}">
                                    Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item"
                                   href="{{ auth()->user()->isAdmin() ? route('admin.password.edit') : route('executive.password.edit') }}">
                                    Change Password
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
    @endauth

    <main class="content-wrapper py-4">
        <div class="container">
            @include('partials.flash')
            @yield('content')
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>

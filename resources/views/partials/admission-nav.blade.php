@php
    $isAdmin = auth()->user()->isAdmin();
    $admissionActive = request()->routeIs($isAdmin ? 'admin.admission.*' : 'executive.admission.*');
@endphp

<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle {{ $admissionActive ? 'active' : '' }}" href="#" role="button"
       data-bs-toggle="dropdown" aria-expanded="false">
        Admission
    </a>
    <ul class="dropdown-menu shadow">
        <li>
            <a class="dropdown-item {{ request()->routeIs($isAdmin ? 'admin.admission.current' : 'executive.admission.current') ? 'active' : '' }}"
               href="{{ route($isAdmin ? 'admin.admission.current' : 'executive.admission.current') }}">Current Session</a>
        </li>
        <li>
            <a class="dropdown-item {{ request()->routeIs($isAdmin ? 'admin.admission.upcoming' : 'executive.admission.upcoming') ? 'active' : '' }}"
               href="{{ route($isAdmin ? 'admin.admission.upcoming' : 'executive.admission.upcoming') }}">Upcoming Session</a>
        </li>
        <li>
            <a class="dropdown-item {{ request()->routeIs($isAdmin ? 'admin.admission.list' : 'executive.admission.list') ? 'active' : '' }}"
               href="{{ route($isAdmin ? 'admin.admission.list' : 'executive.admission.list') }}">List of Admission</a>
        </li>
    </ul>
</li>

@php
    $salaryPaymentActive = request()->routeIs('admin.salary-payment.*');
@endphp

<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle {{ $salaryPaymentActive ? 'active' : '' }}" href="#" role="button"
       data-bs-toggle="dropdown" aria-expanded="false">
        Salary Payment
    </a>
    <ul class="dropdown-menu shadow">
        <li>
            <a class="dropdown-item {{ request()->routeIs('admin.salary-payment.executives.*') ? 'active' : '' }}"
               href="{{ route('admin.salary-payment.executives.index') }}">Pay to Executive</a>
        </li>
        <li>
            <a class="dropdown-item {{ request()->routeIs('admin.salary-payment.trainers.*') ? 'active' : '' }}"
               href="{{ route('admin.salary-payment.trainers.index') }}">Pay to Trainer</a>
        </li>
    </ul>
</li>

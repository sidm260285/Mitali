@php
    $isAdmin = auth()->user()->isAdmin();
    $bankFlowActive = request()->routeIs($isAdmin ? 'admin.bank-flow.*' : 'executive.bank-flow.*');
@endphp

<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle {{ $bankFlowActive ? 'active' : '' }}" href="#" role="button"
       data-bs-toggle="dropdown" aria-expanded="false">
        Bank Flow
    </a>
    <ul class="dropdown-menu shadow">
        <li>
            <a class="dropdown-item {{ request()->routeIs($isAdmin ? 'admin.bank-flow.inflow.*' : 'executive.bank-flow.inflow.*') ? 'active' : '' }}"
               href="{{ route($isAdmin ? 'admin.bank-flow.inflow.create' : 'executive.bank-flow.inflow.create') }}">Inflow</a>
        </li>
        <li>
            <a class="dropdown-item {{ request()->routeIs($isAdmin ? 'admin.bank-flow.outflow.*' : 'executive.bank-flow.outflow.*') ? 'active' : '' }}"
               href="{{ route($isAdmin ? 'admin.bank-flow.outflow.create' : 'executive.bank-flow.outflow.create') }}">Outflow</a>
        </li>
        <li>
            <a class="dropdown-item {{ request()->routeIs($isAdmin ? 'admin.bank-flow.bank-to-cash.*' : 'executive.bank-flow.bank-to-cash.*') ? 'active' : '' }}"
               href="{{ route($isAdmin ? 'admin.bank-flow.bank-to-cash.create' : 'executive.bank-flow.bank-to-cash.create') }}">Bank to Cash</a>
        </li>
        @if($isAdmin)
            <li>
                <a class="dropdown-item {{ request()->routeIs('admin.bank-flow.bank-to-bank.*') ? 'active' : '' }}"
                   href="{{ route('admin.bank-flow.bank-to-bank.create') }}">Bank to Bank</a>
            </li>
            <li>
                <a class="dropdown-item {{ request()->routeIs('admin.bank-flow.transactions.*') ? 'active' : '' }}"
                   href="{{ route('admin.bank-flow.transactions.index') }}">Bank Transaction</a>
            </li>
        @endif
    </ul>
</li>

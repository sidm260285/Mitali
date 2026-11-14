@php
    $isAdmin = auth()->user()->isAdmin();
    $cashFlowActive = request()->routeIs($isAdmin ? 'admin.cash-flow.*' : 'executive.cash-flow.*');
@endphp

<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle {{ $cashFlowActive ? 'active' : '' }}" href="#" role="button"
       data-bs-toggle="dropdown" aria-expanded="false">
        Cash Flow
    </a>
    <ul class="dropdown-menu shadow">
        <li>
            <a class="dropdown-item {{ request()->routeIs($isAdmin ? 'admin.cash-flow.inflow.*' : 'executive.cash-flow.inflow.*') ? 'active' : '' }}"
               href="{{ route($isAdmin ? 'admin.cash-flow.inflow.create' : 'executive.cash-flow.inflow.create') }}">Inflow</a>
        </li>
        <li>
            <a class="dropdown-item {{ request()->routeIs($isAdmin ? 'admin.cash-flow.outflow.*' : 'executive.cash-flow.outflow.*') ? 'active' : '' }}"
               href="{{ route($isAdmin ? 'admin.cash-flow.outflow.create' : 'executive.cash-flow.outflow.create') }}">Outflow</a>
        </li>
        <li>
            <a class="dropdown-item {{ request()->routeIs($isAdmin ? 'admin.cash-flow.cash-to-bank.*' : 'executive.cash-flow.cash-to-bank.*') ? 'active' : '' }}"
               href="{{ route($isAdmin ? 'admin.cash-flow.cash-to-bank.create' : 'executive.cash-flow.cash-to-bank.create') }}">Cash to Bank</a>
        </li>
        @if($isAdmin)
            <li>
                <a class="dropdown-item {{ request()->routeIs('admin.cash-flow.transfer-to-executive.*') ? 'active' : '' }}"
                   href="{{ route('admin.cash-flow.transfer-to-executive.create') }}">Transfer to Executive</a>
            </li>
        @else
            <li>
                <a class="dropdown-item {{ request()->routeIs('executive.cash-flow.transfer-to-executive.*') ? 'active' : '' }}"
                   href="{{ route('executive.cash-flow.transfer-to-executive.create') }}">Transfer to Executive</a>
            </li>
            <li>
                <a class="dropdown-item {{ request()->routeIs('executive.cash-flow.transfer-to-admin.*') ? 'active' : '' }}"
                   href="{{ route('executive.cash-flow.transfer-to-admin.create') }}">Transfer to Admin</a>
            </li>
        @endif
        <li>
            <a class="dropdown-item {{ request()->routeIs($isAdmin ? 'admin.cash-flow.transactions.*' : 'executive.cash-flow.transactions.*') ? 'active' : '' }}"
               href="{{ route($isAdmin ? 'admin.cash-flow.transactions.index' : 'executive.cash-flow.transactions.index') }}">Show Transaction</a>
        </li>
        @if($isAdmin)
            <li>
                <a class="dropdown-item {{ request()->routeIs('admin.cash-flow.executive-transactions.*') ? 'active' : '' }}"
                   href="{{ route('admin.cash-flow.executive-transactions.index') }}">Show Executive Transaction</a>
            </li>
        @endif
    </ul>
</li>

<aside class="co-sidebar" id="co-sidebar">

    <div class="co-sidebar-header">
        <button type="button" class="co-sidebar-toggle" id="co-sidebar-toggle" title="Collapse sidebar" aria-label="Collapse sidebar">
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="1.75"
                stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <polyline points="11 17 6 12 11 7" />
                <polyline points="18 17 13 12 18 7" />
            </svg>
            <span>Collapse</span>
        </button>

        <div class="co-sidebar-company" title="{{ $company->registered_name }}">
            <div class="co-sidebar-company-avatar">{{ strtoupper(substr($company->registered_name, 0, 1)) }}</div>
            <div class="co-sidebar-company-details">
                <p class="co-sidebar-company-name">{{ $company->registered_name }}</p>
                <p class="co-sidebar-company-type">{{ $company->company_type_label }}</p>
            </div>
        </div>
    </div>

    <nav class="co-sidebar-nav">

        <div class="co-sidebar-section collapsed">
            <button type="button" class="co-sidebar-section-label co-sidebar-section-toggle" aria-expanded="false">
                <span>Books</span>
                <svg class="co-sidebar-section-chevron" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </button>

            <div class="co-sidebar-section-items">
                <a href="{{ route('companies.dashboard', $company) }}"
                    title="Dashboard" class="co-nav-item {{ request()->routeIs('companies.dashboard') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <rect x="3" y="3" width="7" height="7" rx="1" />
                        <rect x="14" y="3" width="7" height="4" rx="1" />
                        <rect x="3" y="14" width="7" height="4" rx="1" />
                        <rect x="14" y="11" width="7" height="7" rx="1" />
                    </svg>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('companies.show', $company) }}"
                    title="Business Profile" class="co-nav-item {{ request()->routeIs('companies.show') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z" />
                        <path d="M9 21V12h6v9" />
                    </svg>
                    <span>Business Profile</span>
                </a>

                <a href="{{ route('companies.chart-of-accounts', $company) }}"
                    title="Chart of Accounts" class="co-nav-item {{ request()->routeIs('companies.chart-of-accounts', 'companies.opening-balances.edit') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                        <polyline points="14 2 14 8 20 8" />
                        <line x1="16" y1="13" x2="8" y2="13" />
                        <line x1="16" y1="17" x2="8" y2="17" />
                        <line x1="10" y1="9" x2="8" y2="9" />
                    </svg>
                    <span>Chart of Accounts</span>
                </a>

                <a href="{{ route('companies.transactions', $company) }}"
                    title="Transactions" class="co-nav-item {{ request()->routeIs('companies.transactions') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <polyline points="17 1 21 5 17 9" />
                        <path d="M3 11V9a4 4 0 0 1 4-4h14" />
                        <polyline points="7 23 3 19 7 15" />
                        <path d="M21 13v2a4 4 0 0 1-4 4H3" />
                    </svg>
                    <span>Transactions</span>
                </a>

                @php $openActionsCount = $company->actions()->open()->count(); @endphp
                <a href="{{ route('companies.actions.index', $company) }}"
                    title="Actions" class="co-nav-item {{ request()->routeIs('companies.actions.*') ? 'active' : '' }}"
                    style="position:relative;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M9 11l3 3L22 4" />
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
                    </svg>
                    <span>Actions</span>
                    @if ($openActionsCount > 0)
                        <span style="margin-left:auto;background:#0079c8;color:#fff;font-size:5pt;font-weight:700;border-radius:999px;min-width:10pt;height:10pt;display:inline-flex;align-items:center;justify-content:center;padding:0 2pt;">
                            {{ $openActionsCount }}
                        </span>
                    @endif
                </a>
            </div>
        </div>

        <div class="co-sidebar-section collapsed">
            <button type="button" class="co-sidebar-section-label co-sidebar-section-toggle" aria-expanded="false">
                <span>Sales</span>
                <svg class="co-sidebar-section-chevron" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </button>

            <div class="co-sidebar-section-items">
                <a href="{{ route('companies.invoices.index', $company) }}"
                    title="Invoices" class="co-nav-item {{ request()->routeIs('companies.invoices.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                        <polyline points="14 2 14 8 20 8" />
                        <line x1="16" y1="13" x2="8" y2="13" />
                        <line x1="16" y1="17" x2="8" y2="17" />
                    </svg>
                    <span>Invoices</span>
                </a>

                <a href="{{ route('companies.quotations.index', $company) }}"
                    title="Quotations" class="co-nav-item {{ request()->routeIs('companies.quotations.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                        <polyline points="14 2 14 8 20 8" />
                        <path d="M9 15l2 2 4-4" />
                    </svg>
                    <span>Quotations</span>
                </a>

                <a href="{{ route('companies.delivery-notes.index', $company) }}"
                    title="Delivery Notes" class="co-nav-item {{ request()->routeIs('companies.delivery-notes.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <rect x="1" y="3" width="15" height="13" rx="1"/>
                        <path d="M16 8h4l3 4v4h-7V8z"/>
                        <circle cx="5.5" cy="18.5" r="2.5"/>
                        <circle cx="18.5" cy="18.5" r="2.5"/>
                    </svg>
                    <span>Delivery Notes</span>
                </a>

                <a href="{{ route('companies.credit-notes.index', $company) }}"
                    title="Credit Notes" class="co-nav-item {{ request()->routeIs('companies.credit-notes.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="9" y1="13" x2="15" y2="13"/>
                        <line x1="9" y1="17" x2="11" y2="17"/>
                    </svg>
                    <span>Credit Notes</span>
                </a>

                <a href="{{ route('companies.customers.index', $company) }}"
                    title="Customers" class="co-nav-item {{ request()->routeIs('companies.customers.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5zm0 2c-4.33 0-8 2.17-8 5v1h16v-1c0-2.83-3.67-5-8-5z" />
                    </svg>
                    <span>Customers</span>
                </a>

                <a href="{{ route('companies.inventory.index', $company) }}"
                    title="Inventory" class="co-nav-item {{ request()->routeIs('companies.inventory.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path
                            d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96" />
                        <line x1="12" y1="22.08" x2="12" y2="12" />
                    </svg>
                    <span>Inventory</span>
                </a>

            </div>
        </div>

        @php $purchasesActive = request()->routeIs('companies.suppliers.*') || request()->routeIs('companies.email-accounts.*'); @endphp
        <div class="co-sidebar-section {{ $purchasesActive ? '' : 'collapsed' }}">
            <button type="button" class="co-sidebar-section-label co-sidebar-section-toggle" aria-expanded="{{ $purchasesActive ? 'true' : 'false' }}">
                <span>Purchases</span>
                <svg class="co-sidebar-section-chevron" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </button>

            <div class="co-sidebar-section-items">
                <a href="{{ route('companies.suppliers.index', $company) }}"
                    title="Suppliers" class="co-nav-item {{ request()->routeIs('companies.suppliers.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <rect x="1" y="3" width="15" height="13" />
                        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8" />
                        <circle cx="5.5" cy="18.5" r="2.5" />
                        <circle cx="18.5" cy="18.5" r="2.5" />
                    </svg>
                    <span>Suppliers</span>
                </a>

                <a href="{{ route('companies.email-accounts.index', $company) }}"
                    title="Email Accounts" class="co-nav-item {{ request()->routeIs('companies.email-accounts.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                    <span>Email Accounts</span>
                </a>
            </div>
        </div>

        @php $registersActive = request()->routeIs('companies.assets.*') || request()->routeIs('companies.intangibles.*') || request()->routeIs('companies.investment-properties.*') || request()->routeIs('companies.held-for-sale.*') || request()->routeIs('companies.biological-assets.*') || request()->routeIs('companies.leases.*') || request()->routeIs('companies.ecl-register.*'); @endphp
        <div class="co-sidebar-section {{ $registersActive ? '' : 'collapsed' }}">
            <button type="button" class="co-sidebar-section-label co-sidebar-section-toggle" aria-expanded="{{ $registersActive ? 'true' : 'false' }}">
                <span>Registers</span>
                <svg class="co-sidebar-section-chevron" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </button>

            <div class="co-sidebar-section-items">
                <a href="{{ route('companies.assets.index', $company) }}"
                    title="PPE Register" class="co-nav-item {{ request()->routeIs('companies.assets.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <rect x="2" y="7" width="20" height="14" rx="2" />
                        <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2" />
                    </svg>
                    <span>PPE (IAS 16)</span>
                </a>

                <a href="{{ route('companies.intangibles.index', $company) }}"
                    title="Intangibles Register" class="co-nav-item {{ request()->routeIs('companies.intangibles.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
                        <line x1="12" y1="17" x2="12.01" y2="17" />
                    </svg>
                    <span>Intangibles (IAS 38)</span>
                </a>

                <a href="{{ route('companies.investment-properties.index', $company) }}"
                    title="Investment Property Register" class="co-nav-item {{ request()->routeIs('companies.investment-properties.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M3 21h18" />
                        <path d="M5 21V7l7-4 7 4v14" />
                        <path d="M9 21v-6h6v6" />
                        <path d="M9 9h1" /><path d="M14 9h1" />
                        <path d="M9 13h1" /><path d="M14 13h1" />
                    </svg>
                    <span>Inv. Property (IAS 40)</span>
                </a>

                <a href="{{ route('companies.held-for-sale.index', $company) }}"
                    title="Held for Sale Register" class="co-nav-item {{ request()->routeIs('companies.held-for-sale.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" />
                        <line x1="7" y1="7" x2="7.01" y2="7" />
                    </svg>
                    <span>Held for Sale (IFRS 5)</span>
                </a>

                <a href="{{ route('companies.biological-assets.index', $company) }}"
                    title="Biological Assets Register" class="co-nav-item {{ request()->routeIs('companies.biological-assets.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M12 2a10 10 0 0 1 0 20 10 10 0 0 1 0-20z" opacity="0" />
                        <path d="M7 20l4.5-12L16 12l-4.5 2z" />
                        <path d="M12 22c-1.5-3-2-6-1-9" />
                        <path d="M9 6c2.5 1 4 3.5 4 6" />
                        <path d="M15 6c-1.5 1.5-2.5 3.5-2.5 6" />
                    </svg>
                    <span>Bio Assets (IAS 41)</span>
                </a>

                <a href="{{ route('companies.leases.index', $company) }}"
                    title="Lease Register" class="co-nav-item {{ request()->routeIs('companies.leases.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                        <polyline points="10 17 15 12 10 7" />
                        <line x1="15" y1="12" x2="3" y2="12" />
                    </svg>
                    <span>Leases (IFRS 16)</span>
                </a>

                <a href="{{ route('companies.ecl-register.index', $company) }}"
                    title="ECL Register" class="co-nav-item {{ request()->routeIs('companies.ecl-register.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                        <path d="M9 12l2 2 4-4" />
                    </svg>
                    <span>ECL (IFRS 9)</span>
                </a>
            </div>
        </div>

        <div class="co-sidebar-section collapsed">
            <button type="button" class="co-sidebar-section-label co-sidebar-section-toggle" aria-expanded="false">
                <span>Payroll</span>
                <svg class="co-sidebar-section-chevron" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </button>

            <div class="co-sidebar-section-items">
                <a href="{{ route('companies.payroll.employees.index', $company) }}"
                    title="Employees" class="co-nav-item {{ request()->routeIs('companies.payroll.employees.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                    <span>Employees</span>
                </a>

                <a href="{{ route('companies.payroll.runs.index', $company) }}"
                    title="Payroll Runs" class="co-nav-item {{ request()->routeIs('companies.payroll.runs.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <rect x="2" y="7" width="20" height="14" rx="2" />
                        <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2" />
                        <line x1="12" y1="12" x2="12" y2="16" />
                        <line x1="10" y1="14" x2="14" y2="14" />
                    </svg>
                    <span>Payroll Runs</span>
                </a>

                <a href="{{ route('companies.payroll.components', $company) }}"
                    title="Pay Components" class="co-nav-item {{ request()->routeIs('companies.payroll.components') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <line x1="8" y1="6" x2="21" y2="6" />
                        <line x1="8" y1="12" x2="21" y2="12" />
                        <line x1="8" y1="18" x2="21" y2="18" />
                        <line x1="3" y1="6" x2="3.01" y2="6" />
                        <line x1="3" y1="12" x2="3.01" y2="12" />
                        <line x1="3" y1="18" x2="3.01" y2="18" />
                    </svg>
                    <span>Pay Components</span>
                </a>
            </div>
        </div>

        <div class="co-sidebar-section collapsed">
            <button type="button" class="co-sidebar-section-label co-sidebar-section-toggle" aria-expanded="false">
                <span>Reports</span>
                <svg class="co-sidebar-section-chevron" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </button>

            <div class="co-sidebar-section-items">
                <a href="{{ route('companies.reports.income-statement', $company) }}"
                    title="Income Statement" class="co-nav-item {{ request()->routeIs('companies.reports.income-statement') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <line x1="12" y1="20" x2="12" y2="10" />
                        <line x1="18" y1="20" x2="18" y2="4" />
                        <line x1="6" y1="20" x2="6" y2="16" />
                    </svg>
                    <span>Income Statement</span>
                </a>

                <a href="{{ route('companies.reports.cash-flow', $company) }}"
                    title="Cash Flow" class="co-nav-item {{ request()->routeIs('companies.reports.cash-flow') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2z" />
                        <path d="M12 6v6l4 2" />
                    </svg>
                    <span>Cash Flow</span>
                </a>

                <a href="{{ route('companies.reports.balance-sheet', $company) }}"
                    title="Balance Sheet" class="co-nav-item {{ request()->routeIs('companies.reports.balance-sheet') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <rect x="3" y="3" width="18" height="18" rx="2" />
                        <line x1="3" y1="12" x2="21" y2="12" />
                        <line x1="12" y1="3" x2="12" y2="21" />
                    </svg>
                    <span>Balance Sheet</span>
                </a>

                <a href="{{ route('companies.reports.changes-in-equity', $company) }}"
                    title="Changes in Equity" class="co-nav-item {{ request()->routeIs('companies.reports.changes-in-equity') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M3 3v18h18" />
                        <path d="M7 14l3-3 3 3 5-5" />
                    </svg>
                    <span>Changes in Equity</span>
                </a>

                <a href="{{ route('companies.reports.general-ledger', $company) }}"
                    title="General Ledger" class="co-nav-item {{ request()->routeIs('companies.reports.general-ledger') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                        <line x1="9" y1="9" x2="15" y2="9" />
                        <line x1="9" y1="13" x2="15" y2="13" />
                    </svg>
                    <span>General Ledger</span>
                </a>

                <a href="{{ route('companies.reports.trial-balance', $company) }}"
                    title="Trial Balance" class="co-nav-item {{ request()->routeIs('companies.reports.trial-balance') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <polyline points="9 11 12 14 22 4" />
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
                    </svg>
                    <span>Trial Balance</span>
                </a>

                <a href="{{ route('companies.reports.age-analysis', $company) }}"
                    title="Age Analysis" class="co-nav-item {{ request()->routeIs('companies.reports.age-analysis') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                    </svg>
                    <span>Age Analysis</span>
                </a>

                <a href="{{ route('companies.notes-to-afs.index', $company) }}"
                    title="Notes to AFS" class="co-nav-item {{ request()->routeIs('companies.notes-to-afs.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                        <polyline points="14 2 14 8 20 8" />
                        <line x1="16" y1="13" x2="8" y2="13" />
                        <line x1="16" y1="17" x2="8" y2="17" />
                        <line x1="10" y1="9" x2="8" y2="9" />
                    </svg>
                    <span>Notes to AFS</span>
                </a>

                <a href="{{ route('companies.afs-details.edit', $company) }}"
                    title="AFS Details" class="co-nav-item {{ request()->routeIs('companies.afs-details.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="3" />
                        <path
                            d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
                    </svg>
                    <span>AFS Details</span>
                </a>
            </div>
        </div>

        @php $groupActive = request()->routeIs('companies.group.*'); @endphp
        <div class="co-sidebar-section {{ $groupActive ? '' : 'collapsed' }}">
            <button type="button" class="co-sidebar-section-label co-sidebar-section-toggle" aria-expanded="{{ $groupActive ? 'true' : 'false' }}">
                <span>Group</span>
                <svg class="co-sidebar-section-chevron" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </button>

            <div class="co-sidebar-section-items">
                <a href="{{ route('companies.group.structure', $company) }}"
                    title="Subsidiaries Register" class="co-nav-item {{ request()->routeIs('companies.group.structure') || request()->routeIs('companies.group.subsidiaries.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <rect x="9" y="2" width="6" height="6" rx="1" />
                        <rect x="2" y="16" width="6" height="6" rx="1" />
                        <rect x="16" y="16" width="6" height="6" rx="1" />
                        <path d="M12 8v4M5 16v-2h14v2" />
                    </svg>
                    <span>Investments Register</span>
                </a>

                @if ($company->isGroupParent())
                    <a href="{{ route('companies.group.income-statement', $company) }}"
                        title="Consolidated Income" class="co-nav-item {{ request()->routeIs('companies.group.income-statement') ? 'active' : '' }}">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                            stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <line x1="12" y1="20" x2="12" y2="10" />
                            <line x1="18" y1="20" x2="18" y2="4" />
                            <line x1="6" y1="20" x2="6" y2="16" />
                        </svg>
                        <span>Consolidated P&L</span>
                    </a>

                    <a href="{{ route('companies.group.balance-sheet', $company) }}"
                        title="Consolidated Balance Sheet" class="co-nav-item {{ request()->routeIs('companies.group.balance-sheet') ? 'active' : '' }}">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                            stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <rect x="3" y="3" width="18" height="18" rx="2" />
                            <line x1="3" y1="12" x2="21" y2="12" />
                            <line x1="12" y1="3" x2="12" y2="21" />
                        </svg>
                        <span>Consolidated SFP</span>
                    </a>
                @endif
            </div>
        </div>

        @if ($company->vat_number)
            <div class="co-sidebar-section collapsed">
                <button type="button" class="co-sidebar-section-label co-sidebar-section-toggle" aria-expanded="false">
                    <span>VAT</span>
                    <svg class="co-sidebar-section-chevron" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <polyline points="6 9 12 15 18 9" />
                    </svg>
                </button>

                <div class="co-sidebar-section-items">
                    <a href="{{ route('companies.reports.vat-return', $company) }}"
                        title="VAT Return" class="co-nav-item {{ request()->routeIs('companies.reports.vat-return') ? 'active' : '' }}">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75"
                            stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2" />
                            <line x1="1" y1="10" x2="23" y2="10" />
                        </svg>
                        <span>VAT Return</span>
                    </a>
                </div>
            </div>
        @endif

    </nav>
</aside>

<script>
    (function () {
        const sidebar = document.getElementById('co-sidebar');
        const toggle  = document.getElementById('co-sidebar-toggle');

        // ── Whole-sidebar collapse ─────────────────────────────────
        if (localStorage.getItem('co-sidebar-collapsed') === '1') {
            sidebar.classList.add('collapsed');
        }

        toggle.addEventListener('click', function () {
            const collapsed = sidebar.classList.toggle('collapsed');
            localStorage.setItem('co-sidebar-collapsed', collapsed ? '1' : '0');
            toggle.title = collapsed ? 'Expand sidebar' : 'Collapse sidebar';
            toggle.setAttribute('aria-label', toggle.title);
        });

        // ── Per-section collapse (persisted) ──────────────────────
        document.querySelectorAll('.co-sidebar-section-toggle').forEach(function (btn) {
            const section = btn.closest('.co-sidebar-section');
            const label   = btn.querySelector('span')?.textContent?.trim() ?? '';
            const key     = 'co-section-' + label;

            // Restore saved state; fall back to the HTML default (collapsed class)
            const saved = localStorage.getItem(key);
            if (saved !== null) {
                const isCollapsed = saved === '1';
                section.classList.toggle('collapsed', isCollapsed);
                btn.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
            }

            btn.addEventListener('click', function () {
                const nowCollapsed = section.classList.toggle('collapsed');
                btn.setAttribute('aria-expanded', nowCollapsed ? 'false' : 'true');
                localStorage.setItem(key, nowCollapsed ? '1' : '0');
            });
        });
    })();
</script>

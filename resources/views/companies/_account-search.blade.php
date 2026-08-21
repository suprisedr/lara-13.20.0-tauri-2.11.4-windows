{{--
    Reusable searchable chart-of-accounts field.

    Usage:
        {!! account_search_field('chart_of_account_id', $selectedId, $accounts) !!}

    Renders a text input backed by a hidden <input> (the field actually
    submitted), with a dropdown of matching accounts filtered as you type.

    Include this partial once per page (e.g. @include('companies._account-search'))
    to pull in the shared styles/script — it is safe to include multiple times.
--}}
@once
    @push('styles')
        <style>
            .acct-search {
                position: relative;
            }

            .acct-search-input {
                width: 100%;
                border: 1px solid #d3e2f5;
                border-radius: 0;
                padding: 0.4rem 0.6rem;
                font-size: 0.78rem;
                font-family: inherit;
                outline: none;
                color: #000;
                background: #fff;
                box-sizing: border-box;
                transition: border-color 0.15s;
            }

            .acct-search-input:focus {
                border-color: #000;
            }

            .acct-search-dropdown {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: #fff;
                border: 1px solid #000;
                border-top: none;
                border-radius: 0;
                max-height: 180px;
                overflow-y: auto;
                z-index: 1100;
                box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            }

            .acct-search-option {
                padding: 0.35rem 0.6rem;
                font-size: 0.78rem;
                cursor: pointer;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .acct-search-option:hover {
                background: #f4fafc;
            }

            .acct-search-option.acct-search-empty {
                color: #6f869b;
                font-style: italic;
                cursor: default;
            }

            .acct-search-option.acct-search-empty:hover {
                background: transparent;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            /* ── Generic chart-of-accounts search field (.acct-search) ── */
            function acctSearchFilter(input) {
                const wrap = input.closest('.acct-search');
                const dd = wrap.querySelector('.acct-search-dropdown');
                const q = input.value.toLowerCase();
                let anyVisible = false;
                dd.querySelectorAll('.acct-search-option:not(.acct-search-empty)').forEach(opt => {
                    const match = opt.dataset.label.toLowerCase().includes(q);
                    opt.style.display = match ? '' : 'none';
                    if (match) anyVisible = true;
                });
                let empty = dd.querySelector('.acct-search-empty');
                if (!anyVisible) {
                    if (!empty) {
                        empty = document.createElement('div');
                        empty.className = 'acct-search-option acct-search-empty';
                        empty.textContent = 'No matching accounts';
                        dd.appendChild(empty);
                    }
                } else if (empty) {
                    empty.remove();
                }
                dd.style.display = 'block';
            }

            function acctSearchSelect(option) {
                const wrap = option.closest('.acct-search');
                const input = wrap.querySelector('.acct-search-input');
                const hidden = wrap.querySelector('.acct-search-value');
                input.value = option.dataset.label;
                hidden.value = option.dataset.id;
                wrap.querySelector('.acct-search-dropdown').style.display = 'none';
                hidden.dispatchEvent(new Event('change', { bubbles: true }));
            }

            document.addEventListener('focus', e => {
                if (e.target.classList && e.target.classList.contains('acct-search-input')) {
                    acctSearchFilter(e.target);
                }
            }, true);

            document.addEventListener('input', e => {
                if (e.target.classList && e.target.classList.contains('acct-search-input')) {
                    const wrap = e.target.closest('.acct-search');
                    wrap.querySelector('.acct-search-value').value = '';
                    acctSearchFilter(e.target);
                }
            });

            document.addEventListener('blur', e => {
                if (e.target.classList && e.target.classList.contains('acct-search-input')) {
                    const input = e.target;
                    setTimeout(() => {
                        const wrap = input.closest('.acct-search');
                        const dd = wrap.querySelector('.acct-search-dropdown');
                        dd.style.display = 'none';
                        // Revert to the last confirmed selection if the field was left without picking an option
                        const hidden = wrap.querySelector('.acct-search-value');
                        const selected = dd.querySelector('.acct-search-option[data-id="' + hidden.value + '"]');
                        input.value = hidden.value && selected ? selected.dataset.label : '';
                    }, 150);
                }
            }, true);

            document.addEventListener('mousedown', e => {
                const option = e.target.closest('.acct-search-option:not(.acct-search-empty)');
                if (option) acctSearchSelect(option);
            });
        </script>
    @endpush
@endonce

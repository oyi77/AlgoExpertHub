(function() {
    'use strict';

    const endpoints = {
        users: '/api/admin/users',
        counts: '/api/admin/users/stats/counts',
        bulkMail: '/admin/user/bulk-mail'
    };

    let state = {
        page: 1,
        search: '',
        status: 'active',
        loading: false
    };

    const elements = {
        tabContainer: document.querySelector('.page-link-list'),
        tableContainer: document.querySelector('.card-body.p-4'),
        searchForm: document.querySelector('.card-header-left form'),
        paginationContainer: document.querySelector('.card-footer')
    };

    function init() {
        if (!elements.tableContainer) return;

        loadCounts();
        loadUsers();

        setupTabs();
        setupSearch();
    }

    function setupTabs() {
        if (!elements.tabContainer) return;
        
        elements.tabContainer.addEventListener('click', function(e) {
            const link = e.target.closest('a');
            if (!link || !link.dataset.status) return;
            
            e.preventDefault();
            
            elements.tabContainer.querySelectorAll('a').forEach(a => a.classList.remove('active'));
            link.classList.add('active');
            
            state.status = link.dataset.status;
            state.page = 1;
            loadUsers();
        });
    }

    function setupSearch() {
        if (!elements.searchForm) return;
        
        elements.searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const input = this.querySelector('input[name="search"]');
            if (input) {
                state.search = input.value;
                state.page = 1;
                loadUsers();
            }
        });
    }

    function getCSRFToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute('content') : '';
    }

    async function loadCounts() {
        try {
            const response = await fetch(endpoints.counts, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCSRFToken()
                },
                credentials: 'same-origin' // Include cookies for session auth
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            if (data.success) {
                updateTabs(data.data);
            }
        } catch (error) {
            console.error('Failed to load counts', error);
        }
    }

    function updateTabs(counts) {
        if (!counts) return;
        
        const map = {
            'all': 'count-all',
            'active': 'count-active',
            'deactive': 'count-deactive',
            'kyc_req': 'count-kyc_req'
        };
        
        for (const [key, id] of Object.entries(map)) {
            const el = document.getElementById(id);
            if (el && counts[key] !== undefined) {
                el.textContent = counts[key];
            }
        }
    }

    function renderLoading() {
        elements.tableContainer.innerHTML = `
            <div class="text-center p-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2">Loading users...</p>
            </div>
        `;
    }

    function renderError(error) {
        console.error(error);
        elements.tableContainer.innerHTML = `
            <div class="text-center p-5 text-danger">
                <i class="las la-exclamation-triangle fa-3x mb-3"></i>
                <h5>Error loading data</h5>
                <p>${error.message || 'Unknown error'}</p>
                <button onclick="location.reload()" class="btn btn-sm btn-outline-danger">Reload Page</button>
            </div>
        `;
    }

    function renderPagination(meta) {
    }

    async function loadUsers() {
        state.loading = true;
        renderLoading();

        try {
            const params = new URLSearchParams({
                page: state.page,
                search: state.search,
                user_status: state.status === 'active' ? 'user_active' : (state.status === 'deactive' ? 'user_inactive' : '')
            });

            const response = await fetch(`${endpoints.users}?${params}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCSRFToken()
                },
                credentials: 'same-origin' // Include cookies for session auth
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            if (data.success) {
                renderTable(data.data);
                renderPagination(data.data);
            }
        } catch (error) {
            renderError(error);
        } finally {
            state.loading = false;
        }
    }

    function renderTable(paginatedData) {
        const users = paginatedData.data;
        if (users.length === 0) {
            elements.tableContainer.innerHTML = `
                <div class="text-center p-5">
                    <i class="las la-user-slash fa-3x text-muted mb-3"></i>
                    <h5>No users found</h5>
                </div>
            `;
            return;
        }

        let html = `
            <div class="table-responsive">
                <table class="table student-data-table m-t-20">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="checkAll"></th>
                            <th>User</th>
                            <th>Email/Phone</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        users.forEach(user => {
            html += `
                <tr>
                    <td><input type="checkbox" class="user-checkbox" value="${user.id}"></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-sm mr-3">
                                <span class="avatar-title rounded-circle bg-primary text-white">
                                    ${user.username.charAt(0).toUpperCase()}
                                </span>
                            </div>
                            <div>
                                <h6 class="mb-0">${user.username}</h6>
                                <small class="text-muted">ID: ${user.id}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div>${user.email}</div>
                        <small class="text-muted">${user.phone || 'N/A'}</small>
                    </td>
                    <td>
                        ${getStatusBadge(user)}
                    </td>
                    <td>
                        <a href="/admin/user/details?user=${user.id}" class="btn btn-sm btn-primary">
                            <i class="las la-eye"></i>
                        </a>
                    </td>
                </tr>
            `;
        });

        html += `</tbody></table></div>`;
        elements.tableContainer.innerHTML = html;
    }

    function getStatusBadge(user) {
        if (user.status) {
            return '<span class="badge badge-success">Active</span>';
        }
        return '<span class="badge badge-danger">Banned</span>';
    }

    function getAuthToken() {
        // Just return empty string if using session auth (cookies will handle it)
        // CSRF token is needed for non-GET requests though
        const csrf = document.querySelector('meta[name="csrf-token"]');
        return csrf ? csrf.content : '';
    }

    document.addEventListener('DOMContentLoaded', init);

})();

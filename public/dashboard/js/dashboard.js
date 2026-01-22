// API Configuration
// API Configuration
// Dynamically determine API base URL based on current path
const getBaseUrl = () => {
    const path = window.location.pathname;
    // If we are in /public/, append /api/admin/dashboard
    if (path.includes('/public')) {
        return window.location.origin + '/public/api/admin/dashboard';
    }
    // Otherwise assume root
    return window.location.origin + '/api/admin/dashboard';
};

const API_BASE_URL = getBaseUrl();
let authToken = localStorage.getItem('admin_token') || '';

// API Helper Functions
async function apiCall(endpoint, options = {}) {
    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...options.headers
    };

    if (authToken) {
        headers['Authorization'] = `Bearer ${authToken}`;
    }

    try {
        const response = await fetch(`${API_BASE_URL}${endpoint}`, {
            ...options,
            headers
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        return data;
    } catch (error) {
        console.error('API Error:', error);
        console.error('Failed to fetch:', `${API_BASE_URL}${endpoint}`);
        // Only show alert for critical errors, not for every API call
        throw error;
    }
}

// UI State Management
const state = {
    currentPage: 'dashboard',
    statistics: null,
    users: null,
    posts: null,
    hobbies: null,
    matches: null,
    currentUsersPage: 1,
    currentPostsPage: 1,
    filters: {
        gender: '',
        country: ''
    }
};

// DOM Elements
const elements = {
    sidebar: document.getElementById('sidebar'),
    mainContent: document.getElementById('mainContent'),
    menuToggle: document.getElementById('menuToggle'),
    refreshBtn: document.getElementById('refreshBtn'),
    searchInput: document.getElementById('searchInput'),
    pageTitle: document.getElementById('pageTitle'),
    pageSubtitle: document.getElementById('pageSubtitle'),
    loadingOverlay: document.getElementById('loadingOverlay'),
    statsGrid: document.getElementById('statsGrid')
};

// Initialize Dashboard
document.addEventListener('DOMContentLoaded', () => {
    initializeNavigation();
    initializeEventListeners();
    loadDashboardData();
});

// Navigation
function initializeNavigation() {
    const navLinks = document.querySelectorAll('.nav-link');

    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const page = link.dataset.page;

            // Update active state
            navLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');

            // Switch page
            switchPage(page);

            // Close sidebar on mobile
            if (window.innerWidth <= 768) {
                elements.sidebar.classList.remove('open');
            }
        });
    });
}

function switchPage(page) {
    state.currentPage = page;

    // Hide all pages
    const pages = document.querySelectorAll('#contentArea > section');
    pages.forEach(p => p.style.display = 'none');

    // Show selected page
    const selectedPage = document.getElementById(`${page}Page`);
    if (selectedPage) {
        selectedPage.style.display = 'block';
        selectedPage.classList.add('fade-in');
    }

    // Update page title
    const titles = {
        dashboard: { title: 'لوحة التحكم', subtitle: 'نظرة عامة على إحصائيات التطبيق' },
        users: { title: 'المستخدمين', subtitle: 'إدارة وعرض المستخدمين' },
        posts: { title: 'المنشورات', subtitle: 'إدارة وعرض المنشورات' },
        hobbies: { title: 'الهوايات', subtitle: 'الهوايات الأكثر شعبية' },
        matches: { title: 'المطابقات', subtitle: 'تحليلات المطابقة والتوافق' },
        analytics: { title: 'التحليلات', subtitle: 'تقارير وإحصائيات متقدمة' }
    };

    if (titles[page]) {
        elements.pageTitle.textContent = titles[page].title;
        elements.pageSubtitle.textContent = titles[page].subtitle;
    }

    // Load page-specific data
    loadPageData(page);
}

// Event Listeners
function initializeEventListeners() {
    // Menu toggle
    elements.menuToggle.addEventListener('click', () => {
        elements.sidebar.classList.toggle('open');
    });

    // Refresh button
    elements.refreshBtn.addEventListener('click', () => {
        loadPageData(state.currentPage);
    });

    // Search
    let searchTimeout;
    elements.searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            handleSearch(e.target.value);
        }, 500);
    });

    // Filters
    const genderFilter = document.getElementById('genderFilter');
    const countryFilter = document.getElementById('countryFilter');

    if (genderFilter) {
        genderFilter.addEventListener('change', (e) => {
            state.filters.gender = e.target.value;
            loadUsers();
        });
    }

    if (countryFilter) {
        countryFilter.addEventListener('change', (e) => {
            state.filters.country = e.target.value;
            loadUsers();
        });
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 768) {
            if (!elements.sidebar.contains(e.target) && !elements.menuToggle.contains(e.target)) {
                elements.sidebar.classList.remove('open');
            }
        }
    });
}

// Data Loading Functions
async function loadDashboardData() {
    showLoading();
    try {
        await loadStatistics();
        // Load users data to get user growth
        const usersResponse = await apiCall('/users?per_page=1');
        if (usersResponse.data.user_growth) {
            initUserGrowthChart(usersResponse.data.user_growth);
        }
        // Initialize other charts with statistics data
        if (state.statistics) {
            if (state.statistics.gender_distribution) {
                initGenderChart(state.statistics.gender_distribution);
            }
            if (state.statistics.top_countries) {
                initCountriesChart(state.statistics.top_countries);
            }
        }
    } catch (error) {
        console.error('Error loading dashboard:', error);
    } finally {
        hideLoading();
    }
}

async function loadPageData(page) {
    showLoading();
    try {
        switch (page) {
            case 'dashboard':
                await loadStatistics();
                break;
            case 'users':
                await loadUsers();
                break;
            case 'posts':
                await loadPosts();
                break;
            case 'hobbies':
                await loadHobbies();
                break;
            case 'matches':
                await loadMatches();
                break;
            case 'analytics':
                await loadAnalytics();
                break;
        }
    } catch (error) {
        console.error(`Error loading ${page}:`, error);
    } finally {
        hideLoading();
    }
}

async function loadStatistics() {
    try {
        const response = await apiCall('/statistics');
        state.statistics = response.data;
        renderStatistics(response.data);
    } catch (error) {
        console.error('Error loading statistics:', error);
    }
}

async function loadUsers(page = 1, search = '') {
    try {
        const params = new URLSearchParams({
            per_page: 10,
            page: page,
            ...state.filters
        });

        if (search) {
            params.append('search', search);
        }

        const response = await apiCall(`/users?${params}`);
        state.users = response.data;
        renderUsers(response.data);
        renderPagination(response.data.pagination, 'users', loadUsers);
    } catch (error) {
        console.error('Error loading users:', error);
    }
}

async function loadPosts(page = 1, search = '') {
    try {
        const params = new URLSearchParams({
            per_page: 10,
            page: page
        });

        if (search) {
            params.append('search', search);
        }

        const response = await apiCall(`/posts?${params}`);
        state.posts = response.data;
        renderPosts(response.data);
        renderPagination(response.data.pagination, 'posts', loadPosts);

        // Update posts activity chart
        if (response.data.posts_activity) {
            updatePostsActivityChart(response.data.posts_activity);
        }
    } catch (error) {
        console.error('Error loading posts:', error);
    }
}

async function loadHobbies(search = '') {
    try {
        const params = search ? `?search=${encodeURIComponent(search)}` : '';
        const response = await apiCall(`/hobbies${params}`);
        state.hobbies = response.data;
        renderHobbies(response.data);
        updateHobbiesChart(response.data.hobbies);
    } catch (error) {
        console.error('Error loading hobbies:', error);
    }
}

async function loadMatches() {
    try {
        const response = await apiCall('/matches');
        state.matches = response.data;
        renderMatches(response.data);
    } catch (error) {
        console.error('Error loading matches:', error);
    }
}

async function loadAnalytics() {
    try {
        await loadStatistics();
        const response = await apiCall('/matches');

        if (response.data.country_distribution) {
            updateCountryDistributionChart(response.data.country_distribution);
        }

        renderGeneralStats();
    } catch (error) {
        console.error('Error loading analytics:', error);
    }
}

// Render Functions
function renderStatistics(data) {
    const statsHTML = `
        <div class="stat-card fade-in">
            <div class="stat-header">
                <div class="stat-icon purple">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-trend up">
                    <i class="fas fa-arrow-up"></i>
                    <span>+${data.new_users_this_month}</span>
                </div>
            </div>
            <div class="stat-body">
                <h3>إجمالي المستخدمين</h3>
                <div class="stat-value">${formatNumber(data.total_users)}</div>
            </div>
        </div>
        
        <div class="stat-card fade-in" style="animation-delay: 0.1s;">
            <div class="stat-header">
                <div class="stat-icon pink">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-trend up">
                    <i class="fas fa-arrow-up"></i>
                    <span>+${data.new_posts_this_month}</span>
                </div>
            </div>
            <div class="stat-body">
                <h3>إجمالي المنشورات</h3>
                <div class="stat-value">${formatNumber(data.total_posts)}</div>
            </div>
        </div>
        
        <div class="stat-card fade-in" style="animation-delay: 0.2s;">
            <div class="stat-header">
                <div class="stat-icon green">
                    <i class="fas fa-heart"></i>
                </div>
            </div>
            <div class="stat-body">
                <h3>إجمالي الهوايات</h3>
                <div class="stat-value">${formatNumber(data.total_hobbies)}</div>
            </div>
        </div>
        
        <div class="stat-card fade-in" style="animation-delay: 0.3s;">
            <div class="stat-header">
                <div class="stat-icon orange">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
            <div class="stat-body">
                <h3>متوسط الهوايات لكل مستخدم</h3>
                <div class="stat-value">${data.avg_hobbies_per_user}</div>
            </div>
        </div>
    `;

    elements.statsGrid.innerHTML = statsHTML;
}

function renderUsers(data) {
    const tbody = document.getElementById('usersTableBody');

    if (!data.users || data.users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 2rem;">لا توجد بيانات</td></tr>';
        return;
    }

    tbody.innerHTML = data.users.map(user => `
        <tr class="fade-in">
            <td>
                ${user.profile_image
            ? `<img src="${user.profile_image}" alt="${user.name}" class="user-avatar">`
            : `<div class="user-avatar" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); display: flex; align-items: center; justify-content: center; font-weight: bold;">${user.name.charAt(0)}</div>`
        }
            </td>
            <td style="font-weight: 600;">${user.name}</td>
            <td style="color: var(--text-muted);">${user.nickname || '-'}</td>
            <td>${user.phone || '-'}</td>
            <td>${user.age || '-'}</td>
            <td>${user.country || '-'}</td>
            <td><span class="badge ${user.gender}">${user.gender === 'male' ? 'ذكر' : 'أنثى'}</span></td>
            <td>${user.posts_count}</td>
            <td>${user.hobbies_count}</td>
            <td>${formatDate(user.created_at)}</td>
        </tr>
    `).join('');

    // Populate country filter
    const countries = [...new Set(data.users.map(u => u.country).filter(Boolean))];
    const countryFilter = document.getElementById('countryFilter');
    if (countryFilter && countries.length > 0) {
        countryFilter.innerHTML = '<option value="">كل الدول</option>' +
            countries.map(c => `<option value="${c}">${c}</option>`).join('');
    }
}

function renderPosts(data) {
    const tbody = document.getElementById('postsTableBody');

    if (!data.posts || data.posts.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem;">لا توجد بيانات</td></tr>';
        return;
    }

    tbody.innerHTML = data.posts.map(post => `
        <tr class="fade-in">
            <td>
                ${post.user.profile_image
            ? `<img src="${post.user.profile_image}" alt="${post.user.name}" class="user-avatar">`
            : `<div class="user-avatar" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); display: flex; align-items: center; justify-content: center; font-weight: bold;">${post.user.name.charAt(0)}</div>`
        }
            </td>
            <td style="font-weight: 600;">${post.user.name}</td>
            <td style="color: var(--text-muted);">${post.user.nickname || '-'}</td>
            <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                ${post.content || '-'}
            </td>
            <td>
                ${post.image
            ? `<img src="${post.image}" alt="Post image" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">`
            : '-'
        }
            </td>
            <td>${post.comments_count}</td>
            <td>${formatDate(post.created_at)}</td>
        </tr>
    `).join('');
}

function renderHobbies(data) {
    const tbody = document.getElementById('hobbiesTableBody');

    if (!data.hobbies || data.hobbies.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" style="text-align: center; padding: 2rem;">لا توجد بيانات</td></tr>';
        return;
    }

    tbody.innerHTML = data.hobbies.map(hobby => `
        <tr class="fade-in">
            <td>
                ${hobby.icon
            ? `<img src="${hobby.icon}" alt="${hobby.name}" style="width: 40px; height: 40px; object-fit: contain;">`
            : '<i class="fas fa-heart" style="font-size: 1.5rem; color: var(--primary);"></i>'
        }
            </td>
            <td style="font-weight: 600;">${hobby.name}</td>
            <td>${hobby.users_count}</td>
        </tr>
    `).join('');
}

function renderMatches(data) {
    const tbody = document.getElementById('matchesTableBody');

    if (!data.most_matchable_users || data.most_matchable_users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" style="text-align: center; padding: 2rem;">لا توجد بيانات</td></tr>';
        return;
    }

    tbody.innerHTML = data.most_matchable_users.map((user, index) => `
        <tr class="fade-in">
            <td style="font-size: 1.25rem; font-weight: bold; color: var(--primary);">#${index + 1}</td>
            <td>
                ${user.profile_image
            ? `<img src="${user.profile_image}" alt="${user.name}" class="user-avatar">`
            : `<div class="user-avatar" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); display: flex; align-items: center; justify-content: center; font-weight: bold;">${user.name.charAt(0)}</div>`
        }
            </td>
            <td style="font-weight: 600;">${user.name}</td>
            <td style="color: var(--text-muted);">${user.nickname || '-'}</td>
            <td><span class="badge" style="background: rgba(139, 92, 246, 0.2); color: var(--primary-light);">${user.hobbies_count} هواية</span></td>
        </tr>
    `).join('');
}

function renderGeneralStats() {
    const container = document.getElementById('generalStats');
    if (!state.statistics) return;

    const stats = state.statistics;
    container.innerHTML = `
        <div style="display: grid; gap: 1rem;">
            <div style="padding: 1rem; background: var(--bg-secondary); border-radius: var(--radius-sm);">
                <div style="color: var(--text-muted); font-size: 0.9rem;">إجمالي المستخدمين</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-light);">${formatNumber(stats.total_users)}</div>
            </div>
            <div style="padding: 1rem; background: var(--bg-secondary); border-radius: var(--radius-sm);">
                <div style="color: var(--text-muted); font-size: 0.9rem;">إجمالي المنشورات</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--secondary-light);">${formatNumber(stats.total_posts)}</div>
            </div>
            <div style="padding: 1rem; background: var(--bg-secondary); border-radius: var(--radius-sm);">
                <div style="color: var(--text-muted); font-size: 0.9rem;">متوسط الهوايات</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--accent);">${stats.avg_hobbies_per_user}</div>
            </div>
        </div>
    `;
}

function renderPagination(pagination, type, callback) {
    const container = document.getElementById(`${type}Pagination`);
    if (!container || !pagination) return;

    const pages = [];
    for (let i = 1; i <= pagination.last_page; i++) {
        pages.push(i);
    }

    container.innerHTML = pages.map(page => `
        <button 
            class="btn ${page === pagination.current_page ? 'btn-primary' : ''}" 
            onclick="window.dashboardCallbacks.${type}(${page})"
            style="min-width: 40px;">
            ${page}
        </button>
    `).join('');
}

// Helper Functions
function handleSearch(query) {
    const page = state.currentPage;

    if (page === 'users') {
        loadUsers(1, query);
    } else if (page === 'posts') {
        loadPosts(1, query);
    } else if (page === 'hobbies') {
        loadHobbies(query);
    }
}

function showLoading() {
    elements.loadingOverlay.style.display = 'flex';
}

function hideLoading() {
    elements.loadingOverlay.style.display = 'none';
}

function showError(message) {
    console.warn('Dashboard Error:', message);
    // You can add a toast notification here instead of alert
}

function formatNumber(num) {
    return new Intl.NumberFormat('ar-EG').format(num);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('ar-EG', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
}

// Global callbacks for pagination
window.dashboardCallbacks = {
    users: loadUsers,
    posts: loadPosts
};

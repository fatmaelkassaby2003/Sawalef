// Chart.js Configuration and Initialization

// Chart instances
let userGrowthChart = null;
let genderChart = null;
let countriesChart = null;
let postsActivityChart = null;
let hobbiesChart = null;
let countryDistributionChart = null;

// Chart colors matching the design system
const chartColors = {
    primary: 'rgba(139, 92, 246, 0.8)',
    primaryLight: 'rgba(167, 139, 250, 0.8)',
    secondary: 'rgba(236, 72, 153, 0.8)',
    secondaryLight: 'rgba(244, 114, 182, 0.8)',
    accent: 'rgba(16, 185, 129, 0.8)',
    accentOrange: 'rgba(245, 158, 11, 0.8)',
    gradient: {
        primary: ['rgba(139, 92, 246, 0.8)', 'rgba(124, 58, 237, 0.4)'],
        secondary: ['rgba(236, 72, 153, 0.8)', 'rgba(219, 39, 119, 0.4)'],
        accent: ['rgba(16, 185, 129, 0.8)', 'rgba(5, 150, 105, 0.4)']
    }
};

// Default chart options
const defaultChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            labels: {
                color: '#CBD5E1',
                font: {
                    family: 'Cairo, sans-serif',
                    size: 12
                },
                padding: 15
            }
        },
        tooltip: {
            backgroundColor: 'rgba(30, 41, 59, 0.95)',
            titleColor: '#F1F5F9',
            bodyColor: '#CBD5E1',
            borderColor: 'rgba(139, 92, 246, 0.5)',
            borderWidth: 1,
            padding: 12,
            displayColors: true,
            titleFont: {
                family: 'Cairo, sans-serif',
                size: 14,
                weight: 'bold'
            },
            bodyFont: {
                family: 'Cairo, sans-serif',
                size: 12
            }
        }
    },
    scales: {
        x: {
            ticks: {
                color: '#94A3B8',
                font: {
                    family: 'Cairo, sans-serif'
                }
            },
            grid: {
                color: 'rgba(148, 163, 184, 0.1)',
                borderColor: 'rgba(148, 163, 184, 0.2)'
            }
        },
        y: {
            ticks: {
                color: '#94A3B8',
                font: {
                    family: 'Cairo, sans-serif'
                }
            },
            grid: {
                color: 'rgba(148, 163, 184, 0.1)',
                borderColor: 'rgba(148, 163, 184, 0.2)'
            }
        }
    }
};

// Initialize all charts
async function initializeCharts() {
    try {
        const stats = state.statistics;
        if (!stats) return;

        // User Growth Chart
        if (state.users && state.users.user_growth) {
            initUserGrowthChart(state.users.user_growth);
        }

        // Gender Distribution Chart
        if (stats.gender_distribution) {
            initGenderChart(stats.gender_distribution);
        }

        // Top Countries Chart
        if (stats.top_countries) {
            initCountriesChart(stats.top_countries);
        }
    } catch (error) {
        console.error('Error initializing charts:', error);
    }
}

// User Growth Chart
function initUserGrowthChart(data) {
    const ctx = document.getElementById('userGrowthChart');
    if (!ctx) return;

    if (userGrowthChart) {
        userGrowthChart.destroy();
    }

    const labels = data.map(item => {
        const date = new Date(item.date);
        return new Intl.DateTimeFormat('ar-EG', { month: 'short', day: 'numeric' }).format(date);
    });

    const values = data.map(item => item.count);

    userGrowthChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'مستخدمين جدد',
                data: values,
                backgroundColor: createGradient(ctx, chartColors.gradient.primary),
                borderColor: chartColors.primary,
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: chartColors.primary,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            ...defaultChartOptions,
            plugins: {
                ...defaultChartOptions.plugins,
                legend: {
                    display: false
                }
            }
        }
    });
}

// Gender Distribution Chart
function initGenderChart(data) {
    const ctx = document.getElementById('genderChart');
    if (!ctx) return;

    if (genderChart) {
        genderChart.destroy();
    }

    const maleCount = data.male || 0;
    const femaleCount = data.female || 0;

    genderChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['ذكر', 'أنثى'],
            datasets: [{
                data: [maleCount, femaleCount],
                backgroundColor: [
                    chartColors.primary,
                    chartColors.secondary
                ],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#CBD5E1',
                        font: {
                            family: 'Cairo, sans-serif',
                            size: 14
                        },
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    ...defaultChartOptions.plugins.tooltip,
                    callbacks: {
                        label: function (context) {
                            const total = maleCount + femaleCount;
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return `${context.label}: ${context.parsed} (${percentage}%)`;
                        }
                    }
                }
            },
            cutout: '70%'
        }
    });
}

// Top Countries Chart
function initCountriesChart(data) {
    const ctx = document.getElementById('countriesChart');
    if (!ctx) return;

    if (countriesChart) {
        countriesChart.destroy();
    }

    const labels = data.map(item => item.country_en || item.country);
    const values = data.map(item => item.count);

    const colors = [
        chartColors.primary,
        chartColors.secondary,
        chartColors.accent,
        chartColors.accentOrange,
        chartColors.primaryLight
    ];

    countriesChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'عدد المستخدمين',
                data: values,
                backgroundColor: colors.slice(0, labels.length),
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            ...defaultChartOptions,
            plugins: {
                ...defaultChartOptions.plugins,
                legend: {
                    display: false
                }
            },
            scales: {
                ...defaultChartOptions.scales,
                y: {
                    ...defaultChartOptions.scales.y,
                    beginAtZero: true
                }
            }
        }
    });
}

// Posts Activity Chart
function updatePostsActivityChart(data) {
    const ctx = document.getElementById('postsActivityChart');
    if (!ctx) return;

    if (postsActivityChart) {
        postsActivityChart.destroy();
    }

    const labels = data.map(item => {
        const date = new Date(item.date);
        return new Intl.DateTimeFormat('ar-EG', { month: 'short', day: 'numeric' }).format(date);
    });

    const values = data.map(item => item.count);

    postsActivityChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'منشورات جديدة',
                data: values,
                backgroundColor: createGradient(ctx, chartColors.gradient.secondary),
                borderColor: chartColors.secondary,
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            ...defaultChartOptions,
            plugins: {
                ...defaultChartOptions.plugins,
                legend: {
                    display: false
                }
            },
            scales: {
                ...defaultChartOptions.scales,
                y: {
                    ...defaultChartOptions.scales.y,
                    beginAtZero: true
                }
            }
        }
    });
}

// Hobbies Chart
function updateHobbiesChart(data) {
    const ctx = document.getElementById('hobbiesChart');
    if (!ctx) return;

    if (hobbiesChart) {
        hobbiesChart.destroy();
    }

    // Get top 10 hobbies
    const topHobbies = data.slice(0, 10);
    const labels = topHobbies.map(item => item.name);
    const values = topHobbies.map(item => item.users_count);

    hobbiesChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'عدد المستخدمين',
                data: values,
                backgroundColor: createGradient(ctx, chartColors.gradient.accent),
                borderColor: chartColors.accent,
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            ...defaultChartOptions,
            indexAxis: 'y',
            plugins: {
                ...defaultChartOptions.plugins,
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    ...defaultChartOptions.scales.x,
                    beginAtZero: true
                },
                y: {
                    ...defaultChartOptions.scales.y
                }
            }
        }
    });
}

// Country Distribution Chart
function updateCountryDistributionChart(data) {
    const ctx = document.getElementById('countryDistributionChart');
    if (!ctx) return;

    if (countryDistributionChart) {
        countryDistributionChart.destroy();
    }

    // Get top 8 countries
    const topCountries = data.slice(0, 8);
    const labels = topCountries.map(item => item.country_en || item.country);
    const values = topCountries.map(item => item.count);

    const colors = [
        chartColors.primary,
        chartColors.secondary,
        chartColors.accent,
        chartColors.accentOrange,
        chartColors.primaryLight,
        chartColors.secondaryLight,
        'rgba(99, 102, 241, 0.8)',
        'rgba(245, 158, 11, 0.6)'
    ];

    countryDistributionChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors.slice(0, labels.length),
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        color: '#CBD5E1',
                        font: {
                            family: 'Cairo, sans-serif',
                            size: 12
                        },
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    ...defaultChartOptions.plugins.tooltip,
                    callbacks: {
                        label: function (context) {
                            const total = values.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return `${context.label}: ${context.parsed} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

// Helper function to create gradient
function createGradient(ctx, colors) {
    if (!ctx || !ctx.canvas) return colors[0];

    const gradient = ctx.canvas.getContext('2d').createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, colors[0]);
    gradient.addColorStop(1, colors[1]);
    return gradient;
}

// Export for use in dashboard.js
window.chartHelpers = {
    initializeCharts,
    updatePostsActivityChart,
    updateHobbiesChart,
    updateCountryDistributionChart
};

/**
 * Dashboard Charts Handler
 * AFB Mangaan Attendance System
 */

(function() {
    'use strict';

    // Chart instances storage
    const charts = {};

    // Initialize dashboard charts
    function initDashboardCharts() {
        initAttendanceTrendChart();
        initCategoryDistributionChart();
        initEventTypeChart();
        initRetentionChart();
    }

    // Attendance Trend Chart
    function initAttendanceTrendChart() {
        const ctx = document.getElementById('attendanceTrendChart');
        if (!ctx) return;

        // Fetch data from API
        fetch('/afb_mangaan_php/api/dashboard_stats.php?type=trends')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.trends) {
                    renderTrendChart(ctx, data.trends);
                }
            })
            .catch(error => console.error('Trend chart error:', error));
    }

    function renderTrendChart(ctx, trends) {
        const labels = trends.map(t => t.month);
        const attendance = trends.map(t => parseInt(t.attendance) || 0);
        const events = trends.map(t => parseInt(t.events) || 0);

        charts.trend = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Attendance',
                    data: attendance,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }, {
                    label: 'Events',
                    data: events,
                    borderColor: '#22c55e',
                    backgroundColor: 'transparent',
                    borderDash: [5, 5],
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 15
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Category Distribution Chart
    function initCategoryDistributionChart() {
        const ctx = document.getElementById('categoryDistributionChart');
        if (!ctx) return;

        fetch('/afb_mangaan_php/api/dashboard_stats.php?type=categories')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.categories) {
                    renderCategoryChart(ctx, data.categories);
                }
            })
            .catch(error => console.error('Category chart error:', error));
    }

    function renderCategoryChart(ctx, categories) {
        const labels = categories.map(c => c.category);
        const values = categories.map(c => parseInt(c.count) || 0);

        const colors = ['#6366f1', '#22c55e', '#f59e0b', '#ef4444'];

        charts.category = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 15
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }

    // Event Type Distribution Chart
    function initEventTypeChart() {
        const ctx = document.getElementById('eventTypeChart');
        if (!ctx) return;

        fetch('/afb_mangaan_php/api/dashboard_stats.php?type=event_types')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.event_types) {
                    renderEventTypeChart(ctx, data.event_types);
                }
            })
            .catch(error => console.error('Event type chart error:', error));
    }

    function renderEventTypeChart(ctx, eventTypes) {
        const labels = eventTypes.map(e => e.type);
        const values = eventTypes.map(e => parseInt(e.attendance_count) || 0);

        charts.eventType = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Attendance',
                    data: values,
                    backgroundColor: '#6366f1',
                    borderRadius: 6,
                    barThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Member Retention Chart
    function initRetentionChart() {
        const ctx = document.getElementById('retentionChart');
        if (!ctx) return;

        fetch('/afb_mangaan_php/api/dashboard_stats.php?type=retention')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderRetentionChart(ctx, data);
                }
            })
            .catch(error => console.error('Retention chart error:', error));
    }

    function renderRetentionChart(ctx, data) {
        const consistent = data.consistent_count || 0;
        const atRisk = data.at_risk_count || 0;
        const total = consistent + atRisk;

        charts.retention = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Consistent', 'At Risk'],
                datasets: [{
                    data: [consistent, atRisk],
                    backgroundColor: ['#22c55e', '#ef4444'],
                    borderRadius: 8,
                    barThickness: 60
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const percentage = total > 0 ? 
                                    Math.round((context.raw / total) * 100) : 0;
                                return `${context.raw} members (${percentage}%)`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Update charts when theme changes
    function updateChartTheme() {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.05)';
        const textColor = isDark ? '#94a3b8' : '#64748b';

        Object.values(charts).forEach(chart => {
            if (chart && chart.options) {
                // Update grid colors
                if (chart.options.scales) {
                    if (chart.options.scales.x && chart.options.scales.x.grid) {
                        chart.options.scales.x.grid.color = gridColor;
                    }
                    if (chart.options.scales.y && chart.options.scales.y.grid) {
                        chart.options.scales.y.grid.color = gridColor;
                    }
                    
                    // Update tick colors
                    if (chart.options.scales.x && chart.options.scales.x.ticks) {
                        chart.options.scales.x.ticks.color = textColor;
                    }
                    if (chart.options.scales.y && chart.options.scales.y.ticks) {
                        chart.options.scales.y.ticks.color = textColor;
                    }
                }
                
                // Update legend colors
                if (chart.options.plugins && chart.options.plugins.legend && chart.options.plugins.legend.labels) {
                    chart.options.plugins.legend.labels.color = textColor;
                }
                
                chart.update();
            }
        });
    }

    // Listen for theme changes
    window.addEventListener('themechange', updateChartTheme);

    // Export chart data
    function exportChartData(chartName) {
        const chart = charts[chartName];
        if (!chart) return;

        const data = {
            labels: chart.data.labels,
            datasets: chart.data.datasets.map(ds => ({
                label: ds.label,
                data: ds.data
            }))
        };

        // Create download
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${chartName}_data.json`;
        a.click();
        URL.revokeObjectURL(url);
    }

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        // Check if we're on the dashboard page
        if (document.getElementById('attendanceTrendChart') || 
            document.getElementById('categoryDistributionChart')) {
            initDashboardCharts();
        }
    });

    // Expose API
    window.DashboardCharts = {
        init: initDashboardCharts,
        updateTheme: updateChartTheme,
        export: exportChartData,
        instances: charts
    };
})();

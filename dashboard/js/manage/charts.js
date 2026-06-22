let networkChart, cpuChart, memoryChart, diskChart;
let statsInterval;

async function loadServerStats(type) {
    try {
        const response = await fetch(`/api/servers/usage?id=${serverId}&type=${type}`, { credentials: 'include' });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const result = await response.json();

        if (!result.success) {
            console.error('Error loading stats:', result.message);
            return;
        }

        _statsFailCount = 0;
        updateChart(type, result.data);
    } catch (error) {
        _statsFailCount++;
        console.warn(`Stats fetch error (${_statsFailCount}/${_STATS_MAX_FAILS}):`, error.message);
        if (_statsFailCount >= _STATS_MAX_FAILS) {
            _stopStatsPolling();
        }
    }
}

function updateChart(type, data) {
    const items = data.items || [];

    if (type === 'network') {
        const labels = items.map(item => new Date(item.time).toLocaleTimeString());
        const readData = items.map(item => item.derivative?.read_kb || 0);
        const writeData = items.map(item => item.derivative?.write_kb || 0);

        if (networkChart) networkChart.destroy();

        const ctx = document.getElementById('networkChart').getContext('2d');
        networkChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Lectura (KB/s)',
                        data: readData,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Escritura (KB/s)',
                        data: writeData,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: { color: '#fff' }
                    }
                },
                scales: {
                    y: {
                        ticks: { color: '#fff' },
                        grid: { color: 'rgba(255,255,255,0.1)' }
                    },
                    x: {
                        ticks: { color: '#fff', maxRotation: 45, minRotation: 45 },
                        grid: { color: 'rgba(255,255,255,0.1)' }
                    }
                }
            }
        });
    }

    if (type === 'cpu') {
        const labels = items.map(item => new Date(item.time).toLocaleTimeString());
        const cpuData = items.map(item => item.load_average || 0);

        if (cpuChart) cpuChart.destroy();

        const ctx = document.getElementById('cpuChart').getContext('2d');
        cpuChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Carga CPU (%)',
                    data: cpuData,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: '#fff' } }
                },
                scales: {
                    y: {
                        ticks: { color: '#fff' },
                        grid: { color: 'rgba(255,255,255,0.1)' }
                    },
                    x: {
                        ticks: { color: '#fff', maxRotation: 45, minRotation: 45 },
                        grid: { color: 'rgba(255,255,255,0.1)' }
                    }
                }
            }
        });
    }

    if (type === 'memory') {
        const labels = items.map(item => new Date(item.time).toLocaleTimeString());
        const memData = items.map(item => item.memory || 0);

        if (memoryChart) memoryChart.destroy();

        const ctx = document.getElementById('memoryChart').getContext('2d');
        memoryChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Uso de Memoria (MB)',
                    data: memData,
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: '#fff' } }
                },
                scales: {
                    y: {
                        ticks: { color: '#fff' },
                        grid: { color: 'rgba(255,255,255,0.1)' }
                    },
                    x: {
                        ticks: { color: '#fff', maxRotation: 45, minRotation: 45 },
                        grid: { color: 'rgba(255,255,255,0.1)' }
                    }
                }
            }
        });
    }

    if (type === 'disks') {
        const labels = items.map(item => new Date(item.time).toLocaleTimeString());

        const sample = items.find(i => i.derivative) || {};
        const d = sample.derivative || {};
        const hasIo = (d.read_kb !== undefined || d.write_kb !== undefined || d.read !== undefined || d.write !== undefined);

        if (diskChart) diskChart.destroy();
        const ctx = document.getElementById('diskChart').getContext('2d');

        if (hasIo) {
            const readData = items.map(item => {
                const dev = item.derivative || {};
                return dev.read_kb || dev.read || 0;
            });
            const writeData = items.map(item => {
                const dev = item.derivative || {};
                return dev.write_kb || dev.write || 0;
            });

            diskChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Lectura (KB/s)',
                            data: readData,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Escritura (KB/s)',
                            data: writeData,
                            borderColor: '#ec4899',
                            backgroundColor: 'rgba(236, 72, 153, 0.1)',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: { labels: { color: '#fff' } },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: {
                            ticks: { color: '#fff' },
                            grid: { color: 'rgba(255,255,255,0.1)' }
                        },
                        x: {
                            ticks: { color: '#fff', maxRotation: 45, minRotation: 45 },
                            grid: { color: 'rgba(255,255,255,0.1)' }
                        }
                    }
                }
            });

        } else {
            const diskData = items.map(item => {
                let usedKB = 0;
                if (item.used_kb !== undefined) usedKB = parseFloat(item.used_kb);
                else if (item.used !== undefined) usedKB = parseFloat(item.used);
                else if (item.disk_used !== undefined) usedKB = parseFloat(item.disk_used);
                else if (item.usage !== undefined) usedKB = parseFloat(item.usage);
                return (usedKB / 1024 / 1024).toFixed(2);
            });

            diskChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: LANG_MAN.stats_disk_usage,
                        data: diskData,
                        borderColor: '#ec4899',
                        backgroundColor: 'rgba(236, 72, 153, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { labels: { color: '#fff' } }
                    },
                    scales: {
                        y: {
                            ticks: { color: '#fff' },
                            grid: { color: 'rgba(255,255,255,0.1)' }
                        },
                        x: {
                            ticks: { color: '#fff', maxRotation: 45, minRotation: 45 },
                            grid: { color: 'rgba(255,255,255,0.1)' }
                        }
                    }
                }
            });
        }
    }
}

function switchStatsTab(type) {
    document.querySelectorAll('.stats-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelector(`[data-tab="${type}"]`).classList.add('active');

    document.querySelectorAll('.stats-panel').forEach(panel => {
        panel.classList.remove('active');
    });
    document.getElementById(`${type}-stats`).classList.add('active');

    loadServerStats(type);
}

function _stopStatsPolling() {
    if (statsInterval) {
        clearInterval(statsInterval);
        statsInterval = null;
    }
    console.warn('Stats polling stopped after repeated errors. Reload the page to retry.');
}

function startStatsRefresh() {
    _statsFailCount = 0;
    const activeTab = document.querySelector('.stats-tab.active')?.dataset.tab || 'network';
    loadServerStats(activeTab);

    statsInterval = setInterval(() => {
        const currentTab = document.querySelector('.stats-tab.active')?.dataset.tab || 'network';
        loadServerStats(currentTab);
        updateResourceMeters();
    }, 60000);
}

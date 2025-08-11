<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlertPoint Admin Dashboard</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Firebase SDK -->
    <script type="module">
        import { initializeApp } from 'https://www.gstatic.com/firebasejs/9.22.0/firebase-app.js';
        import { getFirestore, collection, getDocs, query, orderBy, limit, onSnapshot, doc } from 'https://www.gstatic.com/firebasejs/9.22.0/firebase-firestore.js';
        
        // Your Firebase config - Replace with your actual config
          // Your Firebase config - Replace with your actual config
        //  const firebaseConfig = {
        // apiKey: "AIzaSyC4pz2_IBYGkAbIqLFqwyNsrbv-MOCxH3s",
        // authDomain: "alertpointprojectver1.firebaseapp.com",
        // projectId: "alertpointprojectver1",
        // storageBucket: "alertpointprojectver1.firebasestorage.app",
        // messagingSenderId: "1067658987404",
        // appId: "1:1067658987404:web:856330c149f42c245c38a9"
        // };

        const firebaseConfig = {
        apiKey: "AIzaSyDQ_6pFoQDLbbNPT5vFO8tNJK6XIhgOmDY",
        authDomain: "alertpointsystemversion2.firebaseapp.com",
        projectId: "alertpointsystemversion2",
        storageBucket: "alertpointsystemversion2.firebasestorage.app",
        messagingSenderId: "968995359954",
        appId: "1:968995359954:web:5ff8440e84cde97a12f10b",
        measurementId: "G-DRHHJZ2WLC"
        };

        // Initialize Firebase
        const app = initializeApp(firebaseConfig);
        const db = getFirestore(app);
        
        // Global variables for charts and current values
        let temperatureTrendChart;
        let currentTemp = 0;
        let currentHeatIndex = 0;
        let dailyStats = { min: 0, max: 0, avg: 0 };
        let isConnected = false;

        // Get current date in MM-DD-YYYY format
        function getCurrentDate() {
            const now = new Date();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const year = now.getFullYear();
            return `${month}-${day}-${year}`;
        }

        // Convert time string to 24-hour format for sorting
        function convertTo24Hour(timeStr) {
            const [time, modifier] = timeStr.split(' ');
            let [hours, minutes] = time.split(':');
            if (hours === '12') {
                hours = '00';
            }
            if (modifier === 'PM') {
                hours = parseInt(hours, 10) + 12;
            }
            return `${String(hours).padStart(2, '0')}:${minutes}`;
        }

        // Update gauge progress
        function updateGaugeProgress(elementId, value, maxValue, minValue = 0) {
            const circle = document.getElementById(elementId);
            if (!circle) return;
            
            const circumference = 534; // 2 * Math.PI * 85
            const percentage = Math.min(Math.max((value - minValue) / (maxValue - minValue), 0), 1);
            const offset = circumference - (percentage * circumference);
            
            circle.style.strokeDashoffset = offset;
        }

        // Get temperature status
        function getTemperatureStatus(temp) {
            if (temp < 20) return { status: 'Cold', color: 'text-blue-600' };
            if (temp < 25) return { status: 'Cool', color: 'text-green-600' };
            if (temp < 30) return { status: 'Normal', color: 'text-green-600' };
            if (temp < 35) return { status: 'Warm', color: 'text-orange-600' };
            return { status: 'Hot', color: 'text-red-600' };
        }

        // Get heat index status
        function getHeatIndexStatus(heatIndex) {
            if (heatIndex < 27) return { status: 'Safe', color: 'text-green-600' };
            if (heatIndex < 32) return { status: 'Caution', color: 'text-yellow-600' };
            if (heatIndex < 40) return { status: 'Extreme Caution', color: 'text-orange-600' };
            if (heatIndex < 54) return { status: 'Danger', color: 'text-red-600' };
            return { status: 'Extreme Danger', color: 'text-red-800' };
        }

        // Calculate daily statistics
        function calculateDailyStats(readings) {
            if (readings.length === 0) return { min: 0, max: 0, avg: 0 };
            
            const temperatures = readings.map(r => r.temperature);
            const min = Math.min(...temperatures);
            const max = Math.max(...temperatures);
            const avg = temperatures.reduce((sum, temp) => sum + temp, 0) / temperatures.length;
            
            return { min, max, avg };
        }

        // Update temperature display
        function updateTemperatureDisplay(temp, heatIndex, stats) {
            // Update main gauge value
            const tempValueEl = document.getElementById('tempValue');
            if (tempValueEl) {
                tempValueEl.textContent = `${temp.toFixed(1)}°C`;
            }

            // Update heat index gauge value
            const heatIndexValueEl = document.getElementById('heatIndexValue');
            if (heatIndexValueEl) {
                heatIndexValueEl.textContent = `${heatIndex.toFixed(1)}°C`;
            }

            // Update status for temperature
            const tempStatus = getTemperatureStatus(temp);
            const tempStatusEl = document.getElementById('tempStatus');
            if (tempStatusEl) {
                tempStatusEl.textContent = tempStatus.status;
                tempStatusEl.className = `text-xs mt-1 font-medium ${tempStatus.color}`;
            }

            // Update status for heat index
            const heatIndexStatus = getHeatIndexStatus(heatIndex);
            const heatIndexStatusEl = document.getElementById('heatIndexStatus');
            if (heatIndexStatusEl) {
                heatIndexStatusEl.textContent = heatIndexStatus.status;
                heatIndexStatusEl.className = `text-xs mt-1 font-medium ${heatIndexStatus.color}`;
            }

            // Update daily stats
            const minTempEl = document.getElementById('minTemp');
            const maxTempEl = document.getElementById('maxTemp');
            const avgTempEl = document.getElementById('avgTemp');
            
            if (minTempEl) minTempEl.textContent = `${stats.min.toFixed(1)}°C`;
            if (maxTempEl) maxTempEl.textContent = `${stats.max.toFixed(1)}°C`;
            if (avgTempEl) avgTempEl.textContent = `${stats.avg.toFixed(1)}°C`;

            // Update stat cards
            const currentTempEl = document.getElementById('current-temperature');
            const heatIndexCardEl = document.getElementById('heat-index');
            
            if (currentTempEl) currentTempEl.textContent = `${temp.toFixed(1)}°C`;
            if (heatIndexCardEl) heatIndexCardEl.textContent = `${heatIndex.toFixed(1)}°C`;

            // Update gauge progress (max 60°C as requested)
            updateGaugeProgress('tempProgress', temp, 60, 0);
            updateGaugeProgress('heatIndexProgress', heatIndex, 60, 0);

            // Update connection status
            const statusEl = document.getElementById('connectionStatus');
            if (statusEl) {
                statusEl.textContent = isConnected ? 'Connected to Firebase' : 'Connecting...';
                statusEl.className = isConnected ? 'text-green-600 text-sm' : 'text-orange-600 text-sm';
            }
        }

        // Fetch temperature data from Firebase
        async function fetchTemperatureData() {
            try {
                const currentDate = getCurrentDate();
                console.log('Fetching data for date:', currentDate);

                // Reference to the readings collection for today
                const readingsRef = collection(db, 'TemperatureHumidity_Ver2', currentDate, 'Readings');
                const q = query(readingsRef, orderBy('ID', 'desc'));

                // Get all readings for the day
                const querySnapshot = await getDocs(q);
                const readings = [];

                querySnapshot.forEach((doc) => {
                    const data = doc.data();
                    if (data.Temperature_C && data.Time && data.HeatIndex_C) {
                        readings.push({
                            id: data.ID,
                            temperature: parseFloat(data.Temperature_C),
                            heatIndex: parseFloat(data.HeatIndex_C),
                            time: data.Time,
                            time24: convertTo24Hour(data.Time)
                        });
                    }
                });

                console.log('Fetched readings:', readings.length);

                if (readings.length > 0) {
                    isConnected = true;
                    
                    // Sort by time (oldest first for chart)
                    readings.sort((a, b) => a.time24.localeCompare(b.time24));

                    // Update current values with latest reading
                    const latestReading = readings[readings.length - 1];
                    currentTemp = latestReading.temperature;
                    currentHeatIndex = latestReading.heatIndex;
                    
                    // Calculate daily statistics
                    dailyStats = calculateDailyStats(readings);
                    
                    // Update displays
                    updateTemperatureDisplay(currentTemp, currentHeatIndex, dailyStats);

                    // Update chart with filtered data points
                    updateTemperatureChart(readings);
                } else {
                    console.log('No temperature data found for today');
                    isConnected = false;
                }

            } catch (error) {
                console.error('Error fetching temperature data:', error);
                isConnected = false;
                updateTemperatureDisplay(0, 0, { min: 0, max: 0, avg: 0 });
            }
        }

        // Filter data points for chart to avoid overcrowding
        function filterDataForChart(readings) {
            if (readings.length <= 20) {
                return readings; // Show all if less than 20 points
            }

            const filtered = [];
            const interval = Math.floor(readings.length / 15); // Target ~15 points

            for (let i = 0; i < readings.length; i += interval) {
                filtered.push(readings[i]);
            }

            // Always include the last reading
            if (filtered[filtered.length - 1].id !== readings[readings.length - 1].id) {
                filtered.push(readings[readings.length - 1]);
            }

            return filtered;
        }

        // Update temperature trend chart
        function updateTemperatureChart(readings) {
            const filteredData = filterDataForChart(readings);
            
            if (temperatureTrendChart) {
                temperatureTrendChart.data.labels = filteredData.map(r => r.time);
                temperatureTrendChart.data.datasets[0].data = filteredData.map(r => r.temperature);
                temperatureTrendChart.data.datasets[1].data = filteredData.map(r => r.heatIndex);
                temperatureTrendChart.update('none'); // No animation for real-time updates
            }
        }

        // Create temperature trend chart
        function createTemperatureChart() {
            const ctx = document.getElementById('temperatureTrendChart');
            if (!ctx) return;

            temperatureTrendChart = new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Temperature (°C)',
                        data: [],
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.15)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 6,           // Increased from 4
                        pointHoverRadius: 10,     // Increased from 8
                        borderWidth: 4,
                        pointBackgroundColor: '#f59e0b',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 3,      // Increased from 2
                        pointHoverBackgroundColor: '#d97706',
                        pointHoverBorderColor: '#ffffff',
                        pointHoverBorderWidth: 4  // Increased from 3
                    }, {
                        label: 'Heat Index (°C)',
                        data: [],
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.15)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 6,           // Increased from 4
                        pointHoverRadius: 10,     // Increased from 8
                        borderWidth: 4,
                        borderDash: [8, 4],
                        pointBackgroundColor: '#ef4444',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 3,      // Increased from 2
                        pointHoverBackgroundColor: '#dc2626',
                        pointHoverBorderColor: '#ffffff',
                        pointHoverBorderWidth: 4  // Increased from 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { 
                            display: true,
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: { size: 14, weight: 'bold' },
                                color: '#374151'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(17, 24, 39, 0.95)',
                            titleColor: '#f9fafb',
                            bodyColor: '#f9fafb',
                            borderColor: '#6b7280',
                            borderWidth: 1,
                            cornerRadius: 8,
                            displayColors: true,
                            callbacks: {
                                title: function(context) {
                                    return `Time: ${context[0].label}`;
                                },
                                label: function(context) {
                                    return `${context.dataset.label}: ${context.parsed.y.toFixed(1)}°C`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            title: { 
                                display: true, 
                                text: '',
                                color: '#64748b',
                                font: { weight: 'bold', size: 14 }
                            },
                            grid: { 
                                color: 'rgba(203, 213, 225, 0.5)',
                                drawBorder: false,
                                lineWidth: 1
                            },
                            ticks: { 
                                color: '#64748b',
                                font: { size: 12, weight: '500' },
                                callback: function(value) {
                                    return value + '°C';
                                }
                            }
                        },
                        x: {
                            grid: { 
                                color: 'rgba(203, 213, 225, 0.3)',
                                drawBorder: false,
                                lineWidth: 1
                            },
                            ticks: { 
                                color: '#64748b',
                                maxTicksLimit: 10,
                                font: { size: 11, weight: '500' }
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    layout: {
                        padding: 15
                    },
                    elements: {
                        line: {
                            capBezierPoints: false
                        }
                    }
                }
            });
        }

        // Set up real-time listener
        function setupRealtimeListener() {
            const currentDate = getCurrentDate();
            const readingsRef = collection(db, 'TemperatureHumidity_Ver2', currentDate, 'Readings');
            
            // Listen for real-time updates
            onSnapshot(readingsRef, (snapshot) => {
                console.log('Real-time update received');
                fetchTemperatureData(); // Refresh data when changes occur
            });
        }

        // Time display
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleString('en-PH', {
                timeZone: 'Asia/Manila',
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            const timeEl = document.getElementById('current-time');
            if (timeEl) {
                timeEl.textContent = timeString;
            }
        }

        // Initialize dashboard
        async function initDashboard() {
            console.log('Initializing dashboard...');
            
            // Start time updates
            setInterval(updateTime, 1000);
            updateTime();

            // Initialize temperature chart
            createTemperatureChart();

            // Initial data fetch
            await fetchTemperatureData();

            // Set up real-time listener
            setupRealtimeListener();

            // Refresh data every 30 seconds as backup
            setInterval(fetchTemperatureData, 30000);

            // Animate initial gauge
            setTimeout(() => {
                const tempProgress = document.getElementById('tempProgress');
                if (tempProgress && currentTemp > 0) {
                    updateGaugeProgress('tempProgress', currentTemp, 60, 0);
                }
            }, 1000);
        }

        // Start when DOM is ready
        document.addEventListener('DOMContentLoaded', initDashboard);
        
        // Make functions globally available for debugging
        window.fetchTemperatureData = fetchTemperatureData;
        window.getCurrentDate = getCurrentDate;
    </script>
    
    <style>
        .stat-card {
            background: white;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            border-radius: 12px;
            transition: all 0.3s ease;
            border: 1px solid rgba(229, 231, 235, 0.8);
            position: relative;
            overflow: hidden;
        }

        /* Remove the ::before pseudo-element */
        .stat-card:hover {
            transform: translateY(-4px) scale(1.01);
            box-shadow: 0 8px 16px rgba(0,0,0,0.12);
        }
        
        
        .stat-card:hover .stat-icon {
            transform: scale(1.05) rotate(2deg);
        }
        
        
        .hover-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
            
        
       .hover-card:hover {
            transform: translateY(-3px) scale(1.01);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }

        .metric-card {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
            position: relative;
            border: 1px solid rgba(229, 231, 235, 0.6);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }
        
        .metric-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
            /* border-color: rgba(59, 130, 246, 0.3); */
        }
        
        .metric-card:hover::before {
            opacity: 1;
        }
        
        .status-indicator {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .modern-gauge {
            position: relative;
            width: 220px;
            height: 220px;
            margin: 0 auto;
        }
        
        .gauge-progress {
            width: 100%;
            height: 100%;
            transform: rotate(0deg);
        }
        
        .gauge-inner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
        
        .gauge-value {
            font-size: 1.8rem;
            font-weight: bold;
            line-height: 1;
        }
        
        .gauge-label {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }
        
        /* .gauge-icon {
            position: absolute;
            top: 30px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 1.5rem;
            color: #f59e0b;
            z-index: 10;
            transition: transform 0.3s ease;
        } */


        .gauge-icon {
            position: absolute;
            top: 8%;
            right: 5%;
            width: clamp(36px, 7vw, 48px);         /* Was 28-36px → now 36-48px */
            height: clamp(36px, 7vw, 48px); 
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: clamp(1rem, 3vw, 1.25rem);
            color: white;
            z-index: 10;
        }

        .temp-gradient { background: linear-gradient(135deg, #ff6b6b, #ffa726); }

        
        .chart-container {
            height: 400px;
            width: 100%;
            padding: 0.5rem;
        }
        
       .ai-banner {
            border-radius: 12px;
            border: 1px solid rgba(251, 191, 36, 0.3);
        }
        
      
        
        #tempProgress, #heatIndexProgress {
            stroke-dasharray: 534;
            stroke-dashoffset: 534;
            transition: stroke-dashoffset 1.5s cubic-bezier(0.4, 0, 0.2, 1);
            transform: rotate(270deg);
            transform-origin: 100px 100px;
        }
        
        .connection-status {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            border: 1px solid rgba(229, 231, 235, 0.8);
            transition: all 0.3s ease;
        }
        
        .connection-status:hover {
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        /* Enhanced daily stats cards */
        /* .stats-grid .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);

        } */
        
        .stats-grid .stat-card:nth-child(1):hover {
            box-shadow: 0 8px 16px rgba(0,0,0,0.12);
            transition: box-shadow 0.3s ease, transform 0.3s ease; /* Smooth effect */

        }

        .stats-grid .stat-card:nth-child(2):hover {
            box-shadow: 0 8px 16px rgba(0,0,0,0.12);
            transition: box-shadow 0.3s ease, transform 0.3s ease; /* Smooth effect */

        }

        .stats-grid .stat-card:nth-child(3):hover {
            box-shadow: 0 8px 16px rgba(0,0,0,0.12);
            transition: box-shadow 0.3s ease, transform 0.3s ease; /* Smooth effect */

        }

        .chart-enhanced:hover {
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        /* Chart container enhancements */
        .chart-enhanced {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid rgba(229, 231, 235, 0.6);
        }
        

        /* Add this to your existing styles */
        #temperatureTrendChart {
            filter: contrast(1.1) saturate(1.1);
        }
    </style>
</head>

<body class="bg-gray-50">
    <!-- Connection Status -->
    <div class="connection-status">
        <div id="connectionStatus" class="text-orange-600 text-sm font-medium">Connecting...</div>
    </div>

    <main class="max-w-8xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-2 sm:px-0">
            
            <!-- Current Time Display -->
            <div class="mb-6 text-center">
                <div id="current-time" class="text-lg font-semibold text-gray-700"></div>
            </div>

            <!-- Dashboard Tab -->
            <div id="dashboard-content" class="tab-content active">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
                    <div class="stat-card hover-card">
                        <div class="flex items-center justify-between relative z-10">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Current Water Level</p>
                                <p class="text-2xl font-bold text-blue-600" id="current-water-level">0.20m</p>
                                <p class="text-sm text-gray-500">ANKLE LEVEL</p>
                            </div>
                            <i class="fas fa-water text-3xl text-blue-500 stat-icon"></i>
                        </div>
                    </div>

                    <div class="stat-card hover-card">
                        <div class="flex items-center justify-between relative z-10">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Temperature</p>
                                <p class="text-2xl font-bold text-orange-600" id="current-temperature">Loading...</p>
                                <p class="text-sm text-gray-500">Humidity: <span id="current-humidity">Loading...</span></p>
                            </div>
                            <i class="fas fa-thermometer-half text-3xl text-orange-500 stat-icon"></i>
                        </div>
                    </div>

                    <div class="stat-card hover-card">
                        <div class="flex items-center justify-between relative z-10">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Heat Index</p>
                                <p class="text-2xl font-bold text-red-600" id="heat-index">Loading...</p>
                                <p class="text-sm text-yellow-600" id="heat-index-status">Calculating...</p>
                            </div>
                            <i class="fas fa-temperature-high text-3xl text-red-500 stat-icon"></i>
                        </div>
                    </div>

                    <div class="stat-card hover-card">
                        <div class="flex items-center justify-between relative z-10">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Active Alerts</p>
                                <p class="text-2xl font-bold text-red-600">0</p>
                                <p class="text-sm text-green-500">All Systems Normal</p>
                            </div>
                            <i class="fas fa-bell text-3xl text-green-500 stat-icon"></i>
                        </div>
                    </div>

                    <div class="stat-card hover-card">
                        <div class="flex items-center justify-between relative z-10">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Active Mobile Users</p>
                                <p class="text-2xl font-bold text-green-600">12</p>
                                <p class="text-sm text-gray-500">Using AlertPoint App</p>
                            </div>
                            <i class="fas fa-mobile-alt text-3xl text-green-500 stat-icon"></i>
                        </div>
                    </div>
                </div>

                <div class="container mx-auto px-1 py-1">
                    <!-- Charts Row -->
                    <div class="charts-row">
                        <!-- Temperature Section -->
                        <div class="mb-8">
                            <h2 class="text-2xl font-bold text-gray-800 mb-4">Temperature Monitoring</h2>
                            
                            <!-- Daily Statistics Cards -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6 stats-grid">
                                <div class="stat-card hover-card text-center">
                                    <div class="relative z-10">
                                        <p class="text-sm font-medium text-gray-600">Today's Minimum</p>
                                        <p class="text-xl font-bold text-blue-600" id="minTemp">--°C</p>
                                    </div>
                                </div>
                                <div class="stat-card hover-card text-center">
                                    <div class="relative z-10">
                                        <p class="text-sm font-medium text-gray-600">Today's Maximum</p>
                                        <p class="text-xl font-bold text-red-600" id="maxTemp">--°C</p>
                                    </div>
                                </div>
                                <div class="stat-card hover-card text-center">
                                    <div class="relative z-10">
                                        <p class="text-sm font-medium text-gray-600">Daily Average</p>
                                        <p class="text-xl font-bold text-green-600" id="avgTemp">--°C</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Temperature Trend Chart - Full Width -->
                            <div class="metric-card hover-card chart-enhanced mb-6" style="padding: 1.5rem;">
                                <div class="relative z-10">
                                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Real-Time Temperature & Heat Index Trend (24h)</h3>
                                    <div class="chart-container">
                                        <canvas id="temperatureTrendChart"></canvas>
                                    </div>
                                </div>
                            </div>

                            <!-- Temperature and Heat Index Gauges - Below Chart -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Temperature Gauge -->
                                <div class="metric-card hover-card relative" style="padding: 1.5rem;">
                                    <div class="status-indicator bg-orange-500"></div>
                                    <div class="relative z-10">
                                        <div class="text-center mb-2">
                                            <h3 class="text-lg font-bold text-gray-800 mb-1">Temperature</h3>
                                        </div>
                                        
                                        <div class="modern-gauge">
                                            <div class="gauge-icon temp-gradient">
                                                <i class="fas fa-thermometer-half"></i>
                                            </div>
                                            <svg class="gauge-progress" viewBox="0 0 200 200">
                                                <circle cx="100" cy="100" r="85" fill="none" stroke="#e5e7eb" stroke-width="8"/>
                                                <circle id="tempProgress" cx="100" cy="100" r="85" fill="none" 
                                                        stroke="url(#tempGradient)" stroke-width="8" stroke-linecap="round"
                                                        stroke-dasharray="534" stroke-dashoffset="534"/>
                                                <defs>
                                                    <linearGradient id="tempGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                                        <stop offset="0%" style="stop-color:#ff6b6b"/>
                                                        <stop offset="100%" style="stop-color:#ffa726"/>
                                                    </linearGradient>
                                                </defs>
                                            </svg>
                                            <div class="gauge-inner">
                                                <div class="gauge-text">
                                                    <div id="tempValue" class="gauge-value text-orange-600">28°C</div>
                                                    <div class="gauge-label">Temperature</div>
                                                    <div id="tempStatus" class="text-xs mt-1 font-medium text-green-600">Normal</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Heat Index Gauge -->
                                <div class="metric-card hover-card relative" style="padding: 1.5rem;">
                                    <div class="status-indicator bg-red-500"></div>
                                    <div class="relative z-10">
                                        <div class="text-center mb-2">
                                            <h3 class="text-lg font-bold text-gray-800 mb-1">Heat Index</h3>
                                        </div>
                                        
                                        <div class="modern-gauge">
                                            <div class="gauge-icon temp-gradient">
                                                <i class="fas fa-temperature-high"></i>
                                            </div>
                                            <svg class="gauge-progress" viewBox="0 0 200 200">
                                                <circle cx="100" cy="100" r="85" fill="none" stroke="#e5e7eb" stroke-width="8"/>
                                                <circle id="heatIndexProgress" cx="100" cy="100" r="85" fill="none" 
                                                        stroke="url(#heatIndexGradient)" stroke-width="8" stroke-linecap="round"
                                                        stroke-dasharray="534" stroke-dashoffset="534"/>
                                                <defs>
                                                    <linearGradient id="heatIndexGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                                        <stop offset="0%" style="stop-color:#fbbf24"/>
                                                        <stop offset="50%" style="stop-color:#f59e0b"/>
                                                        <stop offset="100%" style="stop-color:#dc2626"/>
                                                    </linearGradient>
                                                </defs>
                                            </svg>
                                            <div class="gauge-inner">
                                                <div class="gauge-text">
                                                    <div id="heatIndexValue" class="gauge-value text-red-600">32°C</div>
                                                    <div class="gauge-label">Feels Like</div>
                                                    <div id="heatIndexStatus" class="text-xs mt-1 font-medium text-yellow-600">Caution</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- AI Alert for Temperature -->
                            <div class="ai-banner bg-orange-50 border-l-4 border-orange-400 p-4 mt-6">
                                <div class="flex items-start">
                                    <i class="fas fa-robot text-orange-400 text-lg mr-3 mt-1"></i>
                                    <div>
                                        <h3 class="text-sm font-medium text-orange-800">AI Temperature Insight</h3>
                                        <p class="text-sm text-orange-700">Temperature levels are within normal range. Consider ventilation if temperature exceeds 32°C for extended periods.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
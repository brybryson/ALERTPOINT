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
        
        const firebaseConfig = {
  apiKey: "AIzaSyBXGY330B2JkDTL-SvleUrUUIMpro-xQuU",
  authDomain: "alertpointsystemversion4.firebaseapp.com",
  projectId: "alertpointsystemversion4",
  storageBucket: "alertpointsystemversion4.firebasestorage.app",
  messagingSenderId: "674178733366",
  appId: "1:674178733366:web:367df7ba3c3530c4d8cf97",
  measurementId: "G-Z9D9PVBS6G"
};

        // Initialize Firebase
        const app = initializeApp(firebaseConfig);
        const db = getFirestore(app);
        
        // Global variables for charts and current values
        let temperatureTrendChart;
        let humidityTrendChart;
        let waterLevelTrendChart;
        let combinedEnvironmentalChart;
        let currentTemp = 0;
        let currentHeatIndex = 0;
        let currentHumidity = 0;
        let currentWaterLevel = 0;
        let currentWaterLevelLabel = '';
        let currentAlarmStatus = false;
        let dailyTempStats = { min: 0, max: 0, avg: 0 };
        let dailyHumidityStats = { min: 0, max: 0, avg: 0 };
        let dailyWaterStats = { min: 0, max: 0, avg: 0 };
        let isConnected = false;

        let lastKnownWaterLevel = 0;
        let lastKnownWaterLabel = 'Very Low';
        let lastUpdateTime = null;
        let isCreekUnitConnected = false;

        let connectionStatusTimeout;
        let lastConnectionStatus = '';

        let currentSensorStatus = 'Unknown';
        let firstReadingTime = null;
        let latestReadingTime = null;

        function calculateSensorUptime() {
            if (!firstReadingTime || !latestReadingTime) {
                return 'Loading...';
            }
            
            const first = new Date(`${getCurrentDate()} ${convertTo24Hour(firstReadingTime)}`);
            const latest = new Date(`${getCurrentDate()} ${convertTo24Hour(latestReadingTime)}`);
            const now = new Date();
            
            // Calculate total time span for the day (from first reading to now)
            const totalMinutes = (now - first) / (1000 * 60);
            
            // Calculate active time span (from first to latest reading)
            const activeMinutes = (latest - first) / (1000 * 60);
            
            if (totalMinutes <= 0) return 'Loading...';
            
            const uptimePercentage = Math.min((activeMinutes / totalMinutes) * 100, 100);
            
            if (uptimePercentage >= 99) return `Uptime: ${uptimePercentage.toFixed(1)}%`;
            if (uptimePercentage >= 95) return `Uptime: ${uptimePercentage.toFixed(1)}%`;
            if (uptimePercentage >= 90) return `Uptime: ${uptimePercentage.toFixed(1)}%`;
            return `Uptime: ${uptimePercentage.toFixed(1)}%`;
        }

        function updateSensorStatusDisplay(sensorStatus, uptimeText) {
            const statusEl = document.getElementById('sensor-health-status');
            const uptimeEl = document.getElementById('sensor-uptime-display');
            
            if (statusEl) {
                // Update status text and color based on sensor status
                statusEl.textContent = sensorStatus || 'Unknown';
                
                // Update color classes based on sensor status
                statusEl.className = 'text-lg font-bold ' + (
                    sensorStatus === 'OK' ? 'text-green-600' :
                    sensorStatus === 'WARNING' ? 'text-yellow-600' :
                    sensorStatus === 'ERROR' ? 'text-red-600' :
                    'text-gray-600'
                );
            }
            
            if (uptimeEl) {
                uptimeEl.textContent = uptimeText || 'Loading...';
            }
        }

        function updateConnectionStatus(status, isConnecting = false) {
            const statusEl = document.getElementById('connectionStatus');
            const containerEl = document.querySelector('.connection-status');
            
            if (!statusEl || !containerEl) return;
            
            // Clear any existing timeout
            if (connectionStatusTimeout) {
                clearTimeout(connectionStatusTimeout);
            }
            
            // Update status text and styling
            if (isConnecting) {
                statusEl.textContent = 'Connecting to Firebase...';
                statusEl.className = 'text-orange-600 text-sm font-medium';
                containerEl.classList.remove('minimized');
            } else if (status === 'connected') {
                statusEl.textContent = 'Connected to Firebase';
                statusEl.className = 'text-green-600 text-sm font-medium';
                containerEl.classList.remove('minimized');
                
                // Auto-minimize after 5 seconds
                connectionStatusTimeout = setTimeout(() => {
                    containerEl.classList.add('minimized');
                }, 5000);
            } else {
                statusEl.textContent = status;
                statusEl.className = 'text-red-600 text-sm font-medium';
                containerEl.classList.remove('minimized');
            }
            
            lastConnectionStatus = status;
        }

        // Mini chart creation and updates
        let miniTempChart, miniHumidityChart;



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

        // Convert water level string to numeric value
        function parseWaterLevel(levelStr) {
            if (!levelStr || levelStr === undefined || levelStr === null) {
                return lastKnownWaterLevel; // Return last known value
            }
            const parsed = parseFloat(levelStr.replace('m', ''));
            return isNaN(parsed) ? lastKnownWaterLevel : parsed;
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

        // Get humidity status
        function getHumidityStatus(humidity) {
            if (humidity < 30) return { status: 'Very Dry', color: 'text-orange-600' };
            if (humidity < 40) return { status: 'Dry', color: 'text-yellow-600' };
            if (humidity < 60) return { status: 'Comfortable', color: 'text-green-600' };
            if (humidity < 70) return { status: 'Humid', color: 'text-yellow-600' };
            return { status: 'Very Humid', color: 'text-red-600' };
        }

        // Get water level status
        // Updated JavaScript function to match the new measurements
        function getWaterLevelStatus(level, label) {
            if (level < 0.10) return { status: 'Very Low', color: 'text-green-600', bgColor: 'bg-green-100' };
            if (level < 0.48) return { status: 'Ankle Level', color: 'text-blue-600', bgColor: 'bg-blue-100' };
            if (level < 0.91) return { status: 'Knee Level', color: 'text-yellow-600', bgColor: 'bg-yellow-100' };
            if (level < 1.12) return { status: 'Waist Level', color: 'text-orange-600', bgColor: 'bg-orange-100' };
            if (level < 1.40) return { status: 'Chest Level', color: 'text-red-600', bgColor: 'bg-red-100' };
            return { status: 'Critical Level', color: 'text-red-800', bgColor: 'bg-red-200' };
        }

        // Calculate daily statistics
        function calculateDailyStats(readings, valueKey) {
    if (!readings || readings.length === 0) {
        return { min: 0, max: 0, avg: 0 };
    }
    
    const values = readings.map(r => {
        let val;
        if (valueKey === 'humidity') {
            val = r.humidity || r.Humidity_Percent || 0;
        } else if (valueKey === 'temperature') {
            val = r.temperature || r.Temperature_C || 0;
        } else if (valueKey === 'level') {
            val = r.level || 0;
        } else {
            val = r[valueKey] || 0;
        }
        return (isNaN(val) || val === undefined || val === null) ? 0 : val;
    }).filter(val => val !== null && val !== undefined && !isNaN(val));
    
    if (values.length === 0) {
        return { min: 0, max: 0, avg: 0 };
    }
    
    const min = Math.min(...values);
    const max = Math.max(...values);
    const avg = values.reduce((sum, val) => sum + val, 0) / values.length;
    
    return { min, max, avg };
}

        // Enhanced connection status check
        function checkCreekUnitConnection() {
            const now = new Date();
            if (lastUpdateTime) {
                const timeDiff = (now - lastUpdateTime) / 1000 / 60; // minutes
                isCreekUnitConnected = timeDiff <= 5; // Consider offline if no data for 5 minutes
            } else {
                isCreekUnitConnected = false;
            }
            return isCreekUnitConnected;
        }

        // Update display functions
        function updateTemperatureDisplay(temp, heatIndex, humidity, stats) {
            // Temperature gauge
            const tempValueEl = document.getElementById('tempValue');
            if (tempValueEl) tempValueEl.textContent = `${temp.toFixed(1)}°C`;

            const heatIndexValueEl = document.getElementById('heatIndexValue');
            if (heatIndexValueEl) heatIndexValueEl.textContent = `${heatIndex.toFixed(1)}°C`;

            const humidityValueEl = document.getElementById('humidityValue');
            if (humidityValueEl) humidityValueEl.textContent = `${humidity.toFixed(1)}%`;

            // Status updates
            const tempStatus = getTemperatureStatus(temp);
            const tempStatusEl = document.getElementById('tempStatus');
            if (tempStatusEl) {
                tempStatusEl.textContent = tempStatus.status;
                tempStatusEl.className = `text-xs mt-1 font-medium ${tempStatus.color}`;
            }

            const heatIndexStatus = getHeatIndexStatus(heatIndex);
            const heatIndexStatusEl = document.getElementById('heatIndexStatus');
            if (heatIndexStatusEl) {
                heatIndexStatusEl.textContent = heatIndexStatus.status;
                heatIndexStatusEl.className = `text-xs mt-1 font-medium ${heatIndexStatus.color}`;
            }

            const humidityStatus = getHumidityStatus(humidity);
            const humidityStatusEl = document.getElementById('humidityStatus');
            if (humidityStatusEl) {
                humidityStatusEl.textContent = humidityStatus.status;
                humidityStatusEl.className = `text-xs mt-1 font-medium ${humidityStatus.color}`;
            }

            // Daily stats
            const minTempEl = document.getElementById('minTemp');
            const maxTempEl = document.getElementById('maxTemp');
            const avgTempEl = document.getElementById('avgTemp');
            
            if (minTempEl) minTempEl.textContent = `${stats.min.toFixed(1)}°C`;
            if (maxTempEl) maxTempEl.textContent = `${stats.max.toFixed(1)}°C`;
            if (avgTempEl) avgTempEl.textContent = `${stats.avg.toFixed(1)}°C`;

            // Stat cards
            const currentTempEl = document.getElementById('current-temperature');
            const heatIndexCardEl = document.getElementById('heat-index');
            const currentHumidityEl = document.getElementById('current-humidity');
            
            if (currentTempEl) currentTempEl.textContent = `${temp.toFixed(1)}°C`;
            if (heatIndexCardEl) heatIndexCardEl.textContent = `${heatIndex.toFixed(1)}°C`;
            if (currentHumidityEl) currentHumidityEl.textContent = `${humidity.toFixed(1)}%`;

            // Gauge progress
            updateGaugeProgress('tempProgress', temp, 60, 0);
            updateGaugeProgress('heatIndexProgress', heatIndex, 60, 0);
            updateGaugeProgress('humidityProgress', humidity, 100, 0);
        }

        function updateWaterLevelDisplay(level, label, alarmStatus, stats) {
            // Validate and sanitize level
            const safeLevel = (isNaN(level) || level === undefined || level === null) ? lastKnownWaterLevel : level;
            const safeLabel = (!label || label === 'undefined') ? lastKnownWaterLabel : label;
            
            // Update last known values if current data is valid
            if (!isNaN(level) && level !== undefined && level !== null) {
                lastKnownWaterLevel = level;
                lastKnownWaterLabel = label;
                lastUpdateTime = new Date();
            }
            
            // Check connection status
            const connectionStatus = checkCreekUnitConnection();
            
            // Water level gauge with safe values
            const waterLevelValueEl = document.getElementById('waterLevelValue');
            if (waterLevelValueEl) waterLevelValueEl.textContent = `${safeLevel.toFixed(2)}m`;

            const waterLevelLabelEl = document.getElementById('waterLevelLabel');
            if (waterLevelLabelEl) waterLevelLabelEl.textContent = safeLabel;

            // Status with safe values
            const waterStatus = getWaterLevelStatus(safeLevel, safeLabel);
            const waterStatusEl = document.getElementById('waterLevelStatus');
            if (waterStatusEl) {
                waterStatusEl.textContent = waterStatus.status;
                waterStatusEl.className = `text-xs mt-1 font-medium ${waterStatus.color}`;
            }

            // Connection Status instead of Alarm Status
            const alarmStatusEl = document.getElementById('alarmStatus');
            if (alarmStatusEl) {
                alarmStatusEl.textContent = connectionStatus ? 'ONLINE' : 'OFFLINE';
                alarmStatusEl.className = `px-2 py-1 rounded text-xs font-bold ${connectionStatus ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`;
            }

            // Validate stats and use safe values
            const safeStats = {
                min: (isNaN(stats.min) || stats.min === undefined) ? lastKnownWaterLevel : stats.min,
                max: (isNaN(stats.max) || stats.max === undefined) ? lastKnownWaterLevel : stats.max,
                avg: (isNaN(stats.avg) || stats.avg === undefined) ? lastKnownWaterLevel : stats.avg
            };

            // Daily water stats with safe values
            const minWaterEl = document.getElementById('minWaterLevel');
            const maxWaterEl = document.getElementById('maxWaterLevel');
            const avgWaterEl = document.getElementById('avgWaterLevel');
            
            if (minWaterEl) minWaterEl.textContent = `${safeStats.min.toFixed(2)}m`;
            if (maxWaterEl) maxWaterEl.textContent = `${safeStats.max.toFixed(2)}m`;
            if (avgWaterEl) avgWaterEl.textContent = `${safeStats.avg.toFixed(2)}m`;

            // Stat cards with safe values
            const currentWaterLevelEl = document.getElementById('current-water-level');
            const waterLabelEl = document.getElementById('water-level-label');
            
            if (currentWaterLevelEl) currentWaterLevelEl.textContent = `${safeLevel.toFixed(2)}m`;
            if (waterLabelEl) waterLabelEl.textContent = safeLabel;

            // Gauge progress with safe value (max 2m)
            updateGaugeProgress('waterLevelProgress', safeLevel, 2, 0);

            // Update quick stats with safe values
            updateQuickWaterStats(safeStats);

            // Update additional displays with safe values
            updateWaterLevelAdditionalDisplays(safeLevel, safeLabel, connectionStatus);
        }

        // Add this function to update the quick stats in the new design
        function updateQuickWaterStats(stats) {
            const quickMinEl = document.getElementById('quick-min-level');
            const quickMaxEl = document.getElementById('quick-max-level');
            
            if (quickMinEl) quickMinEl.textContent = `${stats.min.toFixed(2)}m`;
            if (quickMaxEl) quickMaxEl.textContent = `${stats.max.toFixed(2)}m`;
        }

        // Fetch temperature and humidity data
        async function fetchTemperatureData() {
    try {
        const currentDate = getCurrentDate();
        console.log('Fetching temperature data for date:', currentDate);

        const readingsRef = collection(db, 'TemperatureHumidity_Ver2', currentDate, 'Readings');
        const q = query(readingsRef, orderBy('ID', 'desc'));
        const querySnapshot = await getDocs(q);
        const readings = [];

        querySnapshot.forEach((doc) => {
            const data = doc.data();
            if (data.Temperature_C && data.Time && data.HeatIndex_C && data.Humidity_Percent) {
                readings.push({
                    id: data.ID,
                    temperature: parseFloat(data.Temperature_C),
                    heatIndex: parseFloat(data.HeatIndex_C),
                    humidity: parseFloat(data.Humidity_Percent),
                    time: data.Time,
                    time24: convertTo24Hour(data.Time),
                    sensorStatus: data.SensorStatus || 'Unknown', // ADD this line
                    dateTime: data.DateTime || '' // ADD this line
                });
            }
        });

        console.log('Fetched temperature readings:', readings.length);

        if (readings.length > 0) {
            readings.sort((a, b) => a.time24.localeCompare(b.time24));
            
            const latestReading = readings[readings.length - 1];
            currentTemp = latestReading.temperature;
            currentHeatIndex = latestReading.heatIndex;
            currentHumidity = latestReading.humidity;
            
            dailyTempStats = calculateDailyStats(readings, 'temperature');
            dailyHumidityStats = calculateDailyStats(readings, 'humidity');

            const firstReading = readings[0];
currentSensorStatus = latestReading.sensorStatus;
firstReadingTime = firstReading.time;
latestReadingTime = latestReading.time;

// Calculate and update sensor status display
const uptimeText = calculateSensorUptime();
updateSensorStatusDisplay(currentSensorStatus, uptimeText);
            
            updateTemperatureDisplay(currentTemp, currentHeatIndex, currentHumidity, dailyTempStats);
            updateHumidityStats(dailyHumidityStats); // Add this line
            updateTemperatureChart(readings);
            updateCombinedChart(readings);
            updateAdditionalStats(readings, null);
        }

    } catch (error) {
        console.error('Error fetching temperature data:', error);
    }
}

        // Fetch water level data
  async function fetchWaterLevelData() {
    try {
        const currentDate = getCurrentDate();
        console.log('Fetching water level data for date:', currentDate);

        const readingsRef = collection(db, 'WaterLevels_Ver2', currentDate, 'Readings');
        const q = query(readingsRef, orderBy('ID', 'desc'));
        const querySnapshot = await getDocs(q);
        const readings = [];

        querySnapshot.forEach((doc) => {
            const data = doc.data();
            if (data.Level && data.Time && data.LevelLabel) {
                readings.push({
                    id: data.ID,
                    level: parseWaterLevel(data.Level),
                    levelLabel: data.LevelLabel,
                    alarmTriggered: data.AlarmTriggered === 'Yes',
                    time: data.Time,
                    time24: convertTo24Hour(data.Time),
                    rssi: parseInt(data.RSSI) || 0,
                    sensorStatus: data.SensorStatus || 'OK',
                    dateTime: data.DateTime || ''
                });
            }
        });

        console.log('Fetched water level readings:', readings.length);

        if (readings.length > 0) {
            readings.sort((a, b) => a.time24.localeCompare(b.time24));
            const latestReading = readings[readings.length - 1];
            currentWaterLevel = latestReading.level;
            currentWaterLevelLabel = latestReading.levelLabel;
            currentAlarmStatus = latestReading.alarmTriggered;
            
            dailyWaterStats = calculateDailyStats(readings, 'level');
            
            // Calculate quality score
            const qualityScore = calculateQualityScore(readings);
            
            updateWaterLevelDisplay(currentWaterLevel, currentWaterLevelLabel, currentAlarmStatus, dailyWaterStats);
            updateWaterLevelChart(readings);
            updateRSSIDisplay(latestReading.rssi, latestReading.sensorStatus);
            updateAdditionalStats(null, readings);
            
            // Update live analytics
            updateLiveAnalytics(readings, qualityScore);
            
            // Update active alerts count
            const activeAlertsEl = document.getElementById('active-alerts');
            if (activeAlertsEl) {
                const alertCount = readings.filter(r => r.alarmTriggered).length;
                activeAlertsEl.textContent = alertCount.toString();
            }
        }

    } catch (error) {
        console.error('Error fetching water level data:', error);
    }
}


// Update RSSI display
function updateRSSIDisplay(rssi, sensorStatus) {
    const signalStrengthEl = document.getElementById('signal-strength');
    const sensorStatusEl = document.getElementById('sensor-status');
    
    if (signalStrengthEl) {
        signalStrengthEl.textContent = `${rssi} dBm`;
    }
    
    if (sensorStatusEl) {
        sensorStatusEl.textContent = sensorStatus || 'OK';
    }
}

// Get RSSI signal quality description
function getRSSIDescription(rssi) {
    if (rssi >= -30) return "Excellent signal strength";
    if (rssi >= -67) return "Very good signal strength";
    if (rssi >= -70) return "Good signal strength";
    if (rssi >= -80) return "Fair signal strength";
    if (rssi >= -90) return "Poor signal strength";
    return "Very poor signal strength";
}

// Initialize RSSI tooltip
function initRSSITooltip() {
    const signalStrengthEl = document.getElementById('signal-strength');
    if (signalStrengthEl) {
        signalStrengthEl.style.cursor = 'help';
        signalStrengthEl.title = 'RSSI (Received Signal Strength Indicator) measures the quality of LoRa communication. Negative values closer to 0 indicate better signal quality.';
    }
}




        // Filter data points for chart
        function filterDataForChart(readings) {
            if (readings.length <= 20) return readings;

            const filtered = [];
            const interval = Math.floor(readings.length / 15);

            for (let i = 0; i < readings.length; i += interval) {
                filtered.push(readings[i]);
            }

            if (filtered[filtered.length - 1].id !== readings[readings.length - 1].id) {
                filtered.push(readings[readings.length - 1]);
            }

            return filtered;
        }

        // Update temperature chart
        function updateTemperatureChart(readings) {
            const filteredData = filterDataForChart(readings);
            
            if (temperatureTrendChart) {
                temperatureTrendChart.data.labels = filteredData.map(r => r.time);
                temperatureTrendChart.data.datasets[0].data = filteredData.map(r => r.temperature);
                temperatureTrendChart.data.datasets[1].data = filteredData.map(r => r.heatIndex);
                temperatureTrendChart.update('none');
            }
        }

        // Update combined environmental chart
        function updateCombinedChart(readings) {
            const filteredData = filterDataForChart(readings);
            
            if (combinedEnvironmentalChart) {
                combinedEnvironmentalChart.data.labels = filteredData.map(r => r.time);
                combinedEnvironmentalChart.data.datasets[0].data = filteredData.map(r => r.temperature);
                combinedEnvironmentalChart.data.datasets[1].data = filteredData.map(r => r.heatIndex);
                combinedEnvironmentalChart.data.datasets[2].data = filteredData.map(r => r.humidity);
                combinedEnvironmentalChart.update('none');
            }
        }

        // Update water level chart
        function updateWaterLevelChart(readings) {
            const filteredData = filterDataForChart(readings);
            
            if (waterLevelTrendChart) {
                waterLevelTrendChart.data.labels = filteredData.map(r => r.time);
                waterLevelTrendChart.data.datasets[0].data = filteredData.map(r => r.level);
                // Color points based on alarm status
                waterLevelTrendChart.data.datasets[0].pointBackgroundColor = filteredData.map(r => 
                    r.alarmTriggered ? '#ef4444' : '#3b82f6'
                );
                waterLevelTrendChart.update('none');
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
                        pointRadius: 6,
                        pointHoverRadius: 10,
                        borderWidth: 4
                    }, {
                        label: 'Heat Index (°C)',
                        data: [],
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.15)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 6,
                        pointHoverRadius: 10,
                        borderWidth: 4,
                        borderDash: [8, 4]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: true },
                        tooltip: {
                            backgroundColor: 'rgba(17, 24, 39, 0.95)',
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${context.parsed.y.toFixed(1)}°C`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            ticks: {
                                callback: function(value) {
                                    return value + '°C';
                                }
                            }
                        }
                    }
                }
            });
        }

        // Create combined environmental chart
        function createCombinedChart() {
            const ctx = document.getElementById('combinedEnvironmentalChart');
            if (!ctx) return;

            combinedEnvironmentalChart = new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Temperature (°C)',
                        data: [],
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        yAxisID: 'y',
                        tension: 0.3,
                        pointRadius: 4
                    }, {
                        label: 'Heat Index (°C)',
                        data: [],
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        yAxisID: 'y',
                        tension: 0.3,
                        pointRadius: 4,
                        borderDash: [5, 3]
                    }, {
                        label: 'Humidity (%)',
                        data: [],
                        borderColor: '#06b6d4',
                        backgroundColor: 'rgba(6, 182, 212, 0.1)',
                        yAxisID: 'y1',
                        tension: 0.3,
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: true }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: { display: true, text: 'Temperature (°C)' }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: { display: true, text: 'Humidity (%)' },
                            grid: { drawOnChartArea: false }
                        }
                    }
                }
            });
        }

        // Create water level trend chart
        function createWaterLevelChart() {
            const ctx = document.getElementById('waterLevelTrendChart');
            if (!ctx) return;

            waterLevelTrendChart = new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Water Level (m)',
                        data: [],
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.15)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 6,
                        pointHoverRadius: 10,
                        borderWidth: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: true },
                        tooltip: {
                            backgroundColor: 'rgba(17, 24, 39, 0.95)',
                            callbacks: {
                                label: function(context) {
                                    return `Water Level: ${context.parsed.y.toFixed(2)}m`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + 'm';
                                }
                            }
                        }
                    }
                }
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

        

        // WiFi status variables
let currentWiFiData = {
    connectedSSID: 'Loading...',
    signalQuality: 'Loading...',
    uptimeSeconds: 0
};

// Fetch WiFi status data
async function fetchWiFiStatusData() {
    try {
        const currentDate = getCurrentDate();
        console.log('🔍 Fetching WiFi data for date:', currentDate);

        const readingsRef = collection(db, 'WiFiStatus_Ver2', currentDate, 'Readings');
        const q = query(readingsRef, orderBy('ID', 'desc'), limit(1));
        const querySnapshot = await getDocs(q);

        console.log('📊 WiFi query results:', {
            empty: querySnapshot.empty,
            size: querySnapshot.size
        });

        if (!querySnapshot.empty) {
            querySnapshot.forEach((doc) => {
                const data = doc.data();
                console.log('📄 WiFi document data:', data);
                
                currentWiFiData = {
                    connectedSSID: data.ConnectedSSID || 'Unknown Network',
                    signalQuality: data.SignalQuality || 'Unknown',
                    uptimeSeconds: parseInt(data.UptimeSeconds) || 0
                };
                
                console.log('✅ Processed WiFi data:', currentWiFiData);
            });
        } else {
            console.log('⚠️ No WiFi data found for today');
            currentWiFiData = {
                connectedSSID: 'No Data',
                signalQuality: 'No Data',
                uptimeSeconds: 0
            };
        }

        updateNetworkDisplay(currentWiFiData);

    } catch (error) {
        console.error('❌ Error fetching WiFi status:', error);
        currentWiFiData = {
            connectedSSID: 'Error',
            signalQuality: 'Error',
            uptimeSeconds: 0
        };
        updateNetworkDisplay(currentWiFiData);
    }
}

// Update network display elements
function updateNetworkDisplay(wifiData) {
    console.log('🔄 Updating network display with:', wifiData);
    
    // Update Network SSID
    const networkSSIDEl = document.getElementById('network-ssid');
    if (networkSSIDEl) {
        const displaySSID = wifiData.connectedSSID.length > 25 
            ? wifiData.connectedSSID.substring(0, 10) + '...' 
            : wifiData.connectedSSID;
        networkSSIDEl.textContent = displaySSID;
        networkSSIDEl.title = wifiData.connectedSSID; // Show full name on hover
        console.log('✅ Updated SSID:', displaySSID);
    }
    
    // Update Signal Quality
    const signalQualityEl = document.getElementById('signal-quality');
    if (signalQualityEl) {
        signalQualityEl.textContent = wifiData.signalQuality;
        console.log('✅ Updated signal quality:', wifiData.signalQuality);
    }
    
    // Update Network Uptime
    const networkUptimeEl = document.getElementById('network-uptime');
    if (networkUptimeEl) {
        const uptimeText = formatUptime(wifiData.uptimeSeconds);
        networkUptimeEl.textContent = uptimeText;
        console.log('✅ Updated uptime:', uptimeText);
    }
}

// Format uptime seconds to readable format
function formatUptime(seconds) {
    if (seconds === 0) return '0s';
    
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    
    if (days > 0) {
        return `${days}d ${hours}h`;
    } else if (hours > 0) {
        return `${hours}h ${minutes}m`;
    } else if (minutes > 0) {
        return `${minutes}m`;
    } else {
        return `${seconds}s`;
    }
}

       // Initialize dashboard
async function initDashboard() {
    console.log('Initializing dashboard...');
    
    setInterval(updateTime, 1000);
    updateTime();

    createTemperatureChart();
    createCombinedChart();
    createWaterLevelChart();

    await Promise.all([
        fetchTemperatureData(),
        fetchWaterLevelData(),
        fetchWiFiStatusData() // ADD this line
    ]);

    // Set up real-time listeners
    const currentDate = getCurrentDate();
    const tempReadingsRef = collection(db, 'TemperatureHumidity_Ver2', currentDate, 'Readings');
    const waterReadingsRef = collection(db, 'WaterLevels_Ver2', currentDate, 'Readings');
    const wifiReadingsRef = collection(db, 'WiFiStatus_Ver2', currentDate, 'Readings'); // ADD this line
    
    onSnapshot(tempReadingsRef, () => {
        console.log('Temperature real-time update');
        fetchTemperatureData();
    });

    onSnapshot(waterReadingsRef, () => {
        console.log('Water level real-time update');
        fetchWaterLevelData();
    });

    // ADD this WiFi listener
    onSnapshot(wifiReadingsRef, () => {
        console.log('WiFi status real-time update');
        fetchWiFiStatusData();
    });

    // Refresh every 30 seconds
    setInterval(() => {
        fetchTemperatureData();
        fetchWaterLevelData();
        fetchWiFiStatusData(); // ADD this line
    }, 30000);

    // Update connection status
    const statusEl = document.getElementById('connectionStatus');
    if (statusEl) {
        statusEl.textContent = 'Connected to Firebase';
        statusEl.className = 'text-green-600 text-sm font-medium';
    }
}

        document.addEventListener('DOMContentLoaded', initDashboard);
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

        .stat-card:hover {
            transform: translateY(-4px) scale(1.01);
            box-shadow: 0 8px 16px rgba(0,0,0,0.12);
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

        .gauge-icon {
            position: absolute;
            top: 8%;
            right: 5%;
            width: clamp(36px, 7vw, 48px);
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
        .humidity-gradient { background: linear-gradient(135deg, #06b6d4, #0891b2); }
        .water-gradient { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
        
        .chart-container {
            height: 400px;
            width: 100%;
            padding: 0.5rem;
        }
        
        .ai-banner {
            border-radius: 12px;
            border: 1px solid rgba(251, 191, 36, 0.3);
        }
        
        #tempProgress, #heatIndexProgress, #humidityProgress, #waterLevelProgress {
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
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            transform: translateX(0);
        }

        .connection-status.minimized {
            transform: translateX(calc(100% - 60px));
            opacity: 0.8;
        }

        .connection-status.minimized:hover {
            transform: translateX(0);
            opacity: 1;
        }

        .connection-status.slide-out {
            transform: translateX(100%);
            opacity: 0;
        }

        
        .connection-status:hover {
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        /* Live Status & Analytics enhancements */
        /* .status-analytics-enhanced, .water-level-guide-enhanced {
            min-height: 520px;
            max-height: 520px;
        } */

        .compact-spacing {
            gap: 0.75rem;
        }

        .compact-item {
            padding: 0.75rem;
        }

        .compact-grid {
            gap: 0.5rem;
        }

        
        .chart-enhanced {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid rgba(229, 231, 235, 0.6);
        }

        .chart-enhanced:hover {
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .stats-grid .stat-card:hover {
            box-shadow: 0 8px 16px rgba(0,0,0,0.12);
            transition: box-shadow 0.3s ease, transform 0.3s ease;
        }

        #temperatureTrendChart, #combinedEnvironmentalChart, #waterLevelTrendChart {
            filter: contrast(1.1) saturate(1.1);
        }

        .alarm-active {
            animation: blink 1s infinite;
        }

        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.5; }
        }


        /* Add this to your existing <style> section */
.tooltip-container {
    position: relative;
    display: inline-block;
}

.tooltip-text {
    visibility: hidden;
    width: 300px;
    background-color: rgba(17, 24, 39, 0.95);
    color: white;
    text-align: left;
    border-radius: 8px;
    padding: 12px;
    position: absolute;
    z-index: 9999 !important;
    top: -160px;
    left: 1500%;
    margin-left: -140px;
    opacity: 0;
    transition: opacity 0.3s, visibility 0.3s;
    font-size: 12px;
    line-height: 1.4;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.tooltip-text::after {
    content: "";
    position: absolute;
    bottom: -5px;
    left: 1500%;
    margin-left: -5px;
    border-width: 5px;
    border-style: solid;
    border-color: rgba(17, 24, 39, 0.95) transparent transparent transparent;
}

.tooltip-container:hover .tooltip-text {
    visibility: visible;
    opacity: 1;
}

/* Mobile responsive tooltip */
@media (max-width: 640px) {
    .tooltip-text {
        width: 240px;
        margin-left: -120px;
    }
}

.expanded-spacing {
    gap: 1rem;
}


.expanded-item {
    padding: 1.25rem;
}

/* Replace compact-grid with expanded-grid */
.expanded-grid {
    gap: 1rem;
}

/* Update the status-analytics-enhanced class */
.status-analytics-enhanced, .water-level-guide-enhanced {
    min-height: 600px;
    max-height: 600px;
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
                                <p class="text-2xl font-bold text-blue-600" id="current-water-level">0.00m</p>
                                <p class="text-sm text-gray-500" id="water-level-label">LOADING...</p>
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
                                <p class="text-sm text-gray-500" id="heat-index-status">Updating...</p>
                            </div>
                            <i class="fas fa-temperature-high text-3xl text-red-500 stat-icon"></i>
                        </div>
                    </div>

                    <div class="stat-card hover-card">
                        <div class="flex items-center justify-between relative z-10">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Active Alerts</p>
                                <p class="text-2xl font-bold text-red-600" id="active-alerts">0</p>
                                <p class="text-sm text-gray-500" id="alert-status">All Systems Normal</p>
                            </div>
                            <i class="fas fa-bell text-3xl text-green-500 stat-icon"></i>
                        </div>
                    </div>

                    <div class="stat-card hover-card">
                        <div class="flex items-center justify-between relative z-10">
                            <div>
                                <p class="text-sm font-medium text-gray-600">System Status</p>
                                <p class="text-2xl font-bold text-green-600">ONLINE</p>
                                <p class="text-sm text-gray-500">All Sensors Active</p>
                            </div>
                            <i class="fas fa-signal text-3xl text-green-500 stat-icon"></i>
                        </div>
                    </div>
                </div>

                <div class="container mx-auto px-1 py-1">
                    
                    <!-- Environmental Monitoring Section -->
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4">Environmental Monitoring</h2>
                        
                        <!-- Daily Environmental Statistics Cards -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6 stats-grid">
                            <div class="stat-card hover-card text-center">
                                <div class="relative z-10">
                                    <p class="text-sm font-medium text-gray-600">Temperature Range</p>
                                    <div class="flex justify-between mt-2">
                                        <div>
                                            <p class="text-sm text-blue-600">Min</p>
                                            <p class="text-lg font-bold text-blue-600" id="minTemp">--°C</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-red-600">Max</p>
                                            <p class="text-lg font-bold text-red-600" id="maxTemp">--°C</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-green-600">Avg</p>
                                            <p class="text-lg font-bold text-green-600" id="avgTemp">--°C</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="stat-card hover-card text-center">
                                <div class="relative z-10">
                                    <p class="text-sm font-medium text-gray-600">Humidity Range</p>
                                    <div class="flex justify-between mt-2">
                                        <div>
                                            <p class="text-sm text-blue-600">Min</p>
                                            <p class="text-lg font-bold text-blue-600" id="minHumidity">--%</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-red-600">Max</p>
                                            <p class="text-lg font-bold text-red-600" id="maxHumidity">--%</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-green-600">Avg</p>
                                            <p class="text-lg font-bold text-green-600" id="avgHumidity">--%</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                           <div class="stat-card hover-card text-center">
                                <div class="relative z-10">
                                    <p class="text-sm font-medium text-gray-600">Sensor Status</p>
                                    <div class="mt-2">
                                        <p class="text-lg font-bold text-green-600" id="sensor-health-status">Updating...</p>
                                        <p class="text-sm text-gray-500" id="sensor-uptime-display">Loading...</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Combined Environmental Trend Chart -->
                        <div class="metric-card hover-card chart-enhanced mb-6" style="padding: 1.5rem;">
                            <div class="relative z-10">
                                <h3 class="text-lg font-semibold text-gray-800 mb-3">Real-Time Environmental Data (24h)</h3>
                                <div class="chart-container">
                                    <canvas id="combinedEnvironmentalChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Environmental Gauges -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            
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
                                                <div id="tempValue" class="gauge-value text-orange-600">--°C</div>
                                                <div class="gauge-label">Temperature</div>
                                                <div id="tempStatus" class="text-xs mt-1 font-medium text-gray-600">Loading...</div>
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
                                                <div id="heatIndexValue" class="gauge-value text-red-600">--°C</div>
                                                <div class="gauge-label">Feels Like</div>
                                                <div id="heatIndexStatus" class="text-xs mt-1 font-medium text-gray-600">Loading...</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Humidity Gauge -->
                            <div class="metric-card hover-card relative" style="padding: 1.5rem;">
                                <div class="status-indicator bg-cyan-500"></div>
                                <div class="relative z-10">
                                    <div class="text-center mb-2">
                                        <h3 class="text-lg font-bold text-gray-800 mb-1">Humidity</h3>
                                    </div>
                                    
                                    <div class="modern-gauge">
                                        <div class="gauge-icon humidity-gradient">
                                            <i class="fas fa-tint"></i>
                                        </div>
                                        <svg class="gauge-progress" viewBox="0 0 200 200">
                                            <circle cx="100" cy="100" r="85" fill="none" stroke="#e5e7eb" stroke-width="8"/>
                                            <circle id="humidityProgress" cx="100" cy="100" r="85" fill="none" 
                                                    stroke="url(#humidityGradient)" stroke-width="8" stroke-linecap="round"
                                                    stroke-dasharray="534" stroke-dashoffset="534"/>
                                            <defs>
                                                <linearGradient id="humidityGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                                    <stop offset="0%" style="stop-color:#06b6d4"/>
                                                    <stop offset="100%" style="stop-color:#0891b2"/>
                                                </linearGradient>
                                            </defs>
                                        </svg>
                                        <div class="gauge-inner">
                                            <div class="gauge-text">
                                                <div id="humidityValue" class="gauge-value text-cyan-600">--%</div>
                                                <div class="gauge-label">Humidity</div>
                                                <div id="humidityStatus" class="text-xs mt-1 font-medium text-gray-600">Loading...</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Environmental AI Alert -->
                        <div class="ai-banner bg-orange-50 border-l-4 border-orange-400 p-4">
                            <div class="flex items-start">
                                <i class="fas fa-robot text-orange-400 text-lg mr-3 mt-1"></i>
                                <div>
                                    <h3 class="text-sm font-medium text-orange-800">AI Environmental Insight</h3>
                                    <p class="text-sm text-orange-700" id="environmental-insight">Analyzing environmental conditions...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Water Level Monitoring Section -->
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4">Water Level Monitoring</h2>
                        
                        <!-- Daily Water Level Statistics Cards -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6 stats-grid">
                            <div class="stat-card hover-card text-center">
                                <div class="relative z-10">
                                    <p class="text-sm font-medium text-gray-600">Today's Minimum</p>
                                    <p class="text-xl font-bold text-blue-600" id="minWaterLevel">--m</p>
                                </div>
                            </div>
                            
                            <div class="stat-card hover-card text-center">
                                <div class="relative z-10">
                                    <p class="text-sm font-medium text-gray-600">Today's Maximum</p>
                                    <p class="text-xl font-bold text-red-600" id="maxWaterLevel">--m</p>
                                </div>
                            </div>
                            
                            <div class="stat-card hover-card text-center">
                                <div class="relative z-10">
                                    <p class="text-sm font-medium text-gray-600">Daily Average</p>
                                    <p class="text-xl font-bold text-green-600" id="avgWaterLevel">--m</p>
                                </div>
                            </div>
                        </div>

                        <!-- Water Level Trend Chart -->
                        <div class="metric-card hover-card chart-enhanced mb-6" style="padding: 1.5rem;">
                            <div class="relative z-10">
                                <h3 class="text-lg font-semibold text-gray-800 mb-3">Real-Time Water Level Trend (24h)</h3>
                                <div class="chart-container">
                                    <canvas id="waterLevelTrendChart"></canvas>
                                </div>
                            </div>
                        </div>

        <!-- Water Level Status & Guide Panel - Side by Side -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            
            <!-- Current Status Panel -->
            <div class="metric-card hover-card flex flex-col status-analytics-enhanced" style="padding: 1.25rem;">
                <div class="relative z-10 flex-1 flex flex-col">
                    <div class="flex items-center mb-3">
                        <div class="w-3 h-3 bg-blue-500 rounded-full mr-3 animate-pulse"></div>
                        <h3 class="text-lg font-bold text-gray-800">Live Status & Analytics</h3>
                    </div>
                    
                    <div class="flex-1 flex flex-col expanded-spacing">
                        <!-- Current Level Display - Compressed -->
                        <div class="bg-gradient-to-r from-blue-50 to-cyan-50 compact-item rounded-xl border border-blue-100">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fas fa-tachometer-alt text-blue-600 text-xl mr-2"></i>
                                    <div>
                                        <p class="text-xs font-medium text-gray-600">Current Water Level</p>
                                        <p class="text-2xl font-bold text-blue-700" id="current-level-display">--m</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="inline-block px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 mb-1" id="level-classification">LOADING</span>
                                    <p class="text-xs text-gray-500">Next: <span id="next-update">2 min</span></p>
                                </div>
                            </div>
                        </div>

                        <!-- Data Quality & Statistics - Enhanced -->
                        <div class="bg-gradient-to-r from-indigo-50 to-purple-50 expanded-item rounded-xl border border-indigo-100">
                            <div class="flex items-center mb-3">
                                <i class="fas fa-chart-bar text-indigo-600 text-lg mr-3"></i>
                                <h4 class="text-base font-semibold text-gray-800">Today's Data Analytics</h4>
                            </div>
                            <div class="grid grid-cols-3 expanded-grid text-center mb-3">
                                <div class="p-1 bg-white bg-opacity-50 rounded-lg">
                                    <div class="flex items-center justify-center mb-2">
                                        <p class="text-sm font-medium text-gray-700">Data Points</p>
                                        <div class="tooltip-container ml-1">
                                            <i class="fas fa-info-circle text-gray-400 text-xs cursor-help"></i>
                                            <div class="tooltip-text">
                                                <strong>Data Points - Simple Explanation</strong><br><br>
                                                This shows how many times our sensors checked the water level and weather today.<br><br>
                                                <strong>What this means:</strong><br>
                                                • More numbers = More updates from sensors<br>
                                                • Sensors check every few minutes<br>
                                                • Higher numbers mean better monitoring<br><br>
                                                <strong>Normal Range:</strong><br>
                                                • 200-400: Very Good<br>
                                                • 100-200: Good<br>
                                                • Below 100: May have connection issues<br><br>
                                                <em>Think of it like counting how many times someone checked on your house today - more checks mean better security!</em>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-lg font-bold text-indigo-600" id="data-points-count">--</p>
                                    <p class="text-xs text-gray-500">readings</p>
                                </div>
                                <div class="p-2 bg-white bg-opacity-50 rounded-lg">
                                    <p class="text-sm font-medium text-gray-700 mb-2">Last Update</p>
                                    <p class="text-sm font-bold text-indigo-600" id="last-update-time">--</p>
                                    <p class="text-xs text-gray-500">ago</p>
                                </div>
                                <div class="p-2 bg-white bg-opacity-50 rounded-lg">
                                    <p class="text-sm font-medium text-gray-700 mb-2">Quality Score</p>
                                    <p class="text-sm font-bold text-green-600" id="data-quality">--</p>
                                    <p class="text-xs text-gray-500">--% uptime</p>
                                </div>
                            </div>
                            <!-- Additional stats row -->
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div class="flex items-center justify-between p-2 bg-white bg-opacity-30 rounded">
                                    <span class="text-gray-600">Peak Today:</span>
                                    <span class="font-semibold text-indigo-700" id="peak-today">-- m</span>
                                </div>
                                <div class="flex items-center justify-between p-2 bg-white bg-opacity-30 rounded">
                                    <span class="text-gray-600">Average:</span>
                                    <span class="font-semibold text-indigo-700" id="avg-today">-- m</span>
                                </div>
                            </div>
                        </div>

                        <!-- Connection & Status Grid - Compressed -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 compact-grid">
                            <!-- Connection Status -->
                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 compact-item rounded-xl border border-green-100">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <i class="fas fa-wifi text-green-600 text-base mr-2"></i>
                                        <p class="text-xs font-medium text-gray-700">Creek Unit</p>
                                    </div>
                                    <p class="text-sm font-bold text-green-700" id="alarmStatus">ONLINE</p>
                                </div>
                            </div>

                            <!-- Sensor Status -->
                            <div class="bg-gradient-to-r from-blue-50 to-cyan-50 compact-item rounded-xl border border-blue-100">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <i class="fas fa-cog text-blue-600 text-base mr-2"></i>
                                        <p class="text-xs font-medium text-gray-700">Sensor Status</p>
                                    </div>
                                    <p class="text-sm font-bold text-blue-700" id="sensor-status">ACTIVE</p>
                                </div>
                            </div>
                        </div>

                        <!-- Signal Strength - Compressed (Full Width) -->
                        <div class="bg-gradient-to-r from-purple-50 to-indigo-50 compact-item rounded-xl border border-purple-100">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center">
                                    <i class="fas fa-signal text-purple-600 text-base mr-2"></i>
                                    <div class="flex items-center">
                                        <p class="text-xs font-medium text-gray-700">Signal Strength & Network</p>
                                        <div class="tooltip-container ml-1">
                                            <i class="fas fa-info-circle text-gray-400 text-xs cursor-help"></i>
                                            <div class="tooltip-text">
                                                <strong>Signal Strength - Simple Explanation</strong><br><br>
                                                This shows how strong the connection is between our water sensor at the creek and our receiving station.<br><br>
                                                <strong>What the numbers mean:</strong><br>
                                                • -30 to -60: Excellent (like full cell phone bars)<br>
                                                • -60 to -80: Good (like 3-4 bars)<br>
                                                • -80 to -90: Fair (like 1-2 bars)<br>
                                                • Below -90: Poor (like no bars)<br><br>
                                                <em>Think of it like your cell phone signal - stronger signal means more reliable updates about water levels!</em>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-purple-700" id="signal-strength">-- dBm</p>
                                </div>
                            </div>
                            <!-- Replace the existing Signal details section with this: -->
                            <div class="grid grid-cols-3 gap-2 text-xs">
                                <div class="text-center p-1.5 bg-white bg-opacity-40 rounded">
                                    <div class="flex items-center justify-center mb-1">
                                        <i class="fas fa-broadcast-tower text-purple-500 mr-1"></i>
                                        <span class="text-gray-600">Network</span>
                                    </div>
                                    <p class="font-semibold text-purple-700 break-words" id="network-ssid">Loading...</p>
                                </div>
                                <div class="text-center p-1.5 bg-white bg-opacity-40 rounded">
                                    <div class="flex items-center justify-center mb-1">
                                        <i class="fas fa-tachometer-alt text-purple-500 mr-1"></i>
                                        <span class="text-gray-600">Quality</span>
                                    </div>
                                    <p class="font-semibold text-purple-700" id="signal-quality">Loading...</p>
                                </div>
                                <div class="text-center p-1.5 bg-white bg-opacity-40 rounded">
                                    <div class="flex items-center justify-center mb-1">
                                        <i class="fas fa-satellite-dish text-purple-500 mr-1"></i>
                                        <span class="text-gray-600" id="uptime-label">Uptime</span>
                                    </div>
                                    <p class="font-semibold text-purple-700" id="network-uptime">Loading...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Level Guide Panel -->
            <div class="metric-card hover-card flex flex-col water-level-guide-enhanced" style="padding: 1.25rem;">
                <div class="relative z-10 flex-1 flex flex-col">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-info-circle text-blue-600 text-lg mr-3"></i>
                        <h3 class="text-lg font-bold text-gray-800">Water Level Guide</h3>
                    </div>
                    
                    <div class="flex-1 flex flex-col space-y-1.5">
                        <!-- Level Indicators -->
                        <div class="flex items-center p-2.5 rounded-lg hover:bg-green-50 transition-colors">
                            <div class="w-3 h-3 bg-green-500 rounded-full mr-2 flex-shrink-0"></div>
                            <div class="flex-1">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-700">Very Low</span>
                                    <span class="text-xs text-gray-500">0.00 - 0.10m</span>
                                </div>
                                <p class="text-xs text-gray-500">Safe - Normal condition</p>
                            </div>
                        </div>

                        <div class="flex items-center p-2.5 rounded-lg hover:bg-blue-50 transition-colors">
                            <div class="w-3 h-3 bg-blue-500 rounded-full mr-2 flex-shrink-0"></div>
                            <div class="flex-1">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-700">Ankle Level</span>
                                    <span class="text-xs text-gray-500">0.10 - 0.48m</span>
                                </div>
                                <p class="text-xs text-gray-500">Monitor - Water rising</p>
                            </div>
                        </div>

                        <div class="flex items-center p-2.5 rounded-lg hover:bg-yellow-50 transition-colors">
                            <div class="w-3 h-3 bg-yellow-500 rounded-full mr-2 flex-shrink-0"></div>
                            <div class="flex-1">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-700">Knee Level</span>
                                    <span class="text-xs text-gray-500">0.48 - 0.91m</span>
                                </div>
                                <p class="text-xs text-gray-500">Caution - Be alert</p>
                            </div>
                        </div>

                        <div class="flex items-center p-2.5 rounded-lg hover:bg-orange-50 transition-colors">
                            <div class="w-3 h-3 bg-orange-500 rounded-full mr-2 flex-shrink-0"></div>
                            <div class="flex-1">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-700">Waist Level</span>
                                    <span class="text-xs text-gray-500">0.91 - 1.12m</span>
                                </div>
                                <p class="text-xs text-gray-500">Warning - Prepare to evacuate</p>
                            </div>
                        </div>

                        <div class="flex items-center p-2.5 rounded-lg hover:bg-red-50 transition-colors">
                            <div class="w-3 h-3 bg-red-600 rounded-full mr-2 flex-shrink-0"></div>
                            <div class="flex-1">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-700">Chest Level</span>
                                    <span class="text-xs text-gray-500">1.12 - 1.40m</span>
                                </div>
                                <p class="text-xs text-gray-500">Danger - Evacuate now!</p>
                            </div>
                        </div>

                        <div class="flex items-center p-2.5 rounded-lg hover:bg-red-50 transition-colors">
                            <div class="w-3 h-3 bg-red-500 rounded-full mr-2 flex-shrink-0 animate-pulse"></div>
                            <div class="flex-1">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-700">Critical Level</span>
                                    <span class="text-xs text-gray-500">1.40m+</span>
                                </div>
                                <p class="text-xs text-gray-500">Emergency - Life threatening!</p>
                            </div>
                        </div>

                        <!-- Emergency Contact -->
                        <div class=" mb-2 pt-2">
                            <div class="p-2.5 bg-red-50 border border-red-200 rounded-lg">
                                <div class="flex items-center">
                                    <i class="fas fa-phone text-red-600 mr-2"></i>
                                    <div>
                                        <p class="text-xs font-medium text-red-800">Emergency Hotline</p>
                                        <p class="text-sm font-bold text-red-900">911 | Disaster Risk Office</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Barangay Contact -->
                        <div class="mt-2">
                            <div class="p-2.5 bg-blue-50 border border-blue-200 rounded-lg">
                                <div class="flex items-center">
                                    <i class="fas fa-phone text-blue-600 mr-2"></i>
                                    <div>
                                        <p class="text-xs font-medium text-blue-800">Barangay 170 Caloocan City</p>
                                        <p class="text-sm font-bold text-blue-900">(02) 8961-1234 | Local Emergency</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
       // Update AI insights based on current data
        function updateAIInsights() {
            const envInsight = document.getElementById('environmental-insight');
            const waterInsight = document.getElementById('water-level-insight');
            
            if (envInsight && currentTemp > 0) {
                let tempAdvice = '';
                if (currentTemp > 35) {
                    tempAdvice = 'High temperature detected. Ensure adequate ventilation and hydration.';
                } else if (currentTemp < 20) {
                    tempAdvice = 'Low temperature conditions. Monitor for potential equipment effects.';
                } else {
                    tempAdvice = 'Temperature levels are optimal for normal operations.';
                }
                
                let humidityAdvice = '';
                if (currentHumidity > 70) {
                    humidityAdvice = ' High humidity may affect comfort levels.';
                } else if (currentHumidity < 30) {
                    humidityAdvice = ' Low humidity detected - consider moisture levels.';
                }
                
                envInsight.textContent = tempAdvice + humidityAdvice;
            }
            
            if (waterInsight && currentWaterLevel >= 0) {
                let waterAdvice = '';
                if (currentWaterLevel >= 1.40) {
                    waterAdvice = 'CRITICAL: Water level exceeds 1.40m (mid-head level). EVACUATE IMMEDIATELY!';
                } else if (currentWaterLevel >= 1.12) {
                    waterAdvice = 'DANGER: Water level at chest level (1.12m+). Prepare for immediate evacuation!';
                } else if (currentWaterLevel >= 0.91) {
                    waterAdvice = 'WARNING: Water level at waist level (0.91m+). High risk - monitor continuously!';
                } else if (currentWaterLevel >= 0.48) {
                    waterAdvice = 'CAUTION: Water level at knee level (0.48m+). Prepare for potential flood response.';
                } else if (currentWaterLevel >= 0.10) {
                    waterAdvice = 'Water level at ankle level (0.10m+). Continue monitoring.';
                } else {
                    waterAdvice = 'Water level is very low. No immediate flood risk detected.';
                }
                waterInsight.textContent = waterAdvice;
            }
        }
        
        // Update humidity stats display
        function updateHumidityStats(stats) {
    const minHumidityEl = document.getElementById('minHumidity');
    const maxHumidityEl = document.getElementById('maxHumidity');
    const avgHumidityEl = document.getElementById('avgHumidity');
    
    console.log('Updating humidity stats:', stats); // Debug log
    
    if (minHumidityEl) minHumidityEl.textContent = `${stats.min.toFixed(1)}%`;
    if (maxHumidityEl) maxHumidityEl.textContent = `${stats.max.toFixed(1)}%`;
    if (avgHumidityEl) avgHumidityEl.textContent = `${stats.avg.toFixed(1)}%`;
}


        // Add this function to update the quick stats in the new design
        function updateQuickWaterStats(stats) {
            const quickMinEl = document.getElementById('quick-min-level');
            const quickMaxEl = document.getElementById('quick-max-level');
            
            const safeMin = (isNaN(stats.min) || stats.min === undefined) ? lastKnownWaterLevel : stats.min;
            const safeMax = (isNaN(stats.max) || stats.max === undefined) ? lastKnownWaterLevel : stats.max;
            
            if (quickMinEl) quickMinEl.textContent = `${safeMin.toFixed(2)}m`;
            if (quickMaxEl) quickMaxEl.textContent = `${safeMax.toFixed(2)}m`;
        }

        
function updateAdditionalStats(tempReadings, waterReadings) {
    // Update statistics
    const dataPointsEl = document.getElementById('data-points-count');
    const lastUpdateEl = document.getElementById('last-update-time');
    
    if (dataPointsEl) {
        const totalPoints = (tempReadings ? tempReadings.length : 0) + (waterReadings ? waterReadings.length : 0);
        dataPointsEl.textContent = totalPoints.toString();
    }

    if (lastUpdateEl) {
        if (waterReadings && waterReadings.length > 0) {
            // Use the latest water reading time
            const latestReading = waterReadings[waterReadings.length - 1];
            lastUpdateEl.textContent = latestReading.time || 'Unknown';
        } else {
            const now = new Date();
            lastUpdateEl.textContent = now.toLocaleTimeString('en-PH', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
        }
    }

    // Update peak and average if we have water readings
    if (waterReadings && waterReadings.length > 0) {
        const peakTodayEl = document.getElementById('peak-today');
        const avgTodayEl = document.getElementById('avg-today');
        
        if (peakTodayEl) {
            const maxLevel = Math.max(...waterReadings.map(r => r.level));
            peakTodayEl.textContent = `${maxLevel.toFixed(2)}m`;
        }
        
        if (avgTodayEl) {
            const avgLevel = waterReadings.reduce((sum, r) => sum + r.level, 0) / waterReadings.length;
            avgTodayEl.textContent = `${avgLevel.toFixed(2)}m`;
        }
    }
}

        // Update additional water level displays
       function updateWaterLevelAdditionalDisplays(level, label, connectionStatus) {
        const safeLevel = (isNaN(level) || level === undefined || level === null) ? lastKnownWaterLevel : level;
        
        const currentLevelDisplay = document.getElementById('current-level-display');
        const levelClassification = document.getElementById('level-classification');
        const riskLevel = document.getElementById('risk-level');
        
        if (currentLevelDisplay) currentLevelDisplay.textContent = `${safeLevel.toFixed(2)}m`;
        if (levelClassification) levelClassification.textContent = label;
        
        // Update Risk Assessment based on connection status
        if (riskLevel) {
            let risk = 'LOW';
            let riskColor = 'text-green-600';
            
            if (!connectionStatus) {
                risk = 'UNKNOWN';
                riskColor = 'text-gray-600';
            } else if (safeLevel >= 1.40) {
                risk = 'CRITICAL';
                riskColor = 'text-red-800';
            } else if (safeLevel >= 1.12) {
                risk = 'EXTREME';
                riskColor = 'text-red-600';
            } else if (safeLevel >= 0.91) {
                risk = 'HIGH';
                riskColor = 'text-orange-600';
            } else if (safeLevel >= 0.48) {
                risk = 'MODERATE';
                riskColor = 'text-yellow-600';
            } else if (safeLevel >= 0.10) {
                risk = 'LOW';
                riskColor = 'text-blue-600';
            }
            
            riskLevel.textContent = risk;
            riskLevel.className = `text-lg font-bold ${riskColor}`;
        }

        // Update connection indicator in alarm status badge
        const alarmStatusBadge = document.getElementById('alarm-status-badge');
        if (alarmStatusBadge) {
            const statusText = connectionStatus ? 'ONLINE' : 'OFFLINE';
            const statusColor = connectionStatus ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
            alarmStatusBadge.innerHTML = `<span class="inline-block px-3 py-1 rounded-full text-xs font-semibold ${statusColor}">${statusText}</span>`;
        }
    }




// Calculate quality score based on data reliability
function calculateQualityScore(readings) {
    if (!readings || readings.length === 0) return 0;
    
    // Factor 1: Data frequency (more readings = better)
    const expectedReadings = 288; // Assuming every 5 minutes for 24 hours
    const frequencyScore = Math.min((readings.length / expectedReadings) * 100, 100);
    
    // Factor 2: Sensor status (OK readings percentage)
    const okReadings = readings.filter(r => r.sensorStatus === 'OK').length;
    const statusScore = (okReadings / readings.length) * 100;
    
    // Factor 3: Signal quality (RSSI scores)
    const rssiScores = readings.map(r => {
        const rssi = parseInt(r.rssi) || -100;
        if (rssi >= -30) return 100;
        if (rssi >= -67) return 80;
        if (rssi >= -70) return 60;
        if (rssi >= -80) return 40;
        if (rssi >= -90) return 20;
        return 10;
    });
    const avgRssiScore = rssiScores.reduce((sum, score) => sum + score, 0) / rssiScores.length;
    
    // Weighted average
    const overallScore = (frequencyScore * 0.4 + statusScore * 0.4 + avgRssiScore * 0.2);
    
    return Math.round(overallScore);
}

// Get quality description
function getQualityDescription(score) {
    if (score >= 95) return 'Excellent';
    if (score >= 85) return 'Very Good';
    if (score >= 75) return 'Good';
    if (score >= 60) return 'Fair';
    if (score >= 40) return 'Poor';
    return 'Critical';
}

// Update live analytics display
function updateLiveAnalytics(readings, qualityScore) {
    // Quality Score
    const qualityEl = document.getElementById('data-quality');
    if (qualityEl) {
        const qualityDesc = getQualityDescription(qualityScore);
        qualityEl.textContent = qualityDesc;
        
        // Update color based on quality
        qualityEl.className = `text-sm font-bold ${
            qualityScore >= 85 ? 'text-green-600' : 
            qualityScore >= 60 ? 'text-yellow-600' : 'text-red-600'
        }`;
    }
    
    // Update uptime percentage based on quality score
    const uptimeElements = document.querySelectorAll('p');
    uptimeElements.forEach(el => {
        if (el && el.textContent && el.textContent.includes('98% uptime')) {
            el.textContent = `${qualityScore}% uptime`;
        }
    });
}




        // Enhanced fetch functions to include AI insights and additional displays
        // Enhanced fetch functions to include AI insights and additional displays
        async function fetchTemperatureDataEnhanced() {
            await fetchTemperatureData();
            if (dailyHumidityStats && dailyHumidityStats.min !== undefined) {
                updateHumidityStats(dailyHumidityStats);
            }
            updateAIInsights();
        }

        async function fetchWaterLevelDataEnhanced() {
            await fetchWaterLevelData();
            if (currentWaterLevel >= 0) {
                updateWaterLevelAdditionalDisplays(currentWaterLevel, currentWaterLevelLabel);
            }
            updateAIInsights();
        }

        // Override the original init function
     
        // Replace the original event listener
        document.addEventListener('DOMContentLoaded', initDashboardEnhanced);








    </script>
</body>
</html>
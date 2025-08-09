

// WATER LEVEL AND ENVIRONMENTAL TRENDS CHARTS
// Chart.js global variables
// let waterLevelChart, temperatureChart;

// // Generate time series data
// function generateTimeSeriesData(baseValue, variance, points = 24) {
//     return Array.from({ length: points }, (_, i) => ({
//         time: `${String(i).padStart(2, '0')}:00`,
//         value: baseValue + (Math.random() - 0.5) * variance
//     }));
// }

// // Initialize both charts
// function initCharts() {
//     // Water Level Chart
//     const waterCtx = document.getElementById('waterLevelChart').getContext('2d');
//     const waterData = generateTimeSeriesData(0.3, 0.4);

//     waterLevelChart = new Chart(waterCtx, {
//         type: 'line',
//         data: {
//             labels: waterData.map(d => d.time),
//             datasets: [{
//                 label: 'Water Level (m)',
//                 data: waterData.map(d => Math.max(0.1, d.value)),
//                 borderColor: '#3B82F6',
//                 backgroundColor: 'rgba(59, 130, 246, 0.1)',
//                 fill: true,
//                 tension: 0.4
//             }]
//         },
//         options: {
//             responsive: true,
//             maintainAspectRatio: false,
//             plugins: {
//                 legend: { display: false }
//             },
//             scales: {
//                 y: {
//                     beginAtZero: true,
//                     title: { display: true, text: 'Water Level (m)' }
//                 }
//             }
//         }
//     });

//     // Temperature + Humidity Chart
//     const tempCtx = document.getElementById('temperatureChart').getContext('2d');
//     const tempData = generateTimeSeriesData(29, 4);
//     const humidityData = generateTimeSeriesData(75, 20);

//     temperatureChart = new Chart(tempCtx, {
//         type: 'line',
//         data: {
//             labels: tempData.map(d => d.time),
//             datasets: [
//                 {
//                     label: 'Temperature (°C)',
//                     data: tempData.map(d => Math.max(25, d.value)),
//                     borderColor: '#F59E0B',
//                     backgroundColor: 'rgba(245, 158, 11, 0.1)',
//                     yAxisID: 'y'
//                 },
//                 {
//                     label: 'Humidity (%)',
//                     data: humidityData.map(d => Math.max(50, Math.min(100, d.value))),
//                     borderColor: '#10B981',
//                     backgroundColor: 'rgba(16, 185, 129, 0.1)',
//                     yAxisID: 'y1'
//                 }
//             ]
//         },
//         options: {
//             responsive: true,
//             maintainAspectRatio: false,
//             plugins: {
//                 legend: { display: true }
//             },
//             scales: {
//                 y: {
//                     type: 'linear',
//                     display: true,
//                     position: 'left',
//                     title: { display: true, text: 'Temperature (°C)' }
//                 },
//                 y1: {
//                     type: 'linear',
//                     display: true,
//                     position: 'right',
//                     title: { display: true, text: 'Humidity (%)' },
//                     grid: { drawOnChartArea: false }
//                 }
//             }
//         }
//     });
// }

// // Initialize charts on DOM load
// document.addEventListener('DOMContentLoaded', initCharts);

// // Responsive resizing
// window.addEventListener('resize', () => {
//     if (waterLevelChart) waterLevelChart.resize();
//     if (temperatureChart) temperatureChart.resize();
// });

 // Global variables for charts and current values
        let temperatureTrendChart, humidityTrendChart, waterLevelChart;
        let currentTemp = 28;
        let currentHumidity = 75;
        let currentWaterLevel = 0.4;

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
            document.getElementById('current-time').textContent = timeString;
        }

        setInterval(updateTime, 1000);
        updateTime();

        // Database connection simulation - Replace with actual API calls
        async function fetchSensorData() {
            // This is where you'd make actual API calls to your database
            // Example:
            /*
            try {
                const response = await fetch('/api/sensor-data');
                const data = await response.json();
                return {
                    temperature: data.temperature,
                    humidity: data.humidity,
                    waterLevel: data.waterLevel,
                    timestamp: data.timestamp
                };
            } catch (error) {
                console.error('Error fetching sensor data:', error);
                return null;
            }
            */
            
            // Simulated data for demo
            return {
                temperature: 25 + Math.random() * 10,
                humidity: 60 + Math.random() * 30,
                waterLevel: 0.2 + Math.random() * 0.6,
                timestamp: new Date()
            };
        }

        // Update gauge progress
        function updateGaugeProgress(elementId, value, maxValue, minValue = 0) {
            const circle = document.getElementById(elementId);
            const circumference = 534; // 2 * Math.PI * 85
            const percentage = Math.min(Math.max((value - minValue) / (maxValue - minValue), 0), 1);
            const offset = circumference - (percentage * circumference);
            
            circle.style.strokeDashoffset = offset;
        }

        // Get status text based on value ranges
        function getTemperatureStatus(temp) {
            if (temp < 20) return { status: 'Cold', color: 'text-blue-600' };
            if (temp < 25) return { status: 'Cool', color: 'text-green-600' };
            if (temp < 30) return { status: 'Normal', color: 'text-green-600' };
            if (temp < 35) return { status: 'Warm', color: 'text-orange-600' };
            return { status: 'Hot', color: 'text-red-600' };
        }

        function getHumidityStatus(humidity) {
            if (humidity < 40) return { status: 'Dry', color: 'text-orange-600' };
            if (humidity < 60) return { status: 'Good', color: 'text-green-600' };
            if (humidity < 80) return { status: 'Optimal', color: 'text-green-600' };
            return { status: 'High', color: 'text-blue-600' };
        }

        function getWaterLevelStatus(level) {
            if (level < 0.2) return { status: 'Low', color: 'text-red-600' };
            if (level < 0.4) return { status: 'Medium', color: 'text-orange-600' };
            if (level < 0.7) return { status: 'Safe', color: 'text-green-600' };
            return { status: 'Full', color: 'text-blue-600' };
        }

        // Update all gauge displays
        function updateGaugeDisplays(temp, humidity, waterLevel) {
            // Temperature
            document.getElementById('tempValue').textContent = `${Math.round(temp)}°C`;
            const tempStatus = getTemperatureStatus(temp);
            const tempStatusEl = document.getElementById('tempStatus');
            tempStatusEl.textContent = tempStatus.status;
            tempStatusEl.className = `text-xs mt-1 font-medium ${tempStatus.color}`;
            updateGaugeProgress('tempProgress', temp, 50, 0);

            // Humidity
            document.getElementById('humidityValue').textContent = `${Math.round(humidity)}%`;
            const humidityStatus = getHumidityStatus(humidity);
            const humidityStatusEl = document.getElementById('humidityStatus');
            humidityStatusEl.textContent = humidityStatus.status;
            humidityStatusEl.className = `text-xs mt-1 font-medium ${humidityStatus.color}`;
            updateGaugeProgress('humidityProgress', humidity, 100, 0);

            // Water Level
            document.getElementById('waterValue').textContent = `${waterLevel.toFixed(1)}m`;
            const waterStatus = getWaterLevelStatus(waterLevel);
            const waterStatusEl = document.getElementById('waterStatus');
            waterStatusEl.textContent = waterStatus.status;
            waterStatusEl.className = `text-xs mt-1 font-medium ${waterStatus.color}`;
            updateGaugeProgress('waterProgress', waterLevel, 1, 0);
        }

        // Generate sample trend data
        function generateTrendData(baseValue, variance, points = 24) {
            return Array.from({ length: points }, (_, i) => ({
                time: `${String(i).padStart(2, '0')}:00`,
                value: Math.max(0, baseValue + (Math.random() - 0.5) * variance)
            }));
        }

        // Create trend chart
        function createTrendChart(canvasId, data, label, borderColor, backgroundColor, yAxisLabel) {
            const ctx = document.getElementById(canvasId).getContext('2d');
            
            return new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => d.time),
                    datasets: [{
                        label: label,
                        data: data.map(d => d.value),
                        borderColor: borderColor,
                        backgroundColor: backgroundColor,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 2,
                        pointHoverRadius: 6,
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            title: { 
                                display: true, 
                                text: yAxisLabel,
                                color: '#64748b',
                                font: { weight: 'bold' }
                            },
                            grid: { color: '#f1f5f9' },
                            ticks: { color: '#64748b' }
                        },
                        x: {
                            grid: { color: '#f1f5f9' },
                            ticks: { color: '#64748b' }
                        }
                    }
                }
            });
        }

        // Initialize all charts and gauges
        async function initDashboard() {
            // Generate initial trend data
            const tempData = generateTrendData(28, 6).map(d => ({
                ...d, value: Math.max(15, Math.min(45, d.value))
            }));
            const humidityData = generateTrendData(75, 20).map(d => ({
                ...d, value: Math.max(30, Math.min(95, d.value))
            }));
            const waterData = generateTrendData(0.4, 0.4).map(d => ({
                ...d, value: Math.max(0.1, Math.min(0.9, d.value))
            }));

            // Create trend charts
            temperatureTrendChart = createTrendChart(
                'temperatureTrendChart', tempData, 'Temperature', 
                '#f59e0b', 'rgba(245, 158, 11, 0.1)', 'Temperature (°C)'
            );
            
            humidityTrendChart = createTrendChart(
                'humidityTrendChart', humidityData, 'Humidity',
                '#14b8a6', 'rgba(20, 184, 166, 0.1)', 'Humidity (%)'
            );
            
            waterLevelChart = createTrendChart(
                'waterLevelChart', waterData, 'Water Level',
                '#3b82f6', 'rgba(59, 130, 246, 0.1)', 'Water Level (m)'
            );

            // Initial gauge update
            updateGaugeDisplays(currentTemp, currentHumidity, currentWaterLevel);

            // Set up real-time updates
            setInterval(updateSensorData, 3000); // Update every 3 seconds
        }

        // Update sensor data - connects to database
        async function updateSensorData() {
            const sensorData = await fetchSensorData();
            
            if (sensorData) {
                currentTemp = sensorData.temperature;
                currentHumidity = sensorData.humidity;
                currentWaterLevel = sensorData.waterLevel;
                
                updateGaugeDisplays(currentTemp, currentHumidity, currentWaterLevel);
            }
        }

        // Initialize dashboard when DOM is loaded
        document.addEventListener('DOMContentLoaded', initDashboard);

        // Handle window resize
        window.addEventListener('resize', () => {
            [temperatureTrendChart, humidityTrendChart, waterLevelChart].forEach(chart => {
                if (chart) chart.resize();
            });
        });



// ANIMATION FOR THE CHARTS

   // Initialize gauge animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate temperature gauge to 70% (28°C out of ~40°C max)
            setTimeout(() => {
                const tempProgress = document.getElementById('tempProgress');
                const offset = 534 - (534 * 0.7);
                tempProgress.style.strokeDashoffset = offset;
            }, 500);

            // Animate humidity gauge to 75%
            setTimeout(() => {
                const humidityProgress = document.getElementById('humidityProgress');
                const offset = 534 - (534 * 0.75);
                humidityProgress.style.strokeDashoffset = offset;
            }, 750);

            // Animate water level gauge to 40% (0.4m out of 1m max)
            setTimeout(() => {
                const waterProgress = document.getElementById('waterProgress');
                const offset = 534 - (534 * 0.4);
                waterProgress.style.strokeDashoffset = offset;
            }, 1000);
        });





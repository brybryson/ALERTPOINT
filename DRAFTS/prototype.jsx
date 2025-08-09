 // Global Variables
        let waterLevelChart, temperatureChart;
        let currentReportPeriod = 'day';

        // Mock data
        const reportData = {
            day: {
                waterLevel: { avg: 0.28, max: 0.75, min: 0.15, alerts: 1 },
                temperature: { avg: 28.8, max: 31.2, min: 26.5, heatIndex: 34.9 },
                trends: ['Peak water level at 09:30 AM', 'High heat index from 12-3 PM']
            },
            week: {
                waterLevel: { avg: 0.32, max: 0.85, min: 0.12, alerts: 3 },
                temperature: { avg: 29.1, max: 32.8, min: 25.1, heatIndex: 35.2 },
                trends: ['Highest water levels on Monday/Tuesday', 'Consistent afternoon heat warnings']
            },
            month: {
                waterLevel: { avg: 0.35, max: 0.92, min: 0.08, alerts: 12 },
                temperature: { avg: 28.9, max: 34.1, min: 24.8, heatIndex: 35.8 },
                trends: ['Flood alerts increased by 25%', 'Heat index trending upward']
            },
            year: {
                waterLevel: { avg: 0.31, max: 1.15, min: 0.05, alerts: 45 },
                temperature: { avg: 28.5, max: 36.2, min: 22.1, heatIndex: 35.1 },
                trends: ['Seasonal flooding peaks in July-September', 'Temperature rising 0.3°C annually']
            }
        };

        // AI Responses
        const aiResponses = {
            'water level': 'Based on current data, water levels show a concerning trend. The recent spike to 0.75m at 09:30 AM indicates potential flooding. Recommend immediate evacuation preparations.',
            'temperature': 'Temperature readings show high heat index values. Current 35.2°C heat index poses health risks. Advise residents to stay hydrated and avoid outdoor activities.',
            'trends': 'Analysis shows water levels typically peak between 8-10 AM. Temperature patterns indicate afternoon heat stress periods. Consider scheduling alerts accordingly.',
            'prediction': 'Based on historical patterns, water levels may rise 15-20% in the next 2 hours. Heat index will likely exceed 36°C by afternoon. Recommend proactive measures.'
        };

        // Generate time series data
        function generateTimeSeriesData(baseValue, variance, points = 24) {
            return Array.from({ length: points }, (_, i) => ({
                time: `${String(i).padStart(2, '0')}:00`,
                value: baseValue + (Math.random() - 0.5) * variance
            }));
        }

        // Initialize Charts
        function initCharts() {
            // Water Level Chart
            const waterCtx = document.getElementById('waterLevelChart').getContext('2d');
            const waterData = generateTimeSeriesData(0.3, 0.4, 24);
            
            waterLevelChart = new Chart(waterCtx, {
                type: 'line',
                data: {
                    labels: waterData.map(d => d.time),
                    datasets: [{
                        label: 'Water Level (m)',
                        data: waterData.map(d => Math.max(0.1, d.value)),
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.4
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
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Water Level (m)'
                            }
                        }
                    }
                }
            });

            // Temperature Chart
            const tempCtx = document.getElementById('temperatureChart').getContext('2d');
            const tempData = generateTimeSeriesData(29, 4, 24);
            const humidityData = generateTimeSeriesData(75, 20, 24);

            temperatureChart = new Chart(tempCtx, {
                type: 'line',
                data: {
                    labels: tempData.map(d => d.time),
                    datasets: [{
                        label: 'Temperature (°C)',
                        data: tempData.map(d => Math.max(25, d.value)),
                        borderColor: '#F59E0B',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        yAxisID: 'y'
                    }, {
                        label: 'Humidity (%)',
                        data: humidityData.map(d => Math.max(50, Math.min(100, d.value))),
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        yAxisID: 'y1'
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
                            title: {
                                display: true,
                                text: 'Temperature (°C)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Humidity (%)'
                            },
                            grid: {
                                drawOnChartArea: false,
                            }
                        }
                    }
                }
            });
        }

        // Update Time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleString('en-PH', {
                timeZone: 'Asia/Manila',
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('current-time').textContent = timeString;
        }

        // Switch Tabs
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all nav tabs
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.classList.remove('active', 'border-blue-500', 'text-blue-600');
                tab.classList.add('border-transparent', 'text-gray-500');
            });

            // Show selected tab content
            document.getElementById(`${tabName}-content`).classList.add('active');
            
            // Add active class to selected nav tab
            const activeTab = document.querySelector(`[data-tab="${tabName}"]`);
            activeTab.classList.add('active', 'border-blue-500', 'text-blue-600');
            activeTab.classList.remove('border-transparent', 'text-gray-500');
        }

        // AI Query Handler
        function handleAiQuery() {
            const query = document.getElementById('ai-input').value.toLowerCase();
            let response = 'I can help analyze water levels, temperature trends, predictions, and provide safety recommendations. Try asking about specific metrics or trends.';
            
            for (const [key, value] of Object.entries(aiResponses)) {
                if (query.includes(key)) {
                    response = value;
                    break;
                }
            }
            
            document.getElementById('ai-response-text').textContent = response;
            document.getElementById('ai-response').style.display = 'block';
        }

        // Quick AI Query
        function quickAiQuery(topic) {
            document.getElementById('ai-input').value = topic;
            handleAiQuery();
        }

        // Switch Report Period
        function switchReportPeriod(period) {
            currentReportPeriod = period;
            
            // Update button states
            document.querySelectorAll('.report-period-btn').forEach(btn => {
                btn.classList.remove('active', 'bg-blue-500', 'text-white');
                btn.classList.add('bg-gray-100', 'text-gray-700');
            });
            
            const activeBtn = document.querySelector(`[data-period="${period}"]`);
            activeBtn.classList.add('active', 'bg-blue-500', 'text-white');
            activeBtn.classList.remove('bg-gray-100', 'text-gray-700');
            
            updateReportData();
        }

        // Update Report Data
        function updateReportData() {
            const data = reportData[currentReportPeriod];
            
            // Update analytics summary
            const summaryHtml = `
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h4 class="font-medium text-blue-800">Water Level Stats</h4>
                    <p class="text-sm text-blue-600">Avg: ${data.waterLevel.avg}m</p>
                    <p class="text-sm text-blue-600">Max: ${data.waterLevel.max}m</p>
                    <p class="text-sm text-blue-600">Alerts: ${data.waterLevel.alerts}</p>
                </div>
                <div class="bg-orange-50 p-4 rounded-lg">
                    <h4 class="font-medium text-orange-800">Temperature Stats</h4>
                    <p class="text-sm text-orange-600">Avg: ${data.temperature.avg}°C</p>
                    <p class="text-sm text-orange-600">Max: ${data.temperature.max}°C</p>
                    <p class="text-sm text-orange-600">Heat Index: ${data.temperature.heatIndex}°C</p>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <h4 class="font-medium text-green-800">System Health</h4>
                    <p class="text-sm text-green-600">Uptime: 99.8%</p>
                    <p class="text-sm text-green-600">Battery: 87%</p>
                    <p class="text-sm text-green-600">Signal: Strong</p>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg">
                    <h4 class="font-medium text-purple-800">AI Insights</h4>
                    <p class="text-sm text-purple-600">Predictions: 94% Accurate</p>
                    <p class="text-sm text-purple-600">Recommendations: 12</p>
                    <p class="text-sm text-purple-600">Model Version: 2.1</p>
                </div>
            `;
            
            document.getElementById('analytics-summary').innerHTML = summaryHtml;
            
            // Update insights
            const insightsHtml = data.trends.map(trend => 
                `<li class="text-sm text-gray-600">• ${trend}</li>`
            ).join('');
            
            document.getElementById('trend-insights').innerHTML = insightsHtml;
            document.getElementById('current-period').textContent = currentReportPeriod;
        }

        // Simulate real-time data updates
        function updateSensorData() {
            // Update water level (simulate fluctuation)
            const baseWaterLevel = 0.2;
            const waterVariance = Math.random() * 0.1 - 0.05;
            const currentWater = Math.max(0.1, baseWaterLevel + waterVariance);
            
            document.getElementById('current-water-level').textContent = `${currentWater.toFixed(2)}m`;
            
            // Update temperature
            const baseTemp = 29.1;
            const tempVariance = Math.random() * 2 - 1;
            const currentTemp = baseTemp + tempVariance;
            
            document.getElementById('current-temperature').textContent = `${currentTemp.toFixed(1)}°C`;
            
            // Update humidity
            const baseHumidity = 80.6;
            const humidityVariance = Math.random() * 5 - 2.5;
            const currentHumidity = Math.max(50, Math.min(100, baseHumidity + humidityVariance));
            
            document.getElementById('current-humidity').textContent = `${currentHumidity.toFixed(1)}%`;
            
            // Calculate and update heat index
            const heatIndex = calculateHeatIndex(currentTemp, currentHumidity);
            document.getElementById('heat-index').textContent = `${heatIndex.toFixed(1)}°C`;
            
            // Update chart data if charts are initialized
            if (waterLevelChart) {
                updateChartData();
            }
        }

        // Calculate Heat Index
        function calculateHeatIndex(temp, humidity) {
            // Simplified heat index calculation
            const T = temp;
            const RH = humidity;
            
            let HI = 0.5 * (T + 61.0 + ((T - 68.0) * 1.2) + (RH * 0.094));
            
            if (HI >= 80) {
                HI = -42.379 + 2.04901523 * T + 10.14333127 * RH 
                    - 0.22475541 * T * RH - 0.00683783 * T * T 
                    - 0.05481717 * RH * RH + 0.00122874 * T * T * RH 
                    + 0.00085282 * T * RH * RH - 0.00000199 * T * T * RH * RH;
            }
            
            return HI;
        }

        // Update Chart Data
        function updateChartData() {
            // Get current time
            const now = new Date();
            const currentHour = now.getHours();
            const currentMinute = now.getMinutes();
            const timeLabel = `${String(currentHour).padStart(2, '0')}:${String(currentMinute).padStart(2, '0')}`;
            
            // Update water level chart
            const waterData = waterLevelChart.data;
            waterData.labels.push(timeLabel);
            waterData.datasets[0].data.push(parseFloat(document.getElementById('current-water-level').textContent));
            
            // Keep only last 24 data points
            if (waterData.labels.length > 24) {
                waterData.labels.shift();
                waterData.datasets[0].data.shift();
            }
            
            waterLevelChart.update('none');
            
            // Update temperature chart
            const tempData = temperatureChart.data;
            tempData.labels.push(timeLabel);
            tempData.datasets[0].data.push(parseFloat(document.getElementById('current-temperature').textContent));
            tempData.datasets[1].data.push(parseFloat(document.getElementById('current-humidity').textContent));
            
            // Keep only last 24 data points
            if (tempData.labels.length > 24) {
                tempData.labels.shift();
                tempData.datasets[0].data.shift();
                tempData.datasets[1].data.shift();
            }
            
            temperatureChart.update('none');
        }

        // Handle Enter key in AI input
        document.getElementById('ai-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                handleAiQuery();
            }
        });

        // Initialize dashboard
        function initDashboard() {
            updateTime();
            initCharts();
            updateReportData();
            
            // Start real-time updates
            setInterval(updateTime, 1000);
            setInterval(updateSensorData, 30000); // Update every 30 seconds
            
            // Simulate initial AI response
            setTimeout(() => {
                document.getElementById('ai-response-text').textContent = 'Welcome to AlertPoint AI Assistant! I can help analyze your sensor data, predict trends, and provide safety recommendations.';
                document.getElementById('ai-response').style.display = 'block';
            }, 2000);
        }

        // Add loading state management
        function showLoading(element) {
            element.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        }

        function hideLoading() {
            // Remove loading indicators
            document.querySelectorAll('.fa-spinner').forEach(spinner => {
                spinner.parentElement.style.display = 'none';
            });
        }

        // Error handling for charts
        window.addEventListener('error', function(e) {
            console.error('Dashboard error:', e);
            if (e.message.includes('Chart')) {
                document.getElementById('waterLevelChart').parentElement.innerHTML = 
                    '<div class="flex items-center justify-center h-full text-gray-500"><i class="fas fa-exclamation-triangle mr-2"></i>Chart loading error</div>';
            }
        });

        // Initialize when DOM is loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initDashboard);
        } else {
            initDashboard();
        }

        // Add responsive behavior
        window.addEventListener('resize', function() {
            if (waterLevelChart) waterLevelChart.resize();
            if (temperatureChart) temperatureChart.resize();
        });

        // Export functions (placeholder implementations)
        function exportPDF() {
            alert('PDF export functionality would be implemented here');
        }

        function exportExcel() {
            alert('Excel export functionality would be implemented here');
        }

        function setupAutoReport() {
            alert('Auto-report configuration would be implemented here');
        }

        // Add event listeners for export buttons
        document.addEventListener('DOMContentLoaded', function() {
            const exportButtons = document.querySelectorAll('[class*="bg-blue-500"], [class*="bg-green-500"], [class*="bg-purple-500"]');
            exportButtons.forEach((button, index) => {
                if (button.textContent.includes('PDF')) {
                    button.addEventListener('click', exportPDF);
                } else if (button.textContent.includes('Excel')) {
                    button.addEventListener('click', exportExcel);
                } else if (button.textContent.includes('Auto-Report')) {
                    button.addEventListener('click', setupAutoReport);
                }
            });
        });
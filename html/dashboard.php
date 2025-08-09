

<!-- prototype.html -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlertPoint Admin Dashboard</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/ALERTPOINT/css/dashboard.css">
    <link rel="stylesheet" href="/ALERTPOINT/css/footer.css">
    <link rel="stylesheet" href="/ALERTPOINT/css/nav-bar-2.css">


</head>
<body class="min-h-screen bg-gray-100">

    
   <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <!-- Replaced the icon with an image -->
                    <img src="/ALERTPOINT/ALERTPOINT_LOGO.png" alt="AlertPoint Logo" class="h-11 w-auto mr-3" />
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">AlertPoint</h1>
                        <p class="text-sm text-gray-600">Barangay 170, Caloocan City</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p id="current-time" class="text-sm font-medium text-gray-900"></p>
                        <p class="text-xs text-gray-500">Philippine Standard Time</p>
                    </div>
                    <div class="relative">
                        <i onclick="toggleSettingsDropdown()" class="fas fa-cog text-gray-400 cursor-pointer hover:text-gray-600"></i>
                        <div id="settingsDropdown" class="absolute right-0 mt-2 w-52 bg-white border border-gray-200 rounded-lg shadow-lg z-50 transform scale-95 opacity-0 transition-all duration-200 ease-in-out pointer-events-none">
                            <a href="#" onclick="openProfileModal(); return false;" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user mr-2 text-gray-500"></i> Profile
                            </a>
                            <div class="border-t border-gray-200 my-1"></div>
                            <a href="#" onclick="confirmLogout(); return false;" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="bg-white border-b">
        <div class="max-w-8xl mx-auto px-4">
            <div class="flex justify-center space-x-2 md:space-x-6">
            <!-- Dashboard -->
            <a href="/ALERTPOINT/html/dashboard.php"
                class="nav-tab active flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2 text-gray-500  duration-200">
                <i class="fas fa-chart-bar text-lg"></i>
                <span class="mt-1 md:mt-0 md:ml-2 hidden md:inline">Dashboard</span>
            </a>

            <!-- Alerts -->
            <a href="#"
                class="nav-tab flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2 text-gray-500  duration-200">
                <i class="fas fa-bell text-lg"></i>
                <span class="mt-1 md:mt-0 md:ml-2 hidden md:inline">Alerts</span>
            </a>

            <!-- Reports -->
            <a href="#"
                class="nav-tab flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2 text-gray-500  duration-200">
                <i class="fas fa-chart-line text-lg"></i>
                <span class="mt-1 md:mt-0 md:ml-2 hidden md:inline">Reports</span>
            </a>

            <!-- Users  -->
            <a href="/ALERTPOINT/html/Users.php"
                class="nav-tab  flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2 text-gray-500 duration-200">
                <i class="fas fa-users text-lg"></i>
                <span class="mt-1 md:mt-0 md:ml-2 hidden md:inline">Users</span>
            </a>

            <!-- Evacuation Plan -->
            <a href="/ALERTPOINT/html/EvacuationPlan.php"
                class="nav-tab flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2 text-gray-500  duration-200">
                <i class="fas fa-route text-lg"></i>
                <span class="mt-1 md:mt-0 md:ml-2 hidden md:inline">Evacuation Plan</span>
            </a>

     <!-- Activity Logs -->
                <a href="/ALERTPOINT/html/Activity_Logs.php"
                    class="nav-tab  flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2  text-gray-500  duration-200">
                    <i class="fas fa-history text-lg"></i>
                    <span class="mt-1 md:mt-0 md:ml-2 hidden md:inline">Activity Logs</span>
                </a>

            <!-- Settings -->
            <a href="#"
                class="nav-tab flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2 text-gray-500  duration-200">
                <i class="fas fa-cog text-lg"></i>
                <span class="mt-1 md:mt-0 md:ml-2 hidden md:inline">Settings</span>
            </a>
            </div>
        </div>
    </nav>


     <main class="max-w-8xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-2 sm:px-0">
            
            <!-- Dashboard Tab -->
            <div id="dashboard-content" class="tab-content active">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
                    <div class="stat-card hover-card rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Current Water Level</p>
                                <p class="text-2xl font-bold text-blue-600" id="current-water-level">0.20m</p>
                                <p class="text-sm text-gray-500">ANKLE LEVEL</p>
                            </div>
                            <i class="fas fa-water text-3xl text-blue-500"></i>
                        </div>
                    </div>

                    <div class="stat-card hover-card rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Temperature</p>
                                <p class="text-2xl font-bold text-orange-600" id="current-temperature">29.1°C</p>
                                <p class="text-sm text-gray-500">Humidity: <span id="current-humidity">80.6%</span></p>
                            </div>
                            <i class="fas fa-thermometer-half text-3xl text-orange-500"></i>
                        </div>
                    </div>

                    <div class="stat-card hover-card rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Heat Index</p>
                                <p class="text-2xl font-bold text-red-600" id="heat-index">35.2°C</p>
                                <p class="text-sm text-yellow-600">High Risk</p>
                            </div>
                            <i class="fas fa-temperature-high text-3xl text-red-500"></i>
                        </div>
                    </div>

                    <div class="stat-card hover-card rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Active Alerts</p>
                                <p class="text-2xl font-bold text-red-600">1</p>
                                <p class="text-sm text-red-500">Water Level Alert</p>
                            </div>
                            <i class="fas fa-bell text-3xl text-red-500"></i>
                        </div>
                    </div>

                    <div class="stat-card hover-card rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Active Mobile Users</p>
                                <p class="text-2xl font-bold text-green-600">12</p>
                                <p class="text-sm text-gray-500">Using AlertPoint App</p>
                            </div>
                            <i class="fas fa-mobile-alt text-3xl text-green-500"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert Banner -->
            <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-lg mb-6">
                <div class="flex items-center">
                     <i class="fas fa-exclamation-triangle text-red-400 mr-3 text-2xl"></i>
                     <div>
                         <h3 class="text-sm font-medium text-red-800">Active Flood Alert</h3>
                          <p class="text-sm text-red-700">Water level reached KNEE LEVEL at 09:30 AM. Residents advised to prepare for evacuation.</p>
                    </div>
                </div>
            </div>





            <div class="container mx-auto px-1 py-1">
            <!-- Charts Row -->
            <div class="charts-row">
                <!-- Temperature Section -->
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Temperature Monitoring</h2>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-7">
                        
                        <!-- Temperature Gauge -->
                        <div class="metric-card relative">
                            <div class="status-indicator bg-orange-500"></div>
                            <div class="text-center mb-3">
                                <h3 class="text-xl font-bold text-gray-800 mb-2">Current Temperature</h3>
                            </div>
                            
                            <div class="modern-gauge">
                                <div class="gauge-icon temp-gradient">
                                    <i class="fas fa-thermometer-half"></i>
                                </div>
                                <svg class="gauge-progress" viewBox="0 0 200 200">
                                    <circle cx="100" cy="100" r="85" fill="none" stroke="#e5e7eb" stroke-width="8"/>
                                    <circle id="tempProgress" cx="100" cy="100" r="85" fill="none" 
                                            stroke="url(#tempGradient)" stroke-width="8" stroke-linecap="round"
                                            stroke-dasharray="534" stroke-dashoffset="534"
                                            transform="rotate(-90 100 100)" class="transition-all duration-1000 ease-out"/>
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
                        
                        <!-- Temperature Trend -->
                        <div class="metric-card hover-card">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Temperature Trend (24h)</h3>
                            <div class="chart-container">
                                <canvas id="temperatureTrendChart"></canvas>
                            </div>
                        </div>

                    </div>
                    
                    <!-- AI Alert for Temperature -->
                    <div class="ai-banner bg-orange-50 border-l-4 border-orange-400 p-4 rounded-lg mt-4">
                        <div class="flex items-start">
                            <i class="fas fa-robot text-orange-400 text-lg mr-3 mt-1"></i>
                            <div>
                                <h3 class="text-sm font-medium text-orange-800">AI Temperature Insight</h3>
                                <p class="text-sm text-orange-700">Temperature levels are within normal range. Consider ventilation if temperature exceeds 32°C for extended periods.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Humidity Section -->
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Humidity Monitoring</h2>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Humidity Gauge -->
                        <div class="metric-card relative">
                            <div class="status-indicator humidity-dot"></div>
                            <div class="text-center mb-6">
                                <h3 class="text-xl font-bold text-gray-800 mb-2">Current Humidity</h3>
                            </div>
                            
                            <div class="modern-gauge">
                                <div class="gauge-icon humidity-gradient">
                                    <i class="fas fa-tint"></i>
                                </div>
                                <svg class="gauge-progress" viewBox="0 0 200 200">
                                    <circle cx="100" cy="100" r="85" fill="none" stroke="#e5e7eb" stroke-width="8"/>
                                    <circle id="humidityProgress" cx="100" cy="100" r="85" fill="none" 
                                            stroke="url(#humidityGradient)" stroke-width="8" stroke-linecap="round"
                                            stroke-dasharray="534" stroke-dashoffset="534"
                                            transform="rotate(-90 100 100)" class="transition-all duration-1000 ease-out"/>
                                    <defs>
                                        <linearGradient id="humidityGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                            <stop offset="0%" style="stop-color:#4ecdc4"/>
                                            <stop offset="100%" style="stop-color:#26d0ce"/>
                                        </linearGradient>
                                    </defs>
                                </svg>
                                <div class="gauge-inner">
                                    <div class="gauge-text">
                                        <div id="humidityValue" class="gauge-value text-teal-600">75%</div>
                                        <div class="gauge-label">Humidity</div>
                                        <div id="humidityStatus" class="text-xs mt-1 font-medium text-green-600">Optimal</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Humidity Trend -->
                        <div class="metric-card hover-card">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Humidity Trend (24h)</h3>
                            <div class="chart-container">
                                <canvas id="humidityTrendChart"></canvas>
                            </div>
                        </div>

                    </div>
                    
                    <!-- AI Alert for Humidity -->
                    <div class="ai-banner bg-green-50 border-l-4 border-green-400 p-4 rounded-lg mt-4">
                        <div class="flex items-start">
                            <i class="fas fa-robot text-green-400 text-lg mr-3 mt-1"></i>
                            <div>
                                <h3 class="text-sm font-medium text-green-800">AI Humidity Insight</h3>
                                <p class="text-sm text-green-700">Humidity levels are optimal. Monitor for levels above 80% which may indicate poor ventilation.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Water Level Section -->
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Water Level Monitoring</h2>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Water Level Gauge -->
                        <div class="metric-card relative">
                            <div class="status-indicator bg-blue-500"></div>
                            <div class="text-center mb-6">
                                <h3 class="text-xl font-bold text-gray-800 mb-2">Current Water Level</h3>
                            </div>
                            
                            <div class="modern-gauge">
                                <div class="gauge-icon water-gradient">
                                    <i class="fas fa-water"></i>
                                </div>
                                <svg class="gauge-progress" viewBox="0 0 200 200">
                                    <circle cx="100" cy="100" r="85" fill="none" stroke="#e5e7eb" stroke-width="8"/>
                                    <circle id="waterProgress" cx="100" cy="100" r="85" fill="none" 
                                            stroke="url(#waterGradient)" stroke-width="8" stroke-linecap="round"
                                            stroke-dasharray="534" stroke-dashoffset="534"
                                            transform="rotate(-90 100 100)" class="transition-all duration-1000 ease-out"/>
                                    <defs>
                                        <linearGradient id="waterGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                            <stop offset="0%" style="stop-color:#3b82f6"/>
                                            <stop offset="100%" style="stop-color:#1e40af"/>
                                        </linearGradient>
                                    </defs>
                                </svg>
                                <div class="gauge-inner">
                                    <div class="gauge-text">
                                        <div id="waterValue" class="gauge-value text-blue-600">0.4m</div>
                                        <div class="gauge-label">Water Level</div>
                                        <div id="waterStatus" class="text-xs mt-1 font-medium text-green-600">Safe</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Water Level Trend -->
                        <div class="metric-card hover-card">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Water Level Trend (24h)</h3>
                            <div class="chart-container">
                                <canvas id="waterLevelChart"></canvas>
                            </div>
                        </div>

                    </div>
                    
                    <!-- AI Alert for Water Level -->
                    <div class="ai-banner bg-blue-50 border-l-4 border-blue-400 p-4 rounded-lg mt-4">
                        <div class="flex items-start">
                            <i class="fas fa-robot text-blue-400 text-lg mr-3 mt-1"></i>
                            <div>
                                <h3 class="text-sm font-medium text-blue-800">AI Water Level Insight</h3>
                                <p class="text-sm text-blue-700">Water level is stable. Alert will be triggered if level drops below 0.2m or exceeds 0.8m.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        </div>
    </main>


    <!-- Footer for AlertPoint -->
    <footer class="bg-gray-800 text-white mt-5">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Main Footer Content -->
            <div class="py-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <!-- Company Info -->
                    <div>
                        <div class="flex items-center mb-4">
                            <i class="fas fa-map-marker-alt text-2xl text-blue-400 mr-2"></i>
                            <h3 class="text-lg font-bold">AlertPoint</h3>
                        </div>
                        <p class="text-gray-300 text-sm mb-4">
                           Disaster Risk Management Monitoring system for Barangay 170, Caloocan City
                        </p>
                        <div class="flex space-x-3">
                            <i class="fab fa-facebook text-blue-400 hover:text-blue-300 cursor-pointer"></i>
                            <i class="fab fa-twitter text-blue-400 hover:text-blue-300 cursor-pointer"></i>
                            <i class="fas fa-envelope text-blue-400 hover:text-blue-300 cursor-pointer"></i>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div>
                        <h4 class="text-md font-semibold mb-4">Quick Links</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="/Prototype/html/Dashboard.html" class="text-gray-300 hover:text-white transition-colors">Dashboard</a></li>
                            <li><a href="/Prototype/html/Alerts.html" class="text-gray-300 hover:text-white transition-colors">Active Alerts</a></li>
                            <li><a href="/Prototype/html/Reports.html" class="text-gray-300 hover:text-white transition-colors">Reports</a></li>
                            <li><a href="/Prototype/html/Users.html" class="text-gray-300 hover:text-white transition-colors">User Management</a></li>
                        </ul>
                    </div>

                    <!-- Services -->
                    <div>
                        <h4 class="text-md font-semibold mb-4">Services</h4>
                        <ul class="space-y-2 text-sm text-gray-300">
                            <li>Flood Monitoring</li>
                            <li>Temperature Tracking</li>
                            <li>Humidity Analysis</li>
                            <li>Emergency Alerts</li>
                            <li>AI Insights</li>
                        </ul>
                    </div>

                    <!-- Contact Info -->
                    <div>
                        <h4 class="text-md font-semibold mb-4">Contact</h4>
                        <div class="space-y-2 text-sm text-gray-300">
                            <div class="flex items-center">
                                <i class="fas fa-map-marker-alt mr-2 text-blue-400"></i>
                                <span>Barangay 170, Caloocan City</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-phone mr-2 text-blue-400"></i>
                                <span>+63 (2) 8123-4567</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-envelope mr-2 text-blue-400"></i>
                                <span>admin@alertpoint.gov.ph</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-clock mr-2 text-blue-400"></i>
                                <span>24/7 Monitoring</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Footer -->
            <div class="border-t border-gray-700 py-4">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="text-sm text-gray-400 mb-2 md:mb-0">
                        © <span id="current-year">2025</span> AlertPoint Environmental Monitoring System. All rights reserved.
                    </div>
                    <div class="flex space-x-6 text-sm">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">Privacy Policy</a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">Terms of Service</a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">Support</a>
                    </div>
                </div>
                
                <!-- System Status -->
                <div class="mt-3 text-center">
                    <div class="flex justify-center items-center space-x-4 text-xs text-gray-400">
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-1 animate-pulse"></div>
                            <span>System Online</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-server mr-1 text-green-400"></i>
                            <span>Server Status: Active</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-wifi mr-1 text-green-400"></i>
                            <span>Connection: Stable</span>
                        </div>
                        <div class="flex items-center">
                            <span>Last Update: </span>
                            <span id="last-update-time" class="ml-1"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

   

    <!-- Include script -->
    <script src="/ALERTPOINT/javascript/DASHBOARD/dashboard.js"></script>
    <script src="/ALERTPOINT/javascript/footer.js"></script>
    <script src="/ALERTPOINT/javascript/nav-bar.js"></script>


</body>
</html>

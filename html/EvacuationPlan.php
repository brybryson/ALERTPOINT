<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlertPoint - Evacuation Plan</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>

     <!-- CSS FILES -->
    <link rel="stylesheet" href="/ALERTPOINT/css/footer.css">
    <link rel="stylesheet" href="/ALERTPOINT/css/EvacuationPlan.css">
    <link rel="stylesheet" href="/ALERTPOINT/css/nav-bar-2.css">



    <!-- Leaflet Map JS/CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet-control-geocoder/2.4.0/Control.Geocoder.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet-control-geocoder/2.4.0/Control.Geocoder.min.css"> -->

    <!-- Mapbox API -->
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet">

   

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>


    <!-- MapTiler API -->
    <script>
    // Add this after your existing Leaflet script
    // window.tileOptions = {
    //     osmHot: 'https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png',
    //     esriStreets: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}',
    //     cartoPositron: 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png'
    // };
    </script>

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
                class="nav-tab flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2 text-gray-500  duration-200">
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
                class="nav-tab  flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2 text-gray-500  duration-200">
                <i class="fas fa-users text-lg"></i>
                <span class="mt-1 md:mt-0 md:ml-2 hidden md:inline">Users</span>
            </a>

            <!-- Evacuation Plan -->
            <a href="/ALERTPOINT/html/EvacuationPlan.php"
                class="nav-tab active flex flex-col md:flex-row items-center md:items-start md:justify-start justify-center px-3 py-4 text-sm font-medium border-b-2 text-gray-500  duration-200">
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
            
            <!-- Page Header -->
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Emergency Evacuation Plan</h1>
                <p class="text-gray-600">Interactive evacuation routes and emergency procedures for Barangay 170, Caloocan City</p>
            </div>

            <!-- Emergency Status Bar -->
            <div id="emergency-status" class="bg-green-50 border-l-4 border-green-400 p-4 rounded-lg mb-6">
                <div class="flex items-center">
                    <i class="fas fa-shield-alt text-green-400 mr-3 text-2xl"></i>
                    <div>
                        <h3 class="text-sm font-medium text-green-800">Current Status: NORMAL</h3>
                        <p class="text-sm text-green-700">No active emergencies. All evacuation routes are clear and accessible.</p>
                    </div>
                </div>
            </div>

            <!-- Control Panel -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
                <!-- Quick Actions -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                        <div class="space-y-3">
                            <button id="activate-emergency" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                Activate Emergency
                            </button>
                            <button id="test-evacuation" class="w-full bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition-colors">
                                <i class="fas fa-play mr-2"></i>
                                Test Evacuation
                            </button>
                            <button id="send-alerts" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-bullhorn mr-2"></i>
                                Send Mass Alert
                            </button>
                            <button id="deactivate-emergency" class="w-full bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors hidden">
                                <i class="fas fa-shield-alt mr-2"></i>
                                Return to Normal
                            </button>
                        </div>
                        
                        <div class="mt-6">
                            <h4 class="text-md font-semibold text-gray-900 mb-2">Map Controls</h4>
                            <div class="space-y-2">
                                <button id="add-hardware" class="w-full bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                                    <i class="fas fa-microchip mr-2"></i>
                                    Add Hardware Pin
                                </button>
                                <button id="add-evacuation-center" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                    <i class="fas fa-home mr-2"></i>
                                    Add Evacuation Center
                                </button>
                            </div>
                        </div>

                        <div class="mt-6">
                            <h4 class="text-md font-semibold text-gray-900 mb-2">Route Options</h4>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" id="route-primary" checked class="mr-2 text-blue-600">
                                    <span class="text-sm">Primary Routes</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" id="route-secondary" checked class="mr-2 text-blue-600">
                                    <span class="text-sm">Secondary Routes</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" id="route-emergency" class="mr-2 text-blue-600">
                                    <span class="text-sm">Emergency Only</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Interactive Map -->
                <div class="lg:col-span-3">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex justify-between items-center mb-4">
                            <div class="flex items-center space-x-4">
                                <h3 class="text-lg font-semibold text-gray-900">Evacuation Map</h3>
                                
                                <!-- Search Bar with Autocomplete -->
                                <div class="relative">
                                    <input type="text" 
                                        id="address-search" 
                                        placeholder="Search address (e.g., SM Fairview, Quezon City Hall)..." 
                                        class="w-80 pl-4 pr-10 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        autocomplete="off"
                                        maxlength="150"
                                        style="padding-right: 40px;">
                                    <button id="search-btn" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-blue-600 w-8 h-8 flex items-center justify-center">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <!-- Autocomplete dropdown -->
                                    <div id="search-suggestions" class="absolute top-full left-0 right-0 bg-white border border-gray-300 rounded-b-lg shadow-lg max-h-60 overflow-y-auto z-50 hidden">
                                        <!-- Suggestions will be populated here -->
                                    </div>
                                </div>

                            </div>
                            <div class="flex space-x-2">
                                <button id="zoom-in" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <button id="zoom-out" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <button id="reset-view" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
                                    Reset View
                                </button>
                            </div>
                        </div>
                        <div id="evacuation-map" class="w-full h-100 rounded-lg border"></div>
                    </div>
                </div>
            </div>

            <!-- Evacuation Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-users text-2xl text-blue-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Population</p>
                            <p class="text-2xl font-bold text-gray-900" id="total-population">12,450</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-home text-2xl text-green-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Evacuation Centers</p>
                            <p class="text-2xl font-bold text-gray-900" id="evacuation-centers-count">0</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-microchip text-2xl text-purple-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Hardware Units</p>
                            <p class="text-2xl font-bold text-gray-900" id="hardware-count">0</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-clock text-2xl text-red-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Est. Evacuation Time</p>
                            <p class="text-2xl font-bold text-gray-900" id="evacuation-time">45 min</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Evacuation Centers and Hardware -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Evacuation Centers List -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Evacuation Centers</h3>
                    <div class="space-y-4" id="evacuation-centers-list">
                        <p class="text-gray-500 text-sm">No evacuation centers added yet. Click "Add Evacuation Center" to start.</p>
                    </div>
                </div>

                <!-- Hardware Units -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">AlertPoint Hardware</h3>
                    <div class="space-y-4" id="hardware-list">
                        <p class="text-gray-500 text-sm">No hardware units added yet. Click "Add Hardware Pin" to start.</p>
                    </div>
                </div>
            </div>

        </div>
    </main>

    
    <!-- Add Hardware Modal -->
    <!-- Add Hardware Modal -->
    <div id="hardware-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-[9999]">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold mb-4">Add Hardware Unit</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hardware Name</label>
                        <input type="text" id="hardware-name" class="w-full border border-gray-300 rounded-md px-3 py-2" placeholder="e.g., Barangay Unit 1">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hardware Type</label>
                        <select id="hardware-type" class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="barangay">Barangay Unit</option>
                            <option value="creek">Creek Unit</option>
                        </select>
                    </div>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <div class="flex">
                            <i class="fas fa-info-circle text-blue-400 mt-0.5 mr-2"></i>
                            <div class="text-sm">
                                <p class="text-blue-800 font-medium">Instructions</p>
                                <p class="text-blue-700 mt-1">Click anywhere on the main map to place the hardware unit at that location.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button id="cancel-hardware" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
                    <button id="confirm-hardware" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">Ready to Place</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Add Evacuation Center Modal -->
    <!-- Add Evacuation Center Modal -->
    <div id="center-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-[9999]">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold mb-4">Add Evacuation Center</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Center Name</label>
                        <input type="text" id="center-name" class="w-full border border-gray-300 rounded-md px-3 py-2" placeholder="e.g., Community Center">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Capacity</label>
                        <input type="number" id="center-capacity" class="w-full border border-gray-300 rounded-md px-3 py-2" placeholder="e.g., 500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="center-status" class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="active">Active</option>
                            <option value="standby">Standby</option>
                            <option value="full">Full</option>
                        </select>
                    </div>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                        <div class="flex">
                            <i class="fas fa-info-circle text-green-400 mt-0.5 mr-2"></i>
                            <div class="text-sm">
                                <p class="text-green-800 font-medium">Instructions</p>
                                <p class="text-green-700 mt-1">Click anywhere on the main map to place the evacuation center at that location.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button id="cancel-center" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
                    <button id="confirm-center" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Ready to Place</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Center Modal -->
    <div id="edit-center-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-[9999]">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold mb-4">Edit Evacuation Center</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Center Name</label>
                        <input type="text" id="edit-center-name" class="w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Capacity</label>
                        <input type="number" id="edit-center-capacity" class="w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="edit-center-status" class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="active">Active</option>
                            <option value="standby">Standby</option>
                            <option value="full">Full</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button id="cancel-edit-center" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
                    <button id="delete-center" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Delete</button>
                    <button id="save-center" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ALERT MODAL -->
    <div id="alert-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-[9999]">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg p-6 w-full max-w-lg">
                <h3 class="text-lg font-semibold mb-4">Send Mass Alert</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alert Level</label>
                        <select id="alert-level" class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="green">Green Code - Advisory</option>
                            <option value="yellow">Yellow Code - Caution</option>
                            <option value="orange">Orange Code - Warning</option>
                            <option value="red">Red Code - Emergency</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alert Message</label>
                        <textarea id="alert-message" rows="4" class="w-full border border-gray-300 rounded-md px-3 py-2" placeholder="Enter your alert message here..."></textarea>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-md">
                        <p class="text-sm text-gray-600">Preview:</p>
                        <div id="alert-preview" class="mt-2 p-2 border rounded text-sm bg-white">
                            <span class="font-semibold" id="preview-level">GREEN CODE</span>: <span id="preview-message">Your message will appear here...</span>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button id="cancel-alert" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
                    <button id="send-alert" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Send Alert</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL FOR SETTING NEW BARANGAY HALL LOCATION (add after existing modals) -->
    <div id="set-barangay-location-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-[9999]">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold mb-4">Set New Barangay Hall Location</h3>
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-gray-600 mb-2">Selected Address:</p>
                        <p id="selected-address" class="text-sm font-medium text-gray-900 bg-gray-50 p-2 rounded"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-2">Coordinates:</p>
                        <p id="selected-coordinates" class="text-xs text-gray-500 bg-gray-50 p-2 rounded font-mono"></p>
                    </div>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                        <div class="flex">
                            <i class="fas fa-exclamation-triangle text-yellow-400 mt-0.5 mr-2"></i>
                            <div class="text-sm">
                                <p class="text-yellow-800 font-medium">Important Notice</p>
                                <p class="text-yellow-700 mt-1">This will update the main reference point for all evacuation routes and distance calculations.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button id="cancel-location-change" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
                    <button id="confirm-location-change" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Set as Barangay Hall</button>
                </div>
            </div>
        </div>
    </div>

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



    <script src="/ALERTPOINT/javascript/footer.js"></script>
    <script src="/ALERTPOINT/javascript/EVACUATION/EvacuationPlan.js"></script>
    <script src="/ALERTPOINT/javascript/nav-bar.js"></script>



</body>
</html>
// Time and Date
function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleString('en-PH', {
        timeZone: 'Asia/Manila',
        year: 'numeric',
        month: 'long',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    const timeElement = document.getElementById('current-time');
    if (timeElement) {
        timeElement.textContent = timeString;
    }
}

setInterval(updateTime, 1000);
updateTime(); // call on initial load

            // Initialize map variables
            let map;
            let evacuationRoutes = [];
            let evacuationCenters = [];
            let hardwareUnits = [];
            let modalHardwareMap;
            let modalCenterMap;
            let editingCenterId = null;
            let currentBarangayLocation = [14.73927547331345, 121.03462297276172]; // Default Barangay 170
            let searchResultMarker = null;
            let barangayHallMarker = null;
            let selectedSearchResult = null;

            function initializeMap() {
                // Initialize map with enhanced tile layers
                map = L.map('evacuation-map').setView(currentBarangayLocation, 16);

                // Multiple tile layer options for better detail
                const streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19
                });

                const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                    attribution: 'Tiles © Esri — Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
                    maxZoom: 18
                });

                const topoLayer = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
                    attribution: 'Map data: © OpenStreetMap contributors, SRTM | Map style: © OpenTopoMap (CC-BY-SA)',
                    maxZoom: 17
                });

                // Add default layer
                streetLayer.addTo(map);

                // Layer control
                const baseLayers = {
                    "Street Map": streetLayer,
                    "Satellite": satelliteLayer,
                    "Topographic": topoLayer
                };
                L.control.layers(baseLayers).addTo(map);

                // Add current Barangay Hall marker
                updateBarangayHallMarker();

                // Initialize geocoder for search
                initializeGeocoder();
            }

            // Enhanced search with autocomplete
// Enhanced search with Mapbox Geocoding API (free tier)
let searchTimeout;
let currentSuggestions = [];

function initializeGeocoder() {
    const searchInput = document.getElementById('address-search');
    const suggestionsDiv = document.getElementById('search-suggestions');
    
    // Setup input event for autocomplete
    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.trim();
        
        // Clear existing timeout
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        if (query.length < 2) {
            hideSuggestions();
            return;
        }
        
        // Debounce search requests
        searchTimeout = setTimeout(() => {
            searchForSuggestions(query);
        }, 300);
    });
    
    // Handle search button click
    document.getElementById('search-btn').addEventListener('click', performSearch);
    
    // Handle Enter key
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSearch();
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            navigateSuggestions('down');
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            navigateSuggestions('up');
        } else if (e.key === 'Escape') {
            hideSuggestions();
        }
    });
    
    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.relative')) {
            hideSuggestions();
        }
    });
}

function searchForSuggestions(query) {
    // Using OpenStreetMap Nominatim API which is free and works well for Philippines
    const encodedQuery = encodeURIComponent(query + ', Philippines');
    const url = `https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=5&countrycodes=ph&q=${encodedQuery}`;
    
    // Show loading state
    showSuggestionsLoading();
    
    fetch(url)
        .then(response => response.json())
        .then(results => {
            // Transform results to match expected format
            const transformedResults = results.map(result => ({
                name: result.display_name,
                lat: parseFloat(result.lat),
                lng: parseFloat(result.lon),
                center: { lat: parseFloat(result.lat), lng: parseFloat(result.lon) },
                place_name: result.display_name,
                properties: result
            }));
            
            currentSuggestions = transformedResults;
            displaySuggestions(transformedResults, query);
        })
        .catch(error => {
            console.error('Search error:', error);
            displayNoResults(query);
        });
}

function showSuggestionsLoading() {
    const suggestionsDiv = document.getElementById('search-suggestions');
    suggestionsDiv.innerHTML = `
        <div class="p-3 text-center text-gray-500">
            <i class="fas fa-spinner fa-spin mr-2"></i>Searching Philippines locations...
        </div>
    `;
    suggestionsDiv.classList.remove('hidden');
}

function displaySuggestions(results, query) {
    const suggestionsDiv = document.getElementById('search-suggestions');
    
    if (results.length === 0) {
        displayNoResults(query);
        return;
    }
    
    const suggestionsHTML = results.map((result, index) => {
        // Get a cleaner name for display
        let displayName = result.name;
        let subText = '';
        
        // Try to extract meaningful parts from the display name
        const parts = result.name.split(',');
        if (parts.length > 1) {
            displayName = parts[0].trim();
            subText = parts.slice(1, 3).join(',').trim();
        }
        
        return `
            <div class="suggestion-item p-3 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0" 
                 data-index="${index}"
                 onclick="selectSuggestion(${index})">
                <div class="flex items-start">
                    <i class="fas fa-map-marker-alt text-red-500 mt-1 mr-3"></i>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-gray-900 truncate">${highlightMatch(displayName, query)}</div>
                        <div class="text-xs text-gray-500 truncate">${subText}</div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    suggestionsDiv.innerHTML = suggestionsHTML;
    suggestionsDiv.classList.remove('hidden');
}

function displayNoResults(query) {
    const suggestionsDiv = document.getElementById('search-suggestions');
    suggestionsDiv.innerHTML = `
        <div class="p-3 text-center text-gray-500">
            <i class="fas fa-exclamation-circle mr-2"></i>No results found for "${query}"
            <div class="text-xs mt-1">Try searching for "SM North", "Quezon City Hall", or "Manila"</div>
        </div>
    `;
    suggestionsDiv.classList.remove('hidden');
}

function highlightMatch(text, query) {
    if (!query) return text;
    const regex = new RegExp(`(${escapeRegex(query)})`, 'gi');
    return text.replace(regex, '<mark class="bg-yellow-200 px-1">$1</mark>');
}

function escapeRegex(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function hideSuggestions() {
    document.getElementById('search-suggestions').classList.add('hidden');
    currentSuggestions = [];
}

function selectSuggestion(index) {
    const result = currentSuggestions[index];
    if (!result) return;
    
    const searchInput = document.getElementById('address-search');
    // Use the first part of the name for cleaner display
    const displayName = result.name.split(',')[0].trim();
    searchInput.value = displayName;
    
    performSearchWithResult(result);
    hideSuggestions();
}

function navigateSuggestions(direction) {
    const suggestions = document.querySelectorAll('.suggestion-item');
    const currentActive = document.querySelector('.suggestion-item.bg-blue-100');
    
    let newIndex = 0;
    if (currentActive) {
        const currentIndex = parseInt(currentActive.dataset.index);
        newIndex = direction === 'down' ? 
            Math.min(currentIndex + 1, suggestions.length - 1) : 
            Math.max(currentIndex - 1, 0);
        currentActive.classList.remove('bg-blue-100');
    }
    
    if (suggestions[newIndex]) {
        suggestions[newIndex].classList.add('bg-blue-100');
        // Update search input with selected suggestion
        const result = currentSuggestions[newIndex];
        if (result) {
            const displayName = result.name.split(',')[0].trim();
            document.getElementById('address-search').value = displayName;
        }
    }
}

function performSearch() {
    const query = document.getElementById('address-search').value.trim();
    if (!query) return;
    
    // If we have an active suggestion, use it
    const activeSuggestion = document.querySelector('.suggestion-item.bg-blue-100');
    if (activeSuggestion) {
        const index = parseInt(activeSuggestion.dataset.index);
        selectSuggestion(index);
        return;
    }
    
    // Otherwise perform a new search
    const encodedQuery = encodeURIComponent(query + ', Philippines');
    const url = `https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=1&countrycodes=ph&q=${encodedQuery}`;
    
    // Show loading state
    const searchBtn = document.getElementById('search-btn');
    const originalContent = searchBtn.innerHTML;
    searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    fetch(url)
        .then(response => response.json())
        .then(results => {
            searchBtn.innerHTML = originalContent;
            hideSuggestions();
            
            if (results.length > 0) {
                const result = {
                    name: results[0].display_name,
                    center: { 
                        lat: parseFloat(results[0].lat), 
                        lng: parseFloat(results[0].lon) 
                    }
                };
                performSearchWithResult(result);
            } else {
                alert('Location not found. Please try searching for specific places like "SM North EDSA", "Quezon City Hall", or "Manila City Hall".');
            }
        })
        .catch(error => {
            searchBtn.innerHTML = originalContent;
            console.error('Search error:', error);
            alert('Search failed. Please check your internet connection and try again.');
        });
}

function performSearchWithResult(result) {
    const latlng = result.center;
    
    // Remove previous search result marker
    if (searchResultMarker) {
        map.removeLayer(searchResultMarker);
    }
    
    // Add new search result marker
    searchResultMarker = L.marker([latlng.lat, latlng.lng], {
        icon: L.divIcon({
            className: 'search-result-marker',
            html: '<i class="fas fa-map-pin text-2xl text-red-500 drop-shadow-lg"></i>',
            iconSize: [30, 30],
            iconAnchor: [15, 30]
        })
    }).addTo(map);
    
    // Create popup with option to set as barangay hall
    const cleanName = result.name.split(',')[0].trim();
    const popupContent = `
        <div class="search-result-popup">
            <b>📍 Search Result</b><br>
            <p class="text-sm mt-1 mb-3">${cleanName}</p>
            <button onclick="setAsBarangayHall('${cleanName.replace(/'/g, "\\'")}', ${latlng.lat}, ${latlng.lng})" 
                    class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 transition-colors">
                <i class="fas fa-home mr-1"></i>Set as Barangay Hall
            </button>
        </div>
    `;
    
    searchResultMarker.bindPopup(popupContent).openPopup();
    
    // Center map on result with appropriate zoom
    map.setView([latlng.lat, latlng.lng], 17);
    
    // Store result for modal
    selectedSearchResult = {
        name: cleanName,
        lat: latlng.lat,
        lng: latlng.lng
    };
    
    console.log('Search successful:', cleanName, latlng);
}

            // ADD THIS NEW FUNCTION TO SET BARANGAY HALL LOCATION
            function setAsBarangayHall(name, lat, lng) {
                selectedSearchResult = { name, lat, lng };
                document.getElementById('selected-address').textContent = name;
                document.getElementById('selected-coordinates').textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                document.getElementById('set-barangay-location-modal').classList.remove('hidden');
            }

            // ADD THIS NEW FUNCTION TO UPDATE BARANGAY HALL MARKER
            function updateBarangayHallMarker() {
                // Remove existing marker
                if (barangayHallMarker) {
                    map.removeLayer(barangayHallMarker);
                }
                
                // Add new barangay hall marker
                barangayHallMarker = L.marker(currentBarangayLocation, {
                    icon: L.divIcon({
                        className: 'barangay-hall-marker',
                        html: '<i class="fas fa-building text-2xl text-blue-600"></i>',
                        iconSize: [35, 35],
                        iconAnchor: [17, 35]
                    })
                }).addTo(map)
                .bindPopup("<b>Barangay Hall</b><br>Main Command Center")
                .openPopup();
            }


            // ADD THESE EVENT LISTENERS TO THE EXISTING DOMContentLoaded SECTION
            document.getElementById('confirm-location-change').addEventListener('click', function() {
                if (selectedSearchResult) {
                    // Update barangay location
                    currentBarangayLocation = [selectedSearchResult.lat, selectedSearchResult.lng];
                    
                    // Update marker
                    updateBarangayHallMarker();
                    
                    // Recalculate all routes
                    updateRouteDisplay();
                    updateEvacuationTimeEstimate();
                    
                    // Remove search result marker since it's now the barangay hall
                    if (searchResultMarker) {
                        map.removeLayer(searchResultMarker);
                        searchResultMarker = null;
                    }
                    
                    // Clear search
                    document.getElementById('address-search').value = '';
                    
                    // Close modal
                    document.getElementById('set-barangay-location-modal').classList.add('hidden');
                    
                    // Show success message
                    alert('Barangay Hall location updated successfully!');
                    
                    // Center map on new location
                    map.setView(currentBarangayLocation, 16);
                }
            });

            document.getElementById('cancel-location-change').addEventListener('click', function() {
                document.getElementById('set-barangay-location-modal').classList.add('hidden');
            });


            let modalMode = null; // Track which modal is active
            let modalClickHandler = null; // Store click handler reference

            function initializeModalMaps() {
                // // Initialize hardware modal map
                // modalHardwareMap = L.map('modal-hardware-map').setView([14.73927547331345, 121.03462297276172], 16);
                // L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                //     attribution: '© OpenStreetMap contributors'
                // }).addTo(modalHardwareMap);

                // // Initialize center modal map  
                // modalCenterMap = L.map('modal-center-map').setView([14.73927547331345, 121.03462297276172], 16);
                // L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                //     attribution: '© OpenStreetMap contributors'
                // }).addTo(modalCenterMap);

                // // Add click handlers
                // modalHardwareMap.on('click', function(e) {
                //     addHardwareToMap(e.latlng);
                //     document.getElementById('hardware-modal').classList.add('hidden');
                // });

                // modalCenterMap.on('click', function(e) {
                //     addCenterToMap(e.latlng);
                //     document.getElementById('center-modal').classList.add('hidden');
                // });
            }

            // Hardware management
            function addHardwareToMap(latlng, name, type) {
                // Use parameters if provided, otherwise get from form (for backward compatibility)
                if (!name) {
                    name = document.getElementById('hardware-name').value;
                    type = document.getElementById('hardware-type').value;
                }
                
                if (!name) {
                    alert('Please enter a hardware name');
                    return;
                }

                const hardware = {
                    id: Date.now(),
                    name: name,
                    type: type,
                    lat: latlng.lat,
                    lng: latlng.lng
                };

                const icon = type === 'barangay' ? 'fas fa-building' : 'fas fa-water';
                const color = type === 'barangay' ? 'text-blue-600' : 'text-teal-600';

                const marker = L.marker([latlng.lat, latlng.lng], {
                    icon: L.divIcon({
                        className: 'hardware-marker',
                        html: `<i class="${icon} text-xl ${color}"></i>`,
                        iconSize: [25, 25],
                        iconAnchor: [12, 25]
                    })
                }).addTo(map)
                .bindPopup(`<b>${name}</b><br>Type: ${type === 'barangay' ? 'Barangay Unit' : 'Creek Unit'}`);

                hardware.marker = marker;
                hardwareUnits.push(hardware);
                
                updateHardwareList();
                updateStats();
                
                // Clear form
                document.getElementById('hardware-name').value = '';
            }

            // Evacuation center management
            function addCenterToMap(latlng, name, capacity, status) {
                // Use parameters if provided, otherwise get from form (for backward compatibility)
                if (!name) {
                    name = document.getElementById('center-name').value;
                    capacity = document.getElementById('center-capacity').value;
                    status = document.getElementById('center-status').value;
                }
                
                if (!name || !capacity) {
                    alert('Please fill in all fields');
                    return;
                }

                const center = {
                    id: Date.now(),
                    name: name,
                    capacity: parseInt(capacity),
                    status: status,
                    lat: latlng.lat,
                    lng: latlng.lng
                };

                const statusColor = getStatusColor(status);
                const marker = L.marker([latlng.lat, latlng.lng], {
                    icon: L.divIcon({
                        className: 'center-marker',
                        html: `<i class="fas fa-home text-xl ${statusColor}"></i>`,
                        iconSize: [25, 25],
                        iconAnchor: [12, 25]
                    })
                }).addTo(map)
                .bindPopup(`
                    <b>${name}</b><br>
                    Capacity: ${capacity} persons<br>
                    Status: <span class="${statusColor.replace('text-', 'text-')}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>
                `);

                center.marker = marker;
                evacuationCenters.push(center);
                
                updateCentersList();
                updateStats();
                updateRouteDisplay();

                // Clear form
                document.getElementById('center-name').value = '';
                document.getElementById('center-capacity').value = '';
            }

            function getStatusColor(status) {
                switch(status) {
                    case 'active': return 'text-green-600';
                    case 'standby': return 'text-yellow-600';
                    case 'full': return 'text-red-600';
                    default: return 'text-gray-600';
                }
            }

            function updateHardwareList() {
                const list = document.getElementById('hardware-list');
                
                if (hardwareUnits.length === 0) {
                    list.innerHTML = '<p class="text-gray-500 text-sm">No hardware units added yet. Click "Add Hardware Pin" to start.</p>';
                    return;
                }

                list.innerHTML = hardwareUnits.map(hardware => `
                    <div class="border rounded-lg p-4 hover:bg-gray-50 cursor-pointer" onclick="focusOnMarker(${hardware.lat}, ${hardware.lng})">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-semibold text-gray-900">${hardware.name}</h4>
                                <p class="text-sm text-gray-600">Type: ${hardware.type === 'barangay' ? 'Barangay Unit' : 'Creek Unit'}</p>
                                <p class="text-xs text-gray-500">${hardware.lat.toFixed(6)}, ${hardware.lng.toFixed(6)}</p>
                            </div>
                            <i class="fas fa-${hardware.type === 'barangay' ? 'building' : 'water'} ${hardware.type === 'barangay' ? 'text-blue-600' : 'text-teal-600'}"></i>
                        </div>
                    </div>
                `).join('');
            }

            function updateCentersList() {
                const list = document.getElementById('evacuation-centers-list');
                
                if (evacuationCenters.length === 0) {
                    list.innerHTML = '<p class="text-gray-500 text-sm">No evacuation centers added yet. Click "Add Evacuation Center" to start.</p>';
                    return;
                }

                list.innerHTML = evacuationCenters.map(center => `
                    <div class="border rounded-lg p-4 hover:bg-gray-50">
                        <div class="flex justify-between items-start">
                            <div class="cursor-pointer flex-1" onclick="focusOnMarker(${center.lat}, ${center.lng})">
                                <h4 class="font-semibold text-gray-900">${center.name}</h4>
                                <p class="text-sm text-gray-600">Capacity: ${center.capacity} persons</p>
                                <p class="text-xs ${getStatusColor(center.status)} font-medium">${center.status.charAt(0).toUpperCase() + center.status.slice(1)}</p>
                            </div>
                            <div class="flex space-x-2">
                                <button onclick="editCenter(${center.id})" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('');
            }

            function editCenter(centerId) {
                const center = evacuationCenters.find(c => c.id === centerId);
                if (!center) return;

                editingCenterId = centerId;
                document.getElementById('edit-center-name').value = center.name;
                document.getElementById('edit-center-capacity').value = center.capacity;
                document.getElementById('edit-center-status').value = center.status;
                document.getElementById('edit-center-modal').classList.remove('hidden');
            }

            function saveCenter() {
                const center = evacuationCenters.find(c => c.id === editingCenterId);
                if (!center) return;

                const name = document.getElementById('edit-center-name').value;
                const capacity = document.getElementById('edit-center-capacity').value;
                const status = document.getElementById('edit-center-status').value;

                if (!name || !capacity) {
                    alert('Please fill in all fields');
                    return;
                }

                center.name = name;
                center.capacity = parseInt(capacity);
                center.status = status;

                // Update marker
                const statusColor = getStatusColor(status);
                center.marker.setIcon(L.divIcon({
                    className: 'center-marker',
                    html: `<i class="fas fa-home text-xl ${statusColor}"></i>`,
                    iconSize: [25, 25],
                    iconAnchor: [12, 25]
                }));

                center.marker.setPopupContent(`
                    <b>${name}</b><br>
                    Capacity: ${capacity} persons<br>
                    Status: <span class="${statusColor.replace('text-', 'text-')}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>
                `);

                updateCentersList();
                document.getElementById('edit-center-modal').classList.add('hidden');
                editingCenterId = null;
            }

            function deleteCenter() {
                if (!confirm('Are you sure you want to delete this evacuation center?')) return;

                const centerIndex = evacuationCenters.findIndex(c => c.id === editingCenterId);
                if (centerIndex === -1) return;

                const center = evacuationCenters[centerIndex];
                map.removeLayer(center.marker);
                evacuationCenters.splice(centerIndex, 1);

                updateCentersList();
                updateStats();
                updateRouteDisplay();

                document.getElementById('edit-center-modal').classList.add('hidden');
                editingCenterId = null;
            }

            function focusOnMarker(lat, lng) {
                map.setView([lat, lng], 18);
                
                // Find and open popup for the marker at this location
                const allMarkers = [...hardwareUnits, ...evacuationCenters];
                const marker = allMarkers.find(item => 
                    Math.abs(item.lat - lat) < 0.0001 && Math.abs(item.lng - lng) < 0.0001
                );
                if (marker && marker.marker) {
                    marker.marker.openPopup();
                }
            }

            function updateStats() {
                document.getElementById('evacuation-centers-count').textContent = evacuationCenters.length;
                document.getElementById('hardware-count').textContent = hardwareUnits.length;
            }

            // Event listeners
            document.getElementById('zoom-in').addEventListener('click', () => map.zoomIn());
            document.getElementById('zoom-out').addEventListener('click', () => map.zoomOut());
            document.getElementById('reset-view').addEventListener('click', () => {
                map.setView(currentBarangayLocation, 16); // Updated to use current location
            });

            // Hardware modal handlers - UPDATED
            // Hardware modal handlers - UPDATED
            document.getElementById('add-hardware').addEventListener('click', () => {
                document.getElementById('hardware-modal').classList.remove('hidden');
            });

            document.getElementById('cancel-hardware').addEventListener('click', () => {
                document.getElementById('hardware-modal').classList.add('hidden');
                cancelModalMode();
            });

            document.getElementById('confirm-hardware').addEventListener('click', () => {
                const name = document.getElementById('hardware-name').value.trim();
                const type = document.getElementById('hardware-type').value;
                
                if (!name) {
                    alert('Please enter a hardware name');
                    return;
                }
                
                // Close modal and activate placement mode
                document.getElementById('hardware-modal').classList.add('hidden');
                activateHardwarePlacementMode(name, type);
            });

            // Center modal handlers - UPDATED
            // Center modal handlers - UPDATED
            document.getElementById('add-evacuation-center').addEventListener('click', () => {
                document.getElementById('center-modal').classList.remove('hidden');
            });

            document.getElementById('cancel-center').addEventListener('click', () => {
                document.getElementById('center-modal').classList.add('hidden');
                cancelModalMode();
            });

            document.getElementById('confirm-center').addEventListener('click', () => {
                const name = document.getElementById('center-name').value.trim();
                const capacity = document.getElementById('center-capacity').value;
                const status = document.getElementById('center-status').value;
                
                if (!name || !capacity) {
                    alert('Please fill in all fields');
                    return;
                }
                
                // Close modal and activate placement mode
                document.getElementById('center-modal').classList.add('hidden');
                activateCenterPlacementMode(name, capacity, status);
            });

            // Edit center modal handlers
            document.getElementById('cancel-edit-center').addEventListener('click', () => {
                document.getElementById('edit-center-modal').classList.add('hidden');
                editingCenterId = null;
            });

            document.getElementById('save-center').addEventListener('click', saveCenter);
            document.getElementById('delete-center').addEventListener('click', deleteCenter);

            // Emergency activation
            document.getElementById('activate-emergency').addEventListener('click', () => {
                const statusBar = document.getElementById('emergency-status');
                statusBar.className = 'bg-red-50 border-l-4 border-red-400 p-4 rounded-lg mb-6';
                statusBar.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-400 mr-3 text-2xl animate-pulse"></i>
                        <div>
                            <h3 class="text-sm font-medium text-red-800">EMERGENCY ACTIVATED</h3>
                            <p class="text-sm text-red-700">All evacuation routes are now active. Residents should proceed to nearest evacuation center.</p>
                        </div>
                    </div>
                `;
                
                // Show deactivate button, hide activate button
                document.getElementById('activate-emergency').classList.add('hidden');
                document.getElementById('deactivate-emergency').classList.remove('hidden');
                
                // Show all evacuation routes
                showAllEvacuationRoutes();
                
                alert('Emergency evacuation protocol activated. Mass alerts will be sent to all registered users.');
            });

            document.getElementById('test-evacuation').addEventListener('click', () => {
                alert('Test evacuation drill initiated. This will send test alerts to all registered users.');
            });

            document.getElementById('send-alerts').addEventListener('click', () => {
                alert('Mass alert sent to all registered users in Barangay 170.');
            });



            // Deactivate Emergency
            document.getElementById('deactivate-emergency').addEventListener('click', () => {
                const statusBar = document.getElementById('emergency-status');
                statusBar.className = 'bg-green-50 border-l-4 border-green-400 p-4 rounded-lg mb-6';
                statusBar.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas fa-shield-alt text-green-400 mr-3 text-2xl"></i>
                        <div>
                            <h3 class="text-sm font-medium text-green-800">Current Status: NORMAL</h3>
                            <p class="text-sm text-green-700">No active emergencies. All evacuation routes are clear and accessible.</p>
                        </div>
                    </div>
                `;
                
                // Show activate button, hide deactivate button
                document.getElementById('activate-emergency').classList.remove('hidden');
                document.getElementById('deactivate-emergency').classList.add('hidden');
                
                // Clear routes if not checked
                updateRouteDisplay();
                
                alert('Emergency status returned to normal.');
            });

            // Mass alert modal handlers
            document.getElementById('send-alerts').addEventListener('click', () => {
                document.getElementById('alert-modal').classList.remove('hidden');
            });

            document.getElementById('cancel-alert').addEventListener('click', () => {
                document.getElementById('alert-modal').classList.add('hidden');
            });

            document.getElementById('alert-level').addEventListener('change', updateAlertPreview);
            document.getElementById('alert-message').addEventListener('input', updateAlertPreview);

            document.getElementById('send-alert').addEventListener('click', () => {
                const level = document.getElementById('alert-level').value;
                const message = document.getElementById('alert-message').value;
                
                if (!message.trim()) {
                    alert('Please enter an alert message');
                    return;
                }
                
                const levelText = document.getElementById('alert-level').selectedOptions[0].text;
                alert(`${levelText} alert sent to all registered users:\n"${message}"`);
                
                // Clear form and close modal
                document.getElementById('alert-message').value = '';
                document.getElementById('alert-modal').classList.add('hidden');
            });

            // Route checkbox handlers
            document.getElementById('route-primary').addEventListener('change', updateRouteDisplay);
            document.getElementById('route-secondary').addEventListener('change', updateRouteDisplay);
            document.getElementById('route-emergency').addEventListener('change', updateRouteDisplay);







            // Close modals when clicking outside
            window.addEventListener('click', (e) => {
              const modals = ['hardware-modal', 'center-modal', 'edit-center-modal', 'alert-modal'];
              modals.forEach(modalId => {
                  const modal = document.getElementById(modalId);
                  if (e.target === modal) {
                      modal.classList.add('hidden');
                      editingCenterId = null;
                  }
              });
          });

            // Initialize everything - UPDATED
            document.addEventListener('DOMContentLoaded', function() {
                updateTime();
                setInterval(updateTime, 1000);
                initializeMap();
                initializeModalMaps();
                updateStats();
            });


        
        function updateAlertPreview() {
            const level = document.getElementById('alert-level').value;
            const message = document.getElementById('alert-message').value || 'Your message will appear here...';
            
            const levelColors = {
                green: 'text-green-600',
                yellow: 'text-yellow-600', 
                orange: 'text-orange-600',
                red: 'text-red-600'
            };
            
            const levelText = level.toUpperCase() + ' CODE';
            document.getElementById('preview-level').textContent = levelText;
            document.getElementById('preview-level').className = `font-semibold ${levelColors[level]}`;
            document.getElementById('preview-message').textContent = message;
        }

function calculateDistance(lat1, lng1, lat2, lng2) {
    const R = 6371e3; // Earth's radius in meters
    const φ1 = lat1 * Math.PI/180;
    const φ2 = lat2 * Math.PI/180;
    const Δφ = (lat2-lat1) * Math.PI/180;
    const Δλ = (lng2-lng1) * Math.PI/180;

    const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ/2) * Math.sin(Δλ/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

    return R * c; // Distance in meters
}

function estimateEvacuationTime(distance) {
    // Assume average walking speed of 1.2 m/s (4.3 km/h) for evacuation
    const walkingSpeed = 1.2; // m/s
    const timeInSeconds = distance / walkingSpeed;
    
    if (timeInSeconds < 60) {
        return Math.round(timeInSeconds) + ' sec';
    } else if (timeInSeconds < 3600) {
        return Math.round(timeInSeconds / 60) + ' min';
    } else {
        return Math.round(timeInSeconds / 3600 * 10) / 10 + ' hrs';
    }
}

function createEvacuationRoute(center, routeType) {
    const barangayHall = currentBarangayLocation; // Updated to use current location
    const centerCoords = [center.lat, center.lng];
    
    // Calculate distance and time
    const distance = calculateDistance(barangayHall[0], barangayHall[1], center.lat, center.lng);
    const estimatedTime = estimateEvacuationTime(distance);
    
    // Create different route paths based on type
    let routeCoords;
    if (routeType === 'primary') {
        // Direct route
        routeCoords = [barangayHall, centerCoords];
    } else if (routeType === 'secondary') {
        // Alternative route with a waypoint
        const midLat = (barangayHall[0] + center.lat) / 2 + 0.002;
        const midLng = (barangayHall[1] + center.lng) / 2 + 0.003;
        routeCoords = [barangayHall, [midLat, midLng], centerCoords];
    } else { // emergency
        // Most direct route
        routeCoords = [barangayHall, centerCoords];
    }
    
    const route = L.polyline(routeCoords, {
        className: `evacuation-route route-${routeType}`,
        weight: 4,
        opacity: 0.8
    }).addTo(map);
    
    // Add popup with route information
    const routeTypeText = routeType.charAt(0).toUpperCase() + routeType.slice(1);
    route.bindPopup(`
        <div class="route-info-popup">
            <b>${routeTypeText} Route to ${center.name}</b><br>
            Distance: ${(distance/1000).toFixed(2)} km<br>
            Estimated Time: ${estimatedTime}<br>
            Capacity: ${center.capacity} persons
        </div>
    `);
    
    return route;
}

function updateRouteDisplay() {
    // Clear existing routes
    evacuationRoutes.forEach(route => map.removeLayer(route));
    evacuationRoutes = [];
    
    const showPrimary = document.getElementById('route-primary').checked;
    const showSecondary = document.getElementById('route-secondary').checked;
    const showEmergency = document.getElementById('route-emergency').checked;
    
    evacuationCenters.forEach(center => {
        if (showPrimary) {
            evacuationRoutes.push(createEvacuationRoute(center, 'primary'));
        }
        if (showSecondary) {
            evacuationRoutes.push(createEvacuationRoute(center, 'secondary'));
        }
        if (showEmergency) {
            evacuationRoutes.push(createEvacuationRoute(center, 'emergency'));
        }
    });
    
    // Update evacuation time estimate
    updateEvacuationTimeEstimate();
}

function showAllEvacuationRoutes() {
    // Check all route options during emergency
    document.getElementById('route-primary').checked = true;
    document.getElementById('route-secondary').checked = true;
    document.getElementById('route-emergency').checked = true;
    updateRouteDisplay();
}

function updateEvacuationTimeEstimate() {
    if (evacuationCenters.length === 0) {
        document.getElementById('evacuation-time').textContent = '45 min';
        return;
    }
    
    // Find the nearest evacuation center from current barangay hall location
    const barangayHall = currentBarangayLocation; // Updated to use current location
    let minDistance = Infinity;
    
    evacuationCenters.forEach(center => {
        const distance = calculateDistance(barangayHall[0], barangayHall[1], center.lat, center.lng);
        if (distance < minDistance) {
            minDistance = distance;
        }
    });
    
    const estimatedTime = estimateEvacuationTime(minDistance);
    document.getElementById('evacuation-time').textContent = estimatedTime;
}


function activateHardwarePlacementMode(name, type) {
    modalMode = 'hardware';
    
    // Remove any existing click handler
    if (modalClickHandler) {
        map.off('click', modalClickHandler);
    }
    
    // Add new click handler
    modalClickHandler = function(e) {
        addHardwareToMap(e.latlng, name, type);
        cancelModalMode();
    };
    
    map.on('click', modalClickHandler);
    
    // Change cursor to indicate placement mode
    document.getElementById('evacuation-map').style.cursor = 'crosshair';
    
    // Show instruction message
    showPlacementInstruction('Click on the map to place the hardware unit');
}

function activateCenterPlacementMode(name, capacity, status) {
    modalMode = 'center';
    
    // Remove any existing click handler
    if (modalClickHandler) {
        map.off('click', modalClickHandler);
    }
    
    // Add new click handler
    modalClickHandler = function(e) {
        addCenterToMap(e.latlng, name, capacity, status);
        cancelModalMode();
    };
    
    map.on('click', modalClickHandler);
    
    // Change cursor to indicate placement mode
    document.getElementById('evacuation-map').style.cursor = 'crosshair';
    
    // Show instruction message
    showPlacementInstruction('Click on the map to place the evacuation center');
}

function cancelModalMode() {
    if (modalClickHandler) {
        map.off('click', modalClickHandler);
        modalClickHandler = null;
    }
    modalMode = null;
    document.getElementById('evacuation-map').style.cursor = '';
    hidePlacementInstruction();
}

function showPlacementInstruction(message) {
    // Create or update instruction overlay
    let instruction = document.getElementById('placement-instruction');
    if (!instruction) {
        instruction = document.createElement('div');
        instruction.id = 'placement-instruction';
        instruction.className = 'fixed top-20 left-1/2 transform -translate-x-1/2 bg-blue-600 text-white px-4 py-2 rounded-lg shadow-lg z-50';
        document.body.appendChild(instruction);
    }
    instruction.textContent = message;
    instruction.classList.remove('hidden');
}

function hidePlacementInstruction() {
    const instruction = document.getElementById('placement-instruction');
    if (instruction) {
        instruction.classList.add('hidden');
    }
}


// ADD TO THE EXISTING MODAL CLOSE EVENT LISTENERS
window.addEventListener('click', (e) => {
    const modals = ['hardware-modal', 'center-modal', 'edit-center-modal', 'alert-modal', 'set-barangay-location-modal'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (e.target === modal) {
            modal.classList.add('hidden');
            if (modalId === 'set-barangay-location-modal') {
                selectedSearchResult = null;
            }
            editingCenterId = null;
        }
    });
});
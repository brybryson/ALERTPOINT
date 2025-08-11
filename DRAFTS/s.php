<div class="relative z-10 flex-1 flex flex-col">
                    <div class="flex items-center mb-3">
                        <div class="w-3 h-3 bg-blue-500 rounded-full mr-3 animate-pulse"></div>
                        <h3 class="text-lg font-bold text-gray-800">Live Status & Analytics</h3>
                    </div>
                    
                    <div class="flex-1 flex flex-col compact-spacing">
                        <!-- Current Level Display -->
                        <div class="bg-gradient-to-r from-blue-50 to-cyan-50 compact-item rounded-xl border border-blue-100">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fas fa-tachometer-alt text-blue-600 text-lg mr-2"></i>
                                    <div>
                                        <p class="text-xs font-medium text-gray-600">Current Reading</p>
                                        <p class="text-xl font-bold text-blue-700" id="current-level-display">--m</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="inline-block px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800" id="level-classification">LOADING</span>
                                </div>
                            </div>
                        </div>

                        <!-- Data Quality & Statistics -->
                        <div class="bg-gradient-to-r from-indigo-50 to-purple-50 compact-item rounded-xl border border-indigo-100">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-chart-bar text-indigo-600 text-sm mr-2"></i>
                                <h4 class="text-sm font-semibold text-gray-800">Today's Data</h4>
                            </div>
                            <div class="grid grid-cols-3 compact-grid text-center">
                                <div>
                                    <div class="flex items-center justify-center mb-1">
                                        <p class="text-xs text-gray-600">Data Points</p>
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
                                    <p class="text-sm font-bold text-indigo-600" id="data-points-count">--</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600 mb-1">Last Update</p>
                                    <p class="text-xs font-bold text-indigo-600" id="last-update-time">--</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600 mb-1">Quality</p>
                                    <p class="text-xs font-bold text-green-600" id="data-quality">Good</p>
                                </div>
                            </div>
                        </div>

                        <!-- Connection & Status Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 compact-grid">
                            <!-- Connection Status -->
                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 compact-item rounded-xl border border-green-100">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <i class="fas fa-wifi text-green-600 text-sm mr-2"></i>
                                        <div>
                                            <p class="text-xs font-medium text-gray-600">Creek Unit</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold text-green-700" id="alarmStatus">ONLINE</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Sensor Status -->
                            <div class="bg-gradient-to-r from-blue-50 to-cyan-50 compact-item rounded-xl border border-blue-100">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <i class="fas fa-cog text-blue-600 text-sm mr-2"></i>
                                        <div>
                                            <p class="text-xs font-medium text-gray-600">Sensor Status</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold text-blue-700" id="sensor-status">OK</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Signal Strength (Full Width) -->
                        <div class="bg-gradient-to-r from-purple-50 to-indigo-50 compact-item rounded-xl border border-purple-100">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fas fa-signal text-purple-600 text-sm mr-2"></i>
                                    <div class="flex items-center">
                                        <p class="text-xs font-medium text-gray-600">Signal Strength</p>
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
                        </div>
                    </div>
                </div>


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

/* Responsive breakpoints for gauge */
/* @media (min-width: 640px) {
    .modern-gauge {
        width: min(300px, 70vw);
        height: min(300px, 70vw);
    }
}

@media (min-width: 1024px) {
    .modern-gauge {
        width: min(280px, 100%);
        height: min(280px, 100%);
    }
}

@media (min-width: 1280px) {
    .modern-gauge {
        width: 320px;
        height: 320px;
    }
} */
        
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
        .status-analytics-enhanced, .water-level-guide-enhanced {
            min-height: 520px;
            max-height: 520px;
        }

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
    width: 280px;
    background-color: rgba(17, 24, 39, 0.95);
    color: white;
    text-align: left;
    border-radius: 8px;
    padding: 12px;
    position: absolute;
    z-index: 1000;
    bottom: 125%;
    left: 50%;
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
    top: 100%;
    left: 50%;
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
    </style>
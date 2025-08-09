<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlertPoint Login - Emergency Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    

     <!-- CSS Files -->
    <link rel="stylesheet" href="/ALERTPOINT/css/login.css">

</head>

<body class="login-container">


    <!-- Main Card Container -->
    <div class="main-card animate-fade-in">
        <div class="content-grid">
            
            <!-- Left Side - Branding & Welcome -->
            <div class="brand-section text-gray-800">
                
                <!-- Main Logo and Title -->
                <div class="mb-6">
                    <div class="flex items-center justify-center space-x-3 mb-4">
                        <div>
                            <!-- Replaced icon with image -->
                            <img src="/ALERTPOINT/ALERTPOINT_LOGO.png" alt="AlertPoint Logo" class="h-16 w-auto" />
                        </div>
                        <div>
                            <h1 class="text-3xl lg:text-4xl font-bold text-gray-800 drop-shadow-sm">
                                AlertPoint
                            </h1>
                        </div>
                    </div>
                </div>


                <!-- Welcome Message -->
                <div class="mb-8 space-y-3">
                    <h2 class="text-xl font-bold text-gray-700 leading-tight drop-shadow-sm">
                        Safeguarding Our Community
                    </h2>
                </div>

                <!-- Key Features Grid - Increased Size -->
                <div class="grid grid-cols-2 gap-4 mb-8 max-w-md mx-auto">
                    <div class="feature-highlight p-5 rounded-xl">
                        <div class="flex flex-col items-center text-center space-y-3">
                            <div class="w-10 h-10 bg-red-500 bg-opacity-20 rounded-xl flex items-center justify-center">
                                <i class="fas fa-shield-alt text-red-500 text-lg"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-700 text-sm drop-shadow-sm">Emergency Response</h4>
                                <p class="text-sm text-gray-500">24/7 Crisis Management</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="feature-highlight p-5 rounded-xl">
                        <div class="flex flex-col items-center text-center space-y-3">
                            <div class="w-10 h-10 bg-blue-500 bg-opacity-20 rounded-xl flex items-center justify-center">
                                <i class="fas fa-satellite text-blue-500 text-lg"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-700 text-sm drop-shadow-sm">Weather Monitoring</h4>
                                <p class="text-sm text-gray-500">Real-time Conditions</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="feature-highlight p-5 rounded-xl">
                        <div class="flex flex-col items-center text-center space-y-3">
                            <div class="w-10 h-10 bg-orange-500 bg-opacity-20 rounded-xl flex items-center justify-center">
                                <i class="fas fa-bell text-orange-500 text-lg"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-700 text-sm drop-shadow-sm">Mass Alerts</h4>
                                <p class="text-sm text-gray-500">Instant Notifications</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="feature-highlight p-5 rounded-xl">
                        <div class="flex flex-col items-center text-center space-y-3">
                            <div class="w-10 h-10 bg-green-500 bg-opacity-20 rounded-xl flex items-center justify-center">
                                <i class="fas fa-users text-green-500 text-lg"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-700 text-sm drop-shadow-sm">Community Hub</h4>
                                <p class="text-sm text-gray-500">Unified Coordination</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Status -->
                <div class="flex flex-wrap justify-center gap-2">
                    <div class="status-indicator px-3 py-2 rounded-full">
                        <div class="flex items-center space-x-2">
                            <div class="w-2 h-2 bg-green-400 rounded-full pulse-dot"></div>
                            <span class="font-semibold text-xs text-gray-700">All Systems Operational</span>
                        </div>
                    </div>
                    <div class="status-indicator px-3 py-2 rounded-full">
                        <div class="flex items-center space-x-2">
                            <i id="wifi-icon" class="fas fa-wifi text-blue-400 text-xs"></i>
                                <span id="network-status" class="font-medium text-xs text-gray-700">Checking...</span>
                        </div>
                    </div>
                    <div class="status-indicator px-3 py-2 rounded-full">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-clock text-yellow-400 text-xs"></i>
                            <span id="current-time" class="font-medium text-xs text-gray-700">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vertical Divider -->
            <div class="vertical-divider"></div>

            <!-- Mobile Divider (hidden on desktop) -->
            <div class="mobile-divider md:hidden"></div>

            <!-- Right Side - Login Form -->
            <div class="login-section ">
                
                <!-- Form Header -->
                <div class="text-center mb-6">
                    <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                        <i class="fas fa-user-shield text-white text-l"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">System Access</h3>
                    <p class="text-gray-600 text-sm">Enter your credentials to access the emergency management dashboard</p>
                </div>

                <!-- Login Form -->
                <form id="loginForm" class="space-y-5">
                    
                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-xs font-bold text-gray-700 mb-2">
                            <i class="fas fa-envelope text-gray-400 mr-1"></i>Email Address
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            required
                            class="w-full px-3 py-3 border-2 border-gray-300 rounded-lg focus:outline-none input-focus transition-all duration-300 bg-white text-sm"
                            placeholder="your.name@example.com"
                            autocomplete="email"
                        >
                        <div class="error-message text-red-500 text-xs mt-1 hidden" id="emailError"></div>
                    </div>

                    <!-- Replace the password field section (around line 175-190): -->
                    <div>
                        <label for="password" class="block text-xs font-bold text-gray-700 mb-2">
                            <i class="fas fa-lock text-gray-400 mr-1"></i>Password
                        </label>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required
                                class="w-full px-3 py-3 pr-10 border-2 border-gray-300 rounded-lg focus:outline-none input-focus transition-all duration-300 bg-white text-sm"
                                placeholder="Enter your secure password"
                                autocomplete="current-password"
                            >
                            <button 
                                type="button" 
                                onclick="togglePassword()"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
                            >
                                <i id="passwordToggle" class="fas fa-eye text-sm"></i>
                            </button>
                        </div>
                        <div class="error-message text-red-500 text-xs mt-1 hidden" id="passwordError"></div>
                        
                        <!-- Add this new general error message div below the password field -->
                        <div id="generalError" class="text-red-500 text-xs text-center mt-3 hidden font-medium"></div>
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input 
                                type="checkbox" 
                                id="remember" 
                                name="remember"
                                class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded"
                            >
                            <label for="remember" class="ml-2 text-gray-700 font-medium text-sm">
                                Keep me signed in
                            </label>
                        </div>
                        <button 
                            type="button" 
                            id="forgotPasswordBtn"
                            onclick="handleForgotPassword()" 
                            class="text-red-600 hover:text-red-700 font-bold transition-colors text-sm"
                        >
                            Forgot Password?
                        </button>
                    </div>

                    <!-- Login Button -->
                    <button 
                        type="submit" 
                        class="w-full bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-bold py-3 px-4 rounded-lg transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 flex items-center justify-center text-sm"
                        id="loginBtn"
                    >
                        <i class="fas  mr-2"></i>
                        <span id="loginBtnText">Access AlertPoint Dashboard</span>
                    </button>

                    <!-- Security Notice -->
                    <div class="bg-gradient-to-r from-gray-50 to-red-50 rounded-lg p-4 border border-gray-200">
                        <div class="flex items-start space-x-3">
                            <div class="w-6 h-6 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0 mt-1">
                                <i class="fas fa-info-circle text-red-600 text-xs"></i>
                            </div>
                            <div class="text-xs text-gray-700">
                                <p class="font-bold mb-1">🔒 Security Notice</p>
                                <p>This system is exclusively for authorized emergency personnel. All access attempts are monitored and logged for security compliance.</p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Password Reset Modal -->
    <div id="forgotPasswordModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 id="modalTitle" class="text-lg font-bold text-gray-900">Reset Password</h3>
                    <button onclick="closeForgotPassword()" class="text-gray-400 hover:text-gray-600 text-xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="p-6">
                
                <!-- Step 1: Email Input -->
                <div id="emailStep">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-envelope text-white text-2xl"></i>
                        </div>
                        <h4 class="text-lg font-bold text-gray-900 mb-2">Password Reset</h4>
                        <p class="text-gray-600 text-sm">Enter your email address to receive an OTP code for password reset.</p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="resetEmail" class="block text-xs font-bold text-gray-700 mb-2">
                            <i class="fas fa-envelope text-gray-400 mr-1"></i>Email Address
                        </label>
                        <input 
                            type="email" 
                            id="resetEmail" 
                            class="w-full px-3 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-red-500 focus:border-red-500 transition-all duration-300 bg-white text-sm"
                            placeholder="Enter your registered email"
                        >
                        <div class="reset-error-message text-red-500 text-xs mt-1 hidden" id="resetEmailResetError"></div>
                    </div>
                    
                    <button 
                        onclick="sendOTP()" 
                        id="sendOtpBtn"
                        class="w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold py-3 px-4 rounded-lg transition-all duration-300 text-sm"
                    >
                        <i class="fas fa-paper-plane mr-2"></i>Send OTP Code
                    </button>
                </div>

                <!-- Step 2: OTP Verification -->
                <div id="otpStep" class="hidden">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-shield-alt text-white text-2xl"></i>
                        </div>
                        <h4 class="text-lg font-bold text-gray-900 mb-2">Enter OTP Code</h4>
                        <p class="text-gray-600 text-sm">We've sent a 6-digit code to <span id="otpEmailDisplay" class="font-semibold text-blue-600"></span></p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="otpCode" class="block text-xs font-bold text-gray-700 mb-2">
                            <i class="fas fa-key text-gray-400 mr-1"></i>6-Digit OTP Code
                        </label>
                        <input 
                            type="text" 
                            id="otpCode" 
                            maxlength="6"
                            class="w-full px-3 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 transition-all duration-300 bg-white text-sm text-center text-2xl font-bold tracking-widest"
                            placeholder="000000"
                        >
                        <div class="reset-error-message text-red-500 text-xs mt-1 hidden" id="otpCodeResetError"></div>
                    </div>
                    
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
                        <div class="flex items-center">
                            <i class="fas fa-clock text-yellow-600 mr-2"></i>
                            <span class="text-sm text-yellow-800">Code expires in: <span id="otpCountdown" class="font-bold">10:00</span></span>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <button 
                            onclick="verifyOTP()" 
                            id="verifyOtpBtn"
                            class="w-full bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-bold py-3 px-4 rounded-lg transition-all duration-300 text-sm"
                        >
                            <i class="fas fa-check mr-2"></i>Verify OTP
                        </button>
                        
                        <button 
                            onclick="showEmailStep()" 
                            class="w-full bg-gray-300 hover:bg-gray-400 text-gray-700 font-bold py-2 px-4 rounded-lg transition-all duration-300 text-sm"
                        >
                            <i class="fas fa-arrow-left mr-2"></i>Back to Email
                        </button>
                    </div>
                </div>

                <!-- Step 3: New Password -->
                <div id="passwordStep" class="hidden">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-lock text-white text-2xl"></i>
                        </div>
                        <h4 class="text-lg font-bold text-gray-900 mb-2">Set New Password</h4>
                        <p class="text-gray-600 text-sm">Enter your new secure password below.</p>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="newPassword" class="block text-xs font-bold text-gray-700 mb-2">
                                <i class="fas fa-lock text-gray-400 mr-1"></i>New Password
                            </label>
                            <div class="relative">
                                <input 
                                    type="password" 
                                    id="newPassword" 
                                    class="w-full px-3 py-3 pr-10 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 transition-all duration-300 bg-white text-sm"
                                    placeholder="Enter new password"
                                >
                                <button 
                                    type="button" 
                                    onclick="toggleNewPassword()"
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
                                >
                                    <i id="newPasswordToggle" class="fas fa-eye text-sm"></i>
                                </button>
                            </div>
                            <div class="reset-error-message text-red-500 text-xs mt-1 hidden" id="newPasswordResetError"></div>
                        </div>
                        
                        <div>
                            <label for="confirmPassword" class="block text-xs font-bold text-gray-700 mb-2">
                                <i class="fas fa-lock text-gray-400 mr-1"></i>Confirm Password
                            </label>
                            <div class="relative">
                                <input 
                                    type="password" 
                                    id="confirmPassword" 
                                    class="w-full px-3 py-3 pr-10 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 transition-all duration-300 bg-white text-sm"
                                    placeholder="Confirm new password"
                                >
                                <button 
                                    type="button" 
                                    onclick="toggleConfirmPassword()"
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
                                >
                                    <i id="confirmPasswordToggle" class="fas fa-eye text-sm"></i>
                                </button>
                            </div>
                            <div class="reset-error-message text-red-500 text-xs mt-1 hidden" id="confirmPasswordResetError"></div>
                        </div>
                    </div>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mt-4 mb-4">
                        <div class="text-xs text-blue-800">
                            <p class="font-bold mb-1">Password Requirements:</p>
                            <ul class="space-y-1">
                                <li>• At least 8 characters long</li>
                                <li>• Contains uppercase and lowercase letters</li>
                                <li>• Contains at least one number</li>
                            </ul>
                        </div>
                    </div>
                    
                    <button 
                        onclick="resetPassword()" 
                        id="resetPasswordBtn"
                        class="w-full bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white font-bold py-3 px-4 rounded-lg transition-all duration-300 text-sm"
                    >
                        <i class="fas fa-save mr-2"></i>Reset Password
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 ">
            <div class="p-8 text-center">
                <!-- Success Icon -->
                <div class="w-20 h-20 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-check text-white text-3xl"></i>
                </div>
                
              <!-- Success Message -->
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Access Granted!</h3>
                <p class="text-gray-600 mb-4" id="welcomeMessage">
                    Welcome to AlertPoint: A Disaster Risk Reduction Management System for Barangay 170, Caloocan City
                </p>

                <!-- User Info -->
                <div class="bg-gradient-to-r from-green-50 to-blue-50 border border-green-200 rounded-lg p-4 mb-6">
                    <div class="text-sm text-gray-700">
                        <div class="flex items-center justify-center mb-2">
                            <i class="fas fa-user-shield text-green-600 mr-2"></i>
                            <span class="font-bold" id="userName">Loading...</span>
                        </div>
                        <div class="flex items-center justify-center mb-2">
                            <i class="fas fa-envelope text-red-600 mr-2"></i>
                            <span id="userEmail">Loading...</span>
                        </div>
                        <div class="flex items-center justify-center">
                            <i class="fas fa-briefcase text-purple-600 mr-2"></i>
                            <span id="userPosition">Loading...</span>
                        </div>
                    </div>
                </div>
                
                <!-- Loading Animation -->
                <div class="flex flex-col items-center">
                    <div class="flex space-x-1 mb-3">
                        <div class="w-2 h-2 bg-green-500 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                        <div class="w-2 h-2 bg-green-500 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                        <div class="w-2 h-2 bg-green-500 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                    </div>
                    <p class="text-green-600 font-semibold text-sm">Authenticating...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Modal (replaces alerts) -->
    <div id="statusModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in">
            <div class="p-6">
                <div class="text-center">
                    <div id="statusIcon" class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <!-- Icon will be added dynamically -->
                    </div>
                    <h3 id="statusTitle" class="text-lg font-bold text-gray-900 mb-2"></h3>
                    <p id="statusMessage" class="text-gray-600 text-sm mb-6"></p>
                    <button 
                        onclick="closeStatusModal()" 
                        class="w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold py-3 px-4 rounded-lg transition-all duration-300 text-sm"
                    >
                        <i class="fas fa-check mr-2"></i>Okay
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- OTP Success Modal -->
    <div id="otpSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in">
            <div class="p-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-check text-white text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">OTP Verified!</h3>
                    <p class="text-gray-600 text-sm mb-6">Your OTP has been verified successfully. You can now set your new password.</p>
                    <button 
                        onclick="closeOtpSuccessModal()" 
                        class="w-full bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-bold py-3 px-4 rounded-lg transition-all duration-300 text-sm"
                    >
                        <i class="fas fa-arrow-right mr-2"></i>Continue to Set Password
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Password Reset Success Modal -->
    <div id="passwordResetSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in">
            <div class="p-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-check text-white text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Password Reset Successful!</h3>
                    <p class="text-gray-600 text-sm mb-6">Your password has been reset successfully. You can now login with your new password.</p>
                    <button 
                        onclick="closePasswordResetSuccessModal()" 
                        class="w-full bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white font-bold py-3 px-4 rounded-lg transition-all duration-300 text-sm"
                    >
                        <i class="fas fa-sign-in-alt mr-2"></i>Go to Login
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Network Error Modal -->
<div id="networkErrorModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70 hidden">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in">
        <div class="p-6">
            <div class="text-center">
                <div class="w-16 h-16 bg-gradient-to-br from-red-500 to-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-wifi text-white text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">No Internet Connection</h3>
                <p class="text-gray-600 text-sm mb-6">Password reset requires an active internet connection. Please check your network settings and try again.</p>
                <button 
                    onclick="closeNetworkErrorModal()" 
                    class="w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold py-3 px-4 rounded-lg transition-all duration-300 text-sm"
                >
                    <i class="fas fa-check mr-2"></i>Okay
                </button>
            </div>
        </div>
    </div>
</div>


     <script src="/ALERTPOINT/javascript/LOGIN/login_user.js"></script>
</body>
</html>
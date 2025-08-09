// Network connectivity checking
let isOnline = navigator.onLine;

function updateNetworkStatus() {
    const networkStatus = document.getElementById('network-status');
    const forgotPasswordBtn = document.getElementById('forgotPasswordBtn');
    const wifiIcon = document.getElementById('wifi-icon');
    
    if (isOnline) {
        networkStatus.textContent = 'Connected';
        networkStatus.classList.remove('text-red-700');
        networkStatus.classList.add('text-gray-700');
        
        // Update wifi icon color to green for connected
        wifiIcon.classList.remove('text-red-400');
        wifiIcon.classList.add('text-green-400');
        
        // Enable forgot password functionality
        forgotPasswordBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        forgotPasswordBtn.classList.add('hover:text-blue-700');
    } else {
        networkStatus.textContent = 'Disconnected';
        networkStatus.classList.remove('text-gray-700');
        networkStatus.classList.add('text-red-700');
        
        // Update wifi icon color to red for disconnected
        wifiIcon.classList.remove('text-green-400');
        wifiIcon.classList.add('text-red-400');
        
        // Disable forgot password functionality visually
        forgotPasswordBtn.classList.add('opacity-50', 'cursor-not-allowed');
        forgotPasswordBtn.classList.remove('hover:text-blue-700');
    }
}

// Handle forgot password click
function handleForgotPassword() {
    if (!isOnline) {
        showNetworkErrorModal();
        return;
    }
    showForgotPassword();
}

// Show network error modal
function showNetworkErrorModal() {
    document.getElementById('networkErrorModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

// Close network error modal
function closeNetworkErrorModal() {
    document.getElementById('networkErrorModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Listen for network status changes
window.addEventListener('online', function() {
    isOnline = true;
    updateNetworkStatus();
});

window.addEventListener('offline', function() {
    isOnline = false;
    updateNetworkStatus();
});





// Update current time
function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-PH', {
        timeZone: 'Asia/Manila',
        hour12: true,
        hour: '2-digit',
        minute: '2-digit'
    });
    const currentTimeEl = document.getElementById('current-time');
    if (currentTimeEl) {
        currentTimeEl.textContent = `PHT ${timeString}`;
    }
}

// Toggle password visibility
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const passwordToggle = document.getElementById('passwordToggle');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordToggle.classList.remove('fa-eye');
        passwordToggle.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        passwordToggle.classList.remove('fa-eye-slash');
        passwordToggle.classList.add('fa-eye');
    }
}

// Toggle new password visibility
function toggleNewPassword() {
    const passwordInput = document.getElementById('newPassword');
    const passwordToggle = document.getElementById('newPasswordToggle');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordToggle.classList.remove('fa-eye');
        passwordToggle.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        passwordToggle.classList.remove('fa-eye-slash');
        passwordToggle.classList.add('fa-eye');
    }
}

// Toggle confirm password visibility
function toggleConfirmPassword() {
    const passwordInput = document.getElementById('confirmPassword');
    const passwordToggle = document.getElementById('confirmPasswordToggle');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordToggle.classList.remove('fa-eye');
        passwordToggle.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        passwordToggle.classList.remove('fa-eye-slash');
        passwordToggle.classList.add('fa-eye');
    }
}

// Show forgot password modal
function showForgotPassword() {
    document.getElementById('forgotPasswordModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    
    // Reset modal to initial state
    showEmailStep();
    clearResetErrors();
    document.getElementById('resetEmail').value = '';
}

// Close forgot password modal
function closeForgotPassword() {
    document.getElementById('forgotPasswordModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
    
    // Reset modal state
    showEmailStep();
    clearResetErrors();
    
    // Clear form fields
    document.getElementById('resetEmail').value = '';
    document.getElementById('otpCode').value = '';
    document.getElementById('newPassword').value = '';
    document.getElementById('confirmPassword').value = '';
    
    // Stop any running countdown
    if (window.otpCountdown) {
        clearInterval(window.otpCountdown);
        window.otpCountdown = null;
    }
}

// Show different steps in the password reset modal
function showEmailStep() {
    document.getElementById('emailStep').classList.remove('hidden');
    document.getElementById('otpStep').classList.add('hidden');
    document.getElementById('passwordStep').classList.add('hidden');
    document.getElementById('modalTitle').textContent = 'Reset Password';
    document.getElementById('resetEmail').focus();
}

function showOtpStep() {
    document.getElementById('emailStep').classList.add('hidden');
    document.getElementById('otpStep').classList.remove('hidden');
    document.getElementById('passwordStep').classList.add('hidden');
    document.getElementById('modalTitle').textContent = 'Enter OTP Code';
    document.getElementById('otpCode').focus();
}

function showPasswordStep() {
    document.getElementById('emailStep').classList.add('hidden');
    document.getElementById('otpStep').classList.add('hidden');
    document.getElementById('passwordStep').classList.remove('hidden');
    document.getElementById('modalTitle').textContent = 'Set New Password';
    document.getElementById('newPassword').focus();
}

// Clear reset form errors
function clearResetErrors() {
    const errorElements = document.querySelectorAll('.reset-error-message');
    errorElements.forEach(element => {
        element.classList.add('hidden');
        element.textContent = '';
    });
    
    // Remove error styling from inputs
    const inputs = document.querySelectorAll('#forgotPasswordModal input');
    inputs.forEach(input => {
        input.classList.remove('border-red-500', 'focus:ring-red-500', 'focus:border-red-500');
        input.classList.add('border-gray-300', 'focus:ring-blue-500', 'focus:border-blue-500');
    });
}

// Show error for reset form
function showResetError(field, message) {
    const errorElement = document.getElementById(field + 'ResetError');
    const inputElement = document.getElementById(field);
    
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.classList.remove('hidden');
    }
    
    if (inputElement) {
        inputElement.classList.remove('border-gray-300', 'focus:ring-blue-500', 'focus:border-blue-500');
        inputElement.classList.add('border-red-500', 'focus:ring-red-500', 'focus:border-red-500');
        inputElement.focus();
    }
}

// Send OTP
async function sendOTP() {
    const email = document.getElementById('resetEmail').value.trim();
    const sendOtpBtn = document.getElementById('sendOtpBtn');
    
    if (!email) {
        showResetError('resetEmail', 'Email address is required');
        return;
    }
    
    if (!email.includes('@') || !email.includes('.')) {
        showResetError('resetEmail', 'Please enter a valid email address');
        return;
    }
    
    clearResetErrors();
    
    // Show loading state
    sendOtpBtn.disabled = true;
    sendOtpBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Sending OTP...';
    
    try {
        const response = await fetch('/ALERTPOINT/javascript/LOGIN/send_otp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email: email })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Move to OTP step
            showOtpStep();
            document.getElementById('otpEmailDisplay').textContent = email;
            
            // Start countdown timer
            startOtpCountdown(300); // 5 minutes
            
            // Show success modal instead of alert
            showStatusModal(
                'OTP Sent Successfully!',
                'A 6-digit verification code has been sent to your email address. Please check your inbox and enter the code below. The code will expire in 5 minutes.',
                'success'
            );
            
        } else {
            if (data.rate_limited) {
                showResetError('resetEmail', data.message);
            } else if (data.field) {
                showResetError(data.field, data.message);
            } else {
                showStatusModal('Failed to Send OTP', data.message, 'error');
            }
        }
        
    } catch (error) {
        console.error('Send OTP error:', error);
        showStatusModal('Connection Error', 'Unable to send OTP. Please check your internet connection and try again.', 'error');
    } finally {
        // Reset button
        sendOtpBtn.disabled = false;
        sendOtpBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Send OTP Code';
    }
}

// Verify OTP
async function verifyOTP() {
    const email = document.getElementById('resetEmail').value.trim();
    const otpCode = document.getElementById('otpCode').value.trim();
    const verifyOtpBtn = document.getElementById('verifyOtpBtn');
    
    if (!otpCode) {
        showResetError('otpCode', 'OTP code is required');
        return;
    }
    
    if (otpCode.length !== 6 || !/^\d{6}$/.test(otpCode)) {
        showResetError('otpCode', 'OTP must be exactly 6 digits');
        return;
    }
    
    clearResetErrors();
    
    // Show loading state
    verifyOtpBtn.disabled = true;
    verifyOtpBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Verifying...';
    
    try {
        const response = await fetch('/ALERTPOINT/javascript/LOGIN/verify_otp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                email: email,
                otp_code: otpCode 
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Stop countdown
            if (window.otpCountdown) {
                clearInterval(window.otpCountdown);
                window.otpCountdown = null;
            }
            
            // Show success modal instead of alert
            showOtpSuccessModal();
            
        } else {
            if (data.expired) {
                showResetError('otpCode', data.message);
                setTimeout(() => {
                    showEmailStep();
                }, 2000);
            } else if (data.blocked) {
                showResetError('otpCode', data.message);
                setTimeout(() => {
                    showEmailStep();
                }, 3000);
            } else {
                showResetError('otpCode', data.message);
            }
        }
        
    } catch (error) {
        console.error('Verify OTP error:', error);
        showStatusModal('Connection Error', 'Unable to verify OTP. Please check your internet connection and try again.', 'error');
    } finally {
        // Reset button
        verifyOtpBtn.disabled = false;
        verifyOtpBtn.innerHTML = '<i class="fas fa-check mr-2"></i>Verify OTP';
    }
}


// Reset Password
async function resetPassword() {
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const resetPasswordBtn = document.getElementById('resetPasswordBtn');
    
    // Validate passwords
    if (!newPassword) {
        showResetError('newPassword', 'New password is required');
        return;
    }
    
    if (!confirmPassword) {
        showResetError('confirmPassword', 'Please confirm your password');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        showResetError('confirmPassword', 'Passwords do not match');
        return;
    }
    
    if (newPassword.length < 8) {
        showResetError('newPassword', 'Password must be at least 8 characters long');
        return;
    }
    
    if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(newPassword)) {
        showResetError('newPassword', 'Password must contain uppercase, lowercase, and number');
        return;
    }
    
    clearResetErrors();
    
    // Show loading state
    resetPasswordBtn.disabled = true;
    resetPasswordBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Resetting Password...';
    
    try {
        const response = await fetch('/ALERTPOINT/javascript/LOGIN/reset_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                new_password: newPassword,
                confirm_password: confirmPassword 
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Show success modal instead of alert
            showPasswordResetSuccessModal();
            
        } else {
            if (data.session_expired) {
                showStatusModal('Session Expired', 'Your password reset session has expired. Please start the process again.', 'warning');
                setTimeout(() => {
                    showEmailStep();
                }, 2000);
            } else if (data.field) {
                showResetError(data.field, data.message);
            } else {
                showStatusModal('Reset Failed', data.message, 'error');
            }
        }
        
    } catch (error) {
        console.error('Reset password error:', error);
        showStatusModal('Connection Error', 'Unable to reset password. Please check your internet connection and try again.', 'error');
    } finally {
        // Reset button
        resetPasswordBtn.disabled = false;
        resetPasswordBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Reset Password';
    }
}

// Start OTP countdown timer
function startOtpCountdown(seconds) {
    let timeLeft = seconds;
    const countdownElement = document.getElementById('otpCountdown');
    
    // Stop any existing countdown
    if (window.otpCountdown) {
        clearInterval(window.otpCountdown);
    }
    
    window.otpCountdown = setInterval(() => {
        const minutes = Math.floor(timeLeft / 60);
        const secs = timeLeft % 60;
        countdownElement.textContent = `${minutes}:${secs.toString().padStart(2, '0')}`;
        
        if (timeLeft <= 0) {
            clearInterval(window.otpCountdown);
            countdownElement.textContent = 'Expired';
            countdownElement.parentElement.innerHTML = '<i class="fas fa-times-circle text-red-600 mr-2"></i><span class="text-sm text-red-800">OTP has expired</span>';
            
            // Disable verify button
            const verifyBtn = document.getElementById('verifyOtpBtn');
            verifyBtn.disabled = true;
            verifyBtn.innerHTML = '<i class="fas fa-times mr-2"></i>OTP Expired';
            verifyBtn.classList.remove('from-green-600', 'to-green-700');
            verifyBtn.classList.add('from-red-600', 'to-red-700');
        }
        
        timeLeft--;
    }, 1000);
}

// Clear error messages
function clearErrors() {
    const errorElements = document.querySelectorAll('.error-message');
    errorElements.forEach(element => {
        element.classList.add('hidden');
        element.textContent = '';
    });
    
    // Clear general error message
    const generalError = document.getElementById('generalError');
    if (generalError) {
        generalError.classList.add('hidden');
        generalError.textContent = '';
    }
    
    // Remove error styling from inputs
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => {
        input.classList.remove('border-red-500', 'focus:ring-red-500', 'focus:border-red-500');
        input.classList.add('border-gray-300', 'focus:ring-blue-500', 'focus:border-blue-500');
    });
}


// Add new function to show general error:
function showGeneralError(message) {
    const generalError = document.getElementById('generalError');
    if (generalError) {
        generalError.textContent = message;
        generalError.classList.remove('hidden');
    }
}


// Show error for specific field
function showError(field, message) {
    const errorElement = document.getElementById(field + 'Error');
    const inputElement = document.getElementById(field);
    
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.classList.remove('hidden');
    }
    
    if (inputElement) {
        inputElement.classList.remove('border-gray-300', 'focus:ring-blue-500', 'focus:border-blue-500');
        inputElement.classList.add('border-red-500', 'focus:ring-red-500', 'focus:border-red-500');
        inputElement.focus();
    }
}

// Form validation
function validateForm() {
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    
    let isValid = true;
    
    // Clear previous errors
    clearErrors();

    // Email validation
    if (!email) {
        showError('email', 'Official email address is required for system access');
        isValid = false;
    } else if (!email.includes('@') || !email.includes('.')) {
        showError('email', 'Please enter a valid government email address');
        isValid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showError('email', 'Please enter a valid email format');
        isValid = false;
    }

    // Password validation
    if (!password) {
        showError('password', 'Secure password is required for emergency system access');
        isValid = false;
    } else if (password.length < 8) {
        showError('password', 'Password must be at least 8 characters for security compliance');
        isValid = false;
    }

    return isValid;
}

// Reset login button to original state
function resetLoginButton() {
    const loginBtn = document.getElementById('loginBtn');
    const loginBtnText = document.getElementById('loginBtnText');
    
    loginBtn.disabled = false;
    loginBtn.classList.remove('opacity-75', 'from-green-600', 'to-green-700', 'from-red-600', 'to-red-700');
    loginBtn.classList.add('from-blue-600', 'to-blue-700');
    loginBtnText.innerHTML = '<i class="fas fa-sign-in-alt mr-2"></i>Access Emergency Dashboard';
}

// Show loading state
function showLoadingState(message) {
    const loginBtn = document.getElementById('loginBtn');
    const loginBtnText = document.getElementById('loginBtnText');
    
    loginBtn.disabled = true;
    loginBtn.classList.add('opacity-75');
    loginBtnText.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i>${message}`;
}

// Show success state
function showSuccessState() {
    const loginBtn = document.getElementById('loginBtn');
    const loginBtnText = document.getElementById('loginBtnText');
    
    loginBtnText.innerHTML = '<i class="fas fa-check mr-2"></i>Access Granted - Redirecting...';
    loginBtn.classList.remove('from-blue-600', 'to-blue-700');
    loginBtn.classList.add('from-green-600', 'to-green-700');
}

// Show error state
function showErrorState() {
    const loginBtn = document.getElementById('loginBtn');
    const loginBtnText = document.getElementById('loginBtnText');
    
    loginBtn.classList.remove('from-blue-600', 'to-blue-700');
    loginBtn.classList.add('from-red-600', 'to-red-700');
    loginBtnText.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i>Login Failed';
    
    // Reset after 2 seconds
    setTimeout(() => {
        resetLoginButton();
    }, 2000);
}

// Process login
async function processLogin(email, password) {
    try {
        const response = await fetch('/ALERTPOINT/javascript/LOGIN/process_login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                email: email,
                password: password
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (data.success) {
            // Show success state on button
            showSuccessState();
            
            // Show success modal
            setTimeout(() => {
                showSuccessModal(data.admin);
                
                // Redirect after 3 seconds
                setTimeout(() => {
                    window.location.href = data.redirect_url;
                }, 5000);
            }, 1000);
            
        } else {
            // Handle specific error types
            if (data.field === 'email') {
                showError(data.field, data.message);
            } else if (data.field === 'password') {
                showError(data.field, data.message);
            } else if (data.field === 'general') {
                // Show general error message below password field
                showGeneralError(data.message);
            } else {
                // Fallback for other errors
                showStatusModal('Login Failed', data.message, 'error');
            }
            showErrorState();
        }
        
    } catch (error) {
        console.error('Login error:', error);
        showStatusModal('Connection Error', 'Unable to connect to the authentication server. Please check your internet connection and try again.', 'error');
        showErrorState();
    }
}

// Enhanced form submission
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!validateForm()) {
        return;
    }

    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    
    // Show loading state
    showLoadingState('Authenticating Access...');
    
    // Simulate authentication process with delays for better UX
    setTimeout(() => {
        showLoadingState('Verifying Credentials...');
        
        setTimeout(() => {
            showLoadingState('Checking Account Status...');
            
            setTimeout(() => {
                // Process actual login
                processLogin(email, password);
            }, 800);
        }, 1000);
    }, 500);
});

// Close modal when clicking outside
document.getElementById('forgotPasswordModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeForgotPassword();
    }
});

// Show success modal
function showSuccessModal(adminData) {
    // Update user information in modal
    document.getElementById('userName').textContent = adminData.name;
    document.getElementById('userEmail').textContent = adminData.email;
    document.getElementById('userPosition').textContent = adminData.position;
    
    // Show the modal
    document.getElementById('successModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

// Add input animations and real-time validation
document.querySelectorAll('input[type="email"], input[type="password"]').forEach(input => {
    input.addEventListener('focus', function() {
        this.parentElement.classList.add('focused');
        // Clear errors when user starts typing
        clearErrors();
    });
    
    input.addEventListener('blur', function() {
        this.parentElement.classList.remove('focused');
    });
    
    // Real-time email validation
    if (input.type === 'email') {
        input.addEventListener('input', function() {
            const email = this.value.trim();
            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                this.classList.remove('border-gray-300');
                this.classList.add('border-yellow-400');
            } else {
                this.classList.remove('border-yellow-400', 'border-red-500');
                this.classList.add('border-gray-300');
            }
        });
    }
});

// Add OTP input formatting
document.getElementById('otpCode').addEventListener('input', function() {
    // Only allow numbers
    this.value = this.value.replace(/[^0-9]/g, '');
    
    // Clear errors when typing
    clearResetErrors();
});

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    updateTime();
    setInterval(updateTime, 1000);
    
    // Initialize network status
    updateNetworkStatus();
    
    // Focus on email input after brief delay
    setTimeout(() => {
        document.getElementById('email').focus();
    }, 500);
    
    // Clear any existing errors
    clearErrors();
});

// Handle keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeForgotPassword();
    }
});

// Prevent multiple form submissions
let isSubmitting = false;
document.getElementById('loginForm').addEventListener('submit', function(e) {
    if (isSubmitting) {
        e.preventDefault();
        return false;
    }
    isSubmitting = true;
    
    // Reset flag after 5 seconds to allow retry if something goes wrong
    setTimeout(() => {
        isSubmitting = false;
    }, 5000);
});


// Add these new functions to login_user.js

// Show status modal (replaces alerts)
function showStatusModal(title, message, type = 'info') {
    const modal = document.getElementById('statusModal');
    const icon = document.getElementById('statusIcon');
    const titleEl = document.getElementById('statusTitle');
    const messageEl = document.getElementById('statusMessage');
    
    // Set icon based on type
    icon.className = 'w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4';
    
    switch(type) {
        case 'success':
            icon.classList.add('bg-gradient-to-br', 'from-green-500', 'to-green-600');
            icon.innerHTML = '<i class="fas fa-check text-white text-2xl"></i>';
            break;
        case 'error':
            icon.classList.add('bg-gradient-to-br', 'from-red-500', 'to-red-600');
            icon.innerHTML = '<i class="fas fa-times text-white text-2xl"></i>';
            break;
        case 'warning':
            icon.classList.add('bg-gradient-to-br', 'from-yellow-500', 'to-yellow-600');
            icon.innerHTML = '<i class="fas fa-exclamation-triangle text-white text-2xl"></i>';
            break;
        default:
            icon.classList.add('bg-gradient-to-br', 'from-blue-500', 'to-blue-600');
            icon.innerHTML = '<i class="fas fa-info-circle text-white text-2xl"></i>';
    }
    
    titleEl.textContent = title;
    messageEl.textContent = message;
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

// Close status modal
function closeStatusModal() {
    document.getElementById('statusModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Show OTP success modal
function showOtpSuccessModal() {
    document.getElementById('otpSuccessModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

// Close OTP success modal and proceed to password step
function closeOtpSuccessModal() {
    document.getElementById('otpSuccessModal').classList.add('hidden');
    showPasswordStep();
}

// Show password reset success modal
function showPasswordResetSuccessModal() {
    document.getElementById('passwordResetSuccessModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

// Close password reset success modal and return to login
function closePasswordResetSuccessModal() {
    document.getElementById('passwordResetSuccessModal').classList.add('hidden');
    closeForgotPassword();
    
    // Optionally pre-fill email
    const email = document.getElementById('resetEmail').value.trim();
    if (email) {
        document.getElementById('email').value = email;
        document.getElementById('password').focus();
    }
}




// Global variables
let selectedImageFile = null;
let imagePreviewUrl = null;

// Initialize birthdate selectors
function initializeBirthdateSelectors() {
    const daySelect = document.getElementById('birth_day');
    const yearSelect = document.getElementById('birth_year');

    // Clear previous options (if any)
    daySelect.innerHTML = '<option value="">Day</option>';
    yearSelect.innerHTML = '<option value="">Year</option>';

    // Populate days (1–31)
    for (let i = 1; i <= 31; i++) {
        const option = document.createElement('option');
        option.value = i.toString().padStart(2, '0');
        option.textContent = i;
        daySelect.appendChild(option);
    }

    // Populate years from 1945 up to the current year (inclusive)
    const currentYear = new Date().getFullYear();
    for (let i = currentYear; i >= 1945; i--) {
        const option = document.createElement('option');
        option.value = i;
        option.textContent = i;
        yearSelect.appendChild(option);
    }
}

// Auto-generate username
// Auto-generate username
function generateUsername() {
    const firstName = document.getElementById('admin_fn').value.trim();
    if (firstName) {
        // Clean the first name: get first word, convert to lowercase, remove special characters
        const cleanFirstName = firstName.split(' ')[0].toLowerCase().replace(/[^a-z0-9]/g, '');
        const username = `admin_${cleanFirstName}`;
        document.getElementById('username').value = username;
        
        // Add note about auto-numbering
        const usernameInput = document.getElementById('username');
        const infoDiv = usernameInput.nextElementSibling;
        if (infoDiv && infoDiv.classList.contains('text-gray-500')) {
            infoDiv.innerHTML = `
                <i class="fas fa-info-circle mr-1"></i>
                Auto-generated: admin_${cleanFirstName} (numbered if duplicate)
            `;
        }
    } else {
        document.getElementById('username').value = '';
    }
}

// Email validation function
function validateEmail(email) {
    // Check if email contains @
    if (!email.includes('@')) {
        return { isValid: false, message: 'Email must contain "@" symbol' };
    }
    
    // Check if email ends with .com
    if (!email.toLowerCase().endsWith('.com')) {
        return { isValid: false, message: 'Email must end with ".com"' };
    }
    
    // Check basic email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        return { isValid: false, message: 'Invalid email format' };
    }
    
    // Check if there's content before @ and after domain
    const parts = email.split('@');
    if (parts[0].length < 1) {
        return { isValid: false, message: 'Email must have content before "@"' };
    }
    
    const domainParts = parts[1].split('.');
    if (domainParts[0].length < 1) {
        return { isValid: false, message: 'Email must have a valid domain' };
    }
    
    return { isValid: true, message: '' };
}

// Real-time email validation
function setupEmailValidation() {
    const emailInput = document.getElementById('user_email');
    const errorDiv = emailInput.parentElement.querySelector('.error-message');
    
    emailInput.addEventListener('input', function() {
        const email = this.value.trim();
        
        if (email === '') {
            // Clear error if empty
            this.classList.remove('border-red-500', 'border-green-500');
            if (errorDiv) {
                errorDiv.classList.add('hidden');
            }
            return;
        }
        
        const validation = validateEmail(email);
        
        if (validation.isValid) {
            this.classList.remove('border-red-500');
            this.classList.add('border-green-500');
            if (errorDiv) {
                errorDiv.classList.add('hidden');
            }
        } else {
            this.classList.remove('border-green-500');
            this.classList.add('border-red-500');
            if (errorDiv) {
                errorDiv.textContent = validation.message;
                errorDiv.classList.remove('hidden');
            }
        }
    });
    
    // Also validate on blur
    emailInput.addEventListener('blur', function() {
        const email = this.value.trim();
        if (email !== '') {
            const validation = validateEmail(email);
            if (!validation.isValid) {
                this.classList.add('animate-shake');
                setTimeout(() => this.classList.remove('animate-shake'), 300);
            }
        }
    });
}

// Photo upload functions
function uploadPhoto() {
    document.getElementById('profileImageInput').click();
}

function removePhoto() {
    selectedImageFile = null;
    imagePreviewUrl = null;
    document.getElementById('profilePreview').classList.add('hidden');
    document.getElementById('uploadPlaceholder').classList.remove('hidden');
    document.getElementById('removePhotoBtn').classList.add('hidden');
    document.getElementById('profileImageInput').value = '';
    clearPhotoError();
}

function clearPhotoError() {
    const errorDiv = document.getElementById('photoError');
    errorDiv.classList.add('hidden');
    errorDiv.textContent = '';
}

function showPhotoError(message) {
    const errorDiv = document.getElementById('photoError');
    errorDiv.textContent = message;
    errorDiv.classList.remove('hidden');
}

// Modal functions
function openAddAdminModal() {
    document.getElementById('addAdminModal').classList.remove('hidden');
    document.getElementById('admin_fn').focus();
}

function closeAddAdminModal() {
    document.getElementById('addAdminModal').classList.add('hidden');
    resetForm();
}

function resetForm() {
    document.getElementById('addAdminForm').reset();
    document.getElementById('customRoleDiv').classList.add('hidden');
    document.getElementById('username').value = '';
    removePhoto();
    clearAllErrors();
}

function confirmClose() {
    const form = document.getElementById('addAdminForm');
    const hasData = Array.from(form.elements).some(element =>
        element.type !== 'submit' && element.type !== 'button' && element.value.trim() !== ''
    ) || selectedImageFile;

    if (hasData) {
        showConfirmation(
            'warning',
            'Discard Changes?',
            'All unsaved changes will be lost.',
            () => closeAddAdminModal(),
            'Discard',
            'bg-red-500 hover:bg-red-600 text-white'
        );
    } else {
        closeAddAdminModal();
    }
}

function showConfirmation(type, title, message, onConfirm, confirmText = 'Confirm', confirmClass = 'bg-blue-500 hover:bg-blue-600 text-white') {
    const modal = document.getElementById('confirmationModal');
    const icon = document.getElementById('confirmIcon');
    const iconClass = document.getElementById('confirmIconClass');
    const titleEl = document.getElementById('confirmTitle');
    const messageEl = document.getElementById('confirmMessage');
    const confirmBtn = document.getElementById('confirmAction');
    const cancelBtn = document.getElementById('confirmCancel');

    if (type === 'warning') {
        icon.className = 'w-12 h-12 mx-auto mb-4 rounded-full flex items-center justify-center bg-yellow-100';
        iconClass.className = 'fas fa-exclamation-triangle text-xl text-yellow-500';
    } else if (type === 'success') {
        icon.className = 'w-12 h-12 mx-auto mb-4 rounded-full flex items-center justify-center bg-green-100';  
        iconClass.className = 'fas fa-check-circle text-xl text-green-500';
    } else if (type === 'question') {
        icon.className = 'w-12 h-12 mx-auto mb-4 rounded-full flex items-center justify-center bg-blue-100';
        iconClass.className = 'fas fa-question-circle text-xl text-blue-500';
    }

    titleEl.textContent = title;
    messageEl.textContent = message;
    confirmBtn.textContent = confirmText;
    confirmBtn.className = `px-4 py-2 text-sm rounded-md transition-colors ${confirmClass}`;

    confirmBtn.onclick = () => {
        modal.classList.add('hidden');
        onConfirm();
    };

    cancelBtn.onclick = () => {
        modal.classList.add('hidden');
    };

    modal.classList.remove('hidden');
}

// Password toggle
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('passwordToggle');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.className = 'fas fa-eye-slash';
    } else {
        passwordInput.type = 'password';
        toggleIcon.className = 'fas fa-eye';
    }
}

// Form validation functions
function validateField(fieldId, validationFn, errorMessage) {
    const field = document.getElementById(fieldId);
    const errorDiv = field.parentElement.querySelector('.error-message');

    if (!validationFn(field.value)) {
        field.classList.add('border-red-500', 'animate-shake');
        if (errorDiv) {
            errorDiv.textContent = errorMessage;
            errorDiv.classList.remove('hidden');
        }
        setTimeout(() => field.classList.remove('animate-shake'), 300);
        return false;
    } else {
        field.classList.remove('border-red-500');
        if (errorDiv) {
            errorDiv.classList.add('hidden');
        }
        return true;
    }
}

function validateEmailField() {
    const emailInput = document.getElementById('user_email');
    const email = emailInput.value.trim();
    const errorDiv = emailInput.parentElement.querySelector('.error-message');
    
    if (!email) {
        emailInput.classList.add('border-red-500', 'animate-shake');
        if (errorDiv) {
            errorDiv.textContent = 'Email address is required';
            errorDiv.classList.remove('hidden');
        }
        setTimeout(() => emailInput.classList.remove('animate-shake'), 300);
        return false;
    }
    
    const validation = validateEmail(email);
    if (!validation.isValid) {
        emailInput.classList.add('border-red-500', 'animate-shake');
        if (errorDiv) {
            errorDiv.textContent = validation.message;
            errorDiv.classList.remove('hidden');
        }
        setTimeout(() => emailInput.classList.remove('animate-shake'), 300);
        return false;
    }
    
    emailInput.classList.remove('border-red-500');
    if (errorDiv) {
        errorDiv.classList.add('hidden');
    }
    return true;
}

function validateBirthdate() {
    const month = document.getElementById('birth_month').value;
    const day = document.getElementById('birth_day').value;
    const year = document.getElementById('birth_year').value;
    const errorDiv = document.getElementById('birthdateError');

    if (!month || !day || !year) {
        errorDiv.textContent = 'Please select a complete birthdate';
        errorDiv.classList.remove('hidden');
       
        // Add red border to empty fields
        if (!month) document.getElementById('birth_month').classList.add('border-red-500');
        if (!day) document.getElementById('birth_day').classList.add('border-red-500');
        if (!year) document.getElementById('birth_year').classList.add('border-red-500');
       
        return false;
    } else {
        errorDiv.classList.add('hidden');
        document.getElementById('birth_month').classList.remove('border-red-500');
        document.getElementById('birth_day').classList.remove('border-red-500');
        document.getElementById('birth_year').classList.remove('border-red-500');
        return true;
    }
}

function clearAllErrors() {
    document.querySelectorAll('.error-message').forEach(error => {
        error.classList.add('hidden');
    });
    document.querySelectorAll('input, select').forEach(field => {
        field.classList.remove('border-red-500', 'border-green-500');
    });
    clearPhotoError();
}

function validateForm() {
    let isValid = true;

    // Validate first name
    if (!validateField('admin_fn', val => val.trim().length >= 2, 'First name required (2+ chars)')) {
        isValid = false;
    }

    // Validate last name
    if (!validateField('admin_ln', val => val.trim().length >= 2, 'Last name required (2+ chars)')) {
        isValid = false;
    }

    // Validate email
    if (!validateEmailField()) {
        isValid = false;
    }

    // Validate birthdate
    if (!validateBirthdate()) {
        isValid = false;
    }

    // Validate role
    const roleSelect = document.getElementById('role');
    if (!roleSelect.value) {
        const errorDiv = roleSelect.parentElement.querySelector('.error-message');
        roleSelect.classList.add('border-red-500');
        if (errorDiv) {
            errorDiv.textContent = 'Please select a position';
            errorDiv.classList.remove('hidden');
        }
        isValid = false;
    } else {
        roleSelect.classList.remove('border-red-500');
        const errorDiv = roleSelect.parentElement.querySelector('.error-message');
        if (errorDiv) {
            errorDiv.classList.add('hidden');
        }
    }

    // Validate custom role if selected
    if (roleSelect.value === 'other') {
        if (!validateField('customRole', val => val.trim().length >= 2, 'Please specify position')) {
            isValid = false;
        }
    }

    // Validate password
    if (!validateField('password',
        val => val.length >= 8 && /[a-zA-Z]/.test(val) && /[0-9]/.test(val),
        'Min 8 chars with letters & numbers')) {
        isValid = false;
    }

    return isValid;
}

// Function to show admin details confirmation modal
function showAdminDetailsConfirmation(data) {
    const modal = document.getElementById('adminDetailsModal');
    const detailsContent = document.getElementById('adminDetailsContent');
   
    // Format the details
    const middleName = data.admin_mn ? ` ${data.admin_mn}` : '';
    const photoStatus = selectedImageFile ? 'Uploaded' : 'No photo';
    const birthdate = formatBirthdate(data.birth_month, data.birth_day, data.birth_year);
   
    detailsContent.innerHTML = `
        <div class="space-y-3 text-sm">
            <div class="flex justify-between items-center">
                <span class="flex items-center space-x-2">
                    <i class="fas fa-user text-gray-400 w-4"></i>
                    <span class="font-medium">Full Name:</span>
                </span>
                <span class="text-gray-800">${data.admin_fn}${middleName} ${data.admin_ln}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="flex items-center space-x-2">
                    <i class="fas fa-envelope text-gray-400 w-4"></i>
                    <span class="font-medium">Email:</span>
                </span>
                <span class="text-gray-800">${data.user_email}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="flex items-center space-x-2">
                    <i class="fas fa-calendar text-gray-400 w-4"></i>
                    <span class="font-medium">Birthdate:</span>
                </span>
                <span class="text-gray-800">${birthdate}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="flex items-center space-x-2">
                    <i class="fas fa-briefcase text-gray-400 w-4"></i>
                    <span class="font-medium">Position:</span>
                </span>
                <span class="text-gray-800">${data.role}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="flex items-center space-x-2">
                    <i class="fas fa-at text-gray-400 w-4"></i>
                    <span class="font-medium">Username:</span>
                </span>
                <span class="text-gray-800">${data.username}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="flex items-center space-x-2">
                    <i class="fas fa-camera text-gray-400 w-4"></i>
                    <span class="font-medium">Profile Photo:</span>
                </span>
                <span class="text-gray-800">${photoStatus}</span>
            </div>
        </div>
    `;

    // Show the modal
    modal.classList.remove('hidden');

    // Handle Cancel button
    document.getElementById('cancelDetailsBtn').onclick = () => {
        modal.classList.add('hidden');
        // Reset confirm button in case it was in loading state
        const confirmBtn = document.getElementById('confirmDetailsBtn');
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = 'Create Admin';
        }
    };

    // Handle Confirm button
    document.getElementById('confirmDetailsBtn').onclick = async () => {
        modal.classList.add('hidden');
        // Show loading indicator
        // showLoadingIndicator();
        // Proceed to create admin
        try {
            await createAdminAccount(data);
        } catch (error) {
            console.error('Admin creation failed:', error);
            hideLoadingIndicator();
            showErrorModal('Failed to create admin account. Please try again.');
        }
    };
}

// Function to show loading indicator
// function showLoadingIndicator() {
//     const loadingOverlay = document.getElementById('loadingOverlay');
    
//     // Create loading overlay if it doesn't exist
//     if (!loadingOverlay) {
//         const overlay = document.createElement('div');
//         overlay.id = 'loadingOverlay';
//         overlay.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50';
//         overlay.innerHTML = `
//             <div class="bg-white rounded-lg p-8 shadow-xl max-w-sm mx-4">
//                 <div class="flex items-center space-x-4">
//                     <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
//                     <div class="text-gray-700 font-medium">Creating admin account...</div>
//                 </div>
//                 <div class="mt-4 text-sm text-gray-500 text-center">
//                     Please wait while we process your request.
//                 </div>
//             </div>
//         `;
//         document.body.appendChild(overlay);
//     } else {
//         loadingOverlay.classList.remove('hidden');
//     }
    
//     console.log('Loading indicator shown');
// }

// Function to hide loading indicator
function hideLoadingIndicator() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    if (loadingOverlay) {
        loadingOverlay.classList.add('hidden');
    }
    
    console.log('Loading indicator hidden');
}

// Get the correct path to create_admin.php
function getCreateAdminPath() {
    // Try to detect the current path and determine the correct path to create_admin.php
    const currentPath = window.location.pathname;
    
    // Common possible paths - adjust these based on your actual file structure
    const possiblePaths = [
        './create_admin.php',  // Same directory
        '../create_admin.php', // Parent directory
        './javascript/USERS/create_admin.php', // Subdirectory
        '../javascript/USERS/create_admin.php', // Parent then subdirectory
        '../../create_admin.php', // Two levels up
        './USERS/create_admin.php', // Users subdirectory
    ];
    
    // For now, let's use a relative path that should work for most cases
    // You may need to adjust this based on your actual file structure
    return './create_admin.php';
}

// Updated create admin account function with better error handling
// Updated create admin account function with the CORRECT path
async function createAdminAccount(data) {
    try {
        console.log('Creating admin account with data:', data);
        
        // Create FormData object to handle file upload
        const formData = new FormData();
        
        // Add all form fields to FormData
        formData.append('admin_fn', data.admin_fn);
        formData.append('admin_mn', data.admin_mn || '');
        formData.append('admin_ln', data.admin_ln);
        formData.append('user_email', data.user_email);
        formData.append('birth_month', data.birth_month);
        formData.append('birth_day', data.birth_day);
        formData.append('birth_year', data.birth_year);
        formData.append('role', data.role);
        formData.append('customRole', data.customRole || '');
        formData.append('username', data.username);
        formData.append('password', data.password);
        
        // Add profile image if exists
        if (selectedImageFile) {
            formData.append('profile_image', selectedImageFile);
            console.log('Profile image attached:', selectedImageFile.name);
        }
        
        // CORRECT PATH: Since your Users.php is in /html/ and create_admin.php is in /javascript/USERS/
        // The relative path from Users.php should be: ../javascript/USERS/create_admin.php
        const createAdminPath = '../javascript/USERS/create_admin.php';
        
        console.log(`Using path: ${createAdminPath}`);
        
        const response = await fetch(createAdminPath, {
            method: 'POST',
            body: formData
        });

        console.log('Response status:', response.status);

        // Get response text first
        const responseText = await response.text();
        console.log('Raw response length:', responseText.length);

        // Clean the response text by removing PHP warnings
        let cleanResponseText = responseText;

        // Remove PHP warning/error blocks
        cleanResponseText = cleanResponseText.replace(/<br\s*\/?>\s*<b>Warning<\/b>:.*?<br\s*\/?>/gi, '');
        cleanResponseText = cleanResponseText.replace(/<br\s*\/?>\s*<b>Fatal error<\/b>:.*?<br\s*\/?>/gi, '');
        cleanResponseText = cleanResponseText.replace(/<br\s*\/?>/g, '');

        // Find the JSON part (should start with { or [)
        const jsonMatch = cleanResponseText.match(/(\{.*\}|\[.*\])/s);
        if (jsonMatch) {
            cleanResponseText = jsonMatch[1];
        }

        console.log('Cleaned response:', cleanResponseText);

        // Try to parse JSON response - USE cleanResponseText instead of responseText
        let result;
        try {
            result = JSON.parse(cleanResponseText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Failed to parse:', cleanResponseText.substring(0, 200));
            throw new Error(`Server returned invalid response. Check PHP error logs.`);
        }
        
        console.log('Parsed result:', result);
        
        hideLoadingIndicator();
        
        if (result.success) {
            showSuccessModal({
                first_name: data.admin_fn,
                last_name: data.admin_ln,
                admin_id: result.admin_id || 'Generated'
            });
        } else {
            // Handle specific error cases
            let errorMessage = result.message || 'Failed to create admin account';
            
            if (errorMessage.includes('Email address already exists')) {
                errorMessage = 'This email address is already registered. Please use a different email.';
            // } else if (errorMessage.includes('Username already exists')) {
            //     errorMessage = 'This username is already taken. Please choose a different username.';
            }
            
            showErrorModal(errorMessage);
        }
        
    } catch (error) {
        console.error('Error creating admin account:', error);
        hideLoadingIndicator();
        
        let errorMessage = 'An error occurred while creating the admin account.';
        
        if (error.name === 'TypeError' && error.message.includes('fetch')) {
            errorMessage = 'Unable to connect to server. Please check if create_admin.php exists at ../javascript/USERS/create_admin.php';
        } else if (error.message.includes('HTTP')) {
            errorMessage = `Server error: ${error.message}. Please check server logs and file permissions.`;
        } else if (error.message.includes('JSON')) {
            errorMessage = `Server returned invalid response: ${error.message}`;
        } else if (error.message.includes('PHP error')) {
            errorMessage = 'PHP error in create_admin.php. Please check the server error logs.';
        } else if (error.message.includes('HTML')) {
            errorMessage = 'create_admin.php file not found or returning HTML. Please check the file path.';
        } else if (error.message) {
            errorMessage = error.message;
        }
        
        showErrorModal(errorMessage);
    }
}

// Function to show success modal
function showSuccessModal(data) {
    const modal = document.getElementById('successModal');
    const message = document.getElementById('successMessage');
    
    // Enhanced success message
    let successText = `Admin account for ${data.first_name} ${data.last_name} has been created successfully!`;
    
    if (data.admin_id) {
        successText += `\n\nAdmin ID: ${data.admin_id}`;
    }
    
    // Add username info if it was auto-numbered
    if (data.username && data.username.match(/\d+$/)) {
        successText += `\nUsername: ${data.username} (auto-numbered to avoid duplicates)`;
    } else if (data.username) {
        successText += `\nUsername: ${data.username}`;
    }
    
    message.textContent = successText;
    modal.classList.remove('hidden');

    // Handle OK button - DELAYED RELOAD
    document.getElementById('successOkBtn').onclick = () => {
        modal.classList.add('hidden');
        closeAddAdminModal();
        
        // Small delay then reload
        setTimeout(() => {
            window.location.reload();
        }, 100);
    };
}

function showErrorModal(errorMessage) {
    const modal = document.getElementById('confirmationModal');
    const icon = document.getElementById('confirmIcon');
    const iconClass = document.getElementById('confirmIconClass');
    const titleEl = document.getElementById('confirmTitle');
    const messageEl = document.getElementById('confirmMessage');
    const confirmBtn = document.getElementById('confirmAction');
    const cancelBtn = document.getElementById('confirmCancel');

    // Set error styling
    icon.className = 'w-12 h-12 mx-auto mb-4 rounded-full flex items-center justify-center bg-red-100';
    iconClass.className = 'fas fa-exclamation-circle text-xl text-red-500';
    
    titleEl.textContent = 'Error Creating Admin';
    
    // Generate context-aware troubleshooting tips
    let troubleshootingTips = '';
    if (errorMessage.includes('email') && errorMessage.includes('exist')) {
        troubleshootingTips = 'The email address you entered is already registered in the system. Please use a different email address to proceed with the admin account creation.';
    } else if (errorMessage.includes('username') && errorMessage.includes('exist')) {
        troubleshootingTips = 'The username you entered is already taken. Please modify the first name or try again to generate a new username.';
    } else if (errorMessage.includes('Database')) {
        troubleshootingTips = 'There seems to be a connection issue with the database. Please try again in a few moments or contact your system administrator.';
    } else if (errorMessage.includes('upload') || errorMessage.includes('image')) {
        troubleshootingTips = 'There was an issue uploading the profile image. Please try with a different image file (PNG, JPG, JPEG only, max 5MB).';
    } else if (errorMessage.includes('connect') || errorMessage.includes('server')) {
        troubleshootingTips = 'Cannot connect to the server. Please check your internet connection and try again.';
    } else {
        troubleshootingTips = 'Please review the form information and try again. Make sure all required fields are filled correctly.';
    }
    
    messageEl.innerHTML = `
        <div class="text-left">
            <p class="mb-3 text-red-600 font-medium">${errorMessage}</p>
            <div class="text-sm text-gray-600 mt-3 p-3 bg-yellow-50 border-l-4 border-yellow-400 rounded">
                <div class="flex items-start">
                    <i class="fas fa-lightbulb text-yellow-500 mt-0.5 mr-2"></i>
                    <div>
                        <strong>What to do next:</strong><br>
                        ${troubleshootingTips}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    confirmBtn.textContent = 'Go Back and Fix';
    confirmBtn.className = 'px-4 py-2 text-sm rounded-md transition-colors bg-blue-500 hover:bg-blue-600 text-white';
    
    // Hide cancel button for error modal
    cancelBtn.style.display = 'none';

    confirmBtn.onclick = () => {
        modal.classList.add('hidden');
        cancelBtn.style.display = 'block'; // Show cancel button again for future use
        
        // Reset the Create Admin button to normal state
        const createAdminBtn = document.querySelector('button[type="submit"]');
        if (createAdminBtn) {
            createAdminBtn.disabled = false;
            createAdminBtn.innerHTML = `
                <i class="fas fa-plus"></i>
                <span>Create Admin</span>
            `;
        }
        
        // Reset the details modal confirm button if it exists
        const confirmDetailsBtn = document.getElementById('confirmDetailsBtn');
        if (confirmDetailsBtn) {
            confirmDetailsBtn.disabled = false;
            confirmDetailsBtn.innerHTML = 'Create Admin';
        }
        
        // Keep the form open so user can make corrections
        // Don't close the addAdminModal - let user fix the issues
    };

    modal.classList.remove('hidden');
}

// Helper function to format birthdate
function formatBirthdate(month, day, year) {
    const monthNames = {
        '01': 'January', '02': 'February', '03': 'March',
        '04': 'April', '05': 'May', '06': 'June',
        '07': 'July', '08': 'August', '09': 'September',
        '10': 'October', '11': 'November', '12': 'December'
    };
    
    const monthName = monthNames[month] || 'Unknown';
    return `${monthName} ${parseInt(day)}, ${year}`;
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Initialize form components
    initializeBirthdateSelectors();
    setupEmailValidation();
    
    // Form submit handler
    const form = document.getElementById('addAdminForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            console.log('Form submitted at:', new Date().toISOString());

            if (!validateForm()) {
                console.log('Form validation failed');
                return;
            }

            const formData = new FormData(this);
           
            const data = {
                admin_fn: formData.get('admin_fn'),
                admin_mn: formData.get('admin_mn') || '',
                admin_ln: formData.get('admin_ln'),
                user_email: formData.get('user_email'),
                birth_month: formData.get('birth_month'),
                birth_day: formData.get('birth_day'),
                birth_year: formData.get('birth_year'),
                role: formData.get('role') === 'other' ? formData.get('customRole') : formData.get('role'),
                customRole: formData.get('customRole'),
                username: formData.get('username'),
                password: formData.get('password')
            };

            console.log('Calling showAdminDetailsConfirmation with:', data);
            showAdminDetailsConfirmation(data);
        });
    }
    
    // Add event listener for first name input to generate username
    const firstNameInput = document.getElementById('admin_fn');
    if (firstNameInput) {
        firstNameInput.addEventListener('input', generateUsername);
        firstNameInput.addEventListener('blur', generateUsername);
    }

    // Handle role selection
    const roleSelect = document.getElementById('role');
    if (roleSelect) {
        roleSelect.addEventListener('change', function () {
            const customRoleDiv = document.getElementById('customRoleDiv');
            const customRoleInput = document.getElementById('customRole');

            if (this.value === 'other') {
                customRoleDiv.classList.remove('hidden');
                customRoleInput.required = true;
            } else {
                customRoleDiv.classList.add('hidden');
                customRoleInput.required = false;
                customRoleInput.value = '';
            }
        });
    }

    // Handle file input change for photo upload
    // Handle file input change for photo upload
const profileImageInput = document.getElementById('profileImageInput');
if (profileImageInput) {
    profileImageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        console.log('File selected:', file.name, 'Size:', file.size, 'Type:', file.type);

        // Validate file type
        const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
        if (!allowedTypes.includes(file.type)) {
            showPhotoError('Only PNG, JPG, and JPEG files are allowed.');
            this.value = '';
            return;
        }

        // Validate file size (5MB max) - IMPROVED ERROR MESSAGE
        if (file.size > 5 * 1024 * 1024) {
            const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
            showPhotoError(`File size is ${fileSizeMB}MB. Maximum allowed size is 5MB. Please choose a smaller image.`);
            this.value = '';
            return;
        }

        clearPhotoError();
        selectedImageFile = file;

        // Create preview
        const reader = new FileReader();
        reader.onload = function(e) {
            const result = e.target.result;
            console.log('FileReader result type:', typeof result);
            console.log('FileReader result length:', result.length);
            
            // Store the base64 data URL for preview
            imagePreviewUrl = result;
            
            const preview = document.getElementById('profilePreview');
            const placeholder = document.getElementById('uploadPlaceholder');
            const removeBtn = document.getElementById('removePhotoBtn');
           
            preview.src = imagePreviewUrl;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
            removeBtn.classList.remove('hidden');
            
            console.log('Image preview updated successfully');
        };
        
        reader.onerror = function(error) {
            console.error('FileReader error:', error);
            showPhotoError('Error reading the image file.');
        };
        
        // Read as data URL (base64) for preview
        reader.readAsDataURL(file);
    });
}

    // Handle profile image container click
    const profileContainer = document.querySelector('.profile-image-container');
    if (profileContainer) {
        profileContainer.addEventListener('click', function() {
            uploadPhoto();
        });
    }
});

// Close modals on outside click
window.addEventListener('click', function (e) {
    const addAdminModal = document.getElementById('addAdminModal');
    const confirmModal = document.getElementById('confirmationModal');
    const detailsModal = document.getElementById('adminDetailsModal');
    const successModal = document.getElementById('successModal');

    if (e.target === addAdminModal) confirmClose();
    if (e.target === confirmModal) confirmModal.classList.add('hidden');
    if (e.target === detailsModal) detailsModal.classList.add('hidden');
    if (e.target === successModal) {
        successModal.classList.add('hidden');
        closeAddAdminModal();
    }
});

// Make functions globally available
window.openAddAdminModal = openAddAdminModal;
window.confirmClose = confirmClose;
window.togglePassword = togglePassword;
window.uploadPhoto = uploadPhoto;
window.removePhoto = removePhoto;
// Global variables to store admin data
let currentEditingAdmin = null;
let currentArchivingAdmin = null;
let photoRemoved = false;
let originalPhotoPath = null; // Track original photo path









// Edit admin function
function editAdmin(adminId) {
    console.log(`Opening edit modal for admin ID: ${adminId}`);
    
    try {
        // Find the admin card to get data
        const adminCard = document.querySelector(`[onclick*="editAdmin('${adminId}')"]`).closest('.admin-card');
        if (!adminCard) {
            showErrorMessage('Admin card not found');
            return;
        }
        
        // Extract admin data from the card
        const adminData = extractAdminDataFromCard(adminCard, adminId);
        if (!adminData) {
            showErrorMessage('Failed to extract admin data');
            return;
        }
        
        // Store current editing admin
        currentEditingAdmin = adminData;
        
        // Show the edit modal first
        const editModal = document.getElementById('editAdminModal');
        if (editModal) {
            editModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        } else {
            showErrorMessage('Edit modal not found');
            return;
        }
        
        // Populate the edit form with basic data
        populateEditFormBasic(adminData);
        
        // Fetch complete admin data including birthdate and proper image path
        fetchCompleteAdminData(adminData.adminId);
        
    } catch (error) {
        console.error('Error in editAdmin:', error);
        showErrorMessage('An error occurred while opening the edit form');
    }
}

// Extract admin data from card
function extractAdminDataFromCard(adminCard, adminId) {
    try {
        // Extract full name
        const fullNameElement = adminCard.querySelector('h3.font-semibold');
        const fullName = fullNameElement ? fullNameElement.textContent.trim() : '';
        
        // Extract position
        const positionElement = adminCard.querySelector('p.text-sm.text-gray-600');
        const position = positionElement ? positionElement.textContent.trim() : '';
        
        // Extract username (from the element with fas fa-user-shield icon)
        const usernameElement = adminCard.querySelector('.fas.fa-user-shield');
        let username = '';
        if (usernameElement && usernameElement.parentElement) {
            username = usernameElement.parentElement.textContent.trim();
        }
        
        // Extract profile image
        const profileImg = adminCard.querySelector('img');
        const profilePicture = profileImg ? profileImg.src : null;
        
        // Parse full name into parts (assuming format: "FirstName MiddleName LastName" or "FirstName LastName")
        const nameParts = fullName.split(' ').filter(part => part.length > 0);
        let firstName = '', middleName = '', lastName = '';
        
        if (nameParts.length >= 3) {
            firstName = nameParts[0];
            middleName = nameParts.slice(1, -1).join(' ');
            lastName = nameParts[nameParts.length - 1];
        } else if (nameParts.length === 2) {
            firstName = nameParts[0];
            lastName = nameParts[1];
        } else if (nameParts.length === 1) {
            firstName = nameParts[0];
        }
        
        return {
            adminId: adminId,
            firstName: firstName,
            middleName: middleName,
            lastName: lastName,
            position: position,
            username: username,
            profilePicture: profilePicture
        };
        
    } catch (error) {
        console.error('Error extracting admin data:', error);
        return null;
    }
}

// Populate edit form with basic admin data (non-editable fields)
function populateEditFormBasic(adminData) {
    try {
        // Clear any existing error messages
        clearErrorMessages();
        
        // Populate basic fields (make them read-only)
        const adminIdField = document.getElementById('edit_admin_id');
        const adminIdDisplayField = document.getElementById('edit_admin_id_display');
        const firstNameField = document.getElementById('edit_admin_fn');
        const middleNameField = document.getElementById('edit_admin_mn');
        const lastNameField = document.getElementById('edit_admin_ln');
        const usernameField = document.getElementById('edit_username');
        
        if (adminIdField) adminIdField.value = adminData.adminId;
        if (adminIdDisplayField) adminIdDisplayField.value = adminData.adminId;
        
        // Make name fields read-only and populate them
        if (firstNameField) {
            firstNameField.value = adminData.firstName;
            firstNameField.readOnly = true;
            firstNameField.classList.add('bg-gray-50', 'text-gray-600', 'cursor-not-allowed');
        }
        
        if (middleNameField) {
            middleNameField.value = adminData.middleName;
            middleNameField.readOnly = true;
            middleNameField.classList.add('bg-gray-50', 'text-gray-600', 'cursor-not-allowed');
        }
        
        if (lastNameField) {
            lastNameField.value = adminData.lastName;
            lastNameField.readOnly = true;
            lastNameField.classList.add('bg-gray-50', 'text-gray-600', 'cursor-not-allowed');
        }
        
        if (usernameField) usernameField.value = adminData.username;
        
        // Initialize birthdate dropdowns (will be populated later)
        initializeBirthdateDropdowns();
        
    } catch (error) {
        console.error('Error populating basic edit form:', error);
        showErrorMessage('Failed to populate form data');
    }
}

// Initialize birthdate dropdowns
function initializeBirthdateDropdowns() {
    const dayField = document.getElementById('edit_birth_day');
    const yearField = document.getElementById('edit_birth_year');
    
    // Populate day dropdown (1-31)
    if (dayField) {
        dayField.innerHTML = '<option value="">Day</option>';
        for (let i = 1; i <= 31; i++) {
            const day = i.toString().padStart(2, '0');
            dayField.innerHTML += `<option value="${day}">${i}</option>`;
        }
    }
    
    // Populate year dropdown (1900 to current year)
    if (yearField) {
        const currentYear = new Date().getFullYear();
        yearField.innerHTML = '<option value="">Year</option>';
        for (let i = currentYear; i >= 1900; i--) {
            yearField.innerHTML += `<option value="${i}">${i}</option>`;
        }
    }
}

// Fetch complete admin data from database
function fetchCompleteAdminData(adminId) {
    // Show loading state on form
    const form = document.getElementById('editAdminForm');
    if (form) {
        const loadingOverlay = document.createElement('div');
        loadingOverlay.id = 'formLoadingOverlay';
        loadingOverlay.className = 'absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10';
        loadingOverlay.innerHTML = `
            <div class="flex items-center space-x-2">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                <span class="text-gray-600">Loading admin data...</span>
            </div>
        `;
        form.style.position = 'relative';
        form.appendChild(loadingOverlay);
    }
    
    fetch('/ALERTPOINT/javascript/USERS/get_admin_data.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ admin_id: adminId })
    })
    .then(response => {
        console.log('Response status:', response.status); // Debug log
        console.log('Response headers:', response.headers.get('content-type')); // Debug log
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.text(); // Get as text first to see what we're receiving
    })
    .then(text => {
        console.log('Raw response:', text); // Debug log
        
        try {
            const data = JSON.parse(text);
            return data;
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text:', text);
            throw new Error('Invalid JSON response from server');
        }
    })
    .then(data => {
        console.log('Parsed data:', data); // Debug log
        
        if (data.success && data.admin) {
            // Update current editing admin with complete data
            currentEditingAdmin = { ...currentEditingAdmin, ...data.admin };
            
            // Populate role field (editable)
            populateRoleField(data.admin.barangay_position);
            
            // Populate birthdate fields (read-only)
            populateBirthdateFields(data.admin.birthdate);
            
            // Handle profile picture display
            handleProfilePictureDisplay(data.admin.picture);
            
        } else {
            throw new Error(data.message || 'Failed to fetch admin data');
        }
    })
    .catch(error => {
        console.error('Error fetching admin data:', error);
        showErrorMessage('Failed to load complete admin information: ' + error.message);
    })
    .finally(() => {
        // Remove loading state
        const loadingOverlay = document.getElementById('formLoadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.remove();
        }
    });
}

// Populate role field
function populateRoleField(barangayPosition) {
    console.log('Populating role field with:', barangayPosition); // Debug log
    
    const roleField = document.getElementById('edit_role');
    const customRoleDiv = document.getElementById('editCustomRoleDiv');
    const customRoleField = document.getElementById('editCustomRole');
    
    if (roleField && barangayPosition) {
        // Clear any previous selections
        roleField.value = '';
        if (customRoleDiv) customRoleDiv.classList.add('hidden');
        if (customRoleField) customRoleField.value = '';
        
        // Check if the position is in the predefined options
        const options = roleField.querySelectorAll('option');
        let found = false;
        
        for (let option of options) {
            if (option.value === barangayPosition) {
                roleField.value = barangayPosition;
                found = true;
                console.log('Found matching option:', barangayPosition);
                break;
            }
        }
        
        // If not found in predefined options, set as custom
        if (!found) {
            console.log('Setting as custom role:', barangayPosition);
            roleField.value = 'other';
            if (customRoleField) {
                customRoleField.value = barangayPosition;
            }
            if (customRoleDiv) {
                customRoleDiv.classList.remove('hidden');
            }
        }
    }
}

// Populate birthdate fields (read-only)
// Populate birthdate fields (read-only)
function populateBirthdateFields(birthdateString) {
    console.log('Processing birthdate:', birthdateString); // Debug log
    
    try {
        if (!birthdateString || birthdateString === '0000-00-00' || birthdateString === '' || birthdateString === null) {
            console.log('No valid birthdate provided');
            return;
        }
        
        let month = '', day = '', year = '';
        
        // Handle format like "November 1, 2007"
        if (birthdateString.includes(',')) {
            const parts = birthdateString.split(',');
            if (parts.length === 2) {
                const monthDay = parts[0].trim().split(' ');
                if (monthDay.length === 2) {
                    const monthName = monthDay[0];
                    day = monthDay[1].padStart(2, '0');
                    year = parts[1].trim();
                    
                    // Convert month name to number
                    const monthMap = {
                        'January': '01', 'February': '02', 'March': '03', 'April': '04',
                        'May': '05', 'June': '06', 'July': '07', 'August': '08',
                        'September': '09', 'October': '10', 'November': '11', 'December': '12'
                    };
                    month = monthMap[monthName] || '';
                }
            }
        }
        // Handle format like "2024-01-02" or "2024-1-2"
        else if (birthdateString.includes('-')) {
            const parts = birthdateString.split('-');
            if (parts.length === 3) {
                year = parts[0];
                month = parts[1].padStart(2, '0');
                day = parts[2].padStart(2, '0');
            }
        }
        
        console.log('Parsed birthdate - Month:', month, 'Day:', day, 'Year:', year); // Debug log
        
        // Set the values and make them read-only
        const monthField = document.getElementById('edit_birth_month');
        const dayField = document.getElementById('edit_birth_day');
        const yearField = document.getElementById('edit_birth_year');
        
        if (monthField && month) {
            monthField.value = month;
            monthField.disabled = true;
            monthField.classList.add('bg-gray-50', 'text-gray-600', 'cursor-not-allowed');
        }
        
        if (dayField && day) {
            dayField.value = day;
            dayField.disabled = true;
            dayField.classList.add('bg-gray-50', 'text-gray-600', 'cursor-not-allowed');
        }
        
        if (yearField && year) {
            yearField.value = year;
            yearField.disabled = true;
            yearField.classList.add('bg-gray-50', 'text-gray-600', 'cursor-not-allowed');
        }
        
    } catch (error) {
        console.error('Error parsing birthdate:', error);
    }
}

// Handle profile picture display with proper path normalization
function handleProfilePictureDisplay(profilePicture) {
    const preview = document.getElementById('editProfilePreview');
    const placeholder = document.getElementById('editUploadPlaceholder');
    const removeBtn = document.getElementById('editRemovePhotoBtn');
    
    // Store original photo path and reset removal flag
    originalPhotoPath = profilePicture;
    photoRemoved = false;
    
    console.log('handleProfilePictureDisplay - Original picture path:', profilePicture);
    console.log('handleProfilePictureDisplay - Reset photoRemoved to:', photoRemoved);
    
    if (profilePicture && profilePicture !== 'null' && profilePicture !== '' && profilePicture !== 'NULL') {
        let normalizedPath = profilePicture;
        
        if (profilePicture.startsWith('../../')) {
            normalizedPath = profilePicture.replace('../../', '/ALERTPOINT/');
        } else if (!profilePicture.startsWith('/ALERTPOINT/') && !profilePicture.startsWith('http')) {
            normalizedPath = '/ALERTPOINT/' + profilePicture.replace(/^\/+/, '');
        }
        
        console.log('Normalized picture path:', normalizedPath);
        
        if (preview) {
            preview.src = normalizedPath;
            preview.classList.remove('hidden');
            
            preview.onerror = function() {
                console.warn('Failed to load image:', normalizedPath);
                this.classList.add('hidden');
                if (placeholder) placeholder.classList.remove('hidden');
                if (removeBtn) removeBtn.classList.add('hidden');
            };
            
            preview.onload = function() {
                console.log('Image loaded successfully:', normalizedPath);
                if (placeholder) placeholder.classList.add('hidden');
                if (removeBtn) removeBtn.classList.remove('hidden');
            };
        }
        
        if (placeholder) {
            placeholder.classList.add('hidden');
        }
        
        if (removeBtn) {
            removeBtn.classList.remove('hidden');
        }
    } else {
        console.log('No profile picture or invalid path');
        if (preview) {
            preview.classList.add('hidden');
            preview.src = '';
        }
        if (placeholder) {
            placeholder.classList.remove('hidden');
        }
        if (removeBtn) {
            removeBtn.classList.add('hidden');
        }
    }
}

// Handle custom role selection
function handleCustomRoleSelection() {
    const roleField = document.getElementById('edit_role');
    const customRoleDiv = document.getElementById('editCustomRoleDiv');
    
    if (roleField && customRoleDiv) {
        if (roleField.value === 'other') {
            customRoleDiv.classList.remove('hidden');
        } else {
            customRoleDiv.classList.add('hidden');
        }
    }
}

// Photo upload functions
function editUploadPhoto() {
    const input = document.getElementById('editProfileImageInput');
    if (input) {
        input.click();
    }
}

function editRemovePhoto() {
    console.log('editRemovePhoto called');
    
    const preview = document.getElementById('editProfilePreview');
    const placeholder = document.getElementById('editUploadPlaceholder');
    const removeBtn = document.getElementById('editRemovePhotoBtn');
    const input = document.getElementById('editProfileImageInput');
    
    if (preview) {
        preview.classList.add('hidden');
        preview.src = '';
    }
    if (placeholder) placeholder.classList.remove('hidden');
    if (removeBtn) removeBtn.classList.add('hidden');
    if (input) input.value = '';
    
    // Track that photo was removed
    photoRemoved = true;
    console.log('Photo removal tracked - photoRemoved:', photoRemoved);
    console.log('Original photo path:', originalPhotoPath);
}

// Close edit modal
function closeEditAdminModal() {
    const editModal = document.getElementById('editAdminModal');
    if (editModal) {
        editModal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
    
    // Reset all flags and variables
    currentEditingAdmin = null;
    photoRemoved = false;
    originalPhotoPath = null;
    
    console.log('Modal closed - Reset all flags');
    
    // Clear form
    const form = document.getElementById('editAdminForm');
    if (form) {
        form.reset();
        
        // Remove read-only classes from name fields
        const nameFields = ['edit_admin_fn', 'edit_admin_mn', 'edit_admin_ln'];
        nameFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.readOnly = false;
                field.classList.remove('bg-gray-50', 'text-gray-600', 'cursor-not-allowed');
            }
        });
        
        // Remove disabled state from birthdate fields
        const birthdateFields = ['edit_birth_month', 'edit_birth_day', 'edit_birth_year'];
        birthdateFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.disabled = false;
                field.classList.remove('bg-gray-50', 'text-gray-600', 'cursor-not-allowed');
            }
        });
    }
    
    clearErrorMessages();
}

// Archive admin function
function archiveAdmin(adminId) {
    console.log(`Opening archive confirmation for admin ID: ${adminId}`);
    
    try {
        // Find the admin card to get data
        const adminCard = document.querySelector(`[onclick*="archiveAdmin('${adminId}')"]`).closest('.admin-card');
        if (!adminCard) {
            showErrorMessage('Admin card not found');
            return;
        }
        
        // Extract admin data
        const adminData = extractAdminDataFromCard(adminCard, adminId);
        if (!adminData) {
            showErrorMessage('Failed to extract admin data');
            return;
        }
        
        // Store current archiving admin
        currentArchivingAdmin = adminData;
        
        // Populate archive confirmation details
        populateArchiveConfirmation(adminData);
        
        // Show archive confirmation modal
        const archiveModal = document.getElementById('archiveConfirmationModal');
        if (archiveModal) {
            archiveModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        } else {
            showErrorMessage('Archive confirmation modal not found');
        }
        
    } catch (error) {
        console.error('Error in archiveAdmin:', error);
        showErrorMessage('An error occurred while opening archive confirmation');
    }
}

// Populate archive confirmation details
function populateArchiveConfirmation(adminData) {
    const detailsDiv = document.getElementById('archiveAdminDetails');
    if (detailsDiv) {
        detailsDiv.innerHTML = `
            <div class="text-sm">
                <p class="font-medium text-gray-900">${adminData.firstName} ${adminData.middleName} ${adminData.lastName}</p>
                <p class="text-gray-600">${adminData.position}</p>
                <p class="text-gray-500">ID: ${adminData.adminId}</p>
            </div>
        `;
    }
}

// Utility functions
function showErrorMessage(message) {
    // Create or update error notification
    let errorNotification = document.getElementById('errorNotification');
    
    if (!errorNotification) {
        errorNotification = document.createElement('div');
        errorNotification.id = 'errorNotification';
        errorNotification.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg z-50 max-w-md';
        document.body.appendChild(errorNotification);
    }
    
    errorNotification.innerHTML = `
        <div class="flex items-center space-x-2">
            <i class="fas fa-exclamation-triangle"></i>
            <span>${message}</span>
            <button onclick="closeErrorNotification()" class="ml-2 text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    errorNotification.classList.remove('hidden');
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        if (errorNotification) {
            errorNotification.classList.add('hidden');
        }
    }, 5000);
    
    console.error('Error:', message);
}

function closeErrorNotification() {
    const errorNotification = document.getElementById('errorNotification');
    if (errorNotification) {
        errorNotification.classList.add('hidden');
    }
}

function clearErrorMessages() {
    const errorElements = document.querySelectorAll('.error-message');
    errorElements.forEach(element => {
        element.classList.add('hidden');
        element.textContent = '';
    });
    
    // Clear field error states
    const fields = document.querySelectorAll('.border-red-500');
    fields.forEach(field => {
        field.classList.remove('border-red-500');
    });
}

// Event listeners for modals
document.addEventListener('DOMContentLoaded', function() {
    // Edit form submission
    const editForm = document.getElementById('editAdminForm');
    if (editForm) {
        editForm.addEventListener('submit', handleEditFormSubmission);
    }
    
    // Role selection change
    const roleField = document.getElementById('edit_role');
    if (roleField) {
        roleField.addEventListener('change', handleCustomRoleSelection);
    }
    
    // Profile image input change
    const profileInput = document.getElementById('editProfileImageInput');
    if (profileInput) {
        profileInput.addEventListener('change', handleProfileImageChange);
    }
    
    // Archive confirmation buttons
    const cancelArchiveBtn = document.getElementById('cancelArchiveBtn');
    const confirmArchiveBtn = document.getElementById('confirmArchiveBtn');
    
    if (cancelArchiveBtn) {
        cancelArchiveBtn.addEventListener('click', cancelArchiveAction); // Changed from closeArchiveModal
    }
    
    if (confirmArchiveBtn) {
        confirmArchiveBtn.addEventListener('click', confirmArchiveAdmin);
    }
    
    // Edit confirmation buttons
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const confirmEditBtn = document.getElementById('confirmEditBtn');
    
    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', closeEditConfirmationModal);
    }
    
    if (confirmEditBtn) {
        confirmEditBtn.addEventListener('click', confirmEditAdmin);
    }
    
    // Success modal buttons
    const editSuccessBtn = document.getElementById('editSuccessOkBtn');
    const archiveSuccessBtn = document.getElementById('archiveSuccessOkBtn');
    
    if (editSuccessBtn) {
        editSuccessBtn.addEventListener('click', closeEditSuccessModal);
    }
    
    if (archiveSuccessBtn) {
        archiveSuccessBtn.addEventListener('click', closeArchiveSuccessModal);
    }
});

// Handle profile image change
function handleProfileImageChange(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('editProfilePreview');
    const placeholder = document.getElementById('editUploadPlaceholder');
    const removeBtn = document.getElementById('editRemovePhotoBtn');
    const errorDiv = document.getElementById('editPhotoError');
    
    // Reset photo removal flag when new image is selected
    photoRemoved = false;
    console.log('New image selected - Reset photoRemoved to:', photoRemoved);
    
    // Clear previous errors
    if (errorDiv) {
        errorDiv.classList.add('hidden');
        errorDiv.textContent = '';
    }
    
    if (file) {
        // Validate file type
        const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
        if (!allowedTypes.includes(file.type)) {
            if (errorDiv) {
                errorDiv.textContent = 'Only PNG, JPG, and JPEG files are allowed';
                errorDiv.classList.remove('hidden');
            }
            event.target.value = '';
            return;
        }
        
        // Validate file size (5MB max)
        const maxSize = 5 * 1024 * 1024; // 5MB in bytes
        if (file.size > maxSize) {
            if (errorDiv) {
                errorDiv.textContent = 'File size must be less than 5MB';
                errorDiv.classList.remove('hidden');
            }
            event.target.value = '';
            return;
        }
        
        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            if (preview) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
            }
            if (placeholder) {
                placeholder.classList.add('hidden');
            }
            if (removeBtn) {
                removeBtn.classList.remove('hidden');
            }
        };
        reader.readAsDataURL(file);
    }
}

// Handle edit form submission
function handleEditFormSubmission(event) {
    event.preventDefault();
    
    try {
        // Validate form
        if (!validateEditForm()) {
            return;
        }
        
        // Show confirmation modal
        showEditConfirmation();
        
    } catch (error) {
        console.error('Error handling form submission:', error);
        showErrorMessage('An error occurred while processing the form');
    }
}

// Validate edit form
function validateEditForm() {
    let isValid = true;
    
    // Clear previous errors
    clearErrorMessages();
    
    // Validate required fields (only role is editable and required)
    const roleField = document.getElementById('edit_role');
    if (roleField && !roleField.value) {
        showFieldError('edit_role', 'Position/Role is required');
        isValid = false;
    }
    
    // Validate custom role if selected
    if (roleField && roleField.value === 'other') {
        const customRoleField = document.getElementById('editCustomRole');
        if (customRoleField && !customRoleField.value.trim()) {
            showFieldError('editCustomRole', 'Please specify the position');
            isValid = false;
        }
    }
    
    return isValid;
}

// Show field error
function showFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    if (field) {
        const errorDiv = field.parentElement.querySelector('.error-message');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.classList.remove('hidden');
        }
        field.classList.add('border-red-500');
    }
}

// Update your existing showEditConfirmation function
function showEditConfirmation() {
    const confirmationModal = document.getElementById('editConfirmationModal');
    const contentDiv = document.getElementById('editConfirmationContent');
    
    console.log('showEditConfirmation called');
    console.log('photoRemoved:', photoRemoved);
    console.log('originalPhotoPath:', originalPhotoPath);
    
    if (confirmationModal && contentDiv && currentEditingAdmin) {
        const roleField = document.getElementById('edit_role');
        const customRoleField = document.getElementById('editCustomRole');
        const imageInput = document.getElementById('editProfileImageInput');
        
        let roleText = roleField ? roleField.value : '';
        if (roleText === 'other' && customRoleField) {
            roleText = customRoleField.value;
        }
        
        const hasNewImage = imageInput && imageInput.files.length > 0;
        
        // Determine photo change status with detailed logging
        let photoStatus = 'No changes';
        
        console.log('Photo status check:');
        console.log('- photoRemoved:', photoRemoved);
        console.log('- hasNewImage:', hasNewImage);
        console.log('- originalPhotoPath:', originalPhotoPath);
        
        if (photoRemoved && originalPhotoPath) {
            photoStatus = 'Will be removed';
            console.log('Photo status set to: Will be removed');
        } else if (hasNewImage) {
            photoStatus = 'Will be updated';
            console.log('Photo status set to: Will be updated');
        } else {
            console.log('Photo status remains: No changes');
        }
        
        contentDiv.innerHTML = `
            <div class="space-y-2 text-sm">
                <p><span class="font-medium">Admin:</span> ${currentEditingAdmin.firstName} ${currentEditingAdmin.middleName} ${currentEditingAdmin.lastName}</p>
                <p><span class="font-medium">ID:</span> ${currentEditingAdmin.adminId}</p>
                <p><span class="font-medium">New Position:</span> ${roleText}</p>
                <p><span class="font-medium">Profile Picture:</span> ${photoStatus}</p>
            </div>
        `;
        
        confirmationModal.classList.remove('hidden');
    }
}

// Close edit confirmation modal
function closeEditConfirmationModal() {
    const confirmationModal = document.getElementById('editConfirmationModal');
    if (confirmationModal) {
        confirmationModal.classList.add('hidden');
    }
}

// Confirm edit admin
function confirmEditAdmin() {
    console.log('confirmEditAdmin called');
    console.log('photoRemoved:', photoRemoved);
    console.log('originalPhotoPath:', originalPhotoPath);
    
    closeEditConfirmationModal();
    
    const formData = new FormData();
    formData.append('admin_id', currentEditingAdmin.adminId);
    
    const roleField = document.getElementById('edit_role');
    const customRoleField = document.getElementById('editCustomRole');
    const imageInput = document.getElementById('editProfileImageInput');
    
    // Add role data
    if (roleField) {
        if (roleField.value === 'other' && customRoleField) {
            formData.append('barangay_position', customRoleField.value);
        } else {
            formData.append('barangay_position', roleField.value);
        }
    }
    
    // Add image data or removal flag
    if (photoRemoved && originalPhotoPath) {
        formData.append('remove_photo', 'true');
        console.log('Added remove_photo flag to form data');
    } else if (imageInput && imageInput.files.length > 0) {
        formData.append('profile_image', imageInput.files[0]);
        console.log('Added new profile image to form data');
    }
    
    // Debug: Log all form data
    console.log('Form data being sent:');
    for (let [key, value] of formData.entries()) {
        console.log(key, value);
    }
    
    // Show loading state
    const editModal = document.getElementById('editAdminModal');
    if (editModal) {
        const loadingOverlay = document.createElement('div');
        loadingOverlay.id = 'updateLoadingOverlay';
        loadingOverlay.className = 'absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-20';
        loadingOverlay.innerHTML = `
            <div class="flex items-center space-x-2">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                <span class="text-gray-600">Updating admin...</span>
            </div>
        `;
        editModal.style.position = 'relative';
        editModal.appendChild(loadingOverlay);
    }
    
    fetch('/ALERTPOINT/javascript/USERS/update_admin.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Update response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.text();
    })
    .then(text => {
        console.log('Update raw response:', text);
        
        try {
            const data = JSON.parse(text);
            return data;
        } catch (e) {
            console.error('Update JSON parse error:', e);
            console.error('Update response text:', text);
            throw new Error('Invalid JSON response from server');
        }
    })
    .then(data => {
        console.log('Update parsed data:', data);
        
        if (data.success) {
            closeEditAdminModal();
            showEditSuccessModal(data.message || 'Admin updated successfully');
            
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            throw new Error(data.message || 'Failed to update admin');
        }
    })
    .catch(error => {
        console.error('Error updating admin:', error);
        showErrorMessage('Failed to update admin: ' + error.message);
    })
    .finally(() => {
        const loadingOverlay = document.getElementById('updateLoadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.remove();
        }
    });
}

// Show edit success modal
function showEditSuccessModal(message) {
    const successModal = document.getElementById('editSuccessModal');
    const messageElement = document.getElementById('editSuccessMessage');
    
    if (successModal) {
        if (messageElement) {
            messageElement.textContent = message;
        }
        successModal.classList.remove('hidden');
    }
}

// Close edit success modal
function closeEditSuccessModal() {
    const successModal = document.getElementById('editSuccessModal');
    if (successModal) {
        successModal.classList.add('hidden');
    }
}

// Close archive modal
function closeArchiveModal() {
    console.log('closeArchiveModal called');
    console.log('currentArchivingAdmin before close:', currentArchivingAdmin);
    
    const archiveModal = document.getElementById('archiveConfirmationModal');
    if (archiveModal) {
        archiveModal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
    
    // DO NOT reset currentArchivingAdmin here - keep it until after confirmation
    // Only reset it if user actually cancels (not when confirming)
    console.log('Archive modal closed, currentArchivingAdmin preserved:', currentArchivingAdmin);
}

// Add a separate function for when user actually cancels
function cancelArchiveAction() {
    console.log('User cancelled archive action');
    currentArchivingAdmin = null;
    closeArchiveModal();
}

// New function to update admin counts (add this function)
function updateAdminCounts() {
    // Update total counts displayed on the page if they exist
    const activeCountElement = document.querySelector('.total-active-admins'); // Adjust selector
    const archivedCountElement = document.querySelector('.total-archived-admins'); // Adjust selector
    
    if (activeCountElement) {
        const currentCount = parseInt(activeCountElement.textContent) || 0;
        activeCountElement.textContent = Math.max(0, currentCount - 1);
    }
    
    if (archivedCountElement) {
        const currentCount = parseInt(archivedCountElement.textContent) || 0;
        archivedCountElement.textContent = currentCount + 1;
    }
}

// Confirm archive admin - FIXED WITH DEBUGGING
function confirmArchiveAdmin() {
    console.log('=== confirmArchiveAdmin DEBUG START ===');
    console.log('1. Function called');
    console.log('2. currentArchivingAdmin value:', currentArchivingAdmin);
    console.log('3. currentArchivingAdmin type:', typeof currentArchivingAdmin);
    
    if (currentArchivingAdmin) {
        console.log('4. currentArchivingAdmin properties:', Object.keys(currentArchivingAdmin));
        console.log('5. adminId value:', currentArchivingAdmin.adminId);
    } else {
        console.error('4. currentArchivingAdmin is null/undefined!');
    }
    
    if (!currentArchivingAdmin) {
        console.error('FATAL: No admin selected for archiving');
        showErrorMessage('No admin selected for archiving');
        return;
    }
    
    if (!currentArchivingAdmin.adminId) {
        console.error('FATAL: currentArchivingAdmin exists but adminId is missing');
        console.log('Available properties:', Object.keys(currentArchivingAdmin));
        showErrorMessage('Admin ID is missing');
        return;
    }
    
    console.log('6. Validation passed, proceeding with archive...');
    
    // Close confirmation modal
    closeArchiveModal();
    
    // Show loading state on the admin card
    const adminCard = document.querySelector(`[onclick*="archiveAdmin('${currentArchivingAdmin.adminId}')"]`).closest('.admin-card');
    console.log('7. Found admin card:', !!adminCard);
    
    if (adminCard) {
        adminCard.style.opacity = '0.5';
        adminCard.style.pointerEvents = 'none';
        
        // Add loading spinner to the card
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10';
        loadingDiv.innerHTML = `
            <div class="flex items-center space-x-2">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-orange-600"></div>
                <span class="text-gray-600 text-sm">Archiving...</span>
            </div>
        `;
        adminCard.style.position = 'relative';
        adminCard.appendChild(loadingDiv);
    }
    
    // Prepare the request data
    const requestData = { 
        admin_id: currentArchivingAdmin.adminId 
    };
    
    console.log('8. Sending archive request with data:', requestData);
    
    // Send archive request
    fetch('/ALERTPOINT/javascript/USERS/archive_admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(requestData)
    })
    .then(response => {
        console.log('9. Archive response received:', {
            status: response.status,
            statusText: response.statusText
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status} - ${response.statusText}`);
        }
        
        return response.text();
    })
    .then(text => {
        console.log('10. Archive raw response text:', text);
        
        let data;
        try {
            data = JSON.parse(text);
            console.log('11. Archive parsed JSON data:', data);
        } catch (e) {
            console.error('Failed to parse JSON response:', e);
            throw new Error('Server returned invalid JSON response: ' + text.substring(0, 200));
        }
        
        return data;
    })
    .then(data => {
        console.log('12. Processing archive response data:', data);
        
        if (data.success === true) {
            console.log('13. Archive successful, proceeding with UI updates');
            
            // Remove the admin card with smooth animation
            if (adminCard) {
                adminCard.style.transform = 'scale(0.8)';
                adminCard.style.opacity = '0';
                adminCard.style.transition = 'all 0.3s ease-out';
                
                setTimeout(() => {
                    adminCard.remove();
                    console.log('14. Admin card removed from DOM');
                    
                    // Check if there are no more active admins
                    const remainingCards = document.querySelectorAll('.admin-card');
                    console.log('15. Remaining admin cards:', remainingCards.length);
                    
                    if (remainingCards.length === 0) {
                        const gridContainer = document.querySelector('.grid');
                        if (gridContainer) {
                            const noAdminsDiv = document.createElement('div');
                            noAdminsDiv.className = 'col-span-full text-center py-8 text-gray-500';
                            noAdminsDiv.id = 'no-active-admins';
                            noAdminsDiv.innerHTML = `
                                <i class="fas fa-user-shield text-4xl mb-4 text-gray-400"></i>
                                <p class="text-lg font-semibold">No Active Admins</p>
                                <p class="text-sm">No active admin accounts found in the database.</p>
                            `;
                            gridContainer.appendChild(noAdminsDiv);
                            console.log('16. Added "No Active Admins" message');
                        }
                    }
                }, 300);
            }
            
            // Show success modal
            console.log('17. Showing archive success modal with message:', data.message);
            showArchiveSuccessModal(data.message || 'Admin archived successfully');
            
            // Reset currentArchivingAdmin after successful archive
            currentArchivingAdmin = null;
            console.log('18. Reset currentArchivingAdmin after success');
            
        } else {
            console.error('Archive failed with message:', data.message);
            throw new Error(data.message || 'Failed to archive admin - server returned success: false');
        }
    })
    .catch(error => {
        console.error('Error archiving admin:', error);
        
        showErrorMessage('Failed to archive admin: ' + error.message);
        
        // Restore admin card state
        if (adminCard) {
            adminCard.style.opacity = '1';
            adminCard.style.pointerEvents = 'auto';
            adminCard.style.transform = 'scale(1)';
            adminCard.style.transition = '';
            
            // Remove loading spinner
            const loadingDiv = adminCard.querySelector('.absolute.inset-0');
            if (loadingDiv) {
                loadingDiv.remove();
            }
        }
        
        // Reset currentArchivingAdmin on error too
        currentArchivingAdmin = null;
        console.log('Reset currentArchivingAdmin after error');
    });
    
    console.log('=== confirmArchiveAdmin DEBUG END ===');
}

// Show archive success modal
function showArchiveSuccessModal(message) {
    console.log('showArchiveSuccessModal called with message:', message);
    
    const successModal = document.getElementById('archiveSuccessModal');
    const messageElement = document.getElementById('archiveSuccessMessage');
    
    console.log('Success modal element:', successModal);
    console.log('Message element:', messageElement);
    
    if (successModal) {
        if (messageElement) {
            messageElement.textContent = message;
            console.log('Set message text to:', message);
        }
        
        // Remove hidden class and set display flex to enable centering
        successModal.classList.remove('hidden');
        successModal.style.display = 'flex'; // This is needed for items-center and justify-center to work
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
        console.log('Success modal should now be visible');
        
    } else {
        console.error('Archive success modal not found in DOM');
        // Fallback: show alert if modal not found
        alert(message);
    }
}

// Close archive success modal
function closeArchiveSuccessModal() {
    console.log('closeArchiveSuccessModal called');
    
    const successModal = document.getElementById('archiveSuccessModal');
    if (successModal) {
        // Add hidden class first
        successModal.classList.add('hidden');
        // Remove the inline style completely to let the hidden class work
        successModal.style.removeProperty('display');
        successModal.style.visibility = 'hidden';
        document.body.style.overflow = 'auto';
        console.log('Archive success modal closed');
    } else {
        console.error('Archive success modal not found when trying to close');
    }
    
    // Reset archiving admin
    currentArchivingAdmin = null;
    
    // Force reload immediately
    console.log('Forcing page reload...');
    window.location.replace(window.location.href);
}

// // Also add this enhanced version of closeArchiveSuccessModal
// function closeArchiveSuccessModal() {
//     console.log('closeArchiveSuccessModal called');
    
//     const successModal = document.getElementById('archiveSuccessModal');
//     if (successModal) {
//         successModal.classList.add('hidden');
//         document.body.style.overflow = 'auto'; // Restore scrolling
//         console.log('Archive success modal closed');
//     } else {
//         console.error('Archive success modal not found when trying to close');
//     }
    
//     // Reset archiving admin
//     currentArchivingAdmin = null;
// }
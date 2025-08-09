// Global variables for edit functionality
// Global variables (add these to existing global variables)
let currentRestoringAdminId = null;
let currentDeletingAdminId = null;
let currentEditingAdminId = null;
let currentArchivingAdminId = null;
let hasImageChanged = false;
let originalImageSrc = '';

// Function to fetch admin data for editing
async function fetchAdminData(adminId) {
    try {
        const response = await fetch('../javascript/USERS/admin_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=get_admin&admin_id=${adminId}`
        });
        
        const data = await response.json();
        if (data.success) {
            return data.admin;
        } else {
            throw new Error(data.message || 'Failed to fetch admin data');
        }
    } catch (error) {
        console.error('Error fetching admin data:', error);
        alert('Error loading admin data. Please try again.');
        return null;
    }
}

// Edit Admin function
async function editAdmin(adminId) {
    currentEditingAdminId = adminId;
    
    // Fetch admin data
    const adminData = await fetchAdminData(adminId);
    if (!adminData) return;
    
    // Populate the edit form
    populateEditForm(adminData);
    
    // Show the edit modal
    const modal = document.getElementById('editAdminModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

// Populate edit form with admin data
function populateEditForm(adminData) {
    // Basic info
    document.getElementById('edit_admin_id').value = adminData.id;
    document.getElementById('edit_admin_id_display').value = adminData.admin_id;
    document.getElementById('edit_admin_fn').value = adminData.first_name || '';
    document.getElementById('edit_admin_mn').value = adminData.middle_name || '';
    document.getElementById('edit_admin_ln').value = adminData.last_name || '';
    document.getElementById('edit_username').value = adminData.username || '';
    
    // Handle position/role
    const roleSelect = document.getElementById('edit_role');
    const customRoleDiv = document.getElementById('editCustomRoleDiv');
    const customRoleInput = document.getElementById('editCustomRole');
    
    // Check if the role exists in the dropdown
    const roleOptions = Array.from(roleSelect.options).map(option => option.value);
    if (roleOptions.includes(adminData.barangay_position)) {
        roleSelect.value = adminData.barangay_position;
        customRoleDiv.classList.add('hidden');
    } else {
        roleSelect.value = 'other';
        customRoleDiv.classList.remove('hidden');
        customRoleInput.value = adminData.barangay_position;
    }
    
    // Handle birthdate
    if (adminData.birthdate) {
        parseBirthdate(adminData.birthdate);
    }
    
    // Handle profile picture
    const profilePreview = document.getElementById('editProfilePreview');
    const uploadPlaceholder = document.getElementById('editUploadPlaceholder');
    const removeBtn = document.getElementById('editRemovePhotoBtn');
    
    if (adminData.picture) {
        let picturePath = adminData.picture;
        if (!picturePath.startsWith('/ALERTPOINT')) {
            picturePath = '/ALERTPOINT' + picturePath;
        }
        
        profilePreview.src = picturePath;
        profilePreview.classList.remove('hidden');
        uploadPlaceholder.classList.add('hidden');
        removeBtn.classList.remove('hidden');
        originalImageSrc = picturePath;
    } else {
        profilePreview.classList.add('hidden');
        uploadPlaceholder.classList.remove('hidden');
        removeBtn.classList.add('hidden');
        originalImageSrc = '';
    }
    
    hasImageChanged = false;
    
    // Populate birthdate dropdowns
    populateEditBirthdateDropdowns();
}

// Parse and set birthdate
function parseBirthdate(birthdateStr) {
    try {
        // Handle format: "January 2, 2024" or similar
        if (birthdateStr.includes(',')) {
            // Split by comma to separate year
            const parts = birthdateStr.split(',');
            if (parts.length === 2) {
                const year = parts[1].trim();
                const monthDay = parts[0].trim().split(' ');
                
                if (monthDay.length === 2) {
                    const monthName = monthDay[0];
                    const day = monthDay[1];
                    
                    // Convert month name to number
                    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                                     'July', 'August', 'September', 'October', 'November', 'December'];
                    const monthIndex = monthNames.indexOf(monthName);
                    
                    if (monthIndex !== -1) {
                        const month = String(monthIndex + 1).padStart(2, '0');
                        const dayPadded = String(parseInt(day)).padStart(2, '0');
                        
                        document.getElementById('edit_birth_month').value = month;
                        document.getElementById('edit_birth_day').value = dayPadded;
                        document.getElementById('edit_birth_year').value = year;
                        return;
                    }
                }
            }
        }
        
        // Fallback: try parsing as date
        const date = new Date(birthdateStr);
        if (!isNaN(date.getTime())) {
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const year = String(date.getFullYear());
            
            document.getElementById('edit_birth_month').value = month;
            document.getElementById('edit_birth_day').value = day;
            document.getElementById('edit_birth_year').value = year;
        }
    } catch (error) {
        console.error('Error parsing birthdate:', error);
    }
}

// Populate birthdate dropdowns for edit form
function populateEditBirthdateDropdowns() {
    const daySelect = document.getElementById('edit_birth_day');
    const yearSelect = document.getElementById('edit_birth_year');
    
    // Populate days (1-31)
    daySelect.innerHTML = '<option value="">Day</option>';
    for (let i = 1; i <= 31; i++) {
        const day = String(i).padStart(2, '0');
        daySelect.innerHTML += `<option value="${day}">${i}</option>`;
    }
    
    // Populate years (current year - 80 to current year - 18)
    const currentYear = new Date().getFullYear();
    yearSelect.innerHTML = '<option value="">Year</option>';
    for (let i = currentYear - 18; i >= currentYear - 80; i--) {
        yearSelect.innerHTML += `<option value="${i}">${i}</option>`;
    }
}

// Handle role change in edit form
document.addEventListener('DOMContentLoaded', function() {
    const editRoleSelect = document.getElementById('edit_role');
    if (editRoleSelect) {
        editRoleSelect.addEventListener('change', function() {
            const customRoleDiv = document.getElementById('editCustomRoleDiv');
            if (this.value === 'other') {
                customRoleDiv.classList.remove('hidden');
            } else {
                customRoleDiv.classList.add('hidden');
            }
        });
    }
});

// Edit photo functions
function editUploadPhoto() {
    document.getElementById('editProfileImageInput').click();
}

function editRemovePhoto() {
    const profilePreview = document.getElementById('editProfilePreview');
    const uploadPlaceholder = document.getElementById('editUploadPlaceholder');
    const removeBtn = document.getElementById('editRemovePhotoBtn');
    const fileInput = document.getElementById('editProfileImageInput');
    
    profilePreview.classList.add('hidden');
    uploadPlaceholder.classList.remove('hidden');
    removeBtn.classList.add('hidden');
    fileInput.value = '';
    hasImageChanged = true;
}

// Handle edit photo upload
document.addEventListener('DOMContentLoaded', function() {
    const editFileInput = document.getElementById('editProfileImageInput');
    if (editFileInput) {
        editFileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file type
                const allowedTypes = ['image/png', 'image/jpg', 'image/jpeg'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a PNG, JPG, or JPEG file.');
                    this.value = '';
                    return;
                }
                
                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB.');
                    this.value = '';
                    return;
                }
                
                // Preview the image
                const reader = new FileReader();
                reader.onload = function(e) {
                    const profilePreview = document.getElementById('editProfilePreview');
                    const uploadPlaceholder = document.getElementById('editUploadPlaceholder');
                    const removeBtn = document.getElementById('editRemovePhotoBtn');
                    
                    profilePreview.src = e.target.result;
                    profilePreview.classList.remove('hidden');
                    uploadPlaceholder.classList.add('hidden');
                    removeBtn.classList.remove('hidden');
                    hasImageChanged = true;
                };
                reader.readAsDataURL(file);
            }
        });
    }
});

// Close edit modal
function closeEditAdminModal() {
    const modal = document.getElementById('editAdminModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
    currentEditingAdminId = null;
    hasImageChanged = false;
}

// Handle edit form submission
document.addEventListener('DOMContentLoaded', function() {
    const editForm = document.getElementById('editAdminForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            showEditConfirmation();
        });
    }
});

// Show edit confirmation modal
function showEditConfirmation() {
    const firstName = document.getElementById('edit_admin_fn').value;
    const middleName = document.getElementById('edit_admin_mn').value;
    const lastName = document.getElementById('edit_admin_ln').value;
    const role = document.getElementById('edit_role').value;
    const customRole = document.getElementById('editCustomRole').value;
    const month = document.getElementById('edit_birth_month').value;
    const day = document.getElementById('edit_birth_day').value;
    const year = document.getElementById('edit_birth_year').value;
    
    const fullName = `${firstName} ${middleName} ${lastName}`.replace(/\s+/g, ' ').trim();
    const finalRole = role === 'other' ? customRole : role;
    const monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June',
                       'July', 'August', 'September', 'October', 'November', 'December'];
    const birthdate = `${monthNames[parseInt(month)]} ${parseInt(day)}, ${year}`;
    
    const confirmationContent = document.getElementById('editConfirmationContent');
    confirmationContent.innerHTML = `
        <div class="text-sm space-y-2">
            <div><strong>Name:</strong> ${fullName}</div>
            <div><strong>Position:</strong> ${finalRole}</div>
            <div><strong>Birthdate:</strong> ${birthdate}</div>
            ${hasImageChanged ? '<div><strong>Profile Picture:</strong> Updated</div>' : ''}
        </div>
    `;
    
    const modal = document.getElementById('editConfirmationModal');
    if (modal) {
        modal.classList.remove('hidden');
    }
}

// Edit confirmation event listeners
document.addEventListener('DOMContentLoaded', function() {
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const confirmEditBtn = document.getElementById('confirmEditBtn');
    
    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', function() {
            document.getElementById('editConfirmationModal').classList.add('hidden');
        });
    }
    
    if (confirmEditBtn) {
        confirmEditBtn.addEventListener('click', function() {
            submitEditForm();
        });
    }
});

// Submit edit form
async function submitEditForm() {
    // Hide confirmation modal
    document.getElementById('editConfirmationModal').classList.add('hidden');
    
    // Show loading
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.classList.remove('hidden');
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'edit_admin');
        formData.append('admin_id', document.getElementById('edit_admin_id').value);
        formData.append('first_name', document.getElementById('edit_admin_fn').value);
        formData.append('middle_name', document.getElementById('edit_admin_mn').value);
        formData.append('last_name', document.getElementById('edit_admin_ln').value);
        
        const role = document.getElementById('edit_role').value;
        const finalRole = role === 'other' ? document.getElementById('editCustomRole').value : role;
        formData.append('barangay_position', finalRole);
        
        const month = document.getElementById('edit_birth_month').value;
        const day = document.getElementById('edit_birth_day').value;
        const year = document.getElementById('edit_birth_year').value;
        formData.append('birthdate', `${year}-${month}-${day}`);
        
        // Handle profile picture
        const fileInput = document.getElementById('editProfileImageInput');
        if (hasImageChanged) {
            if (fileInput.files[0]) {
                formData.append('profile_picture', fileInput.files[0]);
            } else {
                formData.append('remove_picture', '1');
            }
        }
        
        const response = await fetch('../javascript/USERS/admin_actions.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        // Hide loading
        if (loadingOverlay) {
            loadingOverlay.classList.add('hidden');
        }
        
        if (result.success) {
            // Close edit modal
            closeEditAdminModal();
            
            // Show success modal
            showEditSuccessModal();
        } else {
            alert('Error updating admin: ' + (result.message || 'Unknown error'));
        }
        
    } catch (error) {
        console.error('Error updating admin:', error);
        // Hide loading
        if (loadingOverlay) {
            loadingOverlay.classList.add('hidden');
        }
        alert('Error updating admin. Please try again.');
    }
}

// Show edit success modal
function showEditSuccessModal() {
    const modal = document.getElementById('editSuccessModal');
    if (modal) {
        modal.classList.remove('hidden');
    }
}

// Edit success modal event listener
document.addEventListener('DOMContentLoaded', function() {
    const editSuccessBtn = document.getElementById('editSuccessOkBtn');
    if (editSuccessBtn) {
        editSuccessBtn.addEventListener('click', function() {
            document.getElementById('editSuccessModal').classList.add('hidden');
            // Force reload the page
            window.location.reload();
        });
    }
});

// Archive Admin function
async function archiveAdmin(adminId) {
    currentArchivingAdminId = adminId;
    
    // Fetch admin data to show in confirmation
    const adminData = await fetchAdminData(adminId);
    if (!adminData) return;
    
    // Populate admin details in confirmation modal
    const adminDetails = document.getElementById('archiveAdminDetails');
    const fullName = `${adminData.first_name} ${adminData.middle_name || ''} ${adminData.last_name}`.replace(/\s+/g, ' ').trim();
    adminDetails.innerHTML = `
        <div class="text-sm">
            <div><strong>Name:</strong> ${fullName}</div>
            <div><strong>Position:</strong> ${adminData.barangay_position}</div>
            <div><strong>Username:</strong> ${adminData.username}</div>
        </div>
    `;
    
    // Show archive confirmation modal
    const modal = document.getElementById('archiveConfirmationModal');
    if (modal) {
        modal.classList.remove('hidden');
    }
}

// Archive confirmation event listeners
document.addEventListener('DOMContentLoaded', function() {
    const cancelArchiveBtn = document.getElementById('cancelArchiveBtn');
    const confirmArchiveBtn = document.getElementById('confirmArchiveBtn');
    
    if (cancelArchiveBtn) {
        cancelArchiveBtn.addEventListener('click', function() {
            document.getElementById('archiveConfirmationModal').classList.add('hidden');
            currentArchivingAdminId = null;
        });
    }
    
    if (confirmArchiveBtn) {
        confirmArchiveBtn.addEventListener('click', function() {
            submitArchiveAdmin();
        });
    }
});

// Submit archive admin
async function submitArchiveAdmin() {
    if (!currentArchivingAdminId) return;
    
    // Hide confirmation modal
    document.getElementById('archiveConfirmationModal').classList.add('hidden');
    
    // Show loading
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.classList.remove('hidden');
    }
    
    try {
        const response = await fetch('../javascript/USERS/admin_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=archive_admin&admin_id=${currentArchivingAdminId}`
        });
        
        const result = await response.json();
        
        // Hide loading
        if (loadingOverlay) {
            loadingOverlay.classList.add('hidden');
        }
        
        if (result.success) {
            // Show success modal
            showArchiveSuccessModal();
        } else {
            alert('Error archiving admin: ' + (result.message || 'Unknown error'));
        }
        
    } catch (error) {
        console.error('Error archiving admin:', error);
        // Hide loading
        if (loadingOverlay) {
            loadingOverlay.classList.add('hidden');
        }
        alert('Error archiving admin. Please try again.');
    }
    
    currentArchivingAdminId = null;
}

// Show archive success modal
function showArchiveSuccessModal() {
    const modal = document.getElementById('archiveSuccessModal');
    if (modal) {
        modal.classList.remove('hidden');
    }
}

// Archive success modal event listener
document.addEventListener('DOMContentLoaded', function() {
    const archiveSuccessBtn = document.getElementById('archiveSuccessOkBtn');
    if (archiveSuccessBtn) {
        archiveSuccessBtn.addEventListener('click', function() {
            document.getElementById('archiveSuccessModal').classList.add('hidden');
            // Force reload the page
            window.location.reload();
        });
    }
});

// Updated Restore Admin function (replaces the existing one)
async function restoreAdmin(adminId) {
    currentRestoringAdminId = adminId;
    
    // Fetch admin data to show in confirmation
    const adminData = await fetchAdminData(adminId);
    if (!adminData) return;
    
    // Populate admin details in confirmation modal
    const adminDetails = document.getElementById('restoreAdminDetails');
    const fullName = `${adminData.first_name} ${adminData.middle_name || ''} ${adminData.last_name}`.replace(/\s+/g, ' ').trim();
    adminDetails.innerHTML = `
        <div class="text-sm">
            <div><strong>Name:</strong> ${fullName}</div>
            <div><strong>Position:</strong> ${adminData.barangay_position}</div>
            <div><strong>Username:</strong> ${adminData.username}</div>
        </div>
    `;
    
    // Show restore confirmation modal
    const modal = document.getElementById('restoreConfirmationModal');
    if (modal) {
        modal.classList.remove('hidden');
    }
}

// Updated Delete Admin function (replaces the existing one)
async function deleteAdmin(adminId) {
    currentDeletingAdminId = adminId;
    
    // Fetch admin data to show in confirmation
    const adminData = await fetchAdminData(adminId);
    if (!adminData) return;
    
    // Populate admin details in confirmation modal
    const adminDetails = document.getElementById('deleteAdminDetails');
    const fullName = `${adminData.first_name} ${adminData.middle_name || ''} ${adminData.last_name}`.replace(/\s+/g, ' ').trim();
    adminDetails.innerHTML = `
        <div class="text-sm">
            <div><strong>Name:</strong> ${fullName}</div>
            <div><strong>Position:</strong> ${adminData.barangay_position}</div>
            <div><strong>Username:</strong> ${adminData.username}</div>
            <div><strong>Admin ID:</strong> ${adminData.admin_id}</div>
        </div>
    `;
    
    // Show delete confirmation modal
    const modal = document.getElementById('deleteConfirmationModal');
    if (modal) {
        modal.classList.remove('hidden');
    }
}




// Add these new event listeners to the existing DOMContentLoaded event listener:
document.addEventListener('DOMContentLoaded', function() {
    // ... existing event listeners ...
    
    // Restore confirmation event listeners
    const cancelRestoreBtn = document.getElementById('cancelRestoreBtn');
    const confirmRestoreBtn = document.getElementById('confirmRestoreBtn');
    
    if (cancelRestoreBtn) {
        cancelRestoreBtn.addEventListener('click', function() {
            document.getElementById('restoreConfirmationModal').classList.add('hidden');
            currentRestoringAdminId = null;
        });
    }
    
    if (confirmRestoreBtn) {
        confirmRestoreBtn.addEventListener('click', function() {
            submitRestoreAdmin();
        });
    }
    
    // Delete confirmation event listeners
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', function() {
            document.getElementById('deleteConfirmationModal').classList.add('hidden');
            currentDeletingAdminId = null;
        });
    }
    
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            submitDeleteAdmin();
        });
    }
    
    // Restore success modal event listener
    const restoreSuccessBtn = document.getElementById('restoreSuccessOkBtn');
    if (restoreSuccessBtn) {
        restoreSuccessBtn.addEventListener('click', function() {
            document.getElementById('restoreSuccessModal').classList.add('hidden');
            // Force reload the page
            window.location.reload();
        });
    }
    
    // Delete success modal event listener
    const deleteSuccessBtn = document.getElementById('deleteSuccessOkBtn');
    if (deleteSuccessBtn) {
        deleteSuccessBtn.addEventListener('click', function() {
            document.getElementById('deleteSuccessModal').classList.add('hidden');
            // Force reload the page
            window.location.reload();
        });
    }
});












// Add these new functions at the end of the file:

// Submit restore admin
async function submitRestoreAdmin() {
    if (!currentRestoringAdminId) return;
    
    // Hide confirmation modal
    document.getElementById('restoreConfirmationModal').classList.add('hidden');
    
    // Show loading
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.classList.remove('hidden');
    }
    
    try {
        const response = await fetch('../javascript/USERS/admin_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=restore_admin&admin_id=${currentRestoringAdminId}`
        });
        
        const result = await response.json();
        
        // Hide loading
        if (loadingOverlay) {
            loadingOverlay.classList.add('hidden');
        }
        
        if (result.success) {
            // Show success modal
            showRestoreSuccessModal();
        } else {
            alert('Error restoring admin: ' + (result.message || 'Unknown error'));
        }
        
    } catch (error) {
        console.error('Error restoring admin:', error);
        // Hide loading
        if (loadingOverlay) {
            loadingOverlay.classList.add('hidden');
        }
        alert('Error restoring admin. Please try again.');
    }
    
    currentRestoringAdminId = null;
}

// Submit delete admin
async function submitDeleteAdmin() {
    if (!currentDeletingAdminId) return;
    
    // Hide confirmation modal
    document.getElementById('deleteConfirmationModal').classList.add('hidden');
    
    // Show loading
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.classList.remove('hidden');
    }
    
    try {
        const response = await fetch('../javascript/USERS/admin_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_admin&admin_id=${currentDeletingAdminId}`
        });
        
        const result = await response.json();
        
        // Hide loading
        if (loadingOverlay) {
            loadingOverlay.classList.add('hidden');
        }
        
        if (result.success) {
            // Show success modal
            showDeleteSuccessModal();
        } else {
            alert('Error deleting admin: ' + (result.message || 'Unknown error'));
        }
        
    } catch (error) {
        console.error('Error deleting admin:', error);
        // Hide loading
        if (loadingOverlay) {
            loadingOverlay.classList.add('hidden');
        }
        alert('Error deleting admin. Please try again.');
    }
    
    currentDeletingAdminId = null;
}

// Show restore success modal
function showRestoreSuccessModal() {
    const modal = document.getElementById('restoreSuccessModal');
    if (modal) {
        modal.classList.remove('hidden');
    }
}

// Show delete success modal
function showDeleteSuccessModal() {
    const modal = document.getElementById('deleteSuccessModal');
    if (modal) {
        modal.classList.remove('hidden');
    }
}
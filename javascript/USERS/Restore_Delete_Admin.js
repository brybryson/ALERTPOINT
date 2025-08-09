// Add these functions to your existing Archived_Admin.js file

// Global variables for restore and delete operations
let currentRestoringAdmin = null;
let currentDeletingAdmin = null;

// Restore admin function
function restoreAdmin(adminId) {
    console.log(`Opening restore confirmation for admin ID: ${adminId}`);
    
    try {
        // Find the admin card to get data
        const adminCard = document.querySelector(`[onclick*="restoreAdmin('${adminId}')"]`).closest('.archived-admin-card');
        if (!adminCard) {
            showErrorMessage('Admin card not found');
            return;
        }
        
        // Extract admin data from the card
        const adminData = extractArchivedAdminDataFromCard(adminCard, adminId);
        if (!adminData) {
            showErrorMessage('Failed to extract admin data');
            return;
        }
        
        // Store current restoring admin
        currentRestoringAdmin = adminData;
        
        // Populate restore confirmation details
        populateRestoreConfirmation(adminData);
        
        // Show restore confirmation modal
        const restoreModal = document.getElementById('restoreConfirmationModal');
        if (restoreModal) {
            restoreModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        } else {
            showErrorMessage('Restore confirmation modal not found');
        }
        
    } catch (error) {
        console.error('Error in restoreAdmin:', error);
        showErrorMessage('An error occurred while opening restore confirmation');
    }
}

// Delete admin function
function deleteAdmin(adminId) {
    console.log(`Opening delete confirmation for admin ID: ${adminId}`);
    
    try {
        // Find the admin card to get data
        const adminCard = document.querySelector(`[onclick*="deleteAdmin('${adminId}')"]`).closest('.archived-admin-card');
        if (!adminCard) {
            showErrorMessage('Admin card not found');
            return;
        }
        
        // Extract admin data from the card
        const adminData = extractArchivedAdminDataFromCard(adminCard, adminId);
        if (!adminData) {
            showErrorMessage('Failed to extract admin data');
            return;
        }
        
        // Store current deleting admin
        currentDeletingAdmin = adminData;
        
        // Populate delete confirmation details
        populateDeleteConfirmation(adminData);
        
        // Show delete confirmation modal
        const deleteModal = document.getElementById('deleteConfirmationModal');
        if (deleteModal) {
            deleteModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        } else {
            showErrorMessage('Delete confirmation modal not found');
        }
        
    } catch (error) {
        console.error('Error in deleteAdmin:', error);
        showErrorMessage('An error occurred while opening delete confirmation');
    }
}

// Extract archived admin data from card
function extractArchivedAdminDataFromCard(adminCard, adminId) {
    try {
        // Extract full name
        const fullNameElement = adminCard.querySelector('h3.font-semibold');
        const fullName = fullNameElement ? fullNameElement.textContent.trim() : '';
        
        // Extract position
        const positionElement = adminCard.querySelector('p.text-sm.text-gray-500');
        const position = positionElement ? positionElement.textContent.trim() : '';
        
        // Extract username (from the element with fas fa-user-shield icon)
        const usernameElement = adminCard.querySelector('.fas.fa-user-shield');
        let username = '';
        if (usernameElement && usernameElement.parentElement) {
            username = usernameElement.parentElement.textContent.trim();
        }
        
        return {
            adminId: adminId,
            fullName: fullName,
            position: position,
            username: username
        };
        
    } catch (error) {
        console.error('Error extracting archived admin data:', error);
        return null;
    }
}

// Populate restore confirmation details
function populateRestoreConfirmation(adminData) {
    const detailsDiv = document.getElementById('restoreAdminDetails');
    if (detailsDiv) {
        detailsDiv.innerHTML = `
            <div class="text-sm">
                <p class="font-medium text-gray-900">${adminData.fullName}</p>
                <p class="text-gray-600">${adminData.position}</p>
                <p class="text-gray-500">ID: ${adminData.adminId}</p>
            </div>
        `;
    }
}

// Populate delete confirmation details
function populateDeleteConfirmation(adminData) {
    const detailsDiv = document.getElementById('deleteAdminDetails');
    if (detailsDiv) {
        detailsDiv.innerHTML = `
            <div class="text-sm">
                <p class="font-medium text-gray-900">${adminData.fullName}</p>
                <p class="text-gray-600">${adminData.position}</p>
                <p class="text-gray-500">ID: ${adminData.adminId}</p>
            </div>
        `;
    }
}

// Close restore modal
function closeRestoreModal() {
    const restoreModal = document.getElementById('restoreConfirmationModal');
    if (restoreModal) {
        restoreModal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
    // Only reset if we're truly canceling, not proceeding
    if (currentRestoringAdmin) {
        currentRestoringAdmin = null;
    }
}

// Close delete modal
function closeDeleteModal() {
    const deleteModal = document.getElementById('deleteConfirmationModal');
    if (deleteModal) {
        deleteModal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
    // Only reset if we're truly canceling, not proceeding
    if (currentDeletingAdmin) {
        currentDeletingAdmin = null;
    }
}

// Confirm restore admin
function confirmRestoreAdmin() {
    console.log('confirmRestoreAdmin called');
    
    if (!currentRestoringAdmin) {
        console.error('No admin selected for restoring');
        showErrorMessage('No admin selected for restoring');
        return;
    }
    
    // Store admin data before closing modal
    const adminToRestore = currentRestoringAdmin;
    
    // Close confirmation modal
    const restoreModal = document.getElementById('restoreConfirmationModal');
    if (restoreModal) {
        restoreModal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
    
    // Show loading state on the admin card
    const adminCard = document.querySelector(`[onclick*="restoreAdmin('${adminToRestore.adminId}')"]`).closest('.archived-admin-card');
    
    if (adminCard) {
        adminCard.style.opacity = '0.5';
        adminCard.style.pointerEvents = 'none';
        
        // Add loading spinner to the card
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10';
        loadingDiv.innerHTML = `
            <div class="flex items-center space-x-2">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-green-600"></div>
                <span class="text-gray-600 text-sm">Restoring...</span>
            </div>
        `;
        adminCard.style.position = 'relative';
        adminCard.appendChild(loadingDiv);
    }
    
    // Send restore request
    fetch('/ALERTPOINT/javascript/USERS/restore_admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ admin_id: adminToRestore.adminId })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
    })
    .then(text => {
        try {
            const data = JSON.parse(text);
            return data;
        } catch (e) {
            console.error('JSON parse error:', e);
            throw new Error('Invalid JSON response from server');
        }
    })
    .then(data => {
        if (data.success) {
            // Remove admin card with animation
            if (adminCard) {
                adminCard.style.transform = 'scale(0.8)';
                adminCard.style.opacity = '0';
                adminCard.style.transition = 'all 0.3s ease-out';
                
                setTimeout(() => {
                    adminCard.remove();
                    
                    // Check if there are no more archived admins
                    const remainingCards = document.querySelectorAll('.archived-admin-card');
                    if (remainingCards.length === 0) {
                        const gridContainer = document.querySelector('#archived-grid');
                        if (gridContainer) {
                            const noAdminsDiv = document.createElement('div');
                            noAdminsDiv.className = 'col-span-full text-center py-8 text-gray-500';
                            noAdminsDiv.id = 'no-archived-admins';
                            noAdminsDiv.innerHTML = `
                                <i class="fas fa-archive text-4xl mb-4 text-gray-400"></i>
                                <p class="text-lg font-semibold">No Archived Accounts</p>
                                <p class="text-sm">No archived accounts found.</p>
                            `;
                            gridContainer.appendChild(noAdminsDiv);
                        }
                    }
                }, 300);
            }
            
            // Show success modal
            showRestoreSuccessModal(data.message || 'Admin restored successfully');
            
            // Reset current restoring admin
            currentRestoringAdmin = null;
        } else {
            throw new Error(data.message || 'Failed to restore admin');
        }
    })
    .catch(error => {
        console.error('Error restoring admin:', error);
        showErrorMessage('Failed to restore admin: ' + error.message);
        
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
        
        currentRestoringAdmin = null;
    });
}

// Confirm delete admin (first confirmation)
function confirmDeleteAdmin() {
    console.log('confirmDeleteAdmin called - showing second confirmation');
    
    if (!currentDeletingAdmin) {
        console.error('No admin selected for deletion');
        showErrorMessage('No admin selected for deletion');
        return;
    }
    
    // Store admin data before closing modal
    const adminToDelete = currentDeletingAdmin;
    
    // Close first confirmation modal
    const deleteModal = document.getElementById('deleteConfirmationModal');
    if (deleteModal) {
        deleteModal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
    
    // Show second confirmation modal with timer
    showSecondDeleteConfirmation(adminToDelete);
}

// Show second delete confirmation with timer
function showSecondDeleteConfirmation(adminToDelete) {
    // Create the second confirmation modal
    const secondModal = document.createElement('div');
    secondModal.id = 'secondDeleteConfirmationModal';
    secondModal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50';
    secondModal.innerHTML = `
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in relative z-[10000]">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-red-50 rounded-t-xl">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900">Final Confirmation</h3>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-skull-crossbones text-red-500 text-2xl"></i>
                    </div>
                    <h4 class="text-lg font-semibold text-red-600 mb-2">LAST WARNING</h4>
                    <p class="text-gray-700 text-sm mb-4 font-medium">
                        You are about to <strong class="text-red-600">PERMANENTLY DELETE</strong> this admin account. This action is <strong class="text-red-600">IRREVERSIBLE</strong>.
                    </p>
                    <div class="bg-red-100 border border-red-200 p-3 rounded-lg mb-4">
                        <p class="font-medium text-gray-900">${adminToDelete.fullName}</p>
                        <p class="text-gray-600">${adminToDelete.position}</p>
                        <p class="text-gray-500">ID: ${adminToDelete.adminId}</p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-center space-x-4">
                    <button id="cancelSecondDeleteBtn" type="button" 
                            class="px-6 py-3 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors flex items-center space-x-2">
                        <i class="fas fa-times"></i>
                        <span>Cancel</span>
                    </button>
                    <button id="confirmSecondDeleteBtn" type="button" disabled
                            class="px-6 py-3 text-sm font-medium bg-gray-400 text-white rounded-lg transition-colors flex items-center space-x-2 cursor-not-allowed">
                        <i class="fas fa-trash"></i>
                        <span id="confirmButtonText">Confirm (5)</span>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(secondModal);
    document.body.style.overflow = 'hidden';
    
    // Timer functionality
    let countdown = 5;
    const confirmBtn = document.getElementById('confirmSecondDeleteBtn');
    const buttonText = document.getElementById('confirmButtonText');
    
    const timer = setInterval(() => {
        countdown--;
        buttonText.textContent = `Confirm (${countdown})`;
        
        if (countdown <= 0) {
            clearInterval(timer);
            confirmBtn.disabled = false;
            confirmBtn.classList.remove('bg-gray-400', 'cursor-not-allowed');
            confirmBtn.classList.add('bg-red-600', 'hover:bg-red-700');
            buttonText.textContent = 'Confirm';
        }
    }, 1000);
    
    // Event listeners for second modal
    document.getElementById('cancelSecondDeleteBtn').addEventListener('click', () => {
        clearInterval(timer);
        secondModal.remove();
        document.body.style.overflow = 'auto';
        currentDeletingAdmin = null;
    });
    
    document.getElementById('confirmSecondDeleteBtn').addEventListener('click', () => {
        if (!confirmBtn.disabled) {
            clearInterval(timer);
            secondModal.remove();
            proceedWithDelete(adminToDelete);
        }
    });
}

// Proceed with actual deletion
function proceedWithDelete(adminToDelete) {
    console.log('proceedWithDelete called');
    
    // Show loading state on the admin card
    const adminCard = document.querySelector(`[onclick*="deleteAdmin('${adminToDelete.adminId}')"]`).closest('.archived-admin-card');
    
    if (adminCard) {
        adminCard.style.opacity = '0.5';
        adminCard.style.pointerEvents = 'none';
        
        // Add loading spinner to the card
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10';
        loadingDiv.innerHTML = `
            <div class="flex items-center space-x-2">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-red-600"></div>
                <span class="text-gray-600 text-sm">Deleting...</span>
            </div>
        `;
        adminCard.style.position = 'relative';
        adminCard.appendChild(loadingDiv);
    }
    
    // Send delete request
    fetch('/ALERTPOINT/javascript/USERS/delete_admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ admin_id: adminToDelete.adminId })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
    })
    .then(text => {
        try {
            const data = JSON.parse(text);
            return data;
        } catch (e) {
            console.error('JSON parse error:', e);
            throw new Error('Invalid JSON response from server');
        }
    })
    .then(data => {
        if (data.success) {
            // Remove admin card with animation
            if (adminCard) {
                adminCard.style.transform = 'scale(0.8)';
                adminCard.style.opacity = '0';
                adminCard.style.transition = 'all 0.3s ease-out';
                
                setTimeout(() => {
                    adminCard.remove();
                    
                    // Check if there are no more archived admins
                    const remainingCards = document.querySelectorAll('.archived-admin-card');
                    if (remainingCards.length === 0) {
                        const gridContainer = document.querySelector('#archived-grid');
                        if (gridContainer) {
                            const noAdminsDiv = document.createElement('div');
                            noAdminsDiv.className = 'col-span-full text-center py-8 text-gray-500';
                            noAdminsDiv.id = 'no-archived-admins';
                            noAdminsDiv.innerHTML = `
                                <i class="fas fa-archive text-4xl mb-4 text-gray-400"></i>
                                <p class="text-lg font-semibold">No Archived Accounts</p>
                                <p class="text-sm">No archived accounts found.</p>
                            `;
                            gridContainer.appendChild(noAdminsDiv);
                        }
                    }
                }, 300);
            }
            
            // Show success modal
            showDeleteSuccessModal(data.message || 'Admin deleted successfully');
            
            // Reset current deleting admin
            currentDeletingAdmin = null;
        } else {
            throw new Error(data.message || 'Failed to delete admin');
        }
    })
    .catch(error => {
        console.error('Error deleting admin:', error);
        showErrorMessage('Failed to delete admin: ' + error.message);
        
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
        
        currentDeletingAdmin = null;
    });
}

// Show restore success modal
function showRestoreSuccessModal(message) {
    const successModal = document.getElementById('restoreSuccessModal');
    const messageElement = document.getElementById('restoreSuccessMessage');
    
    if (successModal) {
        if (messageElement) {
            messageElement.textContent = message;
        }
        successModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

// Show delete success modal
function showDeleteSuccessModal(message) {
    const successModal = document.getElementById('deleteSuccessModal');
    const messageElement = document.getElementById('deleteSuccessMessage');
    
    if (successModal) {
        if (messageElement) {
            messageElement.textContent = message;
        }
        successModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

// Close restore success modal
function closeRestoreSuccessModal() {
    const successModal = document.getElementById('restoreSuccessModal');
    if (successModal) {
        successModal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
    
    // Force reload
    window.location.reload(true);
}

// Close delete success modal
function closeDeleteSuccessModal() {
    const successModal = document.getElementById('deleteSuccessModal');
    if (successModal) {
        successModal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
    
    // Force reload
    window.location.reload(true);
}

// Add event listeners when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Restore confirmation buttons
    const cancelRestoreBtn = document.getElementById('cancelRestoreBtn');
    const confirmRestoreBtn = document.getElementById('confirmRestoreBtn');
    
    if (cancelRestoreBtn) {
        cancelRestoreBtn.addEventListener('click', closeRestoreModal);
    }
    
    if (confirmRestoreBtn) {
        confirmRestoreBtn.addEventListener('click', confirmRestoreAdmin);
    }
    
    // Delete confirmation buttons
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', closeDeleteModal);
    }
    
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', confirmDeleteAdmin);
    }
    
    // Success modal buttons
    const restoreSuccessBtn = document.getElementById('restoreSuccessOkBtn');
    const deleteSuccessBtn = document.getElementById('deleteSuccessOkBtn');
    
    if (restoreSuccessBtn) {
        restoreSuccessBtn.addEventListener('click', closeRestoreSuccessModal);
    }
    
    if (deleteSuccessBtn) {  
        deleteSuccessBtn.addEventListener('click', closeDeleteSuccessModal);
    }
});

// Global variables for pagination and filtering
let currentUserType = 'residents'; // 'residents' or 'admins'
let currentStatusFilter = 'all'; // 'all', 'online', 'offline'
let currentSearchTerm = '';
let currentArchivedSearchTerm = '';
let currentActivePage = 1;
let currentArchivedPage = 1;
const itemsPerPage = 10;

// Update time function
function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleString('en-US', {
        timeZone: 'Asia/Manila',
        weekday: 'long',
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

// Initialize pagination on page load
document.addEventListener('DOMContentLoaded', function() {
    initializePagination();
});

// Initialize pagination
function initializePagination() {
    applyFilters();
    updatePagination('active');
    updatePagination('archived');
}

// Filter users function
function filterUsers(type) {
    console.log(`Switching to: ${type}`);
    
    currentUserType = type;
    currentActivePage = 1; // Reset to first page when switching
    currentStatusFilter = 'all'; // Reset status filter
    
    // Update tab appearance
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.classList.remove('active', 'bg-blue-600', 'text-white');
        tab.classList.add('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
    });
    
    const activeTab = document.getElementById(`filter-${type}`);
    if (activeTab) {
        activeTab.classList.add('active', 'bg-blue-600', 'text-white');
        activeTab.classList.remove('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
    }
    
    // Reset status filter buttons
    document.querySelectorAll('.status-filter').forEach(btn => {
        btn.classList.remove('active', 'bg-blue-600', 'text-white');
        btn.classList.add('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
    });
    document.getElementById('filter-all-status').classList.add('active', 'bg-blue-600', 'text-white');
    document.getElementById('filter-all-status').classList.remove('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
    
    // Show/hide appropriate views
    const residentsView = document.getElementById('residents-view');
    const adminsView = document.getElementById('admins-view');
    const sectionTitle = document.getElementById('section-title');
    
    if (type === 'residents') {
        residentsView.style.display = 'block';
        adminsView.style.display = 'none';
        sectionTitle.textContent = 'AlertPoint Residents';
    } else if (type === 'admins') {
        residentsView.style.display = 'none';
        adminsView.style.display = 'block';
        sectionTitle.textContent = 'AlertPoint Admins';
    }
    
    // Apply filters
    applyFilters();
}

// ADD NEW FUNCTION for status filtering
function filterByStatus(status) {
    console.log(`Filtering by status: ${status}`);
    
    currentStatusFilter = status;
    currentActivePage = 1; // Reset to first page when filtering
    
    // Update status filter tabs
    document.querySelectorAll('.status-filter').forEach(tab => {
        tab.classList.remove('active', 'bg-blue-600', 'text-white');
        tab.classList.add('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
    });
    
    const activeTab = status === 'all' ? 
        document.getElementById('filter-all-status') : 
        document.getElementById(`filter-${status}`);
        
    if (activeTab) {
        activeTab.classList.add('active', 'bg-blue-600', 'text-white');
        activeTab.classList.remove('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
    }
    
    // Apply filters
    applyFilters();
}

// ADD NEW FUNCTION for archived search
function searchArchivedUsers() {
    const searchTerm = document.getElementById('archived-search-input').value.toLowerCase().trim();
    currentArchivedSearchTerm = searchTerm;
    console.log(`Searching archived users for: "${searchTerm}"`);
    
    // Apply archived filters
    applyArchivedFilters();
}




// Search users function
function searchUsers() {
    const searchTerm = document.getElementById('search-input').value.toLowerCase().trim();
    currentSearchTerm = searchTerm;
    console.log(`Searching for: "${searchTerm}"`);
    
    // Apply filters
    applyFilters();
}

// Get filtered cards for a specific section
function getFilteredCards(section) {
    if (section === 'archived') {
        return getFilteredArchivedCards();
    }
    
    const filteredItems = [];
    
    if (currentUserType === 'residents') {
        // Filter residents table rows
        const residentRows = document.querySelectorAll('.resident-row');
        residentRows.forEach(row => {
            let showRow = true;
            
            const status = row.getAttribute('data-status');
            const name = row.getAttribute('data-name');
            
            // Apply status filter
            if (currentStatusFilter === 'online' && status !== 'online') {
                showRow = false;
            } else if (currentStatusFilter === 'offline' && status !== 'offline') {
                showRow = false;
            }
            
            // Apply search filter
            if (currentSearchTerm && name && !name.includes(currentSearchTerm)) {
                showRow = false;
            }
            
            if (showRow) {
                filteredItems.push(row);
            }
        });
    } else if (currentUserType === 'admins') {
        // Filter admin cards
        const adminCards = document.querySelectorAll('.admin-card');
        adminCards.forEach(card => {
            let showCard = true;
            
            const status = card.getAttribute('data-status');
            const name = card.getAttribute('data-name');
            
            // Apply status filter
            if (currentStatusFilter === 'online' && status !== 'online') {
                showCard = false;
            } else if (currentStatusFilter === 'offline' && status !== 'offline') {
                showCard = false;
            }
            
            // Apply search filter
            if (currentSearchTerm && name && !name.includes(currentSearchTerm)) {
                showCard = false;
            }
            
            if (showCard) {
                filteredItems.push(card);
            }
        });
    }
    
    return filteredItems;
}

function editResident(residentId) {
    // Find resident data from the table row
    const residentRow = document.querySelector(`[onclick*="editResident('${residentId}')"]`).closest('tr');
    const nameElement = residentRow.querySelector('.text-sm.font-medium');
    const addressElement = residentRow.querySelector('td:nth-child(2) .text-sm');
    const emailElement = residentRow.querySelector('td:nth-child(3) .text-sm');
    const phoneElement = residentRow.querySelector('td:nth-child(4) .text-sm');
    
    if (nameElement && addressElement && emailElement && phoneElement) {
        const fullName = nameElement.textContent.trim();
        const nameParts = fullName.split(' ');
        const firstName = nameParts[0] || '';
        const lastName = nameParts.slice(1).join(' ') || '';
        
        // Populate the modal form with correct IDs
        document.getElementById('edit_resident_id').value = residentId;
        document.getElementById('edit_resident_fn').value = firstName;
        document.getElementById('edit_resident_ln').value = lastName;
        document.getElementById('edit_resident_email').value = emailElement.textContent.trim();
        document.getElementById('edit_resident_phone').value = phoneElement.textContent.trim();
        document.getElementById('edit_resident_address').value = addressElement.textContent.trim();
        
        // Show the modal
        showEditResidentModal();
    }
}


function showEditResidentModal() {
    const modal = document.getElementById('editResidentModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        // Focus on first input
        setTimeout(() => {
            document.getElementById('edit_resident_fn').focus();
        }, 100);
    }
}

// Update the close modal function
function closeEditResidentModal() {
    const modal = document.getElementById('editResidentModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
        
        // Reset form
        const form = document.getElementById('editResidentForm');
        if (form) {
            form.reset();
        }
        
        // Reset any loading states
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Update Resident';
            submitBtn.disabled = false;
        }
    }
}


document.addEventListener('DOMContentLoaded', function() {
    const editResidentForm = document.getElementById('editResidentForm');
    if (editResidentForm) {
        editResidentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const residentId = formData.get('resident_id');
            
            // Show loading state on submit button
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating...';
            submitBtn.disabled = true;
            
            // Simulate API call (replace with actual AJAX call)
            setTimeout(() => {
                console.log('Updating resident:', residentId, Object.fromEntries(formData));
                
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                
                // Close modal and show success
                closeEditResidentModal();
                
                setTimeout(() => {
                    showEditResidentSuccessModal();
                }, 300);
            }, 1000);
        });
    }
});

// Show success modal function
function showEditResidentSuccessModal() {
    const modal = document.getElementById('editResidentSuccessModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        // Set up OK button listener
        const okBtn = document.getElementById('editResidentSuccessOkBtn');
        if (okBtn) {
            okBtn.onclick = function() {
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
                // Optionally refresh the page or update the table row
                // location.reload(); // Uncomment if you want to refresh the page
            };
        }
    }
}




function archiveResident(residentId) {
    // Find resident data
    const residentRow = document.querySelector(`[onclick*="archiveResident('${residentId}')"]`).closest('tr');
    const residentName = residentRow.querySelector('.text-sm.font-medium').textContent;
    
    // Show archive modal
    showArchiveResidentModal(residentId, residentName);
}

function restoreResident(residentId) {
    // Find resident data
    const residentRow = document.querySelector(`[onclick*="restoreResident('${residentId}')"]`).closest('tr');
    const residentName = residentRow.querySelector('.text-sm.font-medium').textContent;
    
    // Show restore modal
    showRestoreResidentModal(residentId, residentName);
}

function deleteResident(residentId) {
    // Find resident data
    const residentRow = document.querySelector(`[onclick*="deleteResident('${residentId}')"]`).closest('tr');
    const residentName = residentRow.querySelector('.text-sm.font-medium').textContent;
    
    // Show delete modal
    showDeleteResidentModal(residentId, residentName);
}





function showArchiveResidentModal(residentId, residentName) {
    const modal = document.getElementById('archiveResidentModal');
    const detailsDiv = document.getElementById('archiveResidentDetails');
    
    if (modal && detailsDiv) {
        detailsDiv.innerHTML = `
            <p class="text-sm text-gray-600">
                <strong>Resident:</strong> ${residentName}<br>
                <strong>ID:</strong> ${residentId}
            </p>
        `;
        
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        // Set up event listeners
        setupResidentModalListeners('archive', residentId);
    }
}

function showRestoreResidentModal(residentId, residentName) {
    const modal = document.getElementById('restoreResidentModal');
    const detailsDiv = document.getElementById('restoreResidentDetails');
    
    if (modal && detailsDiv) {
        detailsDiv.innerHTML = `
            <p class="text-sm text-gray-600">
                <strong>Resident:</strong> ${residentName}<br>
                <strong>ID:</strong> ${residentId}
            </p>
        `;
        
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        setupResidentModalListeners('restore', residentId);
    }
}

function showDeleteResidentModal(residentId, residentName) {
    const modal = document.getElementById('deleteResidentModal');
    const detailsDiv = document.getElementById('deleteResidentDetails');
    
    if (modal && detailsDiv) {
        detailsDiv.innerHTML = `
            <p class="text-sm text-red-600">
                <strong>Resident:</strong> ${residentName}<br>
                <strong>ID:</strong> ${residentId}
            </p>
        `;
        
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        setupResidentModalListeners('delete', residentId);
    }
}

function setupResidentModalListeners(action, residentId) {
    // Remove existing listeners
    const cancelBtn = document.getElementById(`cancel${action.charAt(0).toUpperCase() + action.slice(1)}ResidentBtn`);
    const confirmBtn = document.getElementById(`confirm${action.charAt(0).toUpperCase() + action.slice(1)}ResidentBtn`);
    
    if (cancelBtn) {
        cancelBtn.replaceWith(cancelBtn.cloneNode(true));
        const newCancelBtn = document.getElementById(`cancel${action.charAt(0).toUpperCase() + action.slice(1)}ResidentBtn`);
        newCancelBtn.addEventListener('click', () => {
            closeResidentModal(action);
        });
    }
    
    if (confirmBtn) {
        confirmBtn.replaceWith(confirmBtn.cloneNode(true));
        const newConfirmBtn = document.getElementById(`confirm${action.charAt(0).toUpperCase() + action.slice(1)}ResidentBtn`);
        newConfirmBtn.addEventListener('click', () => {
            confirmResidentAction(action, residentId);
        });
    }
}

function closeResidentModal(action) {
    const modal = document.getElementById(`${action}ResidentModal`);
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
}

function confirmResidentAction(action, residentId) {
    // Close the modal
    closeResidentModal(action);
    
    // Show success modal
    setTimeout(() => {
        showResidentSuccessModal(action);
    }, 300);
    
    // Here you would make the actual AJAX call to perform the action
    console.log(`Performing ${action} action on resident ${residentId}`);
}

function showResidentSuccessModal(action) {
    const modal = document.getElementById(`${action}ResidentSuccessModal`);
    if (modal) {
        modal.classList.remove('hidden');
        
        // Set up OK button listener
        const okBtn = document.getElementById(`${action}ResidentSuccessOkBtn`);
        if (okBtn) {
            okBtn.replaceWith(okBtn.cloneNode(true));
            const newOkBtn = document.getElementById(`${action}ResidentSuccessOkBtn`);
            newOkBtn.addEventListener('click', () => {
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
                // Optionally refresh the page or update the UI
                // location.reload();
            });
        }
    }
}

function updateArchivedResidentsPagination(totalFilteredItems) {
    const startElement = document.getElementById('archived-residents-showing-start');
    const endElement = document.getElementById('archived-residents-showing-end');
    const totalElement = document.getElementById('archived-residents-total-count');
    const paginationButtons = document.getElementById('archived-residents-pagination-buttons');
    const paginationContainer = document.getElementById('archived-residents-pagination');
    
    if (!startElement || !endElement || !totalElement || !paginationButtons || !paginationContainer) {
        return;
    }
    
    // Update counts
    const startItem = totalFilteredItems > 0 ? (currentArchivedPage - 1) * itemsPerPage + 1 : 0;
    const endItem = Math.min(currentArchivedPage * itemsPerPage, totalFilteredItems);
    
    startElement.textContent = startItem;
    endElement.textContent = endItem;
    totalElement.textContent = totalFilteredItems;
    
    // Calculate total pages
    const totalPages = Math.ceil(totalFilteredItems / itemsPerPage);
    
    // Hide pagination if not needed
    if (totalPages <= 1) {
        paginationContainer.style.display = 'none';
        return;
    } else {
        paginationContainer.style.display = 'flex';
    }
    
    // Generate pagination buttons for archived residents
    generateArchivedResidentsPaginationButtons(totalPages, currentArchivedPage, paginationButtons);
}

function generateArchivedResidentsPaginationButtons(totalPages, currentPage, container) {
    let buttonsHTML = '';
    
    // Previous button
    if (currentPage > 1) {
        buttonsHTML += `
            <button onclick="changeArchivedResidentsPage(${currentPage - 1})" 
                    class="px-3 py-2 text-sm text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                <i class="fas fa-chevron-left"></i>
            </button>
        `;
    }
    
    // Page numbers (similar logic as residents pagination)
    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
    
    if (endPage - startPage < maxVisiblePages - 1) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const isCurrentPage = i === currentPage;
        buttonsHTML += `
            <button onclick="changeArchivedResidentsPage(${i})" 
                    class="px-3 py-2 text-sm ${isCurrentPage 
                        ? 'text-white bg-blue-600 border-blue-600' 
                        : 'text-gray-700 bg-white border-gray-300 hover:bg-gray-50'} border rounded-md">
                ${i}
            </button>
        `;
    }
    
    // Next button
    if (currentPage < totalPages) {
        buttonsHTML += `
            <button onclick="changeArchivedResidentsPage(${currentPage + 1})" 
                    class="px-3 py-2 text-sm text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                <i class="fas fa-chevron-right"></i>
            </button>
        `;
    }
    
    container.innerHTML = buttonsHTML;
}

function changeArchivedResidentsPage(newPage) {
    currentArchivedPage = newPage;
    applyArchivedFilters();
    
    // Scroll to archived residents section
    const archivedSection = document.getElementById('archived-residents-section');
    if (archivedSection) {
        archivedSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}


// ADD NEW FUNCTION for archived filtering
function getFilteredArchivedCards() {
    const filteredItems = [];
    
    // Filter archived resident rows
    const archivedResidentRows = document.querySelectorAll('.archived-resident-row');
    archivedResidentRows.forEach(row => {
        const name = row.getAttribute('data-name');
        
        if (!currentArchivedSearchTerm || (name && name.includes(currentArchivedSearchTerm))) {
            filteredItems.push(row);
        }
    });
    
    // Filter archived admin cards
    const archivedAdminCards = document.querySelectorAll('.archived-admin-card');
    archivedAdminCards.forEach(card => {
        const name = card.getAttribute('data-name');
        
        if (!currentArchivedSearchTerm || (name && name.includes(currentArchivedSearchTerm))) {
            filteredItems.push(card);
        }
    });
    
    return filteredItems;
}

function displayFilteredItems(filteredItems) {
    if (currentUserType === 'residents') {
        // Handle resident rows with pagination
        const allRows = document.querySelectorAll('.resident-row');
        allRows.forEach(row => row.style.display = 'none');
        
        // Calculate pagination for residents
        const startIndex = (currentActivePage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const rowsToShow = filteredItems.slice(startIndex, endIndex);
        
        rowsToShow.forEach(row => {
            row.style.display = 'table-row';
        });
        
        // Update residents pagination
        updateResidentsPagination(filteredItems.length);
        
    } else if (currentUserType === 'admins') {
        // Handle admin cards with pagination  
        const allCards = document.querySelectorAll('.admin-card');
        allCards.forEach(card => card.style.display = 'none');
        
        // Calculate pagination for admins (using existing pagination logic)
        const startIndex = (currentActivePage - 1) * 9; // Keep 9 for admin cards
        const endIndex = startIndex + 9;
        const cardsToShow = filteredItems.slice(startIndex, endIndex);
        
        cardsToShow.forEach(card => {
            card.style.display = 'block';
        });
        
        // Update admins pagination (you can create a separate function for this)
        updateAdminsPagination(filteredItems.length);
    }
}



function updateResidentsPagination(totalFilteredItems) {
    const startElement = document.getElementById('residents-showing-start');
    const endElement = document.getElementById('residents-showing-end');
    const totalElement = document.getElementById('residents-total-count');
    const paginationButtons = document.getElementById('residents-pagination-buttons');
    const paginationContainer = document.getElementById('residents-pagination');
    
    if (!startElement || !endElement || !totalElement || !paginationButtons || !paginationContainer) {
        return;
    }
    
    // Update counts
    const startItem = totalFilteredItems > 0 ? (currentActivePage - 1) * itemsPerPage + 1 : 0;
    const endItem = Math.min(currentActivePage * itemsPerPage, totalFilteredItems);
    
    startElement.textContent = startItem;
    endElement.textContent = endItem;
    totalElement.textContent = totalFilteredItems;
    
    // Calculate total pages
    const totalPages = Math.ceil(totalFilteredItems / itemsPerPage);
    
    // Hide pagination if not needed
    if (totalPages <= 1) {
        paginationContainer.style.display = 'none';
        return;
    } else {
        paginationContainer.style.display = 'flex';
    }
    
    // Generate pagination buttons
    generatePaginationButtons('residents', totalPages, currentActivePage, paginationButtons);
}

function updateAdminsPagination(totalFilteredItems) {
    // Similar to residents but for admins - you can implement this if needed
    // For now, admins don't have visible pagination in the UI
}

function generatePaginationButtons(type, totalPages, currentPage, container) {
    let buttonsHTML = '';
    
    // Previous button
    if (currentPage > 1) {
        buttonsHTML += `
            <button onclick="changeResidentsPage(${currentPage - 1})" 
                    class="px-3 py-2 text-sm text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                <i class="fas fa-chevron-left"></i>
            </button>
        `;
    }
    
    // Page numbers logic
    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
    
    if (endPage - startPage < maxVisiblePages - 1) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    
    // First page and ellipsis
    if (startPage > 1) {
        buttonsHTML += `
            <button onclick="changeResidentsPage(1)" 
                    class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                1
            </button>
        `;
        if (startPage > 2) {
            buttonsHTML += `<span class="px-3 py-2 text-sm text-gray-500">...</span>`;
        }
    }
    
    // Page number buttons
    for (let i = startPage; i <= endPage; i++) {
        const isCurrentPage = i === currentPage;
        buttonsHTML += `
            <button onclick="changeResidentsPage(${i})" 
                    class="px-3 py-2 text-sm ${isCurrentPage 
                        ? 'text-white bg-blue-600 border-blue-600' 
                        : 'text-gray-700 bg-white border-gray-300 hover:bg-gray-50'} border rounded-md">
                ${i}
            </button>
        `;
    }
    
    // Last page and ellipsis
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            buttonsHTML += `<span class="px-3 py-2 text-sm text-gray-500">...</span>`;
        }
        buttonsHTML += `
            <button onclick="changeResidentsPage(${totalPages})" 
                    class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                ${totalPages}
            </button>
        `;
    }
    
    // Next button
    if (currentPage < totalPages) {
        buttonsHTML += `
            <button onclick="changeResidentsPage(${currentPage + 1})" 
                    class="px-3 py-2 text-sm text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                <i class="fas fa-chevron-right"></i>
            </button>
        `;
    }
    
    container.innerHTML = buttonsHTML;
}

function changeResidentsPage(newPage) {
    currentActivePage = newPage;
    applyFilters();
    
    // Scroll to residents table
    const residentsTable = document.getElementById('residents-table');
    if (residentsTable) {
        residentsTable.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}








// Main filter function that handles both filter and search with pagination
function applyFilters() {
    console.log(`Applying filters - Type: ${currentUserType}, Status: ${currentStatusFilter}, Search: "${currentSearchTerm}"`);
    
    // Handle active section
    const activeFilteredItems = getFilteredCards('active');
    displayFilteredItems(activeFilteredItems);
    
    // Show/hide no results message
    const noResultsDiv = document.getElementById('no-results');
    const noResidentsDiv = document.getElementById('no-residents');
    const noAdminsDiv = document.getElementById('no-active-admins');
    
    if (activeFilteredItems.length === 0) {
        if (noResidentsDiv) noResidentsDiv.style.display = 'none';
        if (noAdminsDiv) noAdminsDiv.style.display = 'none';
        if (noResultsDiv) {
            noResultsDiv.classList.remove('hidden');
            noResultsDiv.style.display = 'block';
        }
    } else {
        if (noResultsDiv) {
            noResultsDiv.classList.add('hidden');
            noResultsDiv.style.display = 'none';
        }
        
        // Show original no-data messages if no filtering is applied
        if (currentStatusFilter === 'all' && !currentSearchTerm) {
            if (currentUserType === 'residents' && activeFilteredItems.length === 0) {
                if (noResidentsDiv) noResidentsDiv.style.display = 'block';
            } else if (currentUserType === 'admins' && activeFilteredItems.length === 0) {
                if (noAdminsDiv) noAdminsDiv.style.display = 'block';
            }
        }
    }
    
    console.log(`Filter results - Active: ${activeFilteredItems.length}`);
}

// Display cards for a specific page
function displayPagedCards(section, filteredCards, currentPage) {
    const isArchived = section === 'archived';
    const gridId = isArchived ? 'archived-grid' : 'users-grid';
    const grid = document.getElementById(gridId);
    
    if (!grid) return;
    
    // Hide all user cards first
    const allCards = grid.querySelectorAll('.user-card');
    allCards.forEach(card => {
        // Skip placeholder divs
        if (card.id === 'no-active-admins' || card.id === 'no-archived-admins') {
            return;
        }
        card.style.display = 'none';
    });
    
    // Calculate pagination
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const cardsToShow = filteredCards.slice(startIndex, endIndex);
    
    // Show cards for current page
    cardsToShow.forEach(card => {
        card.style.display = 'block';
        card.style.opacity = '1';
        card.style.transform = 'scale(1)';
    });
}

// ADD NEW FUNCTION for archived filters
function applyArchivedFilters() {
    console.log(`Applying archived filters - Search: "${currentArchivedSearchTerm}"`);
    
    // Get filtered archived residents
    const archivedResidentRows = document.querySelectorAll('.archived-resident-row');
    const filteredArchivedResidents = [];
    
    archivedResidentRows.forEach(row => {
        const name = row.getAttribute('data-name');
        
        if (!currentArchivedSearchTerm || (name && name.includes(currentArchivedSearchTerm))) {
            filteredArchivedResidents.push(row);
        }
    });
    
    // Hide all archived resident rows first
    archivedResidentRows.forEach(row => row.style.display = 'none');
    
    // Apply pagination to archived residents
    const startIndex = (currentArchivedPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const residentsToShow = filteredArchivedResidents.slice(startIndex, endIndex);
    
    // Show paginated archived residents
    residentsToShow.forEach(row => {
        row.style.display = 'table-row';
    });
    
    // Update archived residents pagination
    updateArchivedResidentsPagination(filteredArchivedResidents.length);
    
    // Handle archived admin cards (existing logic)
    const archivedAdminCards = document.querySelectorAll('.archived-admin-card');
    archivedAdminCards.forEach(card => {
        const name = card.getAttribute('data-name');
        
        if (!currentArchivedSearchTerm || (name && name.includes(currentArchivedSearchTerm))) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
    
    console.log(`Archived filter results - Residents: ${filteredArchivedResidents.length}`);
}


// Update pagination controls
function updatePagination(section, totalFilteredItems) {
    const isArchived = section === 'archived';
    const prefix = isArchived ? 'archived' : 'active';
    const currentPage = isArchived ? currentArchivedPage : currentActivePage;
    
    // Update showing text
    const startElement = document.getElementById(`${prefix}-showing-start`);
    const endElement = document.getElementById(`${prefix}-showing-end`);
    const totalElement = document.getElementById(`${prefix}-total-count`);
    const paginationButtons = document.getElementById(`${prefix}-pagination-buttons`);
    const paginationContainer = document.getElementById(`${prefix}-pagination`);
    
    if (!startElement || !endElement || !totalElement || !paginationButtons || !paginationContainer) {
        return;
    }
    
    // Update counts
    const startItem = totalFilteredItems > 0 ? (currentPage - 1) * itemsPerPage + 1 : 0;
    const endItem = Math.min(currentPage * itemsPerPage, totalFilteredItems);
    
    startElement.textContent = startItem;
    endElement.textContent = endItem;
    totalElement.textContent = totalFilteredItems;
    
    // Calculate total pages
    const totalPages = Math.ceil(totalFilteredItems / itemsPerPage);
    
    // Hide pagination if not needed
    if (totalPages <= 1) {
        paginationContainer.style.display = 'none';
        return;
    } else {
        paginationContainer.style.display = 'flex';
    }
    
    // Generate pagination buttons
    let buttonsHTML = '';
    
    // Previous button
    if (currentPage > 1) {
        buttonsHTML += `
            <button onclick="changePage('${section}', ${currentPage - 1})" 
                    class="px-3 py-2 text-sm text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                <i class="fas fa-chevron-left"></i>
            </button>
        `;
    }
    
    // Page numbers
    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
    
    // Adjust start page if we're near the end
    if (endPage - startPage < maxVisiblePages - 1) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    
    // First page and ellipsis
    if (startPage > 1) {
        buttonsHTML += `
            <button onclick="changePage('${section}', 1)" 
                    class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                1
            </button>
        `;
        if (startPage > 2) {
            buttonsHTML += `<span class="px-3 py-2 text-sm text-gray-500">...</span>`;
        }
    }
    
    // Page number buttons
    for (let i = startPage; i <= endPage; i++) {
        const isCurrentPage = i === currentPage;
        buttonsHTML += `
            <button onclick="changePage('${section}', ${i})" 
                    class="px-3 py-2 text-sm ${isCurrentPage 
                        ? 'text-white bg-blue-600 border-blue-600' 
                        : 'text-gray-700 bg-white border-gray-300 hover:bg-gray-50'} border rounded-md">
                ${i}
            </button>
        `;
    }
    
    // Last page and ellipsis
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            buttonsHTML += `<span class="px-3 py-2 text-sm text-gray-500">...</span>`;
        }
        buttonsHTML += `
            <button onclick="changePage('${section}', ${totalPages})" 
                    class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                ${totalPages}
            </button>
        `;
    }
    
    // Next button
    if (currentPage < totalPages) {
        buttonsHTML += `
            <button onclick="changePage('${section}', ${currentPage + 1})" 
                    class="px-3 py-2 text-sm text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                <i class="fas fa-chevron-right"></i>
            </button>
        `;
    }
    
    paginationButtons.innerHTML = buttonsHTML;
}

// Change page function
function changePage(section, newPage) {
    if (section === 'active') {
        currentActivePage = newPage;
    } else if (section === 'archived') {
        currentArchivedPage = newPage;
    }
    
    // Re-apply filters to show the new page
    applyFilters();
    
    // Scroll to top of the section
    const sectionElement = document.getElementById(section === 'active' ? 'active-users-section' : 'archived-section');
    if (sectionElement) {
        sectionElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Toggle archived section
function toggleArchivedSection() {
    const archivedSection = document.getElementById('archived-section');
    const toggleBtn = document.getElementById('archived-toggle');
    
    if (archivedSection && toggleBtn) {
        if (archivedSection.classList.contains('hidden')) {
            // Show archived section
            archivedSection.classList.remove('hidden');
            toggleBtn.innerHTML = '<i class="fas fa-users mr-1"></i>Active Users';
            toggleBtn.classList.remove('bg-red-600', 'hover:bg-red-700');
            toggleBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
            
            // Scroll to archived section
            archivedSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            // Hide archived section
            archivedSection.classList.add('hidden');
            toggleBtn.innerHTML = '<i class="fas fa-archive mr-1"></i>Archived';
            toggleBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
            toggleBtn.classList.add('bg-red-600', 'hover:bg-red-700');
        }
    }
}


// Modal functions
function openAddAdminModal() {
    const modal = document.getElementById('addAdminModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }
}

function closeAddAdminModal() {
    const modal = document.getElementById('addAdminModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto'; // Restore scrolling
    }
}



// Update the existing removeAdmin function to work with new admin IDs
function removeAdmin(adminId) {
    if (confirm('Are you sure you want to remove this admin? This action cannot be undone.')) {
        console.log(`Remove admin functionality for ID: ${adminId}`);
        
        // Show loading state
        const adminCard = document.querySelector(`[onclick*="${adminId}"]`).closest('.admin-card');
        if (adminCard) {
            adminCard.style.opacity = '0.5';
            adminCard.style.pointerEvents = 'none';
        }
        
        // Here you would make an AJAX call to remove the admin
        setTimeout(() => {
            alert(`Remove admin functionality for ID: ${adminId} - To be implemented`);
            if (adminCard) {
                adminCard.style.opacity = '1';
                adminCard.style.pointerEvents = 'auto';
            }
        }, 1000);
    }
}

// Auto-generate username based on first name
function generateUsername() {
    const firstNameInput = document.getElementById('admin_fn');
    const usernameInput = document.getElementById('username');
    
    if (firstNameInput && usernameInput) {
        const firstName = firstNameInput.value.toLowerCase().replace(/\s+/g, '').replace(/[^a-z0-9]/g, '');
        usernameInput.value = firstName ? `admin_${firstName}` : '';
    }
}

// Real-time search with debouncing
let searchTimeout;
function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        searchUsers();
    }, 300); // Wait 300ms after user stops typing
}

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    console.log('Users page initializing...');
    
    // Initialize with residents view
    currentUserType = 'residents';
    currentStatusFilter = 'all';
    currentSearchTerm = '';
    currentArchivedSearchTerm = '';
    
    // Set up search input listeners
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', searchUsers);
    }
    
    const archivedSearchInput = document.getElementById('archived-search-input');
    if (archivedSearchInput) {
        archivedSearchInput.addEventListener('input', searchArchivedUsers);
    }
    
    // Start time updates
    updateTime();
    setInterval(updateTime, 1000);
    
    // Initialize filters
    filterUsers('residents'); // Start with residents view
    
    console.log('Users page initialized successfully');
});

// Form submission handler
document.addEventListener('DOMContentLoaded', function() {
    const addAdminForm = document.getElementById('addAdminForm');
    if (addAdminForm) {
        addAdminForm.addEventListener('submit', function(e) {
            // Let the form submit normally to PHP
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.classList.remove('hidden');
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                // submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creating...';
            }
        });
    }
});

// Additional utility functions for future use
function refreshUserList() {
    // Function to refresh the user list (for future AJAX implementation)
    console.log('Refreshing user list...');
    location.reload(); // For now, just reload the page
}

function updateUserStatus(userId, newStatus) {
    // Function to update user status (for future AJAX implementation)
    console.log(`Updating user ${userId} status to ${newStatus}`);
}

// Clear search function
function clearSearch() {
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.value = '';
        currentSearchTerm = '';
        applyFilters();
    }
}

function countUsers() {
    const userCards = document.querySelectorAll('.user-card');
    let counts = {
        total: 0,
        residents: 0,
        admins: 0,
        online: 0,
        offline: 0
    };
    
    userCards.forEach(card => {
        // Skip placeholder divs
        if (card.id === 'no-active-admins' || card.id === 'no-archived-admins') {
            return;
        }
        
        // Count ALL cards, not just visible ones
        counts.total++;
        const role = card.getAttribute('data-role');
        const status = card.getAttribute('data-status');
        
        if (role === 'resident') counts.residents++;
        if (role === 'admin') counts.admins++;
        if (status === 'online') counts.online++;
        if (status === 'offline') counts.offline++;
    });
    
    return counts;
}


// Function to refresh the page to update "last seen" times
function refreshLastSeenTimes() {
    if (confirm('Refresh the page to update "last seen" times?')) {
        location.reload();
    }
}

// Auto-refresh option (optional - uncomment if you want auto-refresh)

// Auto-refresh every 5 minutes to update last seen times
setInterval(() => {
    location.reload();
}, 300000); // 5 minutes = 300000 milliseconds

// Add refresh button functionality (you can add this button to your HTML if needed)
function addRefreshButton() {
    const refreshBtn = document.createElement('button');
    refreshBtn.innerHTML = '<i class="fas fa-sync-alt mr-2"></i>Refresh Times';
    refreshBtn.className = 'bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm transition-colors';
    refreshBtn.onclick = refreshLastSeenTimes;
    
    // Add to the top of the users section (you can modify placement as needed)
    const usersSection = document.getElementById('active-users-section');
    if (usersSection) {
        const header = usersSection.querySelector('.p-6.border-b');
        if (header) {
            header.appendChild(refreshBtn);
        }
    }
}

// Updated stats function to get database counts and visible counts
function updateStatsCards() {
    const stats = countUsers();
    
    const totalResidentsEl = document.getElementById('total-residents');
    const onlineUsersEl = document.getElementById('online-users');
    const totalAdminsEl = document.getElementById('total-admins');
    const archivedCountEl = document.getElementById('archived-count');
    
    // Update residents and online users with visible counts
    if (totalResidentsEl) totalResidentsEl.textContent = stats.residents;
    if (onlineUsersEl) onlineUsersEl.textContent = stats.online;
    
    // Get database counts from data attributes (set by PHP)
    const adminCard = document.querySelector('[data-total-admins]');
    const archivedCard = document.querySelector('[data-archived-count]');
    
    if (totalAdminsEl && adminCard) {
        const dbAdminCount = adminCard.getAttribute('data-total-admins') || '0';
        totalAdminsEl.textContent = dbAdminCount;
    }
    
    if (archivedCountEl && archivedCard) {
        const dbArchivedCount = archivedCard.getAttribute('data-archived-count') || '0';
        archivedCountEl.textContent = dbArchivedCount;
    }
}

// Enhanced filter function with stats update
function applyFiltersWithStats() {
    applyFilters();
    updateStatsCards();
}

// Add keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + F to focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }
    
    // Escape to clear search
    if (e.key === 'Escape') {
        clearSearch();
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.blur();
        }
    }
});

// Animation helper functions
function fadeIn(element, duration = 300) {
    element.style.opacity = '0';
    element.style.display = 'block';
    
    let start = null;
    function animate(timestamp) {
        if (!start) start = timestamp;
        const progress = timestamp - start;
        
        element.style.opacity = Math.min(progress / duration, 1);
        
        if (progress < duration) {
            requestAnimationFrame(animate);
        }
    }
    
    requestAnimationFrame(animate);
}

function fadeOut(element, duration = 300) {
    let start = null;
    function animate(timestamp) {
        if (!start) start = timestamp;
        const progress = timestamp - start;
        
        element.style.opacity = Math.max(1 - (progress / duration), 0);
        
        if (progress < duration) {
            requestAnimationFrame(animate);
        } else {
            element.style.display = 'none';
        }
    }
    
    requestAnimationFrame(animate);
}




// Initialize stats on page load
document.addEventListener('DOMContentLoaded', function() {
    // Wait a bit for all elements to be ready
    setTimeout(() => {
        updateStatsCards();
    }, 100);
});


// Function to refresh admin counts
function refreshAdminCounts() {
    fetch('../javascript/USERS/get_admin_counts.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('total-admins').textContent = data.totalActive;
                document.getElementById('archived-count').textContent = data.totalArchived;
            }
        })
        .catch(error => console.error('Error refreshing admin counts:', error));
}

// Refresh counts when page loads
document.addEventListener('DOMContentLoaded', refreshAdminCounts);
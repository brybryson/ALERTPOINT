



// Profile Modal Functions
function openProfileModal() {
    // Close settings dropdown first
    const dropdown = document.getElementById('settingsDropdown');
    if (dropdown && !dropdown.classList.contains('pointer-events-none')) {
        dropdown.classList.add('pointer-events-none');
        dropdown.classList.remove('opacity-100', 'scale-100');
        dropdown.classList.add('opacity-0', 'scale-95');
    }
    
    fetchCurrentUserProfile();
    document.getElementById('profileModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeProfileModal() {
    document.getElementById('profileModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

async function fetchCurrentUserProfile() {
    try {
        const response = await fetch('/ALERTPOINT/javascript/LOGIN/get_current_user.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (data.success) {
            populateProfileModal(data.user);
        } else {
            console.error('Error fetching user profile:', data.message);
            alert('Error loading profile data. Please try again.');
        }
        
    } catch (error) {
        console.error('Error fetching user profile:', error);
        alert('Error connecting to server. Please try again.');
    }
}

function populateProfileModal(user) {
    document.getElementById('profileAdminId').textContent = user.admin_id || '-';
    document.getElementById('profileFirstName').textContent = user.first_name || '-';
    document.getElementById('profileMiddleName').textContent = user.middle_name || '-';
    document.getElementById('profileLastName').textContent = user.last_name || '-';
    document.getElementById('profileEmail').textContent = user.user_email || '-';
    document.getElementById('profileUsername').textContent = user.username || '-';
    document.getElementById('profilePosition').textContent = user.barangay_position || '-';
    document.getElementById('profileRole').textContent = user.role || '-';

    const fullName = getFullName(user.first_name, user.middle_name, user.last_name);
    document.getElementById('profileFullName').textContent = fullName;

    // Handle profile photo
    const profilePhotoElement = document.getElementById('profilePhoto');
    const profileInitialsElement = document.getElementById('profileInitials');
    
    if (user.picture && user.picture !== 'NULL' && user.picture.trim() !== '') {
        const normalizedPath = normalizePicturePath(user.picture);
        profilePhotoElement.src = normalizedPath;
        profilePhotoElement.classList.remove('hidden');
        profileInitialsElement.classList.add('hidden');
    } else {
        const initials = getInitials(user.first_name, user.middle_name, user.last_name);
        profileInitialsElement.querySelector('span').textContent = initials;
        profilePhotoElement.classList.add('hidden');
        profileInitialsElement.classList.remove('hidden');
    }

    // Handle birthdate - format as "Nov 27, 2003"
    if (user.birthdate && user.birthdate !== '0000-00-00') {
        const birthDate = new Date(user.birthdate);
        const formattedDate = birthDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
        document.getElementById('profileBirthdate').textContent = formattedDate;
    } else {
        document.getElementById('profileBirthdate').textContent = '-';
    }

    // Handle account status
    const accountStatus = user.account_status || 'unknown';
    const accountStatusDot = document.getElementById('profileAccountStatusDot');
    document.getElementById('profileAccountStatus').textContent = accountStatus;
    
    accountStatusDot.className = 'w-2 h-2 rounded-full ';
    if (accountStatus === 'active') {
        accountStatusDot.className += 'bg-green-500';
    } else if (accountStatus === 'inactive') {
        accountStatusDot.className += 'bg-red-500';
    } else if (accountStatus === 'suspended') {
        accountStatusDot.className += 'bg-orange-500';
    } else {
        accountStatusDot.className += 'bg-gray-500';
    }

    // Handle user status
    const userStatus = user.user_status || 'unknown';
    const userStatusDot = document.getElementById('profileUserStatusDot');
    document.getElementById('profileUserStatus').textContent = userStatus;
    
    userStatusDot.className = 'w-2 h-2 rounded-full ';
    if (userStatus === 'online') {
        userStatusDot.className += 'bg-green-500';
    } else if (userStatus === 'offline') {
        userStatusDot.className += 'bg-gray-500';
    } else {
        userStatusDot.className += 'bg-yellow-500';
    }

    // Handle account created - format as "Aug 2, 2025 - 8:12 PM"
    if (user.account_created) {
        const createdDate = new Date(user.account_created);
        const formattedCreatedDate = createdDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        }) + ' - ' + createdDate.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
        document.getElementById('profileAccountCreated').textContent = formattedCreatedDate;
    } else {
        document.getElementById('profileAccountCreated').textContent = '-';
    }

    // Handle last active - format as "Aug 3, 2025 - 9:33 PM"
    if (user.last_active && user.last_active !== '0000-00-00 00:00:00') {
        const lastActiveDate = new Date(user.last_active);
        const formattedLastActive = lastActiveDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        }) + ' - ' + lastActiveDate.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
        document.getElementById('profileLastActive').textContent = formattedLastActive;
    } else {
        document.getElementById('profileLastActive').textContent = 'Never';
    }
}

// Close modal when clicking outside
document.getElementById('profileModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeProfileModal();
    }
});
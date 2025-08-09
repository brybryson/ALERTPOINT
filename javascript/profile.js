// Debug version - Profile Modal Functions
function openProfileModal() {
    console.log('Opening profile modal...');
    
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
    console.log('Fetching current user profile...');
    
    try {
        const response = await fetch('/ALERTPOINT/javascript/LOGIN/get_current_user.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            }
        });

        console.log('Response status:', response.status);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('Response data:', data);
        
        if (data.success) {
            console.log('User data received:', data.user);
            populateProfileModal(data.user);
        } else {
            console.error('Error fetching user profile:', data.message);
            alert('Error loading profile data: ' + data.message);
        }
        
    } catch (error) {
        console.error('Error fetching user profile:', error);
        alert('Error connecting to server: ' + error.message);
    }
}

function populateProfileModal(user) {
    console.log('Populating profile modal with user:', user);
    
    // Check if elements exist
    const elements = [
        'profilePhoto', 'profileInitials', 'profileFullName', 'profilePosition',
        'profileAdminId', 'profileFirstName', 'profileMiddleName', 'profileLastName',
        'profileEmail', 'profileUsername', 'profileRole', 'profileBirthdate',
        'profileAccountStatus', 'profileAccountStatusDot', 'profileUserStatus', 
        'profileUserStatusDot', 'profileAccountCreated', 'profileLastActive'
    ];
    
    elements.forEach(id => {
        const element = document.getElementById(id);
        if (!element) {
            console.error(`Element with id '${id}' not found!`);
        } else {
            console.log(`Element '${id}' found`);
        }
    });

    // Basic Information
    const profileAdminId = document.getElementById('profileAdminId');
    if (profileAdminId) {
        profileAdminId.textContent = user.admin_id || '-';
        console.log('Set profileAdminId to:', user.admin_id);
    }
    
    const profileFirstName = document.getElementById('profileFirstName');
    if (profileFirstName) {
        profileFirstName.textContent = user.first_name || '-';
        console.log('Set profileFirstName to:', user.first_name);
    }
    
    const profileMiddleName = document.getElementById('profileMiddleName');
    if (profileMiddleName) {
        profileMiddleName.textContent = user.middle_name || '-';
        console.log('Set profileMiddleName to:', user.middle_name);
    }
    
    const profileLastName = document.getElementById('profileLastName');
    if (profileLastName) {
        profileLastName.textContent = user.last_name || '-';
        console.log('Set profileLastName to:', user.last_name);
    }
    
    const profileEmail = document.getElementById('profileEmail');
    if (profileEmail) {
        profileEmail.textContent = user.user_email || '-';
        console.log('Set profileEmail to:', user.user_email);
    }
    
    const profileUsername = document.getElementById('profileUsername');
    if (profileUsername) {
        profileUsername.textContent = user.username || '-';
        console.log('Set profileUsername to:', user.username);
    }
    
    const profilePosition = document.getElementById('profilePosition');
    if (profilePosition) {
        profilePosition.textContent = user.barangay_position || '-';
        console.log('Set profilePosition to:', user.barangay_position);
    }
    
    const profileRole = document.getElementById('profileRole');
    if (profileRole) {
        profileRole.textContent = user.role || '-';
        console.log('Set profileRole to:', user.role);
    }

    // Full Name Construction
    const fullName = getFullName(user.first_name, user.middle_name, user.last_name);
    const profileFullName = document.getElementById('profileFullName');
    if (profileFullName) {
        profileFullName.textContent = fullName;
        console.log('Set profileFullName to:', fullName);
    }

    // Handle Profile Photo
    const profilePhotoElement = document.getElementById('profilePhoto');
    const profileInitialsElement = document.getElementById('profileInitials');
    
    console.log('Original picture path:', user.picture);
    
    if (user.picture && user.picture !== 'NULL' && user.picture.trim() !== '') {
        // Convert ../../ to /ALERTPOINT/
        let normalizedPath = user.picture;
        if (normalizedPath.startsWith('../../')) {
            normalizedPath = normalizedPath.replace('../../', '/ALERTPOINT/');
        }
        console.log('Normalized picture path:', normalizedPath);
        
        if (profilePhotoElement) {
            profilePhotoElement.src = normalizedPath;
            profilePhotoElement.classList.remove('hidden');
            console.log('Showing profile photo');
        }
        if (profileInitialsElement) {
            profileInitialsElement.classList.add('hidden');
        }
    } else {
        console.log('No picture found, showing initials');
        const initials = getInitials(user.first_name, user.middle_name, user.last_name);
        console.log('Generated initials:', initials);
        
        if (profileInitialsElement) {
            const initialsSpan = profileInitialsElement.querySelector('span');
            if (initialsSpan) {
                initialsSpan.textContent = initials;
                console.log('Set initials to:', initials);
            }
            profileInitialsElement.classList.remove('hidden');
        }
        if (profilePhotoElement) {
            profilePhotoElement.classList.add('hidden');
        }
    }

    // Handle Birthdate
    if (user.birthdate && user.birthdate !== '0000-00-00' && user.birthdate !== '0000-00-00 00:00:00') {
        const birthDate = new Date(user.birthdate);
        if (!isNaN(birthDate.getTime())) {
            const formattedDate = birthDate.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            const profileBirthdate = document.getElementById('profileBirthdate');
            if (profileBirthdate) {
                profileBirthdate.textContent = formattedDate;
                console.log('Set profileBirthdate to:', formattedDate);
            }
        }
    }

    // Handle Account Status
    const accountStatus = user.account_status || 'unknown';
    const accountStatusDot = document.getElementById('profileAccountStatusDot');
    document.getElementById('profileAccountStatus').textContent = accountStatus.charAt(0).toUpperCase() + accountStatus.slice(1);
    
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

    // Handle User Status
    const userStatus = user.user_status || 'unknown';
    const userStatusDot = document.getElementById('profileUserStatusDot');
    document.getElementById('profileUserStatus').textContent = userStatus.charAt(0).toUpperCase() + userStatus.slice(1);
    
    userStatusDot.className = 'w-2 h-2 rounded-full ';
    if (userStatus === 'online') {
        userStatusDot.className += 'bg-green-500';
    } else if (userStatus === 'offline') {
        userStatusDot.className += 'bg-red-500';
    } else {
        userStatusDot.className += 'bg-yellow-500';
    }

    // Handle Account Created - Format: "August 10, 2025 • 12:47 AM"
    if (user.account_created) {
        const createdDate = new Date(user.account_created);
        if (!isNaN(createdDate.getTime())) {
            const formattedCreatedDate = createdDate.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }) + ' • ' + createdDate.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            document.getElementById('profileAccountCreated').textContent = formattedCreatedDate;
        } else {
            document.getElementById('profileAccountCreated').textContent = '-';
        }
    } else {
        document.getElementById('profileAccountCreated').textContent = '-';
    }

    // Handle Last Active - Format: "August 10, 2025 • 12:47 AM"
    if (user.last_active && user.last_active !== '0000-00-00 00:00:00') {
        const lastActiveDate = new Date(user.last_active);
        if (!isNaN(lastActiveDate.getTime())) {
            const formattedLastActive = lastActiveDate.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }) + ' • ' + lastActiveDate.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            document.getElementById('profileLastActive').textContent = formattedLastActive;
        } else {
            document.getElementById('profileLastActive').textContent = 'Never';
        }
    } else {
        document.getElementById('profileLastActive').textContent = 'Never';
    }
}

// Helper Functions
function getFullName(firstName, middleName = '', lastName = '') {
    let fullName = firstName || '';
    if (middleName && middleName.trim() !== '') {
        fullName += ' ' + middleName;
    }
    if (lastName && lastName.trim() !== '') {
        fullName += ' ' + lastName;
    }
    const result = fullName.trim() || '-';
    console.log('getFullName result:', result);
    return result;
}

function getInitials(firstName, middleName = '', lastName = '') {
    let initials = '';
    
    if (firstName && firstName.trim() !== '') {
        initials += firstName.charAt(0).toUpperCase();
    }
    
    if (middleName && middleName.trim() !== '') {
        initials += middleName.charAt(0).toUpperCase();
    } else if (lastName && lastName.trim() !== '') {
        initials += lastName.charAt(0).toUpperCase();
    }
    
    const result = initials || '??';
    console.log('getInitials result:', result);
    return result;
}
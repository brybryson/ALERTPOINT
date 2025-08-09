// FOOTER PART

// Auto-update year and timestamps
function updateFooterInfo() {
    // Update current year
    const currentYear = new Date().getFullYear();
    document.getElementById('current-year').textContent = currentYear;
    
    // Update last update time
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-PH', {
        timeZone: 'Asia/Manila',
        hour12: true,
        hour: '2-digit',
        minute: '2-digit'
    });
    const lastUpdateElement = document.getElementById('last-update-time');
    if (lastUpdateElement) {
        lastUpdateElement.textContent = timeString;
    }
}

// Update footer info on page load and every minute
updateFooterInfo();
setInterval(updateFooterInfo, 60000); // Update every minute

// Add smooth scroll behavior for anchor links
document.querySelectorAll('footer a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth'
            });
        }
    });
});
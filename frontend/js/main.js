// Toggle Notification Dropdown
function toggleNotifications() {
    const dropdown = document.getElementById("notif-dropdown");
    dropdown.classList.toggle("show");
}

// Close dropdown if user clicks outside
window.onclick = function(event) {
    if (!event.target.closest('.notification-container')) {
        const dropdown = document.getElementById("notif-dropdown");
        if (dropdown && dropdown.classList.contains('show')) {
            dropdown.classList.remove('show');
        }
    }
}

// Update User Status Dynamically (Mock Function)
function setUserStatus(role) {
    const badge = document.querySelector('.user-profile .badge');
    badge.className = `badge ${role.toLowerCase()}`;
    badge.innerText = role;
}

// //

// Toggle Notification Dropdown
function toggleMessage() {
    const dropdown = document.getElementById("message-dropdown");
    dropdown.classList.toggle("show");
}

// Close dropdown if user clicks outside
window.onclick = function(event) {
    if (!event.target.closest('.message-container')) {
        const dropdown = document.getElementById("message-dropdown");
        if (dropdown && dropdown.classList.contains('show')) {
            dropdown.classList.remove('show');
        }
    }
}

// Update User Status Dynamically (Mock Function)
function setUserStatus(role) {
    const badge = document.querySelector('.user-profile .badge');
    badge.className = `badge ${role.toLowerCase()}`;
    badge.innerText = role;
}
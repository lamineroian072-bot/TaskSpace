// js/main.js - Client Side UI Logic & Interactive Controls

document.addEventListener('DOMContentLoaded', () => {
    console.log('🏠 Boarding House System Loaded');
    initDatePickers();
});

// Quick Fill Demo Credentials on Login Page
function fillCreds(email, password) {
    const emailInput = document.getElementById('loginEmail');
    const passInput = document.getElementById('loginPassword');
    if (emailInput && passInput) {
        emailInput.value = email;
        passInput.value = password;
    }
}

// Open Booking Modal for Room Reservation
function openBookingModal(roomId, roomName) {
    const modalRoomId = document.getElementById('modalRoomId');
    const modalRoomTitle = document.getElementById('modalRoomTitle');
    const modal = document.getElementById('bookingModal');

    if (modalRoomId && modalRoomTitle && modal) {
        modalRoomId.value = roomId;
        modalRoomTitle.innerText = 'Reserve: ' + roomName;
        modal.style.display = 'flex';
    }
}

// Close Booking Modal
function closeBookingModal() {
    const modal = document.getElementById('bookingModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Toggle Admin Room Form (Create / Edit)
function toggleAddForm() {
    const formCard = document.getElementById('roomFormCard');
    if (formCard) {
        formCard.style.display = (formCard.style.display === 'none' || formCard.style.display === '') ? 'block' : 'none';
    }
}

// Print Current Page / Receipt
function printReport() {
    window.print();
}

// Set Default Date Pickers to Today
function initDatePickers() {
    const checkInInput = document.querySelector('input[name="check_in_date"]');
    if (checkInInput && !checkInInput.value) {
        const today = new Date().toISOString().split('T')[0];
        checkInInput.value = today;
    }
}

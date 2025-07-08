document.addEventListener('DOMContentLoaded', function() {
    const bookNowButtons = document.querySelectorAll('.book-now-trigger');

    // Create the fully booked modal HTML
    const fullyBookedModalHTML = `
        <div id="fullyBookedModal" class="room-details-modal">
            <div class="room-details-modal-content">
                <span class="close-button" id="closeFullyBookedModal">&times;</span>
                <h2>Room Unavailable</h2>
                <p>Sorry, we are fully booked right now for this room type.</p>
                <button class="room-details-book-btn" id="okFullyBookedModal">OK</button>
            </div>
        </div>
    `;

    // Append the modal HTML to the body if it doesn't already exist
    if (!document.getElementById('fullyBookedModal')) {
        document.body.insertAdjacentHTML('beforeend', fullyBookedModalHTML);
    }

    const fullyBookedModal = document.getElementById('fullyBookedModal');
    const closeFullyBookedModal = document.getElementById('closeFullyBookedModal');
    const okFullyBookedModal = document.getElementById('okFullyBookedModal');

    // Event listeners for the custom fully booked modal
    if (closeFullyBookedModal) {
        closeFullyBookedModal.addEventListener('click', function() {
            if (fullyBookedModal) fullyBookedModal.style.display = 'none';
        });
    }
    if (okFullyBookedModal) {
        okFullyBookedModal.addEventListener('click', function() {
            if (fullyBookedModal) fullyBookedModal.style.display = 'none';
        });
    }
    // Close modal if clicked outside
    if (fullyBookedModal) {
        window.addEventListener('click', function(event) {
            if (event.target == fullyBookedModal) {
                fullyBookedModal.style.display = 'none';
            }
        });
    }

    bookNowButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            const totalRooms = parseInt(this.dataset.totalRooms, 10);
            const roomId = this.dataset.roomId; // Get room ID for the original modal
            const originalBookingModal = document.getElementById(`roomDetailsModal_${roomId}`);

            if (totalRooms === 0) {
                event.preventDefault(); // Prevent default form submission
                event.stopPropagation(); // Stop event from propagating to other listeners (like the one that opens the original booking modal)
                if (fullyBookedModal) fullyBookedModal.style.display = 'flex'; // Show custom modal
            } else {
                // If rooms are available, proceed with the original booking modal logic
                if (originalBookingModal) {
                    const modalTitle = originalBookingModal.querySelector('h2');
                    if (modalTitle) {
                        const isFromHomeSearch = this.dataset.isFromHomeSearch === 'true';
                        modalTitle.textContent = isFromHomeSearch ? 'Confirm Booking' : 'Proceed to Booking';
                    }

                    // Get the main form elements from the current room section
                    const currentRoomForm = this.closest('form');
                    const bookingFormInModal = originalBookingModal.querySelector('#bookingForm_' + roomId);

                    // Transfer amenities
                    currentRoomForm.querySelectorAll('.amenities-grid input[type="checkbox"]').forEach(checkbox => {
                        if (checkbox.checked) {
                            const hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden';
                            hiddenInput.name = checkbox.name; // e.g., pool_twin
                            hiddenInput.value = checkbox.value;
                            bookingFormInModal.appendChild(hiddenInput);
                        }
                    });

                    // Transfer extras
                    currentRoomForm.querySelectorAll('.extras-grid input[type="number"]').forEach(numberInput => {
                        if (parseInt(numberInput.value) > 0) {
                            const hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden';
                            hiddenInput.name = numberInput.name; // e.g., pillow
                            hiddenInput.value = numberInput.value;
                            bookingFormInModal.appendChild(hiddenInput);
                        }
                    });

                    // Transfer quantity
                    const quantityInput = currentRoomForm.querySelector('input[name="quantity"]');
                    if (quantityInput) {
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = quantityInput.name;
                        hiddenInput.value = quantityInput.value;
                        bookingFormInModal.appendChild(hiddenInput);
                    }

                    originalBookingModal.style.display = 'flex';
                }
            }
        });
    });
}); 
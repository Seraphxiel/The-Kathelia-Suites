document.addEventListener('DOMContentLoaded', function() {
    // Get the booking form
    const bookingForm = document.getElementById('bookingForm');
    
    // Get room type ID from hidden input
    // const roomTypeIdInput = document.getElementById('roomTypeId'); // Removed as no longer needed for availability
    // const roomTypeId = roomTypeIdInput ? roomTypeIdInput.value : 0; // Removed as no longer needed for availability

    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            e.preventDefault();
            // Add your booking form submission logic here
            alert('Booking form submitted!');
        });
    }

    // Removed all Flatpickr and date input logic as per user request to disable availability.

    // Room card click logic
    const roomCards = document.querySelectorAll('.room-card');
    const roomsSection = document.querySelector('.rooms-section');
    const roomDetailsSection = document.getElementById('room-details');

    const roomDetails = {
        twin: {
            title: 'Twin Room',
            desc: 'Good for 2 Pax. Spacious comfort for friends or colleagues. Includes 2 single beds, air conditioning, WiFi, and ensuite bathroom.',
            img: '',
        },
        family: {
            title: 'Family Room',
            desc: 'Good for 3 to 5 Pax. Perfect for family getaways and bonding. Multiple beds, extra space, and family-friendly amenities.',
            img: '',
        },
        harmony: {
            title: 'Harmony Room',
            desc: 'Good for 6 to 10 Pax. Ultimate relaxation for large groups. Elegant design, premium amenities, and lots of space.',
            img: '',
        }
    };

    roomCards.forEach(card => {
        card.addEventListener('click', function() {
            const room = this.dataset.room;
            if (roomDetails[room]) {
                roomsSection.style.display = 'none';
                roomDetailsSection.style.display = 'block';
                roomDetailsSection.innerHTML = `
                  <div class="room-details-card">
                    <div class="room-details-img" style="background:#75491b;height:260px;border-radius:10px 10px 0 0;"></div>
                    <h2 style="margin:24px 0 12px 0;">${roomDetails[room].title}</h2>
                    <p style="font-size:18px;color:#444;">${roomDetails[room].desc}</p>
                    <button class="back-to-rooms-btn" style="margin-top:30px;padding:10px 30px;">Back to Rooms</button>
                  </div>
                `;
            }
        });
    });
    // Back to rooms
    roomDetailsSection.addEventListener('click', function(e) {
        if (e.target.classList.contains('back-to-rooms-btn')) {
            roomDetailsSection.style.display = 'none';
            roomsSection.style.display = 'block';
        }
    });
}); 
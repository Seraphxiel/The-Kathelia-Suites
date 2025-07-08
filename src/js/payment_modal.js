document.addEventListener('DOMContentLoaded', function() {
    const paymentModal = document.getElementById('paymentModal');
    const openPaymentModalBtn = document.getElementById('payNowBtn');
    const closeButton = document.querySelector('.close-button');
    const paymentForm = document.getElementById('paymentForm');

    // Open the modal
    if (openPaymentModalBtn) {
        openPaymentModalBtn.addEventListener('click', function() {
            paymentModal.style.display = 'flex';
        });
    }

    // Close the modal when the close button is clicked
    if (closeButton) {
        closeButton.addEventListener('click', function() {
            paymentModal.style.display = 'none';
        });
    }

    // Close the modal when clicking anywhere outside of the modal content
    if (paymentModal) {
        window.addEventListener('click', function(event) {
            if (event.target === paymentModal) {
                paymentModal.style.display = 'none';
            }
        });
    }

    // Handle form submission
    if (paymentForm) {
        paymentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Create FormData object to handle file upload
            const formData = new FormData(paymentForm);
            
            // Add the proof of payment file
            const proofFile = document.getElementById('proofOfPayment').files[0];
            if (proofFile) {
                formData.append('proof_of_payment', proofFile);
            }

            // Submit the form data
            fetch('/kathelia-suites/public/process_payment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to reservations page on success
                    window.location.href = '/kathelia-suites/public/reservations.php';
                } else {
                    alert(data.message || 'Payment processing failed. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your payment. Please try again.');
            });
        });
    }
}); 
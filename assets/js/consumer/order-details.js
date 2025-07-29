document.addEventListener('DOMContentLoaded', function() {
    // Review form submission
    const reviewForm = document.getElementById('review-form');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const rating = formData.get('rating');
            const comment = formData.get('comment');
            
            // Validate form
            if (!rating) {
                showToast('Please select a rating', 'error');
                return;
            }
            
            if (!comment || comment.trim() === '') {
                showToast('Please enter a review comment', 'error');
                return;
            }
            
            // Submit review via AJAX
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Your review has been submitted successfully', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showToast(data.message || 'An error occurred while submitting your review', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while submitting your review', 'error');
            });
        });
    }
    
    // Edit review form submission
    const editReviewForm = document.getElementById('edit-review-form');
    if (editReviewForm) {
        editReviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const rating = formData.get('rating');
            const comment = formData.get('comment');
            
            // Validate form
            if (!rating) {
                showToast('Please select a rating', 'error');
                return;
            }
            
            if (!comment || comment.trim() === '') {
                showToast('Please enter a review comment', 'error');
                return;
            }
            
            // Submit updated review via AJAX
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Your review has been updated successfully', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showToast(data.message || 'An error occurred while updating your review', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while updating your review', 'error');
            });
        });
    }
    
    // Helper function to show toast notifications
    function showToast(message, type = 'info') {
        const toastEl = document.getElementById('liveToast');
        const toastBody = toastEl.querySelector('.toast-body');
        const toastHeader = toastEl.querySelector('.toast-header');
        
        // Set toast content
        toastBody.textContent = message;
        
        // Set toast header color based on type
        toastHeader.className = 'toast-header ';
        switch (type) {
            case 'success':
                toastHeader.classList.add('bg-success', 'text-white');
                break;
            case 'error':
                toastHeader.classList.add('bg-danger', 'text-white');
                break;
            case 'warning':
                toastHeader.classList.add('bg-warning', 'text-white');
                break;
            default:
                toastHeader.classList.add('bg-primary', 'text-white');
        }
        
        // Show toast
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
    }
});
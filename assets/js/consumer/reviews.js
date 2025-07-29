document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap components
    const toastEl = document.getElementById('liveToast');
    if (toastEl) {
        const toast = new bootstrap.Toast(toastEl);
        
        // Check for URL parameters to show success/error messages
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('success')) {
            showToast(urlParams.get('message') || 'Operation completed successfully', 'success');
        } else if (urlParams.has('error')) {
            showToast(urlParams.get('message') || 'An error occurred', 'error');
        }
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
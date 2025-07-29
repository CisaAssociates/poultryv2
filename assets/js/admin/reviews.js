/**
 * Admin Reviews Management JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable if there are reviews
    if ($('#reviews-datatable tbody tr').length > 0 && !$('#reviews-datatable tbody tr td[colspan]').length) {
        $('#reviews-datatable').DataTable({
            responsive: true,
            order: [
                [5, 'desc']
            ], // Sort by date by default
            language: {
                paginate: {
                    previous: "<i class='mdi mdi-chevron-left'>",
                    next: "<i class='mdi mdi-chevron-right'>"
                }
            },
            drawCallback: function() {
                $('.dataTables_paginate > .pagination').addClass('pagination-rounded');
            },
            columnDefs: [
                { targets: [7], orderable: false } // Actions column is not sortable
            ]
        });
    }

    // Handle review modal data
    $('.view-review').on('click', function() {
        const reviewId = $(this).data('review-id');
        const product = $(this).data('product');
        const farm = $(this).data('farm');
        const customer = $(this).data('customer');
        const email = $(this).data('email');
        const rating = $(this).data('rating');
        const comment = $(this).data('comment');
        const date = $(this).data('date');
        const approved = $(this).data('approved');

        // Set modal values
        $('#modal-review-id').val(reviewId);
        $('#product-name').text(product);
        $('#farm-name').text(farm);
        $('#customer-name').text(customer);
        $('#customer-email').text(email);
        $('#review-date').text(date);
        $('#review-comment').text(comment);
        $('#approved').prop('checked', approved === 1);
        
        // Set rating stars
        let ratingHtml = '';
        for (let i = 1; i <= 5; i++) {
            ratingHtml += `<i class="mdi mdi-star${i <= rating ? '' : '-outline'} text-warning"></i>`;
        }
        $('#review-rating').html(ratingHtml);
    });

    // Initialize toasts if they exist
    const toastElements = document.querySelectorAll('.toast');
    if (toastElements.length > 0) {
        toastElements.forEach(function(toastEl) {
            new bootstrap.Toast(toastEl, {
                autohide: true,
                delay: 3000
            }).show();
        });
    }
});
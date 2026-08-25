/**
 * GearParts - Main Vanilla JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    // Auto-dismiss alert notifications after 4 seconds
    const alerts = document.querySelectorAll('.alert-auto-dismiss');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });

    // Handle Quick Search submission from hero or search forms
    const searchForm = document.querySelector('#heroSearchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            const query = searchForm.querySelector('input[name="q"]').value.trim();
            if (!query) {
                e.preventDefault();
                alert('Please enter a spare part name, vehicle make, or part number to search.');
            }
        });
    }
});

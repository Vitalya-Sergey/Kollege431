// Add smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        document.querySelector(this.getAttribute('href')).scrollIntoView({
            behavior: 'smooth'
        });
    });
});

// Add confirmation for delete actions
document.querySelectorAll('[data-confirm]').forEach(element => {
    element.addEventListener('click', function(e) {
        if (!confirm(this.dataset.confirm)) {
            e.preventDefault();
        }
    });
});

// Add loading state for forms
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const submitButton = this.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Загрузка...';
        }
    });
});

// Add tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}); 
// Form validation and enhancements
document.addEventListener('DOMContentLoaded', function() {
    // Auto-format phone numbers
    const phoneInputs = document.querySelectorAll('input[type="text"][name*="contact"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 10) value = value.substring(0, 10);
            e.target.value = value;
        });
    });

    // Number validation
    const numberInputs = document.querySelectorAll('input[type="number"]');
    numberInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            if (e.target.value < 0) e.target.value = 0;
        });
    });

    // Coordinate validation
    const latInputs = document.querySelectorAll('input[name*="latitude"]');
    const lngInputs = document.querySelectorAll('input[name*="longitude"]');
    
    latInputs.forEach(input => {
        input.addEventListener('blur', function(e) {
            const value = parseFloat(e.target.value);
            if (value < -90 || value > 90) {
                alert('Latitude must be between -90 and 90');
                e.target.focus();
            }
        });
    });

    lngInputs.forEach(input => {
        input.addEventListener('blur', function(e) {
            const value = parseFloat(e.target.value);
            if (value < -180 || value > 180) {
                alert('Longitude must be between -180 and 180');
                e.target.focus();
            }
        });
    });

    // Form submission loading states
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="loading-spinner"></span> Processing...';
            }
        });
    });
});

// Real-time character counters
function setupCharacterCounters() {
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        const counter = document.createElement('div');
        counter.className = 'character-counter';
        counter.style.fontSize = '0.8rem';
        counter.style.color = '#718096';
        counter.style.textAlign = 'right';
        counter.style.marginTop = '0.5rem';
        
        textarea.parentNode.insertBefore(counter, textarea.nextSibling);
        
        textarea.addEventListener('input', function() {
            counter.textContent = `${this.value.length} characters`;
        });
        
        // Initial count
        counter.textContent = `${textarea.value.length} characters`;
    });
}

// Print functionality
function printPage() {
    window.print();
}

// Export to Excel (basic implementation)
function exportToExcel() {
    alert('Export feature would be implemented here with proper backend support');
}
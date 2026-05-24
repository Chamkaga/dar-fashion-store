document.querySelectorAll('form').forEach((form) => {
    form.addEventListener('submit', (event) => {
        const invalid = form.querySelector(':invalid');

        if (invalid) {
            invalid.focus();
            event.preventDefault();
        }
    });
});

// Initialize Bootstrap popovers
if (window.bootstrap) {
    const popoverTriggerList = Array.from(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.forEach((popoverTriggerEl) => new bootstrap.Popover(popoverTriggerEl, { html: true }));
}

// Theme toggle
const themeToggle = document.getElementById('theme-toggle');
const themeIcon = themeToggle?.querySelector('i');

const setTheme = (theme) => {
    document.documentElement.dataset.bsTheme = theme;
    localStorage.setItem('theme', theme);
    if (themeIcon) {
        themeIcon.className = theme === 'light' ? 'bi bi-sun' : 'bi bi-moon';
    }
};

const storedTheme = localStorage.getItem('theme') || 'light';
setTheme(storedTheme);

themeToggle?.addEventListener('click', () => {
    const newTheme = document.documentElement.dataset.bsTheme === 'light' ? 'dark' : 'light';
    setTheme(newTheme);
});

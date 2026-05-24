const menuButton = document.querySelector('[data-menu-toggle]');
const categoryMenu = document.querySelector('#category-menu');

if (menuButton && categoryMenu) {
    menuButton.addEventListener('click', () => {
        const isOpen = categoryMenu.classList.toggle('is-open');
        menuButton.setAttribute('aria-expanded', String(isOpen));
    });
}

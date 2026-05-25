const menuButton = document.querySelector('[data-menu-toggle]');
const categoryMenu = document.querySelector('#category-menu');

if (menuButton && categoryMenu) {
    menuButton.addEventListener('click', () => {
        const isOpen = categoryMenu.classList.toggle('is-open');
        menuButton.setAttribute('aria-expanded', String(isOpen));
    });
}

document.querySelectorAll('[data-style-slider]').forEach((slider) => {
    const slides = Array.from(slider.querySelectorAll('.hero-panel__image'));
    const prevButton = slider.querySelector('[data-slider-prev]');
    const nextButton = slider.querySelector('[data-slider-next]');
    let activeIndex = slides.findIndex((slide) => slide.classList.contains('is-active'));
    let timer;

    if (slides.length < 2) {
        return;
    }

    if (activeIndex < 0) {
        activeIndex = 0;
        slides[activeIndex].classList.add('is-active');
    }

    const showSlide = (index) => {
        slides[activeIndex].classList.remove('is-active');
        activeIndex = (index + slides.length) % slides.length;
        slides[activeIndex].classList.add('is-active');
    };

    const restartTimer = () => {
        window.clearInterval(timer);
        timer = window.setInterval(() => showSlide(activeIndex + 1), 4500);
    };

    prevButton?.addEventListener('click', () => {
        showSlide(activeIndex - 1);
        restartTimer();
    });

    nextButton?.addEventListener('click', () => {
        showSlide(activeIndex + 1);
        restartTimer();
    });

    restartTimer();
});

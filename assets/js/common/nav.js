/**
 * Mobile burger menu.
 */
export default function initNav() {
    const burger = document.querySelector('.burger');
    const links = document.getElementById('nav-links');

    if (burger && links) {
        const toggle = (open) => {
            burger.setAttribute('aria-expanded', String(open));
            links.classList.toggle('is-open', open);
        };

        burger.addEventListener('click', () => toggle(burger.getAttribute('aria-expanded') !== 'true'));
        links.addEventListener('click', (event) => {
            if (event.target.closest('a')) {
                toggle(false);
            }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && links.classList.contains('is-open')) {
                toggle(false);
                burger.focus();
            }
        });
    }
}

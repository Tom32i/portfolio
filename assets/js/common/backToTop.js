/**
 * Fixed "back to top" button: visible once the page header is out of view,
 * hidden on mobile while the full-width contact button is on screen (it would cover it).
 */
export default function initBackToTop() {
    const button = document.querySelector('.back-to-top');
    const header = document.querySelector('.hero, .page-header');

    if (!button || !header) {
        return;
    }

    const mobile = window.matchMedia('(max-width: 900px)');
    const still = window.matchMedia('(prefers-reduced-motion: reduce)');
    const contact = document.querySelector('.email');
    let headerVisible = true;
    let contactVisible = false;

    const update = () => {
        const visible = !headerVisible && !(mobile.matches && contactVisible);

        button.classList.toggle('is-visible', visible);
        button.setAttribute('aria-hidden', String(!visible));
        button.tabIndex = visible ? 0 : -1;

        if (!visible && document.activeElement === button) {
            button.blur();
        }
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(({ target, isIntersecting }) => {
            if (target === header) {
                headerVisible = isIntersecting;
            } else {
                contactVisible = isIntersecting;
            }
        });
        update();
    });

    observer.observe(header);

    if (contact) {
        observer.observe(contact);
    }

    mobile.addEventListener('change', update);

    button.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: still.matches ? 'auto' : 'smooth' });

        // Keyboard users land back on the page heading.
        const heading = document.querySelector('h1');

        if (heading) {
            heading.setAttribute('tabindex', '-1');
            heading.focus({ preventScroll: true });
        }
    });
}

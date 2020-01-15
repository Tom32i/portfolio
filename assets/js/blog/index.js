import 'prismjs';
import Code from './Code';

/**
 * Load codes
 */
function loadCodes() {
    const inputs = Array.from(document.getElementsByClassName('input'));

    inputs.forEach(element => {
        const [input, ...outputs] = element.parentNode.getElementsByTagName('code');

        if (input) {
            new Code(input, outputs);
        }
    });
}

/**
 * Load slides
 */
function loadSlides() {
    const slides = Array.from(document.getElementsByTagName('iframe'));

    slides.forEach(element => {
        const url = element.getAttribute('data-url');

        if (url) {
            element.setAttribute('src', url);
        }
    });
}

// Loading
window.addEventListener('load', loadCodes);
window.addEventListener('load', loadSlides);

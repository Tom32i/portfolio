import 'prismjs';
import Code from './Code';
import initNav from '../common/nav';

initNav();

Prism.manual = true;

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

// Loading

window.addEventListener('load', loadCodes);

import 'prismjs';
import Code from './Code';

/**
 * load codes
 */
function loadCodes() {
    const inputs = Array.from(document.getElementsByClassName('input'));
    console.log('loadCodes', inputs);
    inputs.forEach(element => {
        const [input, ...outputs] = element.parentNode.getElementsByTagName('code');

        if (input) {
            new Code(input, outputs);
        }
    });
}

// Loading

window.addEventListener('load', loadCodes);

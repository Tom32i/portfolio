import Scroller from './Scroller';

/**
 * Load Scroller
 */
function loadScoller() {
    new Scroller(document.getElementsByTagName('section'));
}

// Loading
window.addEventListener('load', loadScoller);

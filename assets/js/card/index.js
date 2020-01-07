import Card from './Card';

/**
 * Load card
 */
function loadCard() {
    new Card(document.getElementById('card'));
}

// Loading
window.addEventListener('load', loadCard);


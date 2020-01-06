import Card from './Card';

console.log("dard");

/**
 * Load card
 */
function loadCard() {
    console.log('loadCard');
    new Card(document.getElementById('card'));
}

// Loading
window.addEventListener('load', loadCard);


import Card from './Card';
import Cover from './Cover';

/**
 * Load card
 */
function loadCard() {
    const cover = new Cover(document.body, 4);
    console.log(window.innerWidth);
    if (window.innerWidth > 767) {
        const card = new Card(document.getElementById('card'), cover.onFlip);
    }
}

// Loading
window.addEventListener('load', loadCard);


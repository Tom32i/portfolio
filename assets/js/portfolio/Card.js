import { easeInOutCubic } from './easing';

export default class Card {
    static get angle() { return 30; }
    static get duration() { return 300; }
    static get zone() { return 1440; }

    constructor(element, onFlip) {
        this.element = element;
        this.onFlip = onFlip;
        this.active = window.innerWidth > 767;
        this.loop = null;
        this.transformation = '';
        this.flipped = false;
        this.flippedAt = Date.now();
        this.flippFrom = 0;
        this.flippedDistance = 0;
        this.flipping = false;
        this.angle = 0;
        this.x = 0;
        this.y = 0;
        this.alpha = null;
        this.beta = null;
        this.gamma = null;
        this.direction = true;
        this.orientationListener = false;

        this.onMouseMove = this.onMouseMove.bind(this);
        this.onDeviceOrientation = this.onDeviceOrientation.bind(this);
        this.onClick = this.onClick.bind(this);
        this.onTouch = this.onTouch.bind(this);
        this.onResize = this.onResize.bind(this);
        this.enableOrientation = this.enableOrientation.bind(this);
        this.update = this.update.bind(this);
        this.flip = this.flip.bind(this);

        window.addEventListener('resize', this.onResize);
        document.addEventListener('mousemove', this.onMouseMove);
        document.addEventListener('click', this.onClick);

        if (typeof DeviceMotionEvent !== 'undefined') {
            try {
                this.enableOrientation();
            } catch (error) {
                document.addEventListener('click', this.onTouch);
            }
        }

        this.start();

        if (this.active) {
            this.element.addEventListener('animationend', this.flip);
        }

        document.body.classList.add('turn-right');
    }

    setActive(active) {
        this.active = active;
    }

    onClick(event) {
        if (event.target.tagName === 'A') {
            return;
        }

        this.flip(true);
    }

    onTouch() {
        document.removeEventListener('click', this.onTouch);

        if (typeof DeviceOrientationEvent.requestPermission === 'function') {
            DeviceMotionEvent.requestPermission().then(this.enableOrientation);
        } else {
            this.enableOrientation();
        }
    }

    onMouseMove(event) {
        if (!this.active) {
            return;
        }

        const { angle, zone } = this.constructor;
        const { innerWidth, innerHeight } = window;
        const width = Math.min(innerWidth, zone);
        const height = Math.min(innerHeight, zone);

        this.x = Math.min(Math.max(event.clientX - (innerWidth / 2), -width / 2), width / 2) / width * angle;
        this.y = Math.min(Math.max(event.clientY - (innerHeight / 2), -height / 2), height / 2) / height * angle;

        this.setDirection(this.x > 0);
    }

    onResize() {
        this.setActive(window.innerWidth > 767);
    }

    onDeviceOrientation(event) {
        const { angle } = this.constructor;
        const { absolute, alpha, beta, gamma } = event;

        if (this.alpha === null) {
            this.alpha = alpha;
            this.beta = beta;
            this.gamma = gamma;
        }

        this.x = Math.min(Math.max((this.gamma - gamma) / 2, -angle / 2), angle / 2);
        this.y = Math.min(Math.max((this.beta - beta) / 2, -angle / 2), angle / 2);
    }

    enableOrientation() {
        if (!this.orientationListener) {
            window.addEventListener('deviceorientation', this.onDeviceOrientation);
            this.orientationListener = true;
        }
    }

    /**
     * Set direction
     *
     * @param {Boolean} direction
     */
    setDirection(direction) {
        if (this.direction === direction) {
            return;
        }

        this.direction = direction;

        document.body.classList.replace(
            this.direction ? 'turn-left' : 'turn-right',
            this.direction ? 'turn-right' : 'turn-left'
        );
    }

    flip(changeCover = false) {
        if (!this.active) {
            return this.onFlip(this.direction);
        }

        const destination = (this.flipped ? 180 : 0) + (this.direction ? 180 : -180);

        this.flipped = !this.flipped;
        this.flippedAt = Date.now();
        this.flippedFrom = this.angle;
        this.flippedDistance = destination - this.angle;
        this.flipping = true;

        if (changeCover === true && !this.flipped) {
            this.onFlip(this.direction);
        }
    }

    render() {
        const X = (-this.y).toFixed(3);
        const Y = (this.x + (this.active ? this.angle : 0)).toFixed(3);

        this.element.style.transform = `rotateX(${X}deg) rotateY(${Y}deg)`;
    }

    updateYAngle() {
        if (!this.flipping) {
            return this.angle;
        }

        const progress = easeInOutCubic((Date.now() - this.flippedAt) / this.constructor.duration);

        this.angle = this.flippedFrom + progress * this.flippedDistance;

        if (progress >= 1) {
            this.flipping = false;
            this.angle = this.flipped ? 180 : 0;
        }
    }

    start() {
        if (!this.loop) {
            this.update();
        }
    }

    stop() {
        if (this.loop) {
            cancelAnimationFrame(this.loop);
            this.loop = null;
        }
    }

    update() {
        this.loop = requestAnimationFrame(this.update);

        this.updateYAngle();
        this.render();
    }
}

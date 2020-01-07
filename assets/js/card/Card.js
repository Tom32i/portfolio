export default class Card {
    static get angle() { return 80; }
    static get duration() { return 300; }

    constructor(element) {
        this.element = element;
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

        this.onMouseMove = this.onMouseMove.bind(this);
        this.onDeviceOrientation = this.onDeviceOrientation.bind(this);
        this.onClick = this.onClick.bind(this);
        this.onTouch = this.onTouch.bind(this);
        this.enableOrientation = this.enableOrientation.bind(this);
        this.update = this.update.bind(this);
        this.flip = this.flip.bind(this);


        document.addEventListener('mousemove', this.onMouseMove);
        document.addEventListener('click', this.onClick);

        if (typeof DeviceMotionEvent !== 'undefined') {
            document.addEventListener('click', this.onTouch);
        }

        this.start();

        //setTimeout(this.flip, 300);
        this.element.addEventListener('animationend', this.flip);
    }

    onClick(event) {
        if (event.target.tagName === 'A') {
            return;
        }

        this.flip();
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
        const { angle } = this.constructor;

        this.x = ((event.clientX / window.innerWidth) - 0.5) * angle;
        this.y = ((event.clientY / window.innerHeight) - 0.5) * angle;
    }

    onDeviceOrientation(event) {
        const { absolute, alpha, beta, gamma } = event;

        if (this.alpha === null) {
            this.alpha = alpha;
            this.beta = beta;
            this.gamma = gamma;
        }

        this.x = this.gamma - gamma;
        this.y = this.beta - beta;
    }

    enableOrientation() {
        window.addEventListener('deviceorientation', this.onDeviceOrientation);
    }

    flip() {
        this.flipped = !this.flipped;
        this.flippedAt = Date.now();
        this.flippedFrom = this.angle;
        const destination = this.flipped ? 180 : (this.angle < 180 ? 0 : 360);
        this.flippedDistance = destination - this.angle;
        this.flipping = true;
    }

    render() {
        const X = (-this.y).toFixed(3);
        const Y = (this.x + this.angle).toFixed(3);

        this.element.style.transform = `rotateX(${X}deg) rotateY(${Y}deg)`;
    }

    updateYAngle() {
        if (!this.flipping) {
            return this.angle;
        }

        const progress = (Date.now() - this.flippedAt) / this.constructor.duration;

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

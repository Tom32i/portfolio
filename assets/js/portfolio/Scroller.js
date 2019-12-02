export default class Scroller {
    constructor(sections) {
        this.sections = Array.from(sections);
        this.request = null;
        this.shouldStop = false;
        this.y = window.scrollY;
        this.height = window.innerHeight;

        this.update = this.update.bind(this);
        this.onResize = this.onResize.bind(this);
        this.onScroll = this.onScroll.bind(this);

        window.addEventListener('resize', this.onResize);
        window.addEventListener('scroll', this.onScroll);

        this.start();
    }

    onResize(event) {
        this.height = window.innerHeight;
        this.start();
    }

    onScroll(event) {
        this.y = window.scrollY;
        this.start();
    }

    start() {
        if (!this.request) {
            this.request = requestAnimationFrame(this.update);
        }
    }

    stop() {
        if (this.request) {
            cancelAnimationFrame(this.request);
            this.request = null;
        }
    }

    update() {
        this.request = requestAnimationFrame(this.update);

        const bottom = this.y + this.height;

        if (this.bottom !== bottom) {
            this.sections.forEach(element => {
                const y = element.clientHeight + element.offsetTop;
                element.classList.toggle('active', y < bottom);
            });
            this.bottom = bottom;
        } else {
            this.stop();
        }
    }
}

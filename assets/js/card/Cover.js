export default class Cover {
    constructor(element, length) {
        this.element = element;
        this.length = length;
        this.covers = this.constructor.getCovers(this.length);
        this.max = this.length - 1;
        this.index = 0;

        this.onFlip = this.onFlip.bind(this);

        this.element.classList.add(this.getClassName());
    }

    /**
     * Get covers
     *
     * @param {Number} length
     *
     * @return {Array}
     */
    static getCovers(length) {
        const values = new Array(length).fill(null).map((value, index) => index);

        values.forEach((value, index) => {
            const random = Math.floor(Math.random() * length);

            values[index] = values[random];
            values[random] = value;
        });

        return values;
    }

    /**
     * On card flip
     */
    onFlip() {
        this.setIndex(this.index >= this.max ? 0 : this.index + 1);
    }

    /**
     * Set current index
     *
     * @param {Number} index
     */
    setIndex(index) {
        const prevClass = this.getClassName();
        const nextClass = this.getClassName(index);

        this.index = index;

        this.element.classList.replace(prevClass, nextClass);
    }

    /**
     * Get class name
     *
     * @param {Number} index
     *
     * @return {String}
     */
    getClassName(index = this.index) {
        return `card-${this.covers[index]}`;
    }
}

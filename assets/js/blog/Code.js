/**
 * Code block
 */
export default class Code {
    constructor(input, outputs) {
        this.parent = input.parentNode;
        this.input = input;
        this.outputs = outputs;
        this.error = this.createOutput('error');

        this.show = this.show.bind(this);
        this.hide = this.hide.bind(this);

        this.exec();
    }

    get keys() {
        return this.outputs.map(output => output.getAttribute('data-output'));
    }

    highlight(content) {
        return Prism.highlight(content, Prism.languages.javascript, 'javascript');
    }

    exec() {
        let values;

        try {
            values = this.eval(this.input.innerText);
        } catch (error) {
            return this.setError(error);
        }

        this.setContent(values);
    }

    eval(code) {
        const callable = `(() => { \n ${code} \n return { ${this.keys.join(', ')} }; \n})();`;

        return eval(callable);
    }

    setContent(values) {
        this.outputs.forEach(output => {
            const key = output.getAttribute('data-output');
            output.innerHTML = this.highlight(JSON.stringify(values[key]));
        });

        this.outputs.forEach(element => element.className += ' success');
        this.outputs.forEach(this.show);
        this.hide(this.error);
    }

    setError(error) {
        this.error.innerText = error.toString();
        this.show(this.error);
        this.outputs.forEach(this.hide);
    }

    createOutput(className) {
        const code = document.createElement('code');

        code.className = className;
        this.parent.appendChild(code);

        return code;
    }

    show(element) {
        element.style.display = undefined;
    }

    hide(element) {
        element.style.display = 'none';
    }
}

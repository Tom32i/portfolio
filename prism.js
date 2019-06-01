const Prism = require('prismjs');

const [language, code] = process.argv.slice(2);
//argsconst code = "const data = 1;";
const html = Prism.highlight(code, Prism.languages[language], language);

process.stdout.end(html);

const sharp = require('sharp');
const glob = require('glob');

const [path] = process.argv.slice(2);

function files(input, callback) {
  const { src, ...options } = input;

  return glob(src, options, (error, files) => files.forEach(callback));
}

function optimize(file, options = {}) {
  const { resize, ...params } = options;
  const ext = file.split('.').pop();
  let img = sharp(file);

  if (resize) {
    img = img.resize(...resize);
  }

  switch (ext) {
    case 'jpeg':
    case 'jpg':
      img = img.jpeg({ ...params });
      break;

    case 'png':
      img = img.png({ compressionLevel: 9, ...params });
      break;

    case 'gif':
      img = img.gif({ ...params });
      break;

    default:
      throw new Error(`Unsupported image type "${file}".`);
  }

  return img.toBuffer().then(buffer => sharp(buffer).toFile(file));
}

const fullWidth = `${path}/img/@(articles|project|talk)/**/*.@(png|jpg|jpeg|gif)`;

files({ src: fullWidth }, file => optimize(file, { resize: [640] }));
files({ src: `${path}/**/*.(png|jpg|jpeg)`, ignore: fullWidth }, optimize);

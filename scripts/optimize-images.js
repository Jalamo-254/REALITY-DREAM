const imageminImport = require('imagemin');
const imagemin = imageminImport.default || imageminImport;
const mozjpegImport = require('imagemin-mozjpeg');
const pngquantImport = require('imagemin-pngquant');
const webpImport = require('imagemin-webp');
const mozjpeg = mozjpegImport.default || mozjpegImport;
const pngquant = pngquantImport.default || pngquantImport;
const webp = webpImport.default || webpImport;
// svgo plugin omitted due to compatibility; focus on JPEG/PNG/WebP optimization
const path = require('path');

(async () => {
  try {
    const srcGlob = 'Assets/images/*.{jpg,jpeg,png,svg}';
    const outDir = 'Assets/images/optimized';

    console.log('Optimizing images from', srcGlob, '->', outDir);

    await imagemin([srcGlob], {
      destination: outDir,
      plugins: [
        // optimize JPEGs; skip pngquant due to platform binary issues
        mozjpeg({quality: 75})
      ]
    });

    // Also create WebP versions
    await imagemin(['Assets/images/*.{jpg,jpeg,png}'], {
      destination: outDir,
      plugins: [
        webp({quality: 75})
      ]
    });

    console.log('Image optimization complete. Optimized files in', path.resolve(outDir));
  } catch (err) {
    console.error('Image optimization failed:', err);
    process.exit(1);
  }
})();

/**
 * Client-Side Automatic Image to WebP Converter & Compressor
 * Automatically resizes large camera photos and converts to WebP before upload.
 */
export async function convertImageFileToWebp(file, maxWidth = 1920, maxHeight = 1920, quality = 0.82) {
  if (!file) return file;

  const isImageMime = file.type && file.type.startsWith('image/');
  const isImageExt = /\.(jpe?g|png|webp|bmp|gif)$/i.test(file.name || '');

  // If not a standard convertible web image (or if PDF / HEIC), return original file safely
  if (!isImageMime && !isImageExt) {
    return file;
  }

  // If already WebP and size is small (< 1MB), keep as is
  if (file.type === 'image/webp' && file.size < 1024 * 1024) {
    return file;
  }

  return new Promise((resolve) => {
    try {
      const reader = new FileReader();
      reader.onload = (e) => {
        try {
          const img = new Image();
          img.onload = () => {
            try {
              let width = img.width;
              let height = img.height;

              // Calculate aspect ratio
              if (width > maxWidth || height > maxHeight) {
                const ratio = Math.min(maxWidth / width, maxHeight / height);
                width = Math.round(width * ratio);
                height = Math.round(height * ratio);
              }

              const canvas = document.createElement('canvas');
              canvas.width = width;
              canvas.height = height;

              const ctx = canvas.getContext('2d');
              ctx.drawImage(img, 0, 0, width, height);

              // Check if browser supports WebP canvas export
              canvas.toBlob(
                (blob) => {
                  if (blob && blob.size > 0) {
                    const originalName = file.name.replace(/\.[^/.]+$/, '');
                    const newFileName = `${originalName}.webp`;
                    const webpFile = new File([blob], newFileName, {
                      type: 'image/webp',
                      lastModified: Date.now(),
                    });
                    resolve(webpFile);
                  } else {
                    resolve(file);
                  }
                },
                'image/webp',
                quality
              );
            } catch (err) {
              resolve(file);
            }
          };

          img.onerror = () => resolve(file);
          img.src = e.target.result;
        } catch (err) {
          resolve(file);
        }
      };

      reader.onerror = () => resolve(file);
      reader.readAsDataURL(file);
    } catch (err) {
      resolve(file);
    }
  });
}

/**
 * Client-Side Automatic Image to WebP Converter & Compressor
 * Automatically resizes large camera photos and converts to WebP before upload.
 */
export async function convertImageFileToWebp(file, maxWidth = 1920, maxHeight = 1920, quality = 0.82) {
  // If not an image or if PDF, return original file unchanged
  if (!file || !file.type.startsWith('image/')) {
    return file;
  }

  return new Promise((resolve) => {
    const reader = new FileReader();
    reader.onload = (e) => {
      const img = new Image();
      img.onload = () => {
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
            if (blob) {
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
      };

      img.onerror = () => resolve(file);
      img.src = e.target.result;
    };

    reader.onerror = () => resolve(file);
    reader.readAsDataURL(file);
  });
}

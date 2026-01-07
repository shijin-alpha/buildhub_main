# Project Images Directory

This directory is for storing construction project images for the BuildHub website.

## Recommended Image Sizes
- **Residential Projects**: 400x300px or 800x600px
- **Commercial Buildings**: 400x300px or 800x600px  
- **Industrial Facilities**: 400x300px or 800x600px
- **Renovation Projects**: 400x300px or 800x600px

## Image Naming Convention
- `residential-1.jpg`, `residential-2.jpg`, etc.
- `commercial-1.jpg`, `commercial-2.jpg`, etc.
- `industrial-1.jpg`, `industrial-2.jpg`, etc.
- `renovation-1.jpg`, `renovation-2.jpg`, etc.

## How to Add Images
1. Place your construction project images in this directory
2. Update the image paths in `frontend/src/App.jsx` in the projects gallery section
3. Replace the current image URLs with your new image paths

## Current Image Paths in Code
- Residential: `/images/buildhub_image.jpg`
- Commercial: `/uploads/portfolios/689f066e47a60_port.jpg`
- Industrial: `/uploads/portfolios/689f36ce6e7ef_port.jpg`
- Renovation: `/uploads/avatars/27_03ea003b6aa9.png`

## Example Usage
```jsx
<div className="project-image residential" style={{backgroundImage: "url('/images/projects/residential-1.jpg')"}}>
```

## Image Optimization Tips
- Use WebP format for better compression
- Compress images to reduce file size
- Ensure images are high quality but optimized for web
- Consider using different images for mobile vs desktop


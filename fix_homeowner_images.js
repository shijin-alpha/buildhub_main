// Quick fix script to replace img tags with SafeImage in HomeownerDashboard
const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'frontend/src/components/HomeownerDashboard.jsx');

try {
  let content = fs.readFileSync(filePath, 'utf8');
  
  // Replace concept preview background images that might be causing black screens
  content = content.replace(
    /background: preview\.status === 'completed' && preview\.image_url \? `url\(\$\{preview\.image_url\}\)` : '#f3f4f6'/g,
    `background: preview.status === 'completed' && preview.image_url ? \`url(\${preview.image_url})\` : '#f3f4f6',
                    backgroundSize: 'cover',
                    backgroundPosition: 'center',
                    backgroundRepeat: 'no-repeat'`
  );
  
  // Add error handling for concept preview images
  content = content.replace(
    /style=\{\{ \s*height: '180px', \s*background: preview\.status === 'completed' && preview\.image_url \? `url\(\$\{preview\.image_url\}\)` : '#f3f4f6',\s*backgroundSize: 'cover',\s*backgroundPosition: 'center',\s*backgroundRepeat: 'no-repeat'\s*\}\}/g,
    `style={{ 
                    height: '180px', 
                    background: preview.status === 'completed' && preview.image_url ? \`url(\${preview.image_url})\` : '#f3f4f6',
                    backgroundSize: 'cover',
                    backgroundPosition: 'center',
                    backgroundRepeat: 'no-repeat',
                    backgroundColor: '#f3f4f6'
                  }}
                  onError={(e) => {
                    e.target.style.background = '#f3f4f6';
                    e.target.style.backgroundImage = 'none';
                  }}`
  );
  
  // Fix layout library images
  content = content.replace(
    /<img\s+src=\{layout\.image_url \|\| '\/images\/default-layout\.jpg'\}/g,
    `<SafeImage 
          src={layout.image_url || '/images/default-layout.jpg'}`
  );
  
  // Fix preview layout images
  content = content.replace(
    /<img src=\{previewLayout\.image_url\} alt="Preview"/g,
    `<SafeImage src={previewLayout.image_url} alt="Preview" fallbackText="Preview not available"`
  );
  
  // Fix design file images
  content = content.replace(
    /<img src=\{previewLayout\.design_file_url\} alt="Layout"/g,
    `<SafeImage src={previewLayout.design_file_url} alt="Layout" fallbackText="Layout not available"`
  );
  
  // Fix technical details modal images
  content = content.replace(
    /<img\s+src=\{technicalDetailsModal\.image_url\}/g,
    `<SafeImage src={technicalDetailsModal.image_url} fallbackText="Technical details image not available"`
  );
  
  fs.writeFileSync(filePath, content, 'utf8');
  console.log('✅ HomeownerDashboard image fixes applied successfully!');
  
} catch (error) {
  console.error('❌ Error applying fixes:', error);
}
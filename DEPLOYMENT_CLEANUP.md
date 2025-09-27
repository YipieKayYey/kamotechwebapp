# 🗑️ Files/Folders to Delete Before Hostinger Upload

## ⚠️ **IMPORTANT:** Keep `vendor/` folder - Hostinger has Composer issues

### 📁 **Delete These Folders:**
```
node_modules/
.git/
storage/app/public/
storage/framework/cache/
storage/framework/sessions/
storage/framework/testing/
storage/framework/views/
storage/logs/
bootstrap/cache/
```

### 📄 **Delete These Files:**
```
.env
.env.example
.env.local
.env.production
.env.staging
composer.json
composer.lock
package.json
package-lock.json
yarn.lock
phpunit.xml
phpunit.xml.dist
.eslintrc.js
.eslintrc.json
eslint.config.js
tsconfig.json
vite.config.ts
.gitignore
.gitattributes
README.md
DEPLOYMENT_CLEANUP.md
```

### 📁 **Keep These Important Folders:**
```
✅ vendor/ (PHP dependencies)
✅ app/
✅ config/
✅ database/
✅ public/ (including public/build/)
✅ resources/views/
✅ routes/
✅ storage/ (empty structure - Hostinger will recreate)
✅ bootstrap/ (except bootstrap/cache/)
```

### 📄 **Keep These Important Files:**
```
✅ .htaccess
✅ artisan
✅ composer.phar (if exists)
✅ index.php
✅ All PHP files in root
```

## 🚀 **Quick Cleanup Commands (Optional):**
```bash
# Delete node_modules
rm -rf node_modules/

# Delete development files
rm -f .env package*.json composer.json composer.lock
rm -f eslint.config.js tsconfig.json vite.config.ts
rm -f phpunit.xml README.md

# Clear storage cache (keep structure)
rm -rf storage/framework/cache/*
rm -rf storage/framework/sessions/*
rm -rf storage/framework/views/*
rm -rf bootstrap/cache/*
```

## 📋 **Upload Checklist:**
- [ ] Delete all files/folders listed above
- [ ] Keep vendor/ folder intact
- [ ] Keep public/build/ folder (contains your map assets)
- [ ] Upload remaining files to Hostinger
- [ ] Set proper permissions on storage/ and bootstrap/cache/

## ⚡ **Result:**
This will significantly reduce your upload time by removing development dependencies and cache files while keeping all production-essential code and the compiled assets for your interactive map!

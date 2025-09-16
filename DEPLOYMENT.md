# WordPress Deployment Guide

This guide shows you how to deploy your WordPress website for testing on GitHub and other platforms.

## 🚀 Option 1: Static Site on GitHub Pages (Recommended for Testing)

### What You Get:
- ✅ Free hosting on GitHub Pages
- ✅ Custom domain support
- ✅ Automatic HTTPS
- ✅ Fast global CDN
- ✅ Version control integration
- ❌ No dynamic features (no forms, comments, admin panel)

### Setup Steps:

1. **Push your code to GitHub:**
   ```bash
   git init
   git add .
   git commit -m "Initial WordPress project"
   git branch -M main
   git remote add origin https://github.com/yourusername/your-repo-name.git
   git push -u origin main
   ```

2. **Enable GitHub Pages:**
   - Go to your repository on GitHub
   - Click "Settings" → "Pages"
   - Select "GitHub Actions" as source
   - The workflow will automatically deploy your site

3. **Generate static site locally (optional):**
   ```bash
   php static-site-generator.php
   ```

4. **Your site will be available at:**
   `https://yourusername.github.io/your-repo-name`

---

## 🌐 Option 2: Full WordPress on Free Hosting

### Recommended Free Hosting Providers:

#### 1. **InfinityFree** (Recommended)
- ✅ Free PHP hosting
- ✅ MySQL database
- ✅ WordPress support
- ✅ No ads on your site
- 🔗 https://infinityfree.net/

#### 2. **000WebHost**
- ✅ Free hosting with PHP/MySQL
- ✅ WordPress installer
- ✅ Custom domain support
- 🔗 https://www.000webhost.com/

#### 3. **Freehostia**
- ✅ Free hosting plan
- ✅ PHP and MySQL
- ✅ WordPress support
- 🔗 https://www.freehostia.com/

### Deployment Steps for Free Hosting:

1. **Prepare your files:**
   ```bash
   # Create deployment package
   zip -r wordpress-site.zip wp-content/ docker-compose.yml README.md
   ```

2. **Upload to hosting provider:**
   - Download WordPress from wordpress.org
   - Upload your custom theme to `wp-content/themes/`
   - Configure database settings
   - Run WordPress installation

---

## 🔧 Option 3: GitHub Actions + External Hosting

### Deploy to Vercel, Netlify, or Railway:

#### Vercel Deployment:
```yaml
# .github/workflows/deploy-vercel.yml
name: Deploy to Vercel
on:
  push:
    branches: [main]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: amondnet/vercel-action@v20
        with:
          vercel-token: ${{ secrets.VERCEL_TOKEN }}
          vercel-org-id: ${{ secrets.ORG_ID }}
          vercel-project-id: ${{ secrets.PROJECT_ID }}
```

#### Netlify Deployment:
```yaml
# .github/workflows/deploy-netlify.yml
name: Deploy to Netlify
on:
  push:
    branches: [main]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: nwtgck/actions-netlify@v1.2
        with:
          publish-dir: './static-site'
          production-branch: main
          github-token: ${{ secrets.GITHUB_TOKEN }}
          deploy-message: "Deploy from GitHub Actions"
```

---

## 📱 Option 4: Local Development + GitHub

### For Development and Testing:

1. **Keep your development environment:**
   ```bash
   docker-compose up -d
   ```

2. **Use GitHub for version control:**
   ```bash
   git add .
   git commit -m "Update theme"
   git push origin main
   ```

3. **Deploy when ready:**
   - Use any of the options above
   - Or push to a hosting provider

---

## 🛠 Quick Commands

### Generate Static Site:
```bash
php static-site-generator.php
```

### Test Locally:
```bash
# Start WordPress
docker-compose up -d

# Generate static version
php static-site-generator.php

# Serve static files (Python)
cd static-site
python -m http.server 8000
```

### Deploy to GitHub Pages:
```bash
git add .
git commit -m "Update static site"
git push origin main
# GitHub Actions will automatically deploy
```

---

## 🎯 Recommendations

### For Testing/Portfolio:
- **Use GitHub Pages** with static site generation
- Perfect for showcasing your theme design
- Free and reliable

### For Full WordPress Features:
- **Use InfinityFree** or similar free hosting
- Upload your theme and configure WordPress
- Full dynamic functionality

### For Production:
- **Use paid hosting** like SiteGround, WP Engine, or DigitalOcean
- Better performance and support
- Professional features

---

## 🔗 Useful Links

- [GitHub Pages Documentation](https://docs.github.com/en/pages)
- [WordPress.org](https://wordpress.org/)
- [InfinityFree](https://infinityfree.net/)
- [Vercel](https://vercel.com/)
- [Netlify](https://www.netlify.com/)

---

## ❓ Need Help?

1. Check the GitHub Actions logs if deployment fails
2. Verify your repository settings
3. Make sure all file paths are correct
4. Test locally before deploying

Happy deploying! 🚀
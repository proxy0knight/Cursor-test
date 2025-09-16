<?php
/**
 * Static Site Generator for WordPress
 * This script generates static HTML files from your WordPress theme
 */

// Configuration
$output_dir = 'static-site';
$base_url = 'https://yourusername.github.io/your-repo-name'; // Update this!

// Create output directory
if (!is_dir($output_dir)) {
    mkdir($output_dir, 0755, true);
}

// Copy theme files
function copyThemeFiles($source, $dest) {
    if (is_dir($source)) {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        $files = scandir($source);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                if (is_dir($source . '/' . $file)) {
                    copyThemeFiles($source . '/' . $file, $dest . '/' . $file);
                } else {
                    copy($source . '/' . $file, $dest . '/' . $file);
                }
            }
        }
    }
}

// Generate static HTML pages
function generateStaticPage($title, $content, $filename) {
    global $output_dir;
    
    $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="site">
        <header class="site-header">
            <div class="container">
                <div class="header-content">
                    <div class="site-branding">
                        <h1 class="site-title">
                            <a href="index.html">My WordPress Site</a>
                        </h1>
                        <p class="site-description">A static version of your WordPress site</p>
                    </div>
                    <nav class="main-navigation">
                        <ul>
                            <li><a href="index.html">Home</a></li>
                            <li><a href="about.html">About</a></li>
                            <li><a href="contact.html">Contact</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </header>
        
        <main class="site-main">
            <div class="container">
                <article class="post">
                    <header class="post-header">
                        <h1 class="post-title">' . htmlspecialchars($title) . '</h1>
                    </header>
                    <div class="post-content">
                        ' . $content . '
                    </div>
                </article>
            </div>
        </main>
        
        <footer class="site-footer">
            <div class="container">
                <div class="site-info">
                    <p>&copy; ' . date('Y') . ' My WordPress Site. All rights reserved.</p>
                    <p>Powered by <a href="https://wordpress.org/" target="_blank" rel="noopener">WordPress</a></p>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>';

    file_put_contents($output_dir . '/' . $filename, $html);
    echo "Generated: $filename\n";
}

// Sample content for static pages
$pages = [
    [
        'title' => 'Welcome to My WordPress Site',
        'content' => '
            <p>This is a static version of your WordPress website hosted on GitHub Pages!</p>
            <p>While this doesn\'t have all the dynamic features of WordPress, it\'s perfect for:</p>
            <ul>
                <li>Testing your theme design</li>
                <li>Showcasing your work</li>
                <li>Creating a portfolio site</li>
                <li>Hosting documentation</li>
            </ul>
            <p>To update this site, simply push changes to your GitHub repository and the GitHub Action will automatically rebuild and deploy it.</p>
        ',
        'filename' => 'index.html'
    ],
    [
        'title' => 'About Us',
        'content' => '
            <p>This is the About page of your static WordPress site.</p>
            <p>You can customize this content by editing the static-site-generator.php file and running it locally.</p>
            <p>The static site generator creates HTML files that can be hosted anywhere, including GitHub Pages.</p>
        ',
        'filename' => 'about.html'
    ],
    [
        'title' => 'Contact',
        'content' => '
            <p>Get in touch with us!</p>
            <p>Since this is a static site, you can\'t use WordPress forms, but you can:</p>
            <ul>
                <li>Add contact forms using services like Formspree or Netlify Forms</li>
                <li>Include your email address</li>
                <li>Add social media links</li>
                <li>Embed contact widgets</li>
            </ul>
        ',
        'filename' => 'contact.html'
    ]
];

// Generate all pages
foreach ($pages as $page) {
    generateStaticPage($page['title'], $page['content'], $page['filename']);
}

// Copy CSS file
copy('wp-content/themes/custom-theme/style.css', $output_dir . '/style.css');
echo "Copied: style.css\n";

// Create a simple README for the static site
$readme = '# Static WordPress Site

This is a static version of your WordPress site, generated for GitHub Pages hosting.

## Features
- Responsive design
- Clean, modern layout
- Fast loading
- SEO-friendly

## How to Update
1. Edit the content in `static-site-generator.php`
2. Run: `php static-site-generator.php`
3. Commit and push changes to GitHub
4. GitHub Actions will automatically deploy the updated site

## Live Site
Visit: ' . $base_url . '
';

file_put_contents($output_dir . '/README.md', $readme);
echo "Generated: README.md\n";

echo "\n✅ Static site generated successfully!\n";
echo "📁 Files created in: $output_dir/\n";
echo "🚀 Ready to deploy to GitHub Pages!\n";
?>
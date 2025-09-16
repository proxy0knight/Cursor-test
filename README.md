# WordPress Website Project

A modern WordPress development setup using Docker for easy local development and deployment.

## 🚀 Quick Start

### Prerequisites
- Docker and Docker Compose installed on your system
- Git (optional, for version control)

### Installation

1. **Clone or download this project**
   ```bash
   git clone <your-repo-url>
   cd wordpress-project
   ```

2. **Start the WordPress environment**
   ```bash
   docker-compose up -d
   ```

3. **Access your WordPress site**
   - WordPress: http://localhost:8080
   - phpMyAdmin: http://localhost:8081

4. **Complete WordPress setup**
   - Follow the WordPress installation wizard
   - Create your admin account
   - Choose your theme and configure your site

## 📁 Project Structure

```
wordpress-project/
├── docker-compose.yml          # Docker services configuration
├── .env                        # Environment variables
├── uploads.ini                 # PHP upload configuration
├── wp-content/                 # WordPress content directory
│   ├── themes/                 # Custom themes
│   │   └── custom-theme/       # Your custom theme
│   ├── plugins/                # Custom plugins
│   └── uploads/                # Media uploads
└── README.md                   # This file
```

## 🛠 Development

### Custom Theme Development
- Your custom theme is located in `wp-content/themes/custom-theme/`
- The theme is automatically loaded and available in WordPress admin
- Make changes to theme files and they'll be reflected immediately

### Database Management
- Access phpMyAdmin at http://localhost:8081
- Username: `wordpress`
- Password: `wordpress_password`

### File Uploads
- Uploaded files are stored in `wp-content/uploads/`
- Maximum file size: 64MB (configurable in `uploads.ini`)

## 🔧 Configuration

### Environment Variables
Edit the `.env` file to customize:
- Database credentials
- WordPress debug settings
- Security keys (generate new ones for production!)

### PHP Configuration
Modify `uploads.ini` to adjust:
- File upload limits
- Memory limits
- Execution time limits

## 🚀 Deployment

### Production Considerations
1. **Security**
   - Change all default passwords
   - Generate new security keys
   - Use HTTPS
   - Update file permissions

2. **Performance**
   - Enable caching
   - Optimize images
   - Use a CDN
   - Consider managed hosting

3. **Backup**
   - Regular database backups
   - File system backups
   - Test restore procedures

## 📝 Useful Commands

```bash
# Start the environment
docker-compose up -d

# Stop the environment
docker-compose down

# View logs
docker-compose logs -f

# Restart WordPress
docker-compose restart wordpress

# Access WordPress container
docker exec -it wordpress_site bash

# Backup database
docker exec wordpress_db mysqldump -u wordpress -pwordpress_password wordpress_db > backup.sql
```

## 🆘 Troubleshooting

### Common Issues

1. **Port conflicts**
   - If ports 8080 or 8081 are in use, modify the ports in `docker-compose.yml`

2. **Permission issues**
   - Ensure Docker has proper permissions
   - Check file ownership in wp-content directory

3. **Database connection errors**
   - Verify database container is running: `docker-compose ps`
   - Check database logs: `docker-compose logs db`

4. **Theme not appearing**
   - Ensure theme files are in the correct directory
   - Check file permissions
   - Verify theme has proper `style.css` header

### Getting Help
- Check Docker logs: `docker-compose logs`
- WordPress Codex: https://codex.wordpress.org/
- Docker documentation: https://docs.docker.com/

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

---

Happy coding! 🎉
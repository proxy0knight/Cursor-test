# CyberSec Database Manager Plugin

A comprehensive WordPress plugin for managing the CyberSec Platform database schema with full control over database operations.

## 🚀 Features

### Database Management
- **Build/Rebuild Database** - Create complete database schema with all tables, indexes, and sample data
- **Remove Database** - Safely remove all CyberSec platform tables and data
- **Export Database** - Export all data to SQL files for backup or migration
- **Fix Database** - Add missing tables and fields without affecting existing data

### Custom Tables for Advanced Features
- **User Classification System** - Student, Professional, Researcher, Instructor, Admin, Moderator, VIP, Sponsor
- **User Moderation & Blocking** - Warning, Suspension, Ban, Restriction with appeal system
- **Activity Logging** - Comprehensive user activity tracking with risk assessment
- **Content Moderation** - Automated content filtering and moderation
- **Reputation System** - Trust levels and reputation scoring
- **Security Events** - System-wide security monitoring and alerting
- **API Usage Tracking** - Rate limiting and usage analytics
- **System Maintenance** - Database versioning and maintenance logs

### Admin Interface
- **Real-time Status Dashboard** - Live database status and statistics
- **One-click Operations** - Simple buttons for all database operations
- **Activity Logs** - Detailed logs of all database operations
- **Backup Management** - View and download database backups
- **Error Handling** - Comprehensive error reporting and recovery

## 📊 Database Schema

### Core Tables (28 tables total)
- **User Management**: users, user_profiles, user_stats, user_classifications, user_moderation
- **Team System**: teams, team_members, team_stats
- **Competitions**: competitions, competition_participants, competition_challenges
- **Challenges**: challenges, challenge_categories, challenge_submissions
- **Learning**: learning_paths, learning_modules, user_learning_progress
- **Achievements**: badges, user_badges
- **Leaderboards**: leaderboards
- **Notifications**: notifications, activity_feed
- **Security**: security_events, api_usage_logs, user_activity_logs
- **Moderation**: content_moderation, user_reputation
- **System**: platform_settings, system_maintenance, schema_versions

## 🛠 Installation

1. **Upload Plugin**
   ```bash
   # Copy the plugin folder to WordPress plugins directory
   cp -r cybersec-db-manager /path/to/wordpress/wp-content/plugins/
   ```

2. **Activate Plugin**
   - Go to WordPress Admin → Plugins
   - Find "CyberSec Database Manager"
   - Click "Activate"

3. **Access Database Control**
   - Go to WordPress Admin → Database Control
   - You'll see the main dashboard with database status

## 🎯 Usage

### Building the Database
1. Go to **Database Control** in WordPress admin
2. Click **"Build Database"** button
3. Confirm the action
4. Wait for completion (usually 30-60 seconds)
5. Database will be created with all tables and sample data

### Exporting Data
1. Click **"Export Database"** button
2. Wait for export to complete
3. Download the generated SQL file
4. Use for backup or migration purposes

### Fixing Database Issues
1. Click **"Fix Database"** button
2. Plugin will automatically detect missing tables/fields
3. Missing elements will be added without affecting existing data
4. Safe to run multiple times

### Removing Database
⚠️ **WARNING**: This will permanently delete all CyberSec platform data!
1. Click **"Remove Database"** button
2. Confirm the action
3. All CyberSec tables will be dropped

## 🔧 Configuration

### Plugin Settings
The plugin automatically creates necessary directories and settings:
- `wp-content/plugins/cybersec-db-manager/schemas/` - Schema files
- `wp-content/plugins/cybersec-db-manager/backups/` - Export files
- `wp-content/plugins/cybersec-db-manager/logs/` - Operation logs

### Database Options
- `cybersec_db_manager_version` - Plugin version
- `cybersec_db_manager_schema_version` - Current schema version
- `cybersec_db_manager_last_backup` - Last backup filename

## 📁 File Structure

```
cybersec-db-manager/
├── cybersec-db-manager.php          # Main plugin file
├── schemas/
│   ├── database-schema.sql          # Core database schema
│   └── database-schema-extended.sql # Extended schema with custom tables
├── templates/
│   ├── admin-page.php               # Main admin interface
│   ├── backup-page.php              # Backup management page
│   └── logs-page.php                # Logs viewing page
├── assets/
│   ├── admin.css                    # Admin interface styles
│   └── admin.js                     # Admin interface JavaScript
├── backups/                         # Database export files
├── logs/                           # Operation logs
└── README.md                       # This file
```

## 🔒 Security Features

### User Classification System
- **Student** - Basic access, learning focus
- **Professional** - Industry experience, advanced features
- **Researcher** - Academic access, research tools
- **Instructor** - Teaching capabilities, student management
- **Admin** - Full platform access
- **Moderator** - Content moderation powers
- **VIP** - Premium features, priority support
- **Sponsor** - Special recognition, branding

### Moderation System
- **Warning** - First offense, educational
- **Suspension** - Temporary access restriction
- **Ban** - Permanent access removal
- **Restriction** - Limited functionality access

### Security Monitoring
- **Login Attempts** - Failed login tracking
- **Suspicious Activity** - Unusual behavior detection
- **Brute Force** - Attack pattern recognition
- **Data Breach Attempts** - Security violation tracking
- **Privilege Escalation** - Unauthorized access attempts
- **System Anomalies** - Unusual system behavior

## 📈 Performance Features

### Optimized Queries
- Proper indexing on all frequently queried fields
- Cached leaderboards for fast ranking queries
- Efficient relationship queries with foreign keys

### Scalability
- Partitioning-ready table structure
- Efficient data types and constraints
- Optimized for large datasets

## 🐛 Troubleshooting

### Common Issues

1. **Permission Errors**
   - Ensure WordPress has write permissions to plugin directories
   - Check file ownership and permissions

2. **Database Connection Issues**
   - Verify WordPress database credentials
   - Check database server availability

3. **Memory Issues**
   - Increase PHP memory limit if needed
   - Large databases may require more memory

4. **Timeout Issues**
   - Increase PHP execution time limit
   - Consider running operations in smaller batches

### Log Files
Check the logs directory for detailed error information:
- `logs/db_manager.log` - Main operation logs
- WordPress error logs for system-level issues

### Support
For issues or questions:
1. Check the logs first
2. Verify WordPress and PHP versions
3. Test with default WordPress theme
4. Check for plugin conflicts

## 🔄 Updates

### Schema Versioning
The plugin includes automatic schema versioning:
- Tracks database schema changes
- Supports rollback operations
- Maintains migration history

### Backup Before Updates
Always create a backup before:
- Updating the plugin
- Running database operations
- Making schema changes

## 📝 Changelog

### Version 1.0.0
- Initial release
- Complete database management system
- Custom tables for user classification and moderation
- Admin interface with real-time status
- Export/import functionality
- Comprehensive logging system

## 🤝 Contributing

To contribute to this plugin:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This plugin is licensed under the GPL v2 or later.

---

**Note**: This plugin is designed specifically for the CyberSec Platform and includes advanced features for cybersecurity education and competition management.
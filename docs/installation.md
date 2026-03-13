# Installation Guide

This guide provides detailed instructions for installing and configuring your Dolibarr module.

## Prerequisites

Before installing the module, ensure you have:

- **Dolibarr ERP/CRM**: Version 12.0 or higher
- **PHP**: Version 7.4 or higher
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Database**: MySQL 5.7+, MariaDB 10.3+, or PostgreSQL 11+
- **PHP Extensions**:
  - mysqli or pgsql
  - gd or imagick
  - curl
  - json
  - xml

## Installation Steps

### 1. Clone the Repository

Navigate to your Dolibarr's custom directory:

```bash
cd /path/to/dolibarr/htdocs/custom/
```

Clone this template repository:

```bash
git clone https://github.com/mokoconsulting-tech/MokoStandards-Template-Dolibarr.git yourmodule
```

Replace `yourmodule` with your desired module name (lowercase, no spaces).

### 2. Rename and Configure

Navigate to the module directory:

```bash
cd yourmodule
```

Rename the module descriptor file:

```bash
mv modYourmodule.class.php modYourModuleName.class.php
```

### 3. Configure Module ID

Open the module descriptor file and set a temporary module ID greater than 600,000:

```php
// In modYourModuleName.class.php
$this->numero = 600001; // Use a number > 600,000 for development
```

**Important:** Before publishing, request an official module ID by creating an issue in the repository.

### 4. Set File Permissions

Ensure proper file permissions:

```bash
# Set ownership (adjust user:group as needed)
chown -R www-data:www-data /path/to/dolibarr/htdocs/custom/yourmodule

# Set directory permissions
find /path/to/dolibarr/htdocs/custom/yourmodule -type d -exec chmod 755 {} \;

# Set file permissions
find /path/to/dolibarr/htdocs/custom/yourmodule -type f -exec chmod 644 {} \;
```

### 5. Enable the Module in Dolibarr

1. Log in to your Dolibarr instance as an administrator
2. Navigate to **Home → Setup → Modules/Applications**
3. Find your module in the list
4. Click the **Activate** button

### 6. Configure Module Settings (if applicable)

After activation:

1. Go to **Home → Setup → Modules/Applications**
2. Click on your module name to access its configuration page
3. Configure any required settings
4. Save changes

## Database Setup

If your module requires database tables:

### Automatic Setup

The module descriptor can handle automatic table creation during activation. Ensure your SQL files are in the `sql/` directory:

```
sql/
├── llx_yourmodule_table.sql
└── llx_yourmodule_table.key.sql
```

### Manual Setup

Alternatively, run SQL scripts manually:

```bash
mysql -u username -p database_name < sql/llx_yourmodule_table.sql
```

## Troubleshooting

### Module Not Appearing

- Clear Dolibarr cache: Delete `/documents/install.lock` and refresh
- Check file permissions
- Verify PHP syntax errors: `php -l modYourModuleName.class.php`

### Permission Errors

- Ensure Apache/Nginx user has read access to all module files
- Check `conf.php` file permissions in Dolibarr root

### Database Errors

- Verify database credentials in Dolibarr's `conf/conf.php`
- Check SQL file syntax
- Ensure database user has CREATE TABLE permissions

## Uninstallation

To remove the module:

1. Navigate to **Home → Setup → Modules/Applications**
2. Find your module and click **Deactivate**
3. Optionally, remove the module directory:
   ```bash
   rm -rf /path/to/dolibarr/htdocs/custom/yourmodule
   ```

**Note:** Deactivating the module does not remove database tables. To remove data:

```sql
DROP TABLE llx_yourmodule_table;
```

## Next Steps

- Review the [Development Guide](development.md) to start customizing your module
- Check the [Module ID Policy](module-id-policy.md) before distribution
- Read the [Changelog](changelog.md) for version history

## Support

For installation issues:
- Create an issue in the repository
- Check Dolibarr logs: `/documents/dolibarr.log`
- Visit the [Dolibarr Forum](https://www.dolibarr.org/forum)

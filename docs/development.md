# Development Guide

This guide provides best practices and guidelines for developing Dolibarr modules using this template.

## Module Structure

A well-organized Dolibarr module follows this structure:

```
yourmodule/
├── class/                          # Business logic classes
│   ├── yourmodule.class.php        # Main business object
│   └── api_yourmodule.class.php    # REST API endpoints (optional)
├── core/                           # Core integrations
│   ├── modules/                    # Numbering modules
│   └── triggers/                   # Event triggers
│       └── interface_99_modYourmodule_YourmoduleTriggers.class.php
├── lang/                           # Translations
│   ├── en_US/
│   │   └── yourmodule.lang
│   └── fr_FR/
│       └── yourmodule.lang
├── sql/                            # Database scripts
│   ├── llx_yourmodule_table.sql
│   └── llx_yourmodule_table.key.sql
├── css/                            # Stylesheets
│   └── yourmodule.css
├── js/                             # JavaScript
│   └── yourmodule.js
├── img/                            # Images and icons
│   └── object_yourmodule.png
├── lib/                            # Helper functions
│   └── yourmodule.lib.php
├── docs/                           # Documentation
├── admin/                          # Admin pages
│   ├── setup.php                   # Configuration page
│   └── about.php                   # About page
├── yourmodule_page.php             # Main module page
├── modYourmodule.class.php         # Module descriptor
└── README.md
```

## Module Descriptor

The module descriptor (`modYourmodule.class.php`) is the core configuration file.

### Essential Properties

```php
<?php
class modYourmodule extends DolibarrModules
{
    public function __construct($db)
    {
        global $langs, $conf;
        $this->db = $db;
        
        // Module ID - use 600,000+ for development
        $this->numero = 600001;
        
        // Module identification
        $this->rights_class = 'yourmodule';
        $this->family = "other";
        $this->module_position = '1000';
        
        // Module name and description
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Module description";
        $this->descriptionlong = "Detailed module description";
        
        // Version
        $this->version = '1.0.0';
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        
        // Dependencies
        $this->depends = array(); // e.g., array('modThirdparty', 'modProduct')
        $this->requiredby = array();
        $this->conflictwith = array();
        
        // Language files
        $this->langfiles = array("yourmodule@yourmodule");
        
        // Configuration page
        $this->config_page_url = array("setup.php@yourmodule");
        
        // Constants
        $this->const = array();
        
        // Permissions
        $this->rights = array();
        $r = 0;
        
        $this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1);
        $this->rights[$r][1] = 'Read objects of YourModule';
        $this->rights[$r][4] = 'yourmodule';
        $this->rights[$r][5] = 'read';
        $r++;
        
        // Menus
        $this->menu = array();
    }
}
```

## Best Practices

### 1. Coding Standards

Follow Dolibarr coding standards:

- **Indentation**: Use tabs for indentation
- **Naming**: Use camelCase for functions, lowercase for files
- **Comments**: Use PHPDoc format for documentation
- **Security**: Always sanitize inputs and escape outputs

Example:

```php
/**
 * Get list of objects
 *
 * @param  string  $sortfield  Sort field
 * @param  string  $sortorder  Sort order
 * @param  int     $limit      Limit
 * @param  int     $offset     Offset
 * @return array               Array of objects
 */
public function fetchAll($sortfield = 's.rowid', $sortorder = 'ASC', $limit = 0, $offset = 0)
{
    $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "yourmodule_table";
    // Add security and parameters
}
```

### 2. Security

Always implement proper security:

```php
// Check permissions
if (!$user->rights->yourmodule->read) {
    accessforbidden();
}

// Sanitize inputs
$id = GETPOST('id', 'int');
$name = GETPOST('name', 'alpha');

// Use prepared statements
$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "table WHERE id = " . (int)$id;

// Escape output
print dol_escape_htmltag($user_input);
```

**Important**: Review our [Security Policy](../SECURITY.md) for comprehensive security guidelines and best practices.

### 3. Database Operations

Use Dolibarr's database abstraction:

```php
// Insert
$sql = "INSERT INTO " . MAIN_DB_PREFIX . "yourmodule_table";
$sql .= " (field1, field2) VALUES ('" . $this->db->escape($value1) . "', '" . $this->db->escape($value2) . "')";
$resql = $this->db->query($sql);

// Update
$sql = "UPDATE " . MAIN_DB_PREFIX . "yourmodule_table";
$sql .= " SET field1 = '" . $this->db->escape($value) . "'";
$sql .= " WHERE rowid = " . (int)$id;
$resql = $this->db->query($sql);

// Select
$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "yourmodule_table";
$resql = $this->db->query($sql);
if ($resql) {
    $num = $this->db->num_rows($resql);
    while ($i < $num) {
        $obj = $this->db->fetch_object($resql);
        // Process object
        $i++;
    }
}
```

### 4. Translations

Use translation keys in language files:

```php
// In lang/en_US/yourmodule.lang
YourModuleSetup = Your Module Setup
YourModuleDescription = This is your module
```

```php
// In PHP code
$langs->load("yourmodule@yourmodule");
print $langs->trans("YourModuleSetup");
```

### 5. Hooks and Triggers

Implement triggers for event handling:

```php
class InterfaceModYourmoduleTriggers
{
    public function runTrigger($action, $object, $user, $langs, $conf)
    {
        if ($action == 'BILL_CREATE') {
            // Handle invoice creation
        }
        return 0;
    }
}
```

### 6. API Development

Create REST API endpoints:

```php
class YourModuleApi extends DolibarrApi
{
    /**
     * @url GET /yourmodule/objects
     */
    public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0)
    {
        // Implement API logic
    }
}
```

## Testing

### Manual Testing

1. Test module activation/deactivation
2. Verify permissions work correctly
3. Check database operations
4. Test with different user roles
5. Verify translations

### Debugging

Enable Dolibarr debugging:

```php
// In conf/conf.php
$dolibarr_main_prod = 0; // Development mode
```

View logs in `/documents/dolibarr.log`

## Module ID Management

### Development Phase

Use module ID > 600,000:

```php
$this->numero = 600001;
```

### Before Distribution

1. Create an issue in the repository requesting a module ID
2. Wait for approval and assignment
3. Update the module descriptor with the assigned ID
4. Test thoroughly before release

See [Module ID Policy](module-id-policy.md) for details.

## Version Control

Follow semantic versioning (MAJOR.MINOR.PATCH):

- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes

Update version in:
- `modYourmodule.class.php`
- `docs/changelog.md`
- Documentation files

## Publishing

Before publishing your module:

1. ✅ Request and receive official module ID
2. ✅ Complete all documentation
3. ✅ Test on multiple Dolibarr versions
4. ✅ Review security best practices
5. ✅ Add license file
6. ✅ Update changelog
7. ✅ Create release notes

## Resources

- [Dolibarr Developer Docs](https://wiki.dolibarr.org/index.php/Developer_documentation)
- [Module Development Guide](https://wiki.dolibarr.org/index.php/Module_development)
- [Dolibarr API Reference](https://www.dolibarr.org/doc/html/)
- [Module ID Registry](https://wiki.dolibarr.org/index.php/List_of_modules_id)

## Support

- Repository issues for template questions
- [Dolibarr Forum](https://www.dolibarr.org/forum) for development help
- [Dolibarr GitHub](https://github.com/Dolibarr/dolibarr) for core issues

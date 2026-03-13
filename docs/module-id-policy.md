# Module ID Policy

This document explains the module ID assignment policy for Dolibarr modules developed using this template.

## Overview

Every Dolibarr module requires a unique numeric identifier (module ID). This ID is critical for:
- Module identification within Dolibarr
- Preventing conflicts with other modules
- Tracking module permissions and configurations
- Database table prefixes and naming conventions

## Module ID Ranges

Dolibarr uses the following ID ranges:

| Range | Purpose | Registration Required |
|-------|---------|----------------------|
| 0 - 94,999 | Core Dolibarr modules | Reserved by Dolibarr core team |
| 95,000 - 99,999 | Community modules (official repos) | Yes, via Dolibarr GitHub |
| 100,000 - 499,999 | Third-party public modules | Yes, via official registry |
| 500,000 - 599,999 | Private/unlisted modules | Recommended for permanent private use |
| 600,000+ | Development/temporary modules | **Use this range during development** |

## Development Phase

### Use Temporary ID (600,000+)

While developing your module, always use an ID greater than 600,000:

```php
// In modYourmodule.class.php
class modYourmodule extends DolibarrModules
{
    public function __construct($db)
    {
        $this->db = $db;
        
        // Temporary development ID
        $this->numero = 600001; // or 600002, 600003, etc.
        
        // ... rest of configuration
    }
}
```

### Why Use 600,000+?

- **No registration required**: Immediate development without waiting for approval
- **No conflicts**: This range is intentionally unreserved for development
- **Easy to change**: Simple to update before distribution
- **Clear indicator**: Shows the module is in development phase

### Choosing Your Temporary ID

1. Pick any number greater than 600,000
2. Use sequential numbers if developing multiple modules (600,001, 600,002, etc.)
3. Document your temporary ID in your development notes
4. Remember to replace it before distribution

## Production Phase

### Request Official Module ID

Before distributing or publishing your module, you **must** request an official module ID.

### How to Request

1. **Create an Issue** in this repository
   - Use the title: "Request Module ID Assignment: [Your Module Name]"
   - Use the "Module ID Request" label if available

2. **Provide Required Information**:
   ```markdown
   ## Module ID Request
   
   **Module Name**: Your Module Name
   
   **Description**: Brief description of what your module does
   
   **Organization/Developer**: Your organization or name
   
   **Distribution Plan**: 
   - [ ] Public (Dolibarr Marketplace)
   - [ ] Public (GitHub/other platform)
   - [ ] Private (internal use only)
   - [ ] Commercial
   
   **Target Audience**: Who will use this module?
   
   **Additional Notes**: Any other relevant information
   ```

3. **Wait for Approval**
   - A maintainer will review your request
   - You'll receive an assigned module ID
   - The ID will be from the appropriate range based on your distribution plan

### ID Assignment Criteria

Module IDs are assigned based on:

- **100,000 - 499,999**: Public modules intended for broad distribution
  - Dolibarr Marketplace modules
  - Open-source modules on GitHub
  - Modules with public documentation
  
- **500,000 - 599,999**: Private or limited distribution
  - Internal company modules
  - Client-specific customizations
  - Modules not intended for public use

### After Receiving Your ID

1. **Update Module Descriptor**:
   ```php
   // Change from development ID
   $this->numero = 600001;
   
   // To your assigned ID
   $this->numero = 123456; // Your assigned ID
   ```

2. **Update Documentation**:
   - Update README.md with the official ID
   - Note the ID in your changelog
   - Document the assignment date

3. **Test Thoroughly**:
   - Reinstall module with new ID
   - Verify no conflicts with existing installations
   - Check all database operations

4. **Commit Changes**:
   ```bash
   git add modYourmodule.class.php
   git commit -m "Update to official module ID: 123456"
   ```

## Module ID Registry

### Official Dolibarr Registry

For modules intended for the official Dolibarr ecosystem, you may also need to register on the official wiki:

- [Dolibarr Module ID List](https://wiki.dolibarr.org/index.php/List_of_modules_id)

### MokoStandards Registry

This repository maintains its own registry of assigned IDs to prevent conflicts among MokoStandards projects.

## Special Cases

### Multiple Modules

If you're developing multiple related modules:
- Request a block of IDs (e.g., 123450-123459)
- Document which ID is used by which module
- Keep sequential IDs for related functionality

### Module Forking

If forking an existing module:
- You **must** request a new module ID
- Do not reuse the original module's ID
- Document the relationship to the original module

### Module Renaming

If renaming a module:
- Keep the same module ID
- Update the module name and descriptor
- Document the name change in changelog

## Troubleshooting

### ID Conflicts

If you experience ID conflicts:
1. Check installed modules: `SELECT * FROM llx_const WHERE name LIKE 'MAIN_MODULE_%';`
2. Verify your ID doesn't conflict
3. If conflict exists, request a new ID
4. Update and redeploy

### Lost or Forgotten ID

If you've lost track of your assigned ID:
1. Check the issues in this repository
2. Search module registry documentation
3. Create a new issue asking for clarification

## Best Practices

1. ✅ **Always start with 600,000+** during development
2. ✅ **Request official ID early** if planning to distribute
3. ✅ **Document your ID** in all relevant files
4. ✅ **Test after ID changes** to ensure no issues
5. ✅ **Never use another module's ID** even in development
6. ❌ **Don't distribute modules** with temporary IDs (600,000+)
7. ❌ **Don't request multiple IDs** without justification
8. ❌ **Don't change IDs** after public distribution

## Examples

### Development Stage
```php
// Good - using temporary development ID
$this->numero = 600001;
```

### Production Stage (Private Module)
```php
// Good - assigned ID from private range
$this->numero = 550001;
```

### Production Stage (Public Module)
```php
// Good - assigned ID from public range
$this->numero = 125000;
```

### Bad Practice
```php
// BAD - using another module's ID
$this->numero = 1; // This is a core module ID!

// BAD - random low number
$this->numero = 42;

// BAD - distributing with development ID
$this->numero = 600001; // Only for development!
```

## References

- [Dolibarr Module Development](https://wiki.dolibarr.org/index.php/Module_development)
- [Dolibarr Module ID List](https://wiki.dolibarr.org/index.php/List_of_modules_id)
- [Dolibarr Module Structure](https://wiki.dolibarr.org/index.php/Module_development#Module_descriptor)

## Contact

For questions about module ID assignment:
- Create an issue in this repository
- Tag it with "module-id-question"
- Provide as much context as possible

---

**Remember**: Using the correct module ID ensures your module integrates seamlessly with Dolibarr and avoids conflicts with other modules in the ecosystem.

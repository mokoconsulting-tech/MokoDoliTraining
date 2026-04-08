---
name: Dolibarr Module Issue
about: Report an issue with a Dolibarr module
title: '[DOLIBARR] '
labels: 'dolibarr'
assignees: ''

---

<!--
SPDX-License-Identifier: GPL-3.0-or-later
Copyright (C) 2024-2026 Moko Consulting Tech

File: .github/ISSUE_TEMPLATE/dolibarr_issue.md
Description: Issue template for Dolibarr module-specific issues
Project: .github-private
Author: Moko Consulting Tech
Version: 03.02.00

Revision History:
- 2026-01-04: Added MokoStandards compliant header with copyright, file info, and metadata
- 2026-03-11: Version bump to 03.02.00 to match MokoStandards
- 2024: Initial creation
-->

## Module Details
- **Module Name**: [e.g., MokoDoliTools]
- **Module Version**: [e.g., 1.2.3]
- **Module Type**: [Custom Module / Third-party Module]

## Dolibarr Environment
- **Dolibarr Version**: [e.g., 18.0.0]
- **PHP Version**: [e.g., 8.1.0]
- **Database**: [MySQL / PostgreSQL / MariaDB]
- **Database Version**: [e.g., 8.0]
- **Server**: [Apache / Nginx / IIS]
- **Hosting**: [Shared / VPS / Dedicated / Cloud]

## Issue Description
Provide a clear and detailed description of the issue.

## Steps to Reproduce
1. Log into Dolibarr
2. Navigate to '...'
3. Click on '...'
4. See error

## Expected Behavior
What you expected to happen.

## Actual Behavior
What actually happened.

## Error Messages
```
# Paste any error messages from Dolibarr logs
# Location: documents/dolibarr.log
```

## PHP Error Logs
```php
// Paste any PHP errors from error_log
```

## Screenshots
Add screenshots to help explain the issue.

## Module Configuration
```php
// Paste relevant module configuration (sanitize sensitive data)
```

## Installed Modules
List other installed modules that might conflict:
- Module 1 (version)
- Module 2 (version)

## User Permissions
- **User Type**: [Admin / User]
- **Permissions**: List relevant permissions enabled

## Database Tables
- [ ] Module tables created correctly
- [ ] Data migration completed
- [ ] Foreign keys intact

## Additional Context
- **Multi-Company**: [Yes / No]
- **Custom Hooks**: [Yes / No]
- **Third-party Integrations**: [List any]
- **Cron Jobs**: [Enabled / Disabled]

## Performance Impact
- **Page Load Time**: [seconds]
- **Database Query Count**: [if known]
- **Memory Usage**: [if known]

## Checklist
- [ ] I have cleared Dolibarr cache
- [ ] I have disabled other modules to test for conflicts
- [ ] I have checked Dolibarr logs
- [ ] I have verified database tables are correct
- [ ] I have checked PHP error logs
- [ ] I have tested with default Dolibarr theme
- [ ] I have searched for similar issues
- [ ] I am using a supported Dolibarr version
- [ ] I have proper user permissions

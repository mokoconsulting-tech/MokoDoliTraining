# Security Policy

## Supported Versions

This template is maintained for the latest stable version of Dolibarr. Security updates are provided for:

| Version | Supported          |
| ------- | ------------------ |
| 1.0.x   | :white_check_mark: |

## Reporting a Vulnerability

The MokoConsulting Tech team takes security vulnerabilities seriously. We appreciate your efforts to responsibly disclose your findings.

### How to Report

**Please do not report security vulnerabilities through public GitHub issues.**

Instead, please report security vulnerabilities by:

1. **Email**: Send an email to security@mokoconsulting.tech (or create a private security advisory on GitHub)
2. **Subject**: Use the subject line "Security Vulnerability Report: MokoStandards-Template-Dolibarr"

### What to Include

Please include the following information in your report:

- **Type of vulnerability** (e.g., SQL injection, XSS, authentication bypass)
- **Full paths of affected files** or URLs
- **Step-by-step instructions** to reproduce the issue
- **Proof-of-concept or exploit code** (if possible)
- **Impact assessment** - what could an attacker potentially do?
- **Suggested fix** (if you have one)
- **Your contact information** for follow-up

### What to Expect

After you submit a report:

1. **Acknowledgment**: We'll acknowledge receipt within **48 hours**
2. **Initial Assessment**: We'll provide an initial assessment within **5 business days**
3. **Regular Updates**: We'll keep you informed about our progress
4. **Resolution**: We'll work to resolve the issue as quickly as possible based on severity:
   - **Critical**: Within 7 days
   - **High**: Within 14 days
   - **Medium**: Within 30 days
   - **Low**: Within 60 days

### Disclosure Policy

- We request that you give us reasonable time to fix the vulnerability before any public disclosure
- We'll coordinate with you on the disclosure timeline
- We'll credit you in the security advisory (unless you prefer to remain anonymous)
- We may provide a CVE identifier for significant vulnerabilities

## Security Best Practices for Module Development

When developing Dolibarr modules using this template, follow these security guidelines:

### 1. Input Validation

Always validate and sanitize user input:

```php
// Use GETPOST with proper filter type
$id = GETPOST('id', 'int');
$name = GETPOST('name', 'alpha');
$email = GETPOST('email', 'email');

// Validate data
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setEventMessages($langs->trans("ErrorInvalidEmail"), null, 'errors');
    header('Location: ' . $_SERVER["PHP_SELF"]);
    exit;
}
```

### 2. SQL Injection Prevention

Use parameterized queries or proper escaping:

```php
// Good - using escape
$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "table";
$sql .= " WHERE field = '" . $this->db->escape($value) . "'";

// Good - using integer casting
$sql .= " WHERE id = " . (int)$id;
```

### 3. Cross-Site Scripting (XSS) Prevention

Escape output to prevent XSS:

```php
// Escape HTML output
print dol_escape_htmltag($user_input);

// Escape JavaScript output
print dol_escape_js($user_input);
```

### 4. Authentication and Authorization

Always check permissions:

```php
// Check if user is logged in
if (!$user->rights->yourmodule->read) {
    accessforbidden();
}

// Check specific permissions
if (!$user->hasRight('yourmodule', 'write')) {
    setEventMessages($langs->trans("NotEnoughPermissions"), null, 'errors');
    header('Location: ' . $_SERVER["PHP_SELF"]);
    exit;
}
```

### 5. File Upload Security

Validate file uploads carefully:

```php
// Check file type
$allowed_types = array('image/jpeg', 'image/png', 'application/pdf');
if (!in_array($_FILES['file']['type'], $allowed_types)) {
    setEventMessages($langs->trans("ErrorInvalidFileType"), null, 'errors');
    exit;
}

// Check file size
$max_size = 5 * 1024 * 1024; // 5MB
if ($_FILES['file']['size'] > $max_size) {
    setEventMessages($langs->trans("ErrorFileTooLarge"), null, 'errors');
    exit;
}

// Use secure file names
$filename = dol_sanitizeFileName($_FILES['file']['name']);
```

### 6. CSRF Protection

Use Dolibarr's CSRF token system:

```php
// In forms, add CSRF token
print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';

// Verify token on submission
if (!verifCsrfToken()) {
    accessforbidden();
}
```

### 7. Secure Configuration

- Never hardcode credentials or API keys
- Use Dolibarr's configuration system for sensitive data
- Store secrets in environment variables or secure configuration files
- Never commit sensitive information to version control

```php
// Good - using configuration
$api_key = $conf->global->YOURMODULE_API_KEY;

// Bad - hardcoded
$api_key = "sk_live_123456789"; // Never do this!
```

### 8. Error Handling

Don't expose sensitive information in error messages:

```php
// Good - generic error message
if (!$result) {
    setEventMessages($langs->trans("ErrorOccurred"), null, 'errors');
    dol_syslog("Detailed error: " . $db->error(), LOG_ERR);
}

// Bad - exposing details
if (!$result) {
    print "Database error: " . $db->error(); // Exposes database structure
}
```

### 9. Session Security

Use secure session handling:

```php
// Regenerate session ID after login
session_regenerate_id(true);

// Use secure session cookies
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
```

### 10. Dependency Management

- Keep dependencies up to date
- Use only trusted libraries
- Regularly check for security advisories
- Use Composer for PHP dependencies with version constraints

## Security Checklist

Before releasing your module, verify:

- [ ] All user inputs are validated and sanitized
- [ ] SQL queries use parameterized queries or proper escaping
- [ ] All output is properly escaped (HTML, JavaScript, etc.)
- [ ] Authentication and authorization checks are in place
- [ ] CSRF protection is implemented for all forms
- [ ] File uploads are validated and secured
- [ ] No sensitive information is hardcoded
- [ ] Error messages don't expose sensitive details
- [ ] Dependencies are up to date
- [ ] Security headers are properly configured
- [ ] Module has been tested for common vulnerabilities (OWASP Top 10)

## Known Security Considerations

### Module ID Selection

- Using temporary IDs (600,000+) during development is secure
- Ensure you request an official ID before public distribution
- Never use another module's ID to prevent conflicts and potential security issues

### Dolibarr Core Security

This template relies on Dolibarr's core security features:
- Authentication system
- Permission management
- CSRF protection
- Session management

Ensure your Dolibarr installation is up to date to benefit from the latest security patches.

## Additional Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Dolibarr Security Recommendations](https://wiki.dolibarr.org/index.php/Security_information)
- [PHP Security Best Practices](https://www.php.net/manual/en/security.php)
- [Secure Coding Guidelines](https://cheatsheetseries.owasp.org/)

## Security Updates

Security updates for this template will be:
- Announced in the repository
- Documented in the changelog
- Tagged with security labels in release notes

## Bug Bounty

At this time, we do not have a formal bug bounty program. However, we greatly appreciate security researchers who report vulnerabilities responsibly and will acknowledge their contributions in our security advisories.

## Contact

For security-related questions or concerns:
- **Security Issues**: security@mokoconsulting.tech (or create a private security advisory)
- **General Questions**: Create an issue in the repository (for non-sensitive matters)

---

**Remember**: Security is everyone's responsibility. When in doubt, ask for a security review before deploying your module to production.

# Contributing to MokoStandards Dolibarr Template

Thank you for your interest in contributing to this Dolibarr module template! This document provides guidelines and instructions for contributing.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [How Can I Contribute?](#how-can-i-contribute)
- [Development Setup](#development-setup)
- [Coding Standards](#coding-standards)
- [Commit Guidelines](#commit-guidelines)
- [Pull Request Process](#pull-request-process)
- [Documentation](#documentation)

## Code of Conduct

This project adheres to the Contributor Covenant [Code of Conduct](CODE_OF_CONDUCT.md). By participating, you are expected to uphold this code.

### Our Standards

- Be respectful and inclusive
- Welcome newcomers and help them learn
- Focus on what is best for the community
- Show empathy towards other community members

### Unacceptable Behavior

- Harassment, discrimination, or offensive comments
- Trolling or insulting/derogatory comments
- Public or private harassment
- Publishing others' private information

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check existing issues. When creating a bug report, include:

- **Clear title and description**
- **Steps to reproduce** the issue
- **Expected behavior** vs actual behavior
- **Screenshots** if applicable
- **Environment details** (Dolibarr version, PHP version, etc.)

**Bug Report Template:**
```markdown
## Bug Description
A clear description of the bug.

## Steps to Reproduce
1. Go to '...'
2. Click on '....'
3. See error

## Expected Behavior
What you expected to happen.

## Actual Behavior
What actually happened.

## Environment
- Dolibarr Version: [e.g., 16.0]
- PHP Version: [e.g., 8.1]
- Database: [e.g., MySQL 8.0]
- OS: [e.g., Ubuntu 22.04]

## Additional Context
Any other information about the problem.
```

### Suggesting Enhancements

Enhancement suggestions are welcome! Please provide:

- **Clear use case** for the enhancement
- **Detailed description** of the proposed functionality
- **Mockups or examples** if applicable
- **Alternatives considered**

**Enhancement Template:**
```markdown
## Enhancement Description
Clear description of the enhancement.

## Use Case
Who would benefit and how?

## Proposed Solution
How should this work?

## Alternatives Considered
What other solutions did you consider?

## Additional Context
Any other relevant information.
```

### Requesting Module IDs

To request an official module ID:

1. Create an issue using the title format: "Request Module ID Assignment: [Module Name]"
2. Provide all required information (see [Module ID Policy](docs/module-id-policy.md))
3. Wait for maintainer review and assignment

### Contributing Code

We welcome code contributions! Here's how:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## Development Setup

### Prerequisites

- Dolibarr 12.0 or higher
- PHP 7.4 or higher
- Git
- Text editor or IDE

### Setup Steps

1. **Fork and clone the repository:**
   ```bash
   git clone https://github.com/YOUR-USERNAME/MokoStandards-Template-Dolibarr.git
   cd MokoStandards-Template-Dolibarr
   ```

2. **Create a feature branch:**
   ```bash
   git checkout -b feature/your-feature-name
   ```

3. **Install in Dolibarr** (for testing):
   ```bash
   ln -s /path/to/your/clone /path/to/dolibarr/htdocs/custom/testmodule
   ```

4. **Make your changes** and test thoroughly

## Coding Standards

### PHP Standards

Follow Dolibarr coding standards:

- **Indentation**: Use tabs (4 spaces width)
- **Line length**: Max 120 characters
- **Naming conventions**:
  - Classes: `PascalCase`
  - Functions/Methods: `camelCase`
  - Variables: `$snake_case` or `$camelCase`
  - Constants: `UPPER_CASE`

### Documentation Standards

- Use PHPDoc format for all classes and functions
- Include parameter types and return types
- Add usage examples for complex functions

Example:
```php
/**
 * Fetch object by ID
 *
 * @param  int    $id    Object ID
 * @return int           <0 if KO, >0 if OK
 */
public function fetch($id)
{
    // Implementation
}
```

### File Organization

- One class per file
- File names match class names
- Use appropriate directories (class/, core/, lib/, etc.)

### Security Best Practices

- Always validate and sanitize inputs
- Use prepared statements or proper escaping
- Implement permission checks
- Escape output to prevent XSS

Example:
```php
// Input validation
$id = GETPOST('id', 'int');
$name = GETPOST('name', 'alpha');

// Permission check
if (!$user->rights->module->read) {
    accessforbidden();
}

// Output escaping
print dol_escape_htmltag($user_input);
```

## Commit Guidelines

### Commit Message Format

Use clear, descriptive commit messages:

```
<type>: <subject>

<body>

<footer>
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, etc.)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

**Examples:**
```
feat: Add user profile management feature

Implement CRUD operations for user profiles including
form validation and database integration.

Closes #123
```

```
fix: Resolve module activation issue

Fix SQL syntax error preventing module activation
on PostgreSQL databases.

Fixes #456
```

### Best Practices

- Keep commits atomic (one logical change per commit)
- Write clear commit messages
- Reference issue numbers when applicable
- Test before committing

## Pull Request Process

### Before Submitting

1. ✅ Update documentation if needed
2. ✅ Add or update tests
3. ✅ Ensure all tests pass
4. ✅ Follow coding standards
5. ✅ Update CHANGELOG.md
6. ✅ Rebase on latest main branch

### Submitting a Pull Request

1. **Push your branch** to your fork:
   ```bash
   git push origin feature/your-feature-name
   ```

2. **Create pull request** on GitHub

3. **Fill out the PR template:**
   ```markdown
   ## Description
   Clear description of changes
   
   ## Type of Change
   - [ ] Bug fix
   - [ ] New feature
   - [ ] Documentation update
   - [ ] Code refactoring
   
   ## Testing
   Describe testing performed
   
   ## Checklist
   - [ ] Code follows project standards
   - [ ] Documentation updated
   - [ ] Tests added/updated
   - [ ] CHANGELOG.md updated
   
   ## Related Issues
   Closes #123
   ```

4. **Wait for review** and address feedback

### Review Process

- Maintainers will review your PR
- Address any requested changes
- Once approved, your PR will be merged

### After Merge

- Delete your feature branch
- Pull latest changes from main
- Update your fork

## Documentation

### What to Document

- New features and functionality
- Configuration options
- API changes
- Breaking changes
- Usage examples

### Where to Document

- **README.md**: Overview and quick start
- **docs/**: Detailed guides and references
- **Code comments**: Inline documentation
- **CHANGELOG.md**: Version history

### Documentation Style

- Use clear, simple language
- Include code examples
- Add screenshots for UI changes
- Keep formatting consistent
- Use proper markdown syntax

## Questions?

If you have questions:

- Check existing documentation
- Search closed issues
- Create a new issue with the "question" label
- Be specific and provide context

## Recognition

Contributors will be recognized in:
- Repository contributors list
- CHANGELOG.md for significant contributions
- Project documentation as appropriate

## License

By contributing, you agree that your contributions will be licensed under the same license as the project (MIT License - see [LICENSE](LICENSE) file).

---

Thank you for contributing to MokoStandards Dolibarr Template! Your efforts help make this template better for everyone.

# Documentation Index

Welcome to the MokoStandards Dolibarr Template documentation. This guide will help you navigate all available documentation resources.

## Quick Links

- [Installation Guide](installation.md) - Get started with installing the template
- [Development Guide](development.md) - Learn how to develop Dolibarr modules
- [Module ID Policy](module-id-policy.md) - Understand module ID assignment process
- [Changelog](changelog.md) - Track version history and changes

## Documentation Structure

### For New Users

If you're new to this template, start here:

1. **[Installation Guide](installation.md)**
   - Prerequisites and requirements
   - Step-by-step installation instructions
   - Configuration and setup
   - Troubleshooting common issues

2. **[Module ID Policy](module-id-policy.md)**
   - Understanding module IDs
   - Development vs. production IDs
   - How to request an official ID
   - Best practices

### For Developers

If you're developing a module, these guides will help:

1. **[Development Guide](development.md)**
   - Module structure and organization
   - Module descriptor configuration
   - Coding standards and best practices
   - Security guidelines
   - Database operations
   - Testing and debugging

2. **[Contributing Guidelines](../CONTRIBUTING.md)**
   - How to contribute
   - Code standards
   - Pull request process
   - Commit message guidelines

### Reference Materials

- **[Changelog](changelog.md)** - Version history and release notes
- **[README](../README.md)** - Project overview and quick start

## Getting Help

### Common Questions

**Q: Where do I start?**  
A: Begin with the [Installation Guide](installation.md) to set up the template, then review the [Development Guide](development.md) for building your module.

**Q: What module ID should I use?**  
A: Use an ID greater than 600,000 during development. See the [Module ID Policy](module-id-policy.md) for details.

**Q: How do I contribute?**  
A: Check out the [Contributing Guidelines](../CONTRIBUTING.md) for the complete process.

**Q: Where are the code examples?**  
A: The [Development Guide](development.md) contains numerous code examples and best practices.

### Support Resources

- **GitHub Issues**: Report bugs or request features
- **Dolibarr Forum**: https://www.dolibarr.org/forum
- **Dolibarr Wiki**: https://wiki.dolibarr.org/
- **Dolibarr Documentation**: https://www.dolibarr.org/doc/html/

## External Resources

### Official Dolibarr Documentation

- [Developer Documentation](https://wiki.dolibarr.org/index.php/Developer_documentation)
- [Module Development](https://wiki.dolibarr.org/index.php/Module_development)
- [Module ID Registry](https://wiki.dolibarr.org/index.php/List_of_modules_id)
- [API Reference](https://www.dolibarr.org/doc/html/)

### MokoStandards

- [MokoConsulting Tech GitHub](https://github.com/mokoconsulting-tech)
- Template Repository: [MokoStandards-Template-Dolibarr](https://github.com/mokoconsulting-tech/MokoStandards-Template-Dolibarr)

## Documentation Conventions

Throughout this documentation, you'll see these conventions:

- **Bold text**: Important concepts or required fields
- `Code formatting`: File names, code snippets, commands
- > Blockquotes: Important notes or warnings
- ✅ Checkmarks: Best practices or recommended actions
- ❌ Cross marks: Things to avoid

### Code Examples

Code examples use syntax highlighting and include comments:

```php
// Example PHP code with explanation
$this->numero = 600001; // Development module ID
```

```bash
# Example command line operations
cd /path/to/dolibarr/
git clone repo-url
```

## Contributing to Documentation

Found an error or want to improve the documentation?

1. Fork the repository
2. Edit the relevant markdown file
3. Submit a pull request
4. Follow the [Contributing Guidelines](../CONTRIBUTING.md)

Good documentation helps everyone!

## Version Information

- **Template Version**: 1.0.0
- **Last Updated**: 2026-01-16
- **Minimum Dolibarr Version**: 12.0
- **PHP Version**: 7.4+

---

**Next Steps**: 
- New to the template? Start with [Installation Guide](installation.md)
- Ready to develop? Check out [Development Guide](development.md)
- Need to request a module ID? Review [Module ID Policy](module-id-policy.md)

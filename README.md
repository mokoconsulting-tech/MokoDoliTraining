# MokoStandards-Template-Dolibarr

A repository template for developing Dolibarr modules according to MokoStandards best practices.

## 📋 Overview

This template provides a standardized structure and documentation framework for developing custom Dolibarr ERP/CRM modules. It follows MokoStandards conventions to ensure consistency, maintainability, and quality across all Dolibarr module projects.

## 🚀 Getting Started

### Prerequisites

- Dolibarr ERP/CRM installation (version 12.0 or higher recommended)
- PHP 7.4 or higher
- Web server (Apache, Nginx, etc.)
- Database (MySQL, MariaDB, or PostgreSQL)

### Installation

1. Clone this repository into your Dolibarr's `custom/` directory:
   ```bash
   cd /path/to/dolibarr/htdocs/custom/
   git clone https://github.com/mokoconsulting-tech/MokoStandards-Template-Dolibarr.git yourmodule
   ```

2. Rename the module directory to match your module name.

3. Configure your module ID according to the [Module ID Policy](#-module-id-policy).

4. Enable the module in Dolibarr:
   - Navigate to **Home → Setup → Modules/Applications**
   - Find your module and click **Activate**

For detailed installation instructions, see [docs/installation.md](docs/installation.md).

## 📦 Module Structure

This template follows the standard Dolibarr module structure:

```
yourmodule/
├── class/              # Business logic classes
├── core/               # Triggers and hooks
├── lang/               # Translation files
├── docs/               # Documentation
├── sql/                # Database scripts
├── css/                # Stylesheets
├── js/                 # JavaScript files
├── img/                # Images and icons
├── modYourmodule.class.php  # Module descriptor
└── README.md           # This file
```

## 🆔 Module ID Policy

**IMPORTANT:** All Dolibarr modules require a unique module ID.

### Temporary Development ID
While developing your module, use a module ID **greater than 600,000** (e.g., 600,001, 600,002, etc.). This range is reserved for private/development modules and doesn't require registration.

### Official Module ID
Before publishing or distributing your module:

1. **Create an issue** in this repository to request an official module ID
2. Use the issue template: "Request Module ID Assignment"
3. Provide the following information:
   - Module name
   - Brief description
   - Organization/Developer name
   - Intended distribution method (private/public/marketplace)

Once approved, you'll be assigned an official module ID from the appropriate range:
- **100,000 - 499,999**: Third-party public modules
- **500,000 - 599,999**: Private or unlisted modules

For more details, see [docs/module-id-policy.md](docs/module-id-policy.md).

## 📖 Documentation

- [Installation Guide](docs/installation.md) - Detailed installation and setup instructions
- [Development Guide](docs/development.md) - Module development guidelines and best practices
- [Module ID Policy](docs/module-id-policy.md) - Module ID assignment process
- [Changelog](docs/changelog.md) - Version history and changes

## 🤝 Contributing

We welcome contributions! Please read our [Contributing Guidelines](CONTRIBUTING.md) before submitting pull requests.

Please also review our:
- [Code of Conduct](CODE_OF_CONDUCT.md) - Community standards and expectations
- [Security Policy](SECURITY.md) - Security vulnerability reporting and best practices

### Quick Contribution Steps

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This template is provided as-is for use in Dolibarr module development projects.

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

**Note**: Your module may use a different license. Please choose an appropriate license for your specific module.

## 🔗 Resources

- [Dolibarr Developer Documentation](https://wiki.dolibarr.org/index.php/Developer_documentation)
- [Dolibarr Module Development Guide](https://wiki.dolibarr.org/index.php/Module_development)
- [Dolibarr Module ID Registry](https://wiki.dolibarr.org/index.php/List_of_modules_id)
- [MokoConsulting Tech](https://github.com/mokoconsulting-tech)

## 💬 Support

- Create an issue in this repository for template-related questions
- For Dolibarr-specific questions, visit the [Dolibarr Forum](https://www.dolibarr.org/forum)
- For module ID requests, create an issue using the "Request Module ID Assignment" template

## 🏷️ Version

Template Version: 1.0.0

---

**Note:** This is a template repository. After cloning, customize all files to match your specific module requirements.

# Hotel Management System

A comprehensive hotel management system built with PHP, featuring room booking, inventory management, AI-powered assistance, and more.

## Features

- **Room Management**: Booking, availability tracking, and room status management
- **Inventory Management**: Bar inventory, menu items, and stock tracking
- **AI Integration**: Maya AI assistant with conversation management and knowledge base
- **User Management**: Multi-role user system with permissions
- **Financial Management**: General ledger, payroll, and financial reporting
- **Maintenance System**: Work orders, parts management, and housekeeping
- **Food & Beverage**: Menu management, order processing, and cart system

## Project Structure

```
Hotel/
├── admin/           # Administrative interfaces
├── api/            # API endpoints
├── assets/         # Static assets (CSS, JS, images)
├── config/         # Configuration files
├── database/       # Database schemas and migrations
├── includes/       # Reusable PHP includes
├── js/            # JavaScript files
├── modules/       # Feature modules
├── maya/          # AI assistant components
└── setup/         # Installation and setup scripts
```

## Getting Started

1. **Prerequisites**
   - XAMPP (Apache + MySQL + PHP)
   - PHP 7.4 or higher
   - MySQL 5.7 or higher

2. **Installation**
   - Clone this repository
   - Configure your database settings in `config/database.php`
   - Run the setup scripts in the `setup/` directory
   - Access the system through your web browser

3. **Configuration**
   - Update database credentials in `config/database.php`
   - Modify system settings in the admin panel
   - Configure AI assistant settings if needed

## Development

### Git Workflow

1. **Initial Setup** (already done):
   ```bash
   git init
   git add .
   git commit -m "Initial commit"
   ```

2. **Daily Development**:
   ```bash
   git add .
   git commit -m "Description of changes"
   git push origin main
   ```

3. **Feature Development**:
   ```bash
   git checkout -b feature/new-feature
   # Make changes
   git add .
   git commit -m "Add new feature"
   git checkout main
   git merge feature/new-feature
   ```

### Code Standards

- Follow PSR-4 autoloading standards
- Use meaningful commit messages
- Include proper error handling
- Document complex functions and classes

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is proprietary software. All rights reserved.

## Support

For support and questions, please contact the development team.

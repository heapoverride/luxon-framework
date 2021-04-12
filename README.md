# Luxon PHP
Luxon PHP is a powerful framework and provides a simple template for your next PHP powered website.

### Prerequisites
- PHP 7.4 or newer
- php-mysqli

### Installation
- Place these files to your webserver's document root (which hopefully is empty)
- Change the default value of APP_SECRET in `config/application.php`
- Configure MySQL database connection and enable it if you need it in `config/database.php`

Note: Luxon's loader will try to load PHP files from certain predefined directories\
and will make them if they don't exist.

### Features
- Lightning fast routing
- Database query builder, ORM and templated queries
- Added security

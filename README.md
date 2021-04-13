# What is Luxon?
Luxon is powerful and minimal framework and provides base for your next website.

### Prerequisites
- PHP 7.4 or newer
- php-mysqli

### Installation
- Place these files to your webserver's document root (which hopefully is empty)
- Change the default value of APP_SECRET in `config/application.php`
- Configure MySQL database connection and enable it if you need it in `config/database.php`

Note: Luxon's loader will try to load PHP files from certain predefined directories\
and will make them if they don't exist (for example, `controllers` and `models`).

### Features
- Lightning fast routing
- Database query builder, ORM and templated queries
- Added security

### Extras
- Enable OPcache in PHP config (php.ini) for extra performance
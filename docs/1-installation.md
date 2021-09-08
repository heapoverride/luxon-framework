# Installation on Ubuntu

### [Optional] Install and configure MariaDB/MySQL server
You can skip this step if you aren't going to use Luxon's `Database` features or if your database server is remote.
```sh
sudo apt update
sudo apt install mariadb-server
sudo mysql_secure_installation
```
Maybe you want to create a database and a user for luxon framework to automatically connect to?\
Run `mariadb` (or `mysql`) to enter mysql commandline client and execute the SQL queries/commands below.
```sql
-- This will create a database 'luxon' for you (feel free to change it to whatever you want to use, I don't mind).
CREATE DATABASE luxon;

-- This command creates a new user 'luxon' and makes it so it is only accessible on localhost and then
-- grants it access to every database and type (*.*).
CREATE USER 'luxon'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON *.* TO 'luxon'@'localhost';
GRANT USAGE ON *.* TO 'luxon'@'localhost' IDENTIFIED BY PASSWORD 'password';
FLUSH PRIVILEGES;

-- You might want to add an another user for remote management later 
-- (I use HeidiSQL for this on Windows because it's so beautiful)
```
Make sure to update database connection info in your `config/database.php`!

### [Optional] Enable remote logins to MySQL/MariaDB server
Add few lines at the bottom of MySQL configuration file and then restart mysql/mariadb\
**/etc/mysql/my.cnf**
```conf
[mysqld]
skip-networking = 0
bind-address = 0.0.0.0
```
```sh
service mariadb restart
```

### Install apache2 web server
```sh
sudo apt install apache2
```
Optionally you can [install NGINX]() instead of apache2.

### Install PHP with cURL and MySQL extensions
```sh
sudo apt install php libapache2-mod-php php-{curl,mysql}
```

### Configure PHP and enable curl and mysqli extensions
Replace `PHP_VERSION` with the PHP version you have installed (check with `ls /etc/php` or `php --version`).
```sh
sudo a2enmod rewrite
sudo nano /etc/php/PHP_VERSION/apache2/php.ini
```
In `php.ini` locate the lines `;extension=mysqli` and `;extension=curl` and remove the\
semicolon in front of those lines to uncomment them.

### Install luxon framework
Notice the period in the `git clone` command ` .`!
```sh
cd /var/www/html
sudo git clone https://github.com/UnrealSecurity/luxon-framework.git .
```

### Create some directories that `git clone` didn't create for us
```sh
cd /var/www/html
sudo mkdir {assets,controllers,models,other}
```

### Restart apache2 web server
This will enable `mod_rewrite` and luxon framework's `.htaccess` rules
```sh
sudo service apache2 restart
```

### Directory permissions
```sh
cd /var/www/html
sudo chown -R www-data:www-data ./
sudo chmod -R 755 ./
```

## Configure luxon framework
All configuration files are located in `config` directory.

### Application configuration
You should disable `APP_REQUIRE_HTTPS` in `config/application.php` if your server doesn't support **HTTPS** yet,\
otherwise luxon will try to redirect you to `https://` site which will not respond.

### Database configuration
Database is not enabled by default.\
Configure and enable it in `config/database.php`.

### To enable .htaccess
For apache web server you should check `/etc/apache2/apache2.conf` and ensure that the `Directory`
directive for your document root (typically /var/www/html) has `AllowOverride All` 
and `Options -Indexes` like in the example below.

**/etc/apache2/apache2.conf**
```apache
<Directory /var/www/html>
        Options -Indexes
        AllowOverride All
        Require all granted
</Directory>
```
**Remember to restart apache2 after you're done!**\
\
You can test that your .htaccess file is working and that routing is 
working by navigating to your website and appending something to the path that 
you do not have a route for. For example `/asd`.\
\
If you get a __Route not Found__ error, this means that your .htaccess file is working.


---
If you're here it hopefully means that luxon is properly installed and you see a text\
`This is the default page for web servers using Luxon.` when you navigate to your website.


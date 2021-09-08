# Installation on Ubuntu

### To install and configure mariadb run the following commands
You can skip this step if you aren't going to use Luxon's `Database` features or if your database server is remote.
```sh
sudo apt update
sudo apt install mariadb-server
sudo mysql_secure_installation
```

### To install apache2 run the following commands
```sh
sudo apt install apache2
```

### To install and configure PHP run the following commands
```sh
sudo apt install php libapache2-mod-php php-mcrypt php-mysql
```

### To configure PHP and enable certain extensions (curl, mysqli!)
```sh
sudo nano /etc/php/7.4/apache2/php.ini
sudo a2enmod rewrite
```

### To install luxon framework run the following commands
Notice the period ` .`!
```sh
cd /var/www/html
sudo git clone https://github.com/UnrealSecurity/luxon-framework.git .
```

### Create some directories that `git clone` didn't create for us
```sh
cd /var/www/html
sudo mkdir {assets,controllers,models,other}
```

### Restart apache2
This will now enable mod-rewrite and luxon framework's .htaccess
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
# What is Luxon?
Luxon is powerful and minimal framework and provides a solid base for your next website.

### Prerequisites
- PHP 7.4 or newer
- php-mysqli

### Installation
- Download this repo as zip file and copy the files in folder `luxon-framework-main` to your webserver's document root (which hopefully is empty)
```bash
# or install luxon from terminal (make sure that '/var/www/html' is your web server's document root and that it is empty)
cd /var/www/html
git clone https://github.com/UnrealSecurity/luxon-framework.git .
chmod -R 777 .
```
- Change the default value of **APP_SECRET** in `config/application.php`
- If your website does not support secure connection through HTTPS make sure to set **APP_REQUIRE_HTTPS** to **false** in `config/application.php`
- Configure MySQL database connection and enable it if you need it in `config/database.php`

**Note:** Luxon's loader will try to load PHP files from certain predefined directories and will make them if they don't exist (for example, `controllers` and `models`). If the loader fails to create a missing directory it will throw an error and you'll see 503 error in your web browser. (check directory permissions!)

### Features
- Lightning fast routing
- Database query builder, ORM and templated queries
- Added security

### Extras
- Enable OPcache in PHP config (php.ini) for extra performance


# 1. Router and routes
Router is one of Luxon's core modules that is used to route incoming request to handler that then takes care of that request. Routes are checked from bottom to top.

### Adding new routes
For example, to route GET requests to our front page we could use something like this
```php
Router::route("GET", "/^\/$/", function() {
    view('index');
});
```

If we have a controller named `FrontController` with static function named `get_frontPage` we could tell Router that we want that function handle this request
```php
Router::route("GET", "/^\/$/", ['FrontController', 'get_frontPage']);
```

We can also specify capture groups if we want to extract certain values from the requested URI. This example shows how you can get product category, subcategory and optional page number from the URI.
This would handle requests like `GET /products/office-supplies/chairs/12/` and `GET /products/office-supplies/chairs/`. I do recommend you use separate controllers with more complex routes like this one.
```php
Router::route("GET", "/^\/products\/([\w\-\_]*)\/([\w\-\_]*)\/?(\d*?)\/?$/", function($maincat, $subcat, $pagenum) {
    // handle request here
    if ($pagenum === "") $pagenum = 0;
    // more code here ...
});
```

# 2. Controllers
In MVC (model-view-controller) model the controller responds to the user input and performs interactions on the data model objects. The controller receives the input, optionally validates it and then passes the input to the model. **Below is a simple login & registration example with Luxon**. You could also create a separate model for these user specific database operations and then from your controller call the user model's methods.

**/routes/routes.php**
```php
<?php

    Router::route("GET", "/^\/$/", ['DemoController', 'viewIndex']);
    Router::route("POST", "/^\/login\/?$/", ['DemoController', 'doLogin']);
    Router::route("POST", "/^\/register\/?$/", ['DemoController', 'doRegister']);

?>
```

**/controllers/DemoController.php**
```php
<?php

    class DemoController {

        public static function viewIndex() {
            if (Session::has('user')) {
                view('clientarea');
            } else {
                view('index');
            }
        }

        public static function doLogin() {
            $username = $_POST['username'];
            $password = $_POST['password'];

            // get one record from table 'users' where username equals $username
            $result = ORM::instance()
                ->select('users')
                ->where(['username', $username])
                ->limit(1)
                ->exec();

            // make sure the QueryResult isn't an error
            if (!$result->isError) {
                // fetch one record from QueryResult and make sure we actually got a record
                if (($row = $result->fetch()) !== null) {
                    // test password
                    if (Password::test($password, $row['salt'], $row['password'])) {
                        Session::set('user', $row);
                    }
                }
            }

            // redirect user back to index
            header('Location: /');
        }

        public static function doRegister() {
            $username = $_POST['username'];
            $email = $_POST['email'];
            $password = $_POST['password'];

            $salt = Password::salt();
            $hash = Password::hash($password, $salt);

            $result = ORM::instance()
                ->insert('users', [
                    'username'  => $username,
                    'email'     => $email,
                    'password'  => $hash,
                    'salt'      => $salt
                ])->exec();

            // do something with $result?
            // ...

            // redirect user back to index
            header('Location: /');
        }

    }

?>
```
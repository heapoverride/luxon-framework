# Router and routes
Incoming request is matched against routes you have defined. If a match is found, the request\
will be then handled by the handler associated with that route, otherwise a __Route not Found__ error will be displayed.

### Route with a handler (function)
```php
Router::route("GET", "/^\/$/", function() {
    echo "Hello world!";
});
```

### Route with a handler (array)
This tells Router to access class `Frontend` and call it's (static) function `Home`.\
**routes/routes.php**
```php
Router::route("GET", "/^\/$/", ["Frontend", "Home"]);
```
**controllers/Frontend.php**
```php
class Frontend {

    // Render the home page
    public static Home() {
        echo "Hello world!";
    }

}
```


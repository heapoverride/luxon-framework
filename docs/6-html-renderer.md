# HTML renderer
This is a feature built into Luxon that you can use but you don't have to!\
Think of it like how you'd create and manipulate DOM elements using JavaScript in browser.

### Code examples

#### Document template
Define a pretty simple re-usable document template\
\
**./other/components/Document.php**
```php
class Document extends Html\Html {

    public $head, $title, $body, $header, $main, $footer;

    function __construct($page = null)
    {
        parent::__construct();

        $this->add(
            ($this->head = new Html\Head())
            ->add(
                ($this->title = new Html\Title($page !== null ? SITE_NAME." - ".$page : SITE_NAME))
            ),
            ($this->body = new Html\Body())
            ->add(
                ($this->header = new Html\Header()),
                ($this->main = new Html\Main()),
                ($this->footer = new Html\Footer())
            )
        );
    }

}
```

#### Using the Document template

**./config/site.php**
```php
define("SITE_NAME", "Demo Site");
```

**./routes/Frontend.php**
```php
Router::route("GET", "/^\/$/", ["Frontend", "Home"]);
```

**./controllers/Frontend.php**
```php
class Frontend {

    /**
     * Render home page
     */
    static function Home() {
        /**
         * Create new document
         */
        $doc = new Document("Home");

        /**
         * Add some content
         */
        $doc->main->add("Lorem ipsum dolor sit amet...");

        /**
         * Print minified source code to visitor's browser
         */
        print($doc);
    }

}
```
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

    public $head, $title, $body, $header, $nav, $main, $footer;

    /**
     * Create new Document with optional page name 
     * that's displayed in the title after the site's name
     * @param string $page
     */
    function __construct($page = null)
    {
        parent::__construct();

        $this->add(
            ($this->head = new Html\Head())->add(
                ($this->title = new Html\Title($page !== null ? SITE_NAME." - ".$page : SITE_NAME)),
                (new Html\Link("icon", "/assets/favicon.png", "image/png")),
                (new Html\Style("/assets/styles/common.css")),
                (new Html\Meta())
                ->set("name", "viewport")
            ),
            ($this->body = new Html\Body())->add(
                ($this->header = new Html\Header())->add(
                    new Html\H1("Lorem ipsum dolor sit amet..."),
                ),
                ($this->nav = new Html\Nav())->add(
                    (new Html\Div())
                    ->set("class", "links")
                    ->add(
                        // navigation links
                        (new Html\A("Home", "/"))
                        ->set("data-page", "home"),

                        (new Html\A("About us", "/about-us/"))
                        ->set("data-page", "about")
                    )
                ),
                ($this->main = new Html\Main()),
                ($this->footer = new Html\Footer())->add(
                    "Copyright © ".date('Y')
                ),
            )
        );
    }

}
```

#### Using the Document template

**./config/application.php**
```php
define("SITE_NAME", "My Website");
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
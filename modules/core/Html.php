<?php

    /**
     * HTML source code generator thing.. something..
     * written by <github.com/UnrealSecurity>
     */

    namespace Html;

    /**
     * Defines a HTML element
     */
    class Element {
        private $name;
        private $children = [];
        private $text = null;
        private $attributes = [];
        private $styles = [];
        private $nobody = false;
        private $before = null;
        private $after = null;
        private $depth = 0;

        function __construct($name) {
            $this->name = strtolower($name);
        }

        private function indent($depth) {
            if ($depth === 0) return "";

            $str = "";
            for ($i = 0; $i<$depth; $i++) {
                $str .= "   ";
            }

            return $str;
        }

        private function sa_str(&$str) {
            $str = str_replace(" ", "-", $str);
            $str = str_replace("\"", "-", $str);
            return $str;
        }

        private function sa_arr(&$arr) {
            for ($i=0; $i<count($arr); $i++) {
                $arr[$i] = $this->sa_str($arr[$i]);
            }
        }

        /**
         * Get the number of children this element holds
         */
        function countChildren() {
            return count($this->children);
        }

        /**
         * Set element's ID
         * @param string|int $id
         * @return Element
         */
        function setId($id) {
            $this->set("id", $id);
            return $this;
        }

        /**
         * Set element's name (forms)
         * @param string|int $name
         * @return Element
         */
        function setName($name) {
            $this->set("name", $name);
            return $this;
        }

        /**
         * Set element's value
         * @param string|int $value
         * @return Element
         */
        function setValue($value) {
            $this->set("value", $value);
            return $this;
        }

        /**
         * Set element's class list
         * @param array $classList
         * @return Element
         */
        function setClassList($classList) {
            $this->sa_arr($classList);
            $this->set("class", implode(" ", $classList));
            return $this;
        }

        /**
         * Set element that appears before this element
         * @param Element|string $element
         * @return Element
         */
        function setBefore($element) {
            $this->before = $element;
            return $this;
        }

        /**
         * Set element that appears after this element
         * @param Element|string $element
         * @return Element
         */
        function setAfter($element) {
            $this->after = $element;
            return $this;
        }

        /**
         * Set displayed text
         * @param string $text
         * @return Element
         */
        function setText($text) {
            $this->text = !is_string($text) ? strval($text) : $text;
            return $this;
        }

        /**
         * Set displayed text by passing a reference to a string variable
         * @param string &$text
         * @return Element
         */
        function setTextRef(&$text) {
            if (!is_string($text)) throw new \Exception();
            $this->text = &$text;
            return $this;
        }

        /**
         * Get if this element is a text element
         * @return bool
         */
        function isText() {
            return $this instanceof Text || $this->text !== null;
        }

        /**
         * Set all child elements
         * @param mixed[] $children
         * @return Element
         */
        function setChildren($children) {
            if (!is_array($children)) throw new \Exception();

            for ($i=0; $i<count($children); $i++) {
                if (!($children[$i] instanceof Element)) {
                    $children[$i] = new Text(strval($children[$i]));
                }
            }

            $this->children = $children;
            return $this;
        }

        private function _add(...$elements) {
            $depth = $this->depth + 1;

            foreach ($elements as $element) {
                if ($element instanceof Element) {
                    $element->depth = $depth;

                    $this->children[] = $element;
                } else {
                    $text = new Text(strval($element));
                    $text->depth = $depth;

                    $this->children[] = $text;
                }
            }

            return $this;
        }

        /**
         * Add child elements
         * @param Element|string $element,...
         * @return Element
         */
        function add(...$elements) {
            foreach ($elements as $element) {
                if (is_array($element)) {
                    $this->_add(...$element);
                } else {
                    $this->_add($element);
                }
            }

            return $this;
        }

        /**
         * Add or set attribute value
         * @param string $name
         * @param string $value
         * @return Element
         */
        function set($name, $value = null) {
            $name = strtolower($name);

            if ($value !== null && !is_string($value)) {
                $value = strval($value);
            }

            $this->attributes[$name] = $value;

            return $this;
        }

        /**
         * Add or set style property
         * @param string $name
         * @param string $value
         * @return Element
         */
        function setStyle($name, $value) {
            $this->styles[$name] = strval($value);
            return $this;
        }

        /**
         * Set if element has a body
         * @param bool $hasBody
         * @return Element
         */
        function setHasBody($hasBody) {
            $this->nobody = !$hasBody;
            return $this;
        }

        /**
         * Get if this element is empty
         * @return bool
         */
        function isEmpty() {
            return count($this->children) === 0 && ($this->text === null || strlen($this->text) === 0);
        }

        /**
         * Get if this element has children
         * @return bool
         */
        function hasChildren() {
            return !$this->isText() && count($this->children) > 0;
        }

        /**
         * Repeat this element n times
         * @param int $times How many times to repeat this element
         * @return Element[]
         */
        function repeat($times) {
            $times = intval($times);
            $array = [];

            for ($i=0; $i<$times; $i++)
                $array[] = $this;

            return $array;
        }

        /**
         * Print the HTML source code
         * @param bool $return Set to `true` to return the generated code instead of writing it to response body
         * @param bool $format Set to `true` to format the generated HTML source code
         * @return string
         */
        function html($return = false, $format = false) {
            $html = [];

            $indent = $format ? $this->indent($this->depth) : "";
            $expand = $format ? (count($this->children) >= 2 || count($this->children) == 1 && !$this->children[0]->isText()) : false;

            // before
            if ($this->before !== null) {
                if ($this->before instanceof Element) {
                    $html[] = $this->before->html(true, $format);
                } else {
                    $html[] = strval($this->before);
                }
            }

            if ($this->text === null) {
                // opening tag
                $html[] = $indent."<".$this->name;

                // attributes
                if (count($this->attributes) !== 0) {
                    foreach ($this->attributes as $name => $value) {
                        if ($value !== null) {
                            $html[] = " $name=\"$value\"";
                        } else {
                            $html[] = " $name";
                        }
                    }
                }

                // styles
                if (count($this->styles) !== 0) {
                    $styles = [];
                    foreach ($this->styles as $name => $value) {
                        $styles[] = "$name: $value;";
                    }
                    $html[] = " styles=\"".implode(" ", $styles)."\"";
                }

                if (!$this->nobody) $html[] = ">";
                if ($format && $expand) $html[] = "\n";
            } else {
                // text
                $html[] = htmlspecialchars($this->text);
            }

            // children
            foreach ($this->children as $child) {
                if (is_string($child)) {
                    $html[] = htmlspecialchars($child);
                } else if ($child instanceof Element) {
                    $child->depth = $this->depth + 1;
                    $html[] = $child->html(true, $format);
                }
            }

            if ($this->text === null) {
                if (!$this->nobody) {
                    // closing tag
                    if ($expand) $html[] = $indent;
                    $html[] = "</".$this->name.">";
                } else {
                    $html[] = " />";
                }
                if ($format) $html[] = "\n";
            }

            // after
            if ($this->after !== null) {
                if ($this->after instanceof Element) {
                    $html[] = $this->after->html(true, $format);
                } else {
                    $html[] = strval($this->after);
                }
            }

            // return HTML source code
            if (!$return) {
                echo implode("", $html);
            } else {
                return implode("", $html);
            }
        }
    }

    /**
     * Text element
     */
    class Text extends Element {
        function __construct($text = null) {
            parent::__construct("");
            if ($text !== null) $this->setText($text);
        }

        function setText($text) {
            parent::setText($text);
        }
    }

    /**
     * TextRef element
     */
    class TextRef extends Element {
        function __construct(&$text) {
            parent::__construct("");
            $this->setTextRef($text);
        }
    }

    /**
     * Represents the root (top-level element) of an HTML document
     */
    class Html extends Element {
        function __construct() {
            parent::__construct("html");
            parent::setBefore("<!DOCTYPE html>\n");
        }
    }

    /**
     * Defines the head section of an HTML document
     */
    class Head extends Element {
        function __construct() {
            parent::__construct("head");
        }
    }

    /**
     * Defines the title or name of an HTML document
     */
    class Title extends Element {
        function __construct($title = null) {
            parent::__construct("title");
            if ($title !== null) parent::setChildren([$title]);
        }
    }

    /**
     * Defines metadata of an HTML document
     */
    class Meta extends Element {
        function __construct() {
            parent::__construct("meta");
        }
    }

    /**
     * Defines the style information for an HTML document
     */
    class Style extends Element {
        function __construct($target = null) {
            if ($target !== null) {
                parent::__construct("link");
                parent::set("rel", "stylesheet");
                parent::set("href", $target);
                parent::set("type", "text/css");
                parent::setHasBody(false);
            } else {
                parent::__construct("style");
            }
        }
    }

    /**
     * Defines the body section of an HTML document
     */
    class Body extends Element {
        function __construct() {
            parent::__construct("body");
        }
    }

    /**
     * Defines a division or section within HTML document
     */
    class Div extends Element {
        function __construct() {
            parent::__construct("div");
        }
    }

    /**
     * Used for styling and grouping inline
     */
    class Span extends Element {
        function __construct() {
            parent::__construct("span");
        }
    }

    /**
     * Defines self-contained content
     */
    class Article extends Element {
        function __construct() {
            parent::__construct("article");
        }
    }

    /**
     * Defines content aside from main content\
     * Mainly represented as sidebar
     */
    class Aside extends Element {
        function __construct() {
            parent::__construct("aside");
        }
    }

    /**
     * Defines additional details which user can either view or hide
     */
    class Details extends Element {
        function __construct() {
            parent::__construct("details");
        }
    }

    /**
     * Used to add a caption or explanation for the Figure element
     */
    class Figcaption extends Element {
        function __construct() {
            parent::__construct("figcaption");
        }
    }

    /**
     * Used to define a caption for a table
     */
    class Caption extends Element {
        function __construct() {
            parent::__construct("caption");
        }
    }

    /**
     * Used to define the title of the work, book, website, ...
     */
    class Cite extends Element {
        function __construct() {
            parent::__construct("cite");
        }
    }

    /**
     * Used to define the self-contained content
     */
    class Figure extends Element {
        function __construct() {
            parent::__construct("figure");
        }
    }

    /**
     * Defines the footer section of a webpage
     */
    class Footer extends Element {
        function __construct() {
            parent::__construct("footer");
        }
    }

    /**
     * Defines the header of a section or webpage
     */
    class Header extends Element {
        function __construct() {
            parent::__construct("header");
        }
    }

    /**
     * Represents the main content of an HTML document
     */
    class Main extends Element {
        function __construct() {
            parent::__construct("main");
        }
    }

    /**
     * Represents a highlighted text
     */
    class Mark extends Element {
        function __construct() {
            parent::__construct("mark");
        }
    }

    /**
     * Represents section of page to represent navigation links
     */
    class Nav extends Element {
        function __construct() {
            parent::__construct("nav");
        }
    }

    /**
     * Defines a generic section for a document
     */
    class Section extends Element {
        function __construct() {
            parent::__construct("section");
        }
    }

    /**
     * Defines summary which can be used with `Details`
     */
    class Summary extends Element {
        function __construct() {
            parent::__construct("summary");
        }
    }

    /**
     * Define data/time within an HTML document
     */
    class Time extends Element {
        function __construct() {
            parent::__construct("time");
        }
    }

    /**
     * Creates a hyperlink or link
     */
    class A extends Element {
        function __construct($target = null) {
            parent::__construct("a");
            if ($target !== null) {
                parent::set("href", $target);
            } else {
                parent::set("href", "#");
            }
        }
    }

    class Hyperlink extends A {
        function __construct($target = null) {
            parent::__construct($target);
        }
    }

    /**
     * Represents a relationship between current document and an external resource
     */
    class Link extends Element {
        function __construct($target) {
            parent::__construct("link");
        }
    }

    /**
     * Defines the area of an image map
     */
    class Area extends Element {
        function __construct() {
            parent::__construct("area");
        }
    }

    /**
     * Used to define a content which is taken from another source
     */
    class Blockquote extends Element {
        function __construct() {
            parent::__construct("blockquote");
        }
    }

    /**
     * Produces a line break in text (carriage-return)
     */
    class Br extends Element {
        function __construct() {
            parent::__construct("br");
            parent::setHasBody(false);
        }
    }

    class LineBreak extends Br {
        function __construct() {
            parent::__construct();
        }
    }

    /**
     * Used to represent a clickable button
     */
    class Button extends Element {
        function __construct() {
            parent::__construct("button");
        }
    }

    /**
     * Used to provide a graphics space within a web document
     */
    class Canvas extends Element {
        function __construct() {
            parent::__construct("canvas");
        }
    }

    /**
     * Used to display a part of programming code in an HTML document
     */
    class Code extends Element {
        function __construct() {
            parent::__construct("code");
        }
    }

    /**
     * Ddefines a column within a `Table` which represent common properties of columns and used with the `Colgroup`
     */
    class Col extends Element {
        function __construct() {
            parent::__construct("col");
        }
    }

    /**
     * Used to define group of columns in a table
     */
    class Colgroup extends Element {
        function __construct() {
            parent::__construct("colgroup");
        }
    }

    /**
     * Used to link the content with the machine-readable translation
     */
    class Data extends Element {
        function __construct() {
            parent::__construct("data");
        }
    }

    /**
     * Used to provide a predefined list for input option
     */
    class Datalist extends Element {
        function __construct() {
            parent::__construct("datalist");
        }
    }

    /**
     * Defines a dialog box or other interactive components
     */
    class Dialog extends Element {
        function __construct() {
            parent::__construct("dialog");
        }
    }

    /**
     * Used as embedded container for external file/application/media
     */
    class Embed extends Element {
        function __construct() {
            parent::__construct("embed");
        }
    }

    /**
     * Used to group related elements/labels within a web form
     */
    class Fieldset extends Element {
        function __construct() {
            parent::__construct("fieldset");
        }
    }

    /**
     * Defines a caption for content of `Fieldset`
     */
    class Legend extends Element {
        function __construct() {
            parent::__construct("legend");
        }
    }

    /**
     * Used to define an HTML form
     */
    class Form extends Element {
        function __construct() {
            parent::__construct("form");
        }
    }

    /**
     * Defines an input field within an HTML form
     */
    class Input extends Element {
        function __construct() {
            parent::__construct("input");
            parent::setHasBody(false);
        }
    }

    /**
     * Used to define multiple line input, such as comment, feedback, and review
     */
    class Textarea extends Element {
        function __construct() {
            parent::__construct("textarea");
        }
    }

    /**
     * Used to declare the JavaScript within HTML document
     */
    class Script extends Element {
        function __construct($source = null) {
            parent::__construct("script");

            if ($source !== null) {
                parent::set("src", $source);
            }
        }
    }

    /**
     * Represents a control which provides a menu of options
     */
    class Select extends Element {
        function __construct() {
            parent::__construct("select");
        }
    }
    
    /**
     * Used to define options or items in a drop-down list (`Select`)
     */
    class Option extends Element {
        function __construct() {
            parent::__construct("option");
        }
    }

    /**
     * Defines an inline frame which can embed other content
     */
    class Iframe extends Element {
        function __construct() {
            parent::__construct("iframe");
        }
    }

    /**
     * Used to present data in tabular form or to create a table within HTML document
     */
    class Table extends Element {
        function __construct() {
            parent::__construct("table");
        }
    }

    /**
     * Defines the header of an HTML table\
     * It is used along with `TableBody` and `TableFooter`
     */
    class Thead extends Element {
        function __construct() {
            parent::__construct("thead");
        }
    }

    class TableHeader extends Thead {
        function __construct() {
            parent::__construct();
        }
    }

    /**
     * Represents the body content of an HTML table and used along with `TableHeader` and `TableFooter`
     */
    class Tbody extends Element {
        function __construct() {
            parent::__construct("tbody");
        }
    }

    class TableBody extends Tbody {
        function __construct() {
            parent::__construct();
        }
    }

    /**
     * Defines the footer content of an HTML table
     */
    class Tfoot extends Element {
        function __construct() {
            parent::__construct("tfoot");
        }
    }

    class TableFooter extends Tfoot {
        function __construct() {
            parent::__construct();
        }
    }

    /**
     * Defines the head cell of an HTML table\
     * Used with `TableHeader`
     */
    class Th extends Element {
        function __construct() {
            parent::__construct("th");
        }
    }
    
    class TableHeaderCell extends Th {
        function __construct() {
            parent::__construct();
        }
    }

    /**
     * Defines the row cells in an HTML table
     */
    class Tr extends Element {
        function __construct() {
            parent::__construct("tr");
        }
    }

    class TableRow extends Tr {
        function __construct() {
            parent::__construct();
        }
    }

    /**
     * Used to define cells of an HTML table which contains table data
     */
    class Td extends Element {
        function __construct() {
            parent::__construct("td");
        }
    }

    class TableCell extends Td {
        function __construct() {
            parent::__construct();
        }
    }

    /**
     * Used to insert an image within an HTML document
     */
    class Img extends Element {
        function __construct($source = "") {
            parent::__construct("img");
            parent::set("src", $source);
            parent::setHasBody(false);
        }
    }

    class Image extends Img {
        function __construct($source = "") {
            parent::__construct($source);
        }
    }

    /**
     * It defines multiple media recourses for different media element such as `Picture`, `Video`, and `Audio`
     */
    class Source extends Element {
        function __construct($source = "", $type = "") {
            parent::__construct("source");
            parent::set("src", $source);
            parent::set("type", $type);
            parent::setHasBody(false);
        }
    }

    /**
     * Defines more than one source elements and one image element
     */
    class Picture extends Element {
        function __construct() {
            parent::__construct("picture");
        }
    }

    /**
     * Used to embed sound content in HTML document
     */
    class Audio extends Element {
        function __construct() {
            parent::__construct("audio");
        }
    }

    /**
     * Used to embed a video content with an HTML document
     */
    class Video extends Element {
        function __construct() {
            parent::__construct("video");
        }
    }

    /**
     * Heading 1
     */
    class H1 extends Element { function __construct() { parent::__construct("h1"); } }
    /**
     * Heading 2
     */
    class H2 extends Element { function __construct() { parent::__construct("h2"); } }
    /**
     * Heading 3
     */
    class H3 extends Element { function __construct() { parent::__construct("h3"); } }
    /**
     * Heading 4
     */
    class H4 extends Element { function __construct() { parent::__construct("h4"); } }
    /**
     * Heading 5
     */
    class H5 extends Element { function __construct() { parent::__construct("h5"); } }
    /**
     * Heading 6
     */
    class H6 extends Element { function __construct() { parent::__construct("h6"); } }

    /**
     * Defines unordered list of items
     */
    class Ul extends Element {
        function __construct() {
            parent::__construct("ul");
        }
    }

    class UnorderedList extends Ul {
        function __construct() {
            parent::__construct();
        }
    }

    /**
     * Defines ordered list of items
     */
    class Ol extends Element {
        function __construct() {
            parent::__construct("ol");
        }
    }

    class OrderedList extends Ol {
        function __construct() {
            parent::__construct();
        }
    }

    /**
     * Used to represent items in list
     */
    class Li extends Element {
        function __construct() {
            parent::__construct("li");
        }
    }

    class ListItem extends Li {
        function __construct() {
            parent::__construct();
        }
    }

    /**
     * Used to define text tracks for `Audio` and `Video`
     */
    class Track extends Element {
        function __construct() {
            parent::__construct("track");
        }
    }

    /**
     * Used to make text font one size smaller than document's base font size
     */
    class Small extends Element {
        function __construct() {
            parent::__construct("small");
        }
    }

    /**
     * Defines preformatted text in an HTML document
     */
    class Pre extends Element {
        function __construct() {
            parent::__construct("pre");
        }
    }

    /**
     * Represents a paragraph in an HTML document
     */
    class P extends Element {
        function __construct() {
            parent::__construct("p");
        }
    }

    class Paragraph extends P {
        function __construct() {
            parent::__construct();
        }
    }

    /**
     * Provides an alternative content if a script type is not supported in browser
     */
    class Noscript extends Element {
        function __construct() {
            parent::__construct("noscript");
        }
    }

    /**
     * Used to define important text
     */
    class Strong extends Element {
        function __construct() {
            parent::__construct("strong");
        }
    }

    /**
     * Used to represent a text in some different voice
     */
    class I extends Element {
        function __construct() {
            parent::__construct("i");
        }
    }

    class Italic extends I {
        function __construct() {
            parent::__construct();
        }
    }

    /**
     * Used to render enclosed text with an underline
     */
    class U extends Element {
        function __construct() {
            parent::__construct("u");
        }
    }

    class Underline extends U {
        function __construct() {
            parent::__construct();
        }
    }

    /**
     * Used to make a text bold
     */
    class B extends Element {
        function __construct() {
            parent::__construct("b");
        }
    }

    class Bold extends B {
        function __construct() {
            parent::__construct();
        }
    }

    /**
     * Used to emphasis the content applied within this element
     */
    class Em extends Element {
        function __construct() {
            parent::__construct("em");
        }
    }

    class Emphasis extends Em {
        function __construct() {
            parent::__construct();
        }
    }

    /**
     * Defines a text label for `Input` of `Form`
     */
    class Label extends Element {
        function __construct() {
            parent::__construct("label");
        }
    }

    /**
     * Defines a text which displays as a subscript text
     */
    class Sub extends Element {
        function __construct() {
            parent::__construct("sub");
        }
    }

    class Subscript extends Sub {
        function __construct() {
            parent::__construct();
        }
    }

    /**
     * Defines a text which displays as a superscript text
     */
    class Sup extends Element {
        function __construct() {
            parent::__construct("sup");
        }
    }

    class Superscript extends Sup {
        function __construct() {
            parent::__construct();
        }
    }

?>
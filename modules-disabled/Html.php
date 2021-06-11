<?php

    /**
     * HTML source code generator thing.. something..
     * written by <github.com/UnrealSecurity>
     */

    namespace Html;

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
         * Set element's ID
         */
        function setId($id) {
            $this->set("id", $id);
        }

        /**
         * Set element's class list
         * @param array $classList
         */
        function setClassList($classList) {
            $this->sa_arr($classList);
            $this->set("class", implode(" ", $classList));
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
            $this->text = strval($text);
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
            if (!is_array($children)) {
                throw new \Exception();
            }
            for ($i=0; $i<count($children); $i++) {
                if (!($children[$i] instanceof Element)) {
                    $children[$i] = htmlspecialchars(strval($children[$i]));
                }
            }
            $this->children = $children;
            return $this;
        }

        /**
         * Add child element
         * @param Element|string $element
         * @return Element
         */
        function add($element) {
            if ($element instanceof Element) {
                $element->depth = $this->depth + 1;
                $this->children[] = $element;
            } else {
                $text = new Text(strval($element));
                $text->depth = $this->depth + 1;
                $this->children[] = $text;
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
     * Html element
     */
    class Html extends Element {
        function __construct() {
            parent::__construct("html");
            parent::setBefore("<!DOCTYPE html>\n");
        }
    }

    /**
     * Head element
     */
    class Head extends Element {
        function __construct() {
            parent::__construct("head");
        }
    }

    /**
     * Title element
     */
    class Title extends Element {
        function __construct($title = null) {
            parent::__construct("title");
            if ($title !== null) parent::setChildren([$title]);
        }
    }

    /**
     * Meta element
     */
    class Meta extends Element {
        function __construct() {
            parent::__construct("meta");
        }
    }

    /**
     * Style element
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
     * Body element
     */
    class Body extends Element {
        function __construct() {
            parent::__construct("body");
        }
    }

    /**
     * Div element
     */
    class Div extends Element {
        function __construct() {
            parent::__construct("div");
        }
    }

    /**
     * Span element
     */
    class Span extends Element {
        function __construct() {
            parent::__construct("span");
        }
    }

    /**
     * Article element
     */
    class Article extends Element {
        function __construct() {
            parent::__construct("article");
        }
    }

    /**
     * Aside element
     */
    class Aside extends Element {
        function __construct() {
            parent::__construct("aside");
        }
    }

    /**
     * details element
     */
    class Details extends Element {
        function __construct() {
            parent::__construct("details");
        }
    }

    /**
     * Figcaption element
     */
    class Figcaption extends Element {
        function __construct() {
            parent::__construct("figcaption");
        }
    }

    /**
     * Caption element
     */
    class Caption extends Element {
        function __construct() {
            parent::__construct("caption");
        }
    }

    /**
     * Cite element
     */
    class Cite extends Element {
        function __construct() {
            parent::__construct("cite");
        }
    }

    /**
     * Figure element
     */
    class Figure extends Element {
        function __construct() {
            parent::__construct("figure");
        }
    }

    /**
     * Footer element
     */
    class Footer extends Element {
        function __construct() {
            parent::__construct("footer");
        }
    }

    /**
     * Header element
     */
    class Header extends Element {
        function __construct() {
            parent::__construct("header");
        }
    }

    /**
     * Main element
     */
    class Main extends Element {
        function __construct() {
            parent::__construct("main");
        }
    }

    /**
     * Mark element
     */
    class Mark extends Element {
        function __construct() {
            parent::__construct("mark");
        }
    }

    /**
     * Nav element
     */
    class Nav extends Element {
        function __construct() {
            parent::__construct("nav");
        }
    }

    /**
     * Section element
     */
    class Section extends Element {
        function __construct() {
            parent::__construct("section");
        }
    }

    /**
     * Summary element
     */
    class Summary extends Element {
        function __construct() {
            parent::__construct("summary");
        }
    }

    /**
     * Time element
     */
    class Time extends Element {
        function __construct() {
            parent::__construct("time");
        }
    }

    /**
     * A element
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
     * Link element
     */
    class Link extends Element {
        function __construct($target) {
            parent::__construct("link");
        }
    }

    /**
     * Area element
     */
    class Area extends Element {
        function __construct() {
            parent::__construct("area");
        }
    }

    /**
     * Blockquote element
     */
    class Blockquote extends Element {
        function __construct() {
            parent::__construct("blockquote");
        }
    }

    /**
     * Button element
     */
    class Button extends Element {
        function __construct() {
            parent::__construct("button");
        }
    }

    /**
     * Canvas element
     */
    class Canvas extends Element {
        function __construct() {
            parent::__construct("canvas");
        }
    }

    /**
     * Code element
     */
    class Code extends Element {
        function __construct() {
            parent::__construct("code");
        }
    }

    /**
     * Col element
     */
    class Col extends Element {
        function __construct() {
            parent::__construct("col");
        }
    }

    /**
     * Colgroup element
     */
    class Colgroup extends Element {
        function __construct() {
            parent::__construct("colgroup");
        }
    }

    /**
     * Data element
     */
    class Data extends Element {
        function __construct() {
            parent::__construct("data");
        }
    }

    /**
     * Datalist element
     */
    class Datalist extends Element {
        function __construct() {
            parent::__construct("datalist");
        }
    }

    /**
     * Dialog element
     */
    class Dialog extends Element {
        function __construct() {
            parent::__construct("dialog");
        }
    }

    /**
     * Embed element
     */
    class Embed extends Element {
        function __construct() {
            parent::__construct("embed");
        }
    }

    /**
     * Fieldset element
     */
    class Fieldset extends Element {
        function __construct() {
            parent::__construct("fieldset");
        }
    }

    /**
     * Form element
     */
    class Form extends Element {
        function __construct() {
            parent::__construct("form");
        }
    }

    /**
     * Input element
     */
    class Input extends Element {
        function __construct() {
            parent::__construct("input");
            parent::setHasBody(false);
        }
    }

    /**
     * Textarea element
     */
    class Textarea extends Element {
        function __construct() {
            parent::__construct("textarea");
        }
    }

    /**
     * Script element
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
     * Select element
     */
    class Select extends Element {
        function __construct() {
            parent::__construct("select");
        }
    }
    
    /**
     * Option element
     */
    class Option extends Element {
        function __construct() {
            parent::__construct("option");
        }
    }

    /**
     * Iframe element
     */
    class Iframe extends Element {
        function __construct() {
            parent::__construct("iframe");
        }
    }

    /**
     * Table element
     */
    class Table extends Element {
        function __construct() {
            parent::__construct("table");
        }
    }

    /**
     * Thead element
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
     * Tbody element
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
     * Tfoot element
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
     * Th element (parent: thead)
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
     * Tr element (parent: table|thead|tbody|tfoot)
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
     * Td element (parent: table|tbody|tfoot)
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
     * Img element
     */
    class Img extends Element {
        function __construct($source = "") {
            parent::__construct("img");
            parent::set("src", $source);
            parent::setHasBody(false);
        }
    }
    class Image extends Img {
        function __construct() {
            parent::__construct();
        }
    }

    /**
     * Source element (parent: audio|video)
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
     * Audio element
     */
    class Audio extends Element {
        function __construct() {
            parent::__construct("audio");
        }
    }

    /**
     * Video element
     */
    class Video extends Element {
        function __construct() {
            parent::__construct("video");
        }
    }

    /**
     * H1..H6 elements (headings)
     */
    class H1 extends Element { function __construct() { parent::__construct("h1"); } }
    class H2 extends Element { function __construct() { parent::__construct("h2"); } }
    class H3 extends Element { function __construct() { parent::__construct("h3"); } }
    class H4 extends Element { function __construct() { parent::__construct("h4"); } }
    class H5 extends Element { function __construct() { parent::__construct("h5"); } }
    class H6 extends Element { function __construct() { parent::__construct("h6"); } }

    /**
     * Ul element (unordered list)
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
     * Ol element (ordered list)
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
     * Li element (ordered list)
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
     * Track element (parent: audio|video)
     */
    class Track extends Element {
        function __construct() {
            parent::__construct("track");
        }
    }

    /**
     * Small element
     */
    class Small extends Element {
        function __construct() {
            parent::__construct("small");
        }
    }

    /**
     * Pre element
     */
    class Pre extends Element {
        function __construct() {
            parent::__construct("pre");
        }
    }

    /**
     * Paragraph element
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
     * Noscript element
     */
    class Noscript extends Element {
        function __construct() {
            parent::__construct("noscript");
        }
    }

    /**
     * Strong element
     */
    class Strong extends Element {
        function __construct() {
            parent::__construct("strong");
        }
    }

    /**
     * Italic element
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
     * Underline element
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
     * Bold element
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
     * Em element
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
     * Label element
     */
    class Label extends Element {
        function __construct() {
            parent::__construct("label");
        }
    }

    /**
     * Sub element (subscripted text)
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
     * Sup element (superscripted text)
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
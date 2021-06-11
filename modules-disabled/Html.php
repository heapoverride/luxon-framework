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

        function __construct($name) {
            $this->name = strtolower($name);
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
                $this->children[] = $element;
            } else {
                $this->children[] = new Text(strval($element));
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
         * Print the HTML source code
         * @param bool $return Set to True to return the generated code instead of writing it to response body
         * @return string
         */
        function html($return = false) {
            $html = [];

            // before
            if ($this->before !== null) {
                if ($this->before instanceof Element) {
                    $html[] = $this->before->html();
                } else {
                    $html[] = strval($this->before);
                }
            }

            if ($this->text === null) {
                // opening tag
                $html[] = "<".$this->name;

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
            } else {
                // text
                $html[] = htmlspecialchars($this->text);
            }

            // children
            foreach ($this->children as $child) {
                if (is_string($child)) {
                    $html[] = htmlspecialchars($child);
                } else if ($child instanceof Element) {
                    $html[] = $child->html(true);
                }
            }

            if ($this->text === null) {
                if (!$this->nobody) {
                    // closing tag
                    $html[] = "</".$this->name.">";
                } else {
                    $html[] = "/>";
                }
            }

            // after
            if ($this->after !== null) {
                if ($this->after instanceof Element) {
                    $html[] = $this->after->html();
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
            parent::setBefore("<!DOCTYPE html>");
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
        function __construct($target) {
            parent::__construct("a");
            parent::set("href", $target);
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

    /**
     * Tbody element
     */
    class Tbody extends Element {
        function __construct() {
            parent::__construct("tbody");
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

    /**
     * Th element (parent: thead)
     */
    class Th extends Element {
        function __construct() {
            parent::__construct("th");
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

    /**
     * Td element (parent: table|tbody|tfoot)
     */
    class Td extends Element {
        function __construct() {
            parent::__construct("td");
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
    class UnorderedList extends Element {
        function __construct() {
            parent::__construct("ul");
        }
    }

    /**
     * Ol element (ordered list)
     */
    class OrderedList extends Element {
        function __construct() {
            parent::__construct("ol");
        }
    }

    /**
     * Li element (ordered list)
     */
    class ListItem extends Element {
        function __construct() {
            parent::__construct("li");
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
    class Paragraph extends Element {
        function __construct() {
            parent::__construct("p");
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
    class Italic extends Element {
        function __construct() {
            parent::__construct("i");
        }
    }

    /**
     * Underline element
     */
    class Underline extends Element {
        function __construct() {
            parent::__construct("u");
        }
    }

    /**
     * Bold element
     */
    class Bold extends Element {
        function __construct() {
            parent::__construct("b");
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

    /**
     * Sub element (subscripted text)
     */
    class Sub extends Element {
        function __construct() {
            parent::__construct("sub");
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

?>
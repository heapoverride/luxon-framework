<?php

    /**
     * A small utility to assist with pagination\
     * written by <github.com/UnrealSecurity>
     */
    class Paginator {
        private $totalItems;
        private $pageNum;
        private $pageSize;
        private $maxNeighbors;

        /**
         * @param integer $totalItems Total number of items to paginate
         * @param integer $pageNum Current page number
         * @param integer $pageSize Maximum number of items per page
         * @param integer $maxNeighbors Maximum number of pages on either side of currently active page
         */
        public function __construct($totalItems, $pageNum, $pageSize, $maxNeighbors) {
            $this->totalItems = ($totalItems < 0 ? 0 : $totalItems);
            $this->pageNum = ($pageNum < 1 ? 1 : $pageNum);
            $this->pageSize = ($pageSize < 0 ? 0 : $pageSize);
            $this->maxNeighbors = ($maxNeighbors < 1 ? 1 : $maxNeighbors);
        }

        /**
         * Gets the current start offset (determined by current page number and page size)
         * @return integer
         */
        public function getOffset() {
            return ($this->pageNum - 1) * $this->pageSize;
        }

        /**
         * Gets total number of pages
         */
        public function getNumPages() {
            return ceil($this->totalItems / $this->pageSize);
        }

        /**
         * Gets the set page size
         */
        public function getPageSize() {
            return $this->pageSize;
        }

        /**
         * Run Paginator and return array of PaginatorPage objects
         * @return PaginatorPage[]
         */
        public function run() {
            if ($this->totalItems <= 0 || $this->pageNum < 1 || $this->pageSize <= 0) return null;

            $numPages = $this->getNumPages();
            $pageNum = $this->pageNum - 1;
            $array = [];

            for ($i = $pageNum - $this->maxNeighbors; $i < $pageNum; $i++) {
                if ($i < 0) continue;
                $page = new PaginatorPage();
                $page->Number = $i + 1;
                $page->IsActive = false;
                $array[] = $page;
            }

            $page = new PaginatorPage();
            $page->Number = $pageNum + 1;
            $page->IsActive = true;
            $array[] = $page;

            $next = $pageNum + 1;
            for ($i = $next; $i > 0 && $i < $next + $this->maxNeighbors && $i < $numPages; $i++) {
                $page = new PaginatorPage();
                $page->Number = $i + 1;
                $page->IsActive = false;
                $array[] = $page;
            }

            return $array;
        }
    }

    class PaginatorPage {
        /**
         * Page number
         * @return integer
         */
        public $Number;
        /**
         * Set to true if this page is the current page
         * @return boolean
         */
        public $IsActive;
    }

?>
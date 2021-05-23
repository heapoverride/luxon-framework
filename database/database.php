<?php

    class Database {
        private static $connection = null;

        /**
         * Try to connect to database server
         */
        public static function connect() {
            self::$connection = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
            if (self::$connection->connect_errno) {
                throw new Exception('Database connection failed: '.self::$connection->connect_error);
            }
            self::$connection->set_charset("utf8mb4");
        }

        /** 
         * Return the underlying mysqli instance
         * @return mysqli
         */
        public static function mysqli() {
            return self::$connection;
        }

        /**
         * Constructs a safe query string using a query template\
         * Example: Database::template('INSERT INTO users $ VALUES ?', ['firstName', 'lastName'], ['John', 'Connor'])
         * @param string $template Query template
         * @param {...mixed} Rest of the arguments are used to fill the template
         * @return string
         */
        public static function template($template, ...$data) {
            $array = str_split($template);
            $j = 0;
            for ($i = 0; $i < count($array); $i++) {
                if ($array[$i] === '&') {
                    $value = $data[$j++];
                    $d = self::get_array_d($value);

                    if ($d === 0) {
                        if (!self::is_valid_field_name($value)) {
                            throw new Exception();
                        }
                        $array[$i] = self::escape($value, false);
                    } else if ($d === 1) {
                        $select = [];
                        foreach ($value as &$v) {
                            if (!self::is_valid_field_name($v)) {
                                throw new Exception();
                            }
                            $select[] = self::escape($v, false);
                        }
                        $array[$i] = implode(',', $select);
                    }
                } else if ($array[$i] === '?') {
                    $value = $data[$j++];
                    $d = self::get_array_d($value);

                    if ($d === 0) {
                        $array[$i] = self::escape($value);
                    } else if ($d === 1) {
                        $array[$i] = self::escape_array($value);
                    } else if ($d === 2) {
                        $array[$i] = self::escape_array_2d($value);
                    }
                } else if ($array[$i] === '$') {
                    $value = $data[$j++];
                    $d = self::get_array_d($value);

                    if ($d === 0) {
                        if (!self::is_valid_field_name($value)) {
                            throw new Exception();
                        }
                        $array[$i] = self::escape($value, false);
                    } else if ($d === 1) {
                        foreach ($value as $v) {
                            if (!self::is_valid_field_name($v)) {
                                throw new Exception();
                            }
                        }
                        $array[$i] = self::escape_array($value, false);
                    } else if ($d === 2) {
                        foreach ($value as $v1) {
                            foreach ($v1 as $v2) {
                                if (!self::is_valid_field_name($v2)) {
                                    throw new Exception();
                                }
                            }
                        }
                        $array[$i] = self::escape_array_2d($value, false);
                    }
                }
            }
            return implode('', $array);
        }
        
        /**
         * Executes a database query (unsafe)
         * @param string $query SQL query string
         * @return QueryResult
         */
        public static function query($query) {
            $result = self::$connection->query($query);
            return new QueryResult($result);
        }

        /**
         * Gets last insert ID (MariaDB)
         * @return integer|null
         */
        public static function getLastInsertId() {
            $result = self::query("SELECT LAST_INSERT_ID() AS Id");
            return intval($result->fetch()["Id"]);
        }

        public static function escape($value, $quotes = true) {
            if ($value === null) {
                return 'NULL';
            } else if (is_bool($value)) {
                return $value ? 'TRUE' : 'FALSE';
            } else if (!is_string($value) && is_numeric($value)) {
                return strval($value);
            }
            
            $value = str_replace("\\", "\\\\", $value);
            $value = str_replace('\'', '\\\'', strval($value));
            return $quotes ? "'".$value."'" : $value;
        }

        public static function escape_array($array, $quotes = true) {
            for ($i = 0; $i < count($array); $i++) {
                $array[$i] = self::escape($array[$i], $quotes);
            }
            return "(".implode(',', $array).")";
        }

        public static function escape_array_2d($array, $quotes = true) {
            for ($i = 0; $i < count($array); $i++) {
                $array[$i] = self::escape_array($array[$i], $quotes);
            }
            return implode(',', $array);
        }

        public static function is_valid_field_name($name) {
            $name = strval($name);
            return preg_match('/^.[A-z0-9_\.\(\)]*$/', $name) === 1;
        }

        public static function get_array_d($array) {
            if (is_array($array)) {
                if (count($array) > 0 && is_array($array[0])) {
                    return 2;
                }
                return 1;
            }
            return 0;
        }
    }

    class QueryResult {
        /**
         * Underlying mysqli_result object
         */
        public $result = null;
        /**
         * Boolean value set to true if there were any errors with this query
         */
        public $isError = null;

        function __construct($mysqli_result) {
            $this->result = $mysqli_result;
            $this->isError = !$mysqli_result;
        }

        /**
         * Fetch next available row as an associative array (returns null when no more rows are available)
         * @return array|null
         */
        public function fetch() {
            return $this->result->fetch_assoc();
        }

        /**
         * Count number of rows returned by the query
         * @return integer
         */
        public function count() {
            return mysqli_num_rows($this->result) || 0;
        }
    }
    
    if (DB_CONNECT) Database::connect(); 

?>
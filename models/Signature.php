<?php

    class Signature {
        public $id;
        public $nick;
        public $ip;

        public function __construct() {
            
        }

        public static function getById($id) {
            $query = Database::template('SELECT * FROM signatures WHERE id = $ LIMIT 1',
                                        intval($id));
            $result = Database::query($query);

            if (!$result->error && $result->count() == 1) {
                $row = $result->fetch();

                if ($row != null) {
                    $sig = new Signature();
                    $sig->id = $row['id'];
                    $sig->nick = $row['nick'];
                    $sig->ip = $row['ip'];

                    return $sig;
                }
            }

            return null;
        }

        public static function getAll() {
            $result = Database::query('SELECT * FROM signatures');
            $array = array();

            if (!$result->error) {
                while (($row = $result->fetch()) != null) {
                    $sig = new Signature();
                    $sig->id = $row['id'];
                    $sig->nick = $row['nick'];
                    $sig->ip = $row['ip'];
                    
                    $array[] = $sig;
                }
            }

            return $array;
        }
    }

?>
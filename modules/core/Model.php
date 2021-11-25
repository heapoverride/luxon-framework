<?php

/**
 * Minimal database model for Luxon framework\
 * written by <github.com/UnrealSecurity>
 */

class ColumnValue {
    public $value = null;
    public $changed = false;

    /**
     * Create new column value
     * @param string $value Column's value
     */
    function __construct($value) {
        $this->value = $value;
    }
}

class ColumnDefinition {
    public $primary = false;
    public $type = null;

    /**
     * Create new column definition
     * @param string $type Column's data type
     */
    function __construct($type)
    {
        $this->type = $type;
    }
}

class Model {
    protected $table = null;
    protected $columns = [];

    /**
     * Define new model
     * @param string $table Table name
     * @param array $columns Table column definitions
     * @return Model
     */
    static function define($table, $columns)
    {
        $model = new Model();
        $model->table = $table;

        $hasPk = false;

        foreach ($columns as $key => $array) {
            $definition = new ColumnDefinition($array["type"]);
            
            if (isset($array["primary"]) && $array["primary"] === true) {
                $definition->primary = true;
                $hasPk = true;
            }

            $model->columns[$key] = [$definition, null];
        }

        if (!$hasPk) { throw new Exception("Model definition must have a primary key column!"); }
        
        return $model;
    }

    /**
     * Get default options
     * @param array [$options]
     */
    private static function getOptions($options = null) {
        $defaults = [
            "where" => [],
            "order" => [
                "order" => "ASC",
                "columns" => []
            ],
            "limit" => []
        ];

        /**
         * Override default options
         */
        if ($options !== null) {
            foreach ($options as $key => $value) {
                $defaults[$key] = $value;
            }
        }

        return $defaults;
    }

    /**
     * Convert value to specific type
     * @param mixed $value
     * @param string $type "int" | "float" | "double" | "string" | "json"
     */
    private static function convert($value, $type) {
        if ($type === "int") {
            return intval($value);
        }
        else if ($type === "float") {
            return floatval($value);
        }
        else if ($type === "double") {
            return doubleval($value);
        }
        else if ($type === "string") {
            return strval($value);
        }
        else if ($type === "json") {
            return json_decode(strval($value));
        }

        return null;
    }

    /**
     * Get model or `false` on error
     * @param array [$options]
     * @return Model|false
     */
    function getOne($options = null) {
        $options = self::getOptions($options);
        $columns = array_keys($this->columns);
        
        $result = ORM::instance()
            ->select($this->table, $columns)
            ->where(...$options["where"])
            ->exec();

        if (!$result->isError && $result->count() === 1) {
            $row = $result->fetch();
            
            $model = new Model();
            $model->table = $this->table;

            foreach ($columns as $column) {
                $value = self::convert($row[$column], $this->columns[$column][0]->type);
                $model->columns[$column] = [
                    $this->columns[$column][0], 
                    new ColumnValue($value)
                ];
            }

            $pk = $this->findPK();
            if ($model->columns[$pk][0]->type === "int") {
                $model->columns[$pk][1]->value = intval($model->columns[$pk][1]->value);
            }

            return $model;
        }

        return false;
    }

    /**
     * Get model by it's primary key or `false` on error
     * @param int $pk Primary key
     */
    function getByPK($pk) {
        return $this->getOne([
            "where" => [$this->findPK(), $pk]
        ]);
    }

    /**
     * Get array of models or `false` on error
     * @param array [$options]
     * @return Model[]|false
     */
    function getMany($options = null) {
        $options = self::getOptions($options);
        $columns = array_keys($this->columns);
        $models = [];
        
        $result = ORM::instance()
            ->select($this->table, $columns)
            ->where(...$options["where"])
            ->orderBy($options["order"]["columns"], $options["order"]["order"])
            ->limit(...$options["limit"])
            ->exec();

        if (!$result->isError && $result->count() > 0) {
            while ($row = $result->fetch()) {
                $model = new Model();
                $model->table = $this->table;
    
                foreach ($columns as $column) {
                    $value = self::convert($row[$column], $this->columns[$column][0]->type);
                    $model->columns[$column] = [
                        $this->columns[$column][0], 
                        new ColumnValue($value)
                    ];
                }
    
                $models[] = $model;
            }

            return $models;
        }

        return false;
    }

    /**
     * Find primary key column from column definitions
     * @return string|null
     */
    private function findPK() {
        foreach ($this->columns as $column => $array) {
            if ($array[0]->primary === true) return $column;
        }

        return null;
    }

    /**
     * Get column's value
     * @param string $column Column name
     * @return mixed
     */
    function get($column) {
        return $this->columns[$column][1]->value;
    }

    /**
     * Set column's value
     * @param string $column Column name
     * @param mixed $value Column value
     */
    function set($column, $value) {
        $columnValue = $this->columns[$column][1];
        $columnValue->value = self::convert($columnValue->value, $this->columns[$column][0]->type);

        if ($columnValue->value !== $value) {
            $columnValue->changed = true;
        }

        $columnValue->value = $value;
    }

    /**
     * Save changed columns and return `true` on success and `false` on error
     * @return bool
     */
    function save() {
        $update = [];

        foreach ($this->columns as $column => $array) {
            if ($array[1]->changed === true) {
                if ($array[0]->type === "json") {
                    $update[$column] = json_encode($array[1]->value);
                }
                else {
                    $update[$column] = $array[1]->value;
                }
            }
        }

        $pk = $this->findPK();
        
        $result = ORM::instance()
            ->update($this->table, $update)
            ->where($pk, $this->columns[$pk][1]->value)
            ->exec();

        if ($result->isError) return false;

        foreach ($this->columns as $column => $array) {
            $array[1]->changed = false;
        }

        return true;
    }

    /**
     * Create and return a new model or `false` on error
     * @param array
     * @return Model|false
     */
    function create($columns) {
        $model = new Model();
        $model->table = $this->table;

        foreach ($this->columns as $column => $array) {
            $model->columns[$column] = [
                $array[0], 
                new ColumnValue(null)
            ];
        }

        $array = [];

        foreach ($columns as $column => $value) {
            $model->columns[$column][1] = new ColumnValue($value);

            if ($this->columns[$column][0]->type === "json") {
                $array[$column] = json_encode($value);
            }
            else {
                $array[$column] = $value;
            }
        }
        
        $result = ORM::instance()
            ->insert($this->table, $array)
            ->exec();

        if (!$result->isError) {
            $model->columns[$this->findPK()][1]->value = Database::getLastInsertId();
            return $model;
        }

        return false;
    }

    /**
     * Delete model and return `true` on success and `false` on error
     * @return bool
     */
    function delete() {
        $pk = $this->findPK();
        
        $result = ORM::instance()
            ->deleteFrom($this->table)
            ->where($pk, $this->columns[$pk][1]->value)
            ->exec();

        return !$result->isError;
    }
}
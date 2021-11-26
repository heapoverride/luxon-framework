<?php

class ORM {
	private $array = [];
	private $ops = ['=', '>', '<', '>=', '<=', '<>', '!=', 'LIKE'];

	/**
	 * Get new instance of ORM
	 * @return ORM
	 */
	public static function instance() {
		return new ORM();
	}

	private function _is_valid_field_name($name) {
		if (!is_string($name)) return false;
		return preg_match('/^[A-Z0-9 \.\-_]*$/i', $name) === 1;
	}

	/**
	 * Select data from a database
	 * @param string $table Table name
	 * @param null|string[] $columns Columns to select (null or "*" to select all columns)
	 * @return ORM
	 */
	public function select($table, $columns = null) {
		if ($columns === null || $columns === '*') {
			$columns = ['*'];
		} else {
			for ($i=0; $i<count($columns); $i++) {
				if (is_array($columns[$i])) {
					$columns[$i] = implode(" AS ", [
						Database::escape_field_array($columns[$i][0]),
						Database::escape_field_array($columns[$i][1])
					]);
				} else {
					$columns[$i] = Database::escape_field($columns[$i]);
				}
			}
		}

		$this->array[] = "SELECT ".implode(', ', $columns)." FROM ".Database::escape_field($table);
		return $this;
	}

	private function _has_no_arrays($condition) {
		foreach ($condition as $el) {
			if (is_array($el)) return false;
		}
		return true;
	}

	private function _where($inverted, ...$condition) {
		if (count($condition) === 0) return $this;

		$array = [];

		$array[] = "WHERE";
		if ($inverted) { $array[] = 'NOT'; }

		if ((count($condition) === 2 || count($condition) === 3) && $this->_has_no_arrays($condition)) {
			$condition = [$condition];
		}

		foreach ($condition as $element) {
			if (is_string($element)) {
				$op = strtoupper($element);
				if ($op === 'AND' || $op === 'OR') {
					$array[] = $op;
				}
			} else if (is_array($element)) {
				if (count($element) === 2) {
					$L = $element[0];
					$R = $element[1];

					$array[] = Database::escape_field($L).' = '.Database::escape($R);
				} else if (count($element) === 3) {
					$L = $element[0];
					$R = $element[2];

					$op = strtoupper(strval($element[1]));
					if (!in_array($op, $this->ops)) throw new Exception('Invalid compare operator');

					$array[] = Database::escape_field($L).' '.$op.' '.Database::escape($R);
				}
			}
		}

		$this->array[] = implode(' ', $array);
		return $this;
	}

	/**
	 * Perform operation on records where condition is met
	 * @param string|mixed[] ...$condition Example: where(['column', '=', 'value'], 'AND', ['column', '=', 'value'])
	 * @return ORM
	 */
	public function where(...$condition) {
		return $this->_where(false, ...$condition);
	}

	/**
	 * Perform operation on records where condition is met
	 * @param string|mixed[] ...$condition Example: whereNot(['column', '=', 'value'])
	 * @return ORM
	 */
	public function whereNot(...$condition) {
		return $this->_where(true, ...$condition);
	}

	/**
	 * Limit amount of records that will be returned (if two parameters are given the first is used to skip n records)
	 * @param integer ...$limit Accepts max 2 parameters
	 * @return ORM
	 */
	public function limit(...$limit) {
		if (count($limit) === 0) return $this;

		for ($i=0; $i<count($limit); $i++) {
			if (!is_numeric($limit[$i])) {
				$limit[$i] = intval($limit[$i]);
			}
		}

		if (count($limit) == 1) {
			$this->array[] = "LIMIT ".strval($limit[0]);
		} else if (count($limit) == 2) {
			$this->array[] = "LIMIT ".strval($limit[0]).", ".strval($limit[1]);
		}

		return $this;
	}

	/**
	 * Insert new record(s) to table
	 * @param string $table Table name
	 * @param mixed[] $data Associative array or simple array if values for all fields are provided
	 */
	public function insert($table, $data) {
		$d = Database::get_array_d($data);

		if ($d == 1) {
			$keys = array_keys($data);
		} else if ($d == 2) {
			$keys = array_keys($data[0]);
		}

		$assoc = true;
		$n = 0;
		foreach ($keys as $key) {
			if (is_numeric($key)) $n++;
		}
		if ($n === count($keys)) $assoc = false;

		$array = [];
		$array[] = 'INSERT INTO '.Database::escape_field($table);
		if ($assoc) $array[] = Database::escape_field_array_brackets($keys);

		if ($d == 1) {
			if ($assoc) {
				$values = [];

				foreach ($data as $column => $value) {
					if (!$this->_is_valid_field_name($column)) throw new Exception('Column name is invalid');
					$values[] = $value;
				}

				$array[] = 'VALUES '.Database::escape_array($values, true);
			} else {
				$array[] = 'VALUES '.Database::escape_array($data, true);
			}
		} else if ($d == 2) {
			if ($assoc) {
				$values = [];
				$i = 0;

				foreach ($data as $row) {
					foreach ($row as $column => $value) {
						if (!$this->_is_valid_field_name($column)) throw new Exception('Column name is invalid');
						$values[$i][] = $value;
					}
					$i++;
				}

				$array[] = 'VALUES '.Database::escape_array_2d($values, true);
			} else {
				$array[] = 'VALUES '.Database::escape_array_2d($data, true);
			}
		}

		$this->array[] = implode(' ', $array);
		return $this;
	}

	/**
	 * Create new table with specified columns
	 * @param string $table Table name
	 * @param string[] $columns Associative array (column name => data type)
	 */
	public function create($table, $columns) {
		$array = [];
		$array[] = 'CREATE TABLE '.Database::escape_field($table);

		$fields = [];
		foreach ($columns as $name => $value) {
			$fields[] = Database::escape_field($name)." ".strval($value);
		}
		$array[] = "(".implode(', ', $fields).")";

		$this->array[] = implode(' ', $array);
		return $this;
	}

	/**
	 * Delete records from table
	 * @param string $table Table name
	 */
	public function deleteFrom($table) {
		$this->array[] = 'DELETE FROM '.Database::escape_field($table);
		return $this;
	}

	/**
	 * Update records in table
	 * @param string $table Table name
	 * @param mixed[] $data Associative array (column name => new value)
	 */
	public function update($table, $data) {
		$array = [];
		$array[] = 'UPDATE '.Database::escape_field($table).' SET';

		$sets = [];
		foreach ($data as $column => $value) {
			$sets[] = Database::escape_field($column).' = '.Database::escape($value, true);
		}
		$array[] = implode(', ', $sets);

		$this->array[] = implode(' ', $array);
		return $this;
	}

	/**
	 * Add, delete or modify columns or constraints in an existing table
	 * @param string $table Table name
	 */
	public function alter($table) {
		$this->array[] = 'ALTER TABLE '.Database::escape_field($table);
		return $this;
	}

	/**
	 * Note: This method is used after call to alter()
	 * @param string $column Column to add
	 * @param string $datatype New column's datatype
	 */
	public function add($column, $datatype) {
		$this->array[] = 'ADD '.Database::escape_field($column).' '.Database::escape($datatype, false);
		return $this;
	}

	/**
	 * Note: This method is used after call to alter()
	 * @param string $column Column to drop
	 */
	public function dropColumn($column) {
		$this->array[] = 'DROP COLUMN '.Database::escape_field($column);
		return $this;
	}

	/**
	 * Note: This method is used after call to alter()
	 * @param string $column Column to drop
	 */
	public function dropIndex($name) {
		$this->array[] = 'DROP INDEX '.Database::escape_field($name);
		return $this;
	}

	/**
	 * Drop an existing table in a database
	 * @param string $table Table to drop
	 */
	public function dropTable($table) {
		$this->array[] = 'DROP TABLE '.Database::escape_field($table);
		return $this;
	}

	/**
	 * Delete the data inside a table, but not the table itself
	 * @param string $table Table to truncate
	 */
	public function truncate($table) {
		$this->array[] = 'TRUNCATE TABLE '.Database::escape_field($table);
		return $this;
	}

	/**
	 * Return records that have matching values in both tables
	 * @param string $table Table name
	 * @param string[] $left Format: ["table", "column"]
	 * @param string[] $right Format: ["table", "column"]
	 */
	public function innerJoin($table, $left, $right) {
		$array = [];
		$array[] = 'INNER JOIN '.Database::escape_field($table, false).' ON';
		$array[] = Database::escape_field_array($left).' = '.Database::escape_field_array($right);

		$this->array[] = implode(' ', $array);
		return $this;
	}

	/**
	 * Return all records from the left table, and the matched records from the right table
	 * @param string $table Table name
	 * @param string[] $left Format: ["table", "column"]
	 * @param string[] $right Format: ["table", "column"]
	 */
	public function leftJoin($table, $left, $right) {
		$array = [];
		$array[] = 'LEFT OUTER JOIN '.Database::escape_field($table).' ON';
		$array[] = Database::escape_field_array($left, false).' = '.Database::escape_field_array($right);

		$this->array[] = implode(' ', $array);
		return $this;
	}

	/**
	 * Return all records from the right table, and the matched records from the left table
	 * @param string $table Table name
	 * @param string[] $left Format: ["table", "column"]
	 * @param string[] $right Format: ["table", "column"]
	 */
	public function rightJoin($table, $left, $right) {
		$array = [];
		$array[] = 'RIGHT OUTER JOIN '.Database::escape_field($table).' ON';
		$array[] = Database::escape_field_array($left).' = '.Database::escape_field_array($right);

		$this->array[] = implode(' ', $array);
		return $this;
	}

	/**
	 * Return all records when there is a match in either left or right table
	 * @param string $table Table name
	 * @param string[] $left Format: ["table", "column"]
	 * @param string[] $right Format: ["table", "column"]
	 */
	public function fullJoin($table, $left, $right) {
		$array = [];
		$array[] = 'FULL OUTER JOIN '.Database::escape_field($table).' ON';
		$array[] = Database::escape_field_array($left).' = '.Database::escape_field_array($right);

		$this->array[] = implode(' ', $array);
		return $this;
	}

	/**
	 * Sort the result-set in ascending or descending order (defaults to ascending order)
	 * @param string|string[] $columns Columns used when sorting
	 * @param "ASC"|"DESC" $order Specify the sort order (ascending, descending)
	 */
	public function orderBy($columns, $order = "ASC") {
		if (is_string($columns)) { $columns = [$columns]; }
		if (count($columns) === 0) return $this;

		for ($i=0; $i<count($columns); $i++) {
			$columns[$i] = Database::escape_field($columns[$i]);
		}

		$order = strtoupper($order);
		if ($order !== "ASC" && $order !== "DESC") {
			$order = "ASC";
		}

		$this->array[] = 'ORDER BY '.implode(', ', $columns).' '.$order;
		return $this;
	}

	/**
	 * Get the SQL query string
	 * @return string
	 */
	public function getQuery() {
		return implode(' ', $this->array);
	}

	/**
	 * Execute the query and return QueryResult
	 * @return QueryResult
	 */
	public function exec() {
		if (count($this->array) === 0) {
			throw new Exception('SQL query cannot be empty');
		}
		
		$query = implode(' ', $this->array);
		array_splice($this->array, 0);

		return Database::query($query);
	}
}
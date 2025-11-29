<?php

abstract class BaseRecord implements JsonSerializable
{
	//--------------------------------------------------------------------------
	// Properties
	//

	// --- Constants ---
	const DEFAULT_DB_NAME = 'myDb';
	const METADATA = ['id', 'created_at', 'updated_at'];
	const COLUMNS = [
		'id' => [
			'type' => 'INTEGER',
			'constraints' => 'PRIMARY KEY AUTOINCREMENT NOT NULL',
		],
		'created_at' => [
			'type' => 'DATETIME',
			'constraints' => 'DEFAULT CURRENT_TIMESTAMP',
		],
		'updated_at' => [
			'type' => 'DATETIME',
			'constraints' => 'DEFAULT CURRENT_TIMESTAMP',
		]
	];

    // --- Variables ---
	/**
	 * @used-by BaseRecord::__get() to check property existance and retrieve stored data
	 * @used-by BaseRecord::__set() to check property existance and store validated data
	 * @var array $data Stores property data after being validated
	 */
	protected $data = [
		'id' => null,
		'created_at' => null,
		'updated_at' => null,
	];

    //--------------------------------------------------------------------------
    // Magic Methods
    //

	/**
	 * Override constructor
	 *
	 * Hides constructor and prevents children from overriding
	 *
	 * @used-by BaseRecord::create()
	 * @return BaseRecord
	 */
	final protected function __construct() {}

	/**
	 * Override cloner
	 *
	 * Hides cloner and prevents children from overriding
	 *
	 * @return void
	 */
	final protected function __clone() {}

	/**
	 * Override getter
	 *
	 * Override default getter and prevents children from
	 * overriding
	 *
	 * @param string $prop
	 * @uses BaseRecord::$data to validate property existance and retrieve property data
	 * @uses BaseRecord::$data to expose all existing data
	 * @throws BadMethodCallException if $prop doesn't match a property key
	 * @return mixed
	 */
	final public function __get($prop)
	{
		if (array_key_exists($prop, $this->data)) {
			return $this->data[$prop];
		} elseif ($prop === 'data') {
			return $this->data;
		} else {
			throw new BadMethodCallException("Tried to access non-existant property\"$prop\"", 1);
		}
	}

	/**
	 * Override setter
	 *
	 * Override default setter to disallow editing metadata, prevents children
	 * from overriding
	 *
	 * @param string $prop
	 * @param mixed $value
	 * @uses BaseRecord::METADATA to disallow outside manipulation of metadata
	 * @uses BaseRecord::$data to validate property existance and retrieve property data
	 * @throws BadMethodCallException if $prop doesn't match a property key
	 * @return void
	 */
	final public function __set($prop, $value)
	{
		if (array_key_exists($prop, $this->data)) {
			if (!in_array($prop, static::METADATA)) {
				$this->data[$prop] = $value;
			}
		} else {
			throw new BadMethodCallException("Tried to set non-existant property \"$prop\"", 1);
		}
	}

    //--------------------------------------------------------------------------
    // Public Methods
    //

    // --- Static ---

	/**
	 * Validate potential values for properties
	 *
	 * Return true on success
	 *
	 * @param string $prop
	 * @param mixed $value
	 * @used-by BaseRecord::__set()
	 * @used-by BaseRecord::find()
	 * @used-by BaseRecord::findBy()
	 * @used-by BaseRecord::destroy()
	 * @used-by BaseRecord::validateSelf()
	 * @used-by BaseRecord::setId()
	 * @return bool
	 */
	abstract public static function validate($prop, $value);
    // {
    //     switch ($prop) {
    //         case 'id':
    //             if ($value !== null && !is_int($value)) {
    //                 throw new InvalidArgumentException("$prop must be an integer or null {{$value}}", 1);
    //             } elseif (is_int($value) && $value < 1) {
    //                 throw new InvalidArgumentException("$prop must be greater than 0 {{$value}}", 1);
    //             }
    //             break;
    //         case 'created_at':
    //             break;
    //         case 'updated_at':
    //             break;
    //         default:
    //             throw new UnexpectedValueException("Unexpected property \"$prop\"", 1);
    //     }
    //     return true;
    // }

	/**
	 * Creates a database table for the class
	 *
	 * Generates table columns as defined in static::COLUMNS
	 *
	 * @uses BaseRecord::getTableName() to get or generate the class table name
	 * @uses BaseRecord::validateColumnName() to make sure properties have good column names
	 * @uses BaseRecord::COLUMNS to generate the table columns
	 * @uses BaseRecord::openDataBase() to open a database connection
	 * @see https://secure.php.net/manual/en/book.sqlite3.php for PHP SQLite docs
	 * @throws InvalidArgumentException if column names fail validation
	 * @return void
	 */
	public static function migrate()
	{
		$columns = [];
		$table = static::getTableName();
		foreach (static::COLUMNS as $key => $manifest) {
			if (!static::validateColumnName($key)) {
				throw new InvalidArgumentException('Column names must be underscored & lowercase', 1);
			}
			$columns[] = implode(' ', ["\"$key\"", $manifest['type'], $manifest['constraints']]);
		}
		$columns = implode(', ', $columns);
		$db = static::openDataBase();
		$db->query("CREATE TABLE IF NOT EXISTS  \"$table\" ($columns)");
		$db->close();
		unset($db);
	}

	/**
	 * Creates a Record instance
	 *
	 * All children of this class will use this function
	 *
	 * @param mixed[]|null $params An optional associative array of properties and values
	 * @see BaseRecord::$data for usable properties
	 * @see BaseRecord::validate() for validation rules for those properties
	 * @uses BaseRecord::__get() to set property values
	 * @uses BaseRecord::__construct() to initialize an empty instance
	 * @used-by BaseRecord::parseQuery()
	 * @throws InvalidArgumentException if $params is not an array or null
	 * @return BaseRecord
	 */
	final public static function create($params = null)
	{
		$myObject = new static;
		if (is_array($params)) {
			foreach ($params as $prop => $value) {
				$myObject->{$prop} = $value;
			}
		} elseif ($params !== null) {
			throw new InvalidArgumentException('Parameters must be in an associative array', 1);
		}
		return $myObject;
	}

	/**
	 * Get all stored Records from the database
	 *
	 * All children of this class will use this function
	 * Returns false if query returns no results
	 *
	 * @uses BaseRecord::getTableName() to get or generate the class table name
	 * @uses BaseRecord::openDataBase() to open a database connection
	 * @uses BaseRecord::parseQuery() to parse SQLite3 results into array of Records
	 * @see https://secure.php.net/manual/en/book.sqlite3.php for SQLite docs
	 * @return BaseRecord[]|bool
	 */
	final public static function all()
	{
		$table = static::getTableName();
		$db = static::openDataBase();
		$statement = $db->prepare("SELECT * FROM \"$table\"");
		$results = $statement->execute();
		$results->finalize();
		$results = is_bool($results) ? $results : static::parseQuery($results);
		$db->close();
		unset($db);
		return $results;
	}

	/**
	 * Get single Record from the database with matching id
	 *
	 * All children of this class will use this function
	 * Returns false if query returns no results
	 *
	 * @param int $id defaults to 1
	 * @uses BaseRecord::validate() to validate the value for the specified property
	 * @uses BaseRecord::getTableName() to get or generate the class table name
	 * @uses BaseRecord::openDataBase() to open a database connection
	 * @uses BaseRecord::parseQuery() to parse SQLite3 results into single Record
	 * @used-by BaseRecord::mirror()
	 * @see https://secure.php.net/manual/en/book.sqlite3.php for SQLite docs
	 * @throws Exception via BaseRecord::validate() if id validation fails
	 * @return BaseRecord|bool
	 */
	final public static function find($id = 1)
	{
		static::validate('id', $id);
		$table = static::getTableName();
		$db = static::openDataBase();
		$statement = $db->prepare("SELECT * FROM \"$table\" WHERE \"id\" = :id LIMIT 1");
		$statement->bindValue(':id', $id);
		$results = $statement->execute();
		$results->finalize();
		$results = is_bool($results) ? $results : static::parseQuery($results)[0];
		$db->close();
		unset($db);
		return $results;
	}


	/**
	 * Get single Record from the database with matching property
	 *
	 * All children of this class will use this function
	 * Returns false if query returns no results
	 *
	 * @param string $prop the property to check
	 * @param mixed $value the value to check against
	 * @uses BaseRecord::validate() to validate the value for the specified property
	 * @uses BaseRecord::getTableName() to get or generate the class table name
	 * @uses BaseRecord::openDataBase() to open a database connection
	 * @uses BaseRecord::parseQuery() to parse SQLite3 results into single Record
	 * @see https://secure.php.net/manual/en/book.sqlite3.php for SQLite docs
	 * @throws Exception via BaseRecord::validate() if property validation fails
	 * @return BaseRecord|bool
	 */
	final public static function findBy($prop, $value)
	{
		static::validate($prop, $value);
		$table = static::getTableName();
		$db = static::openDataBase();
		$statement = $db->prepare("SELECT * FROM \"$table\" WHERE \"$prop\" = :val LIMIT 1");
		$statement->bindValue(':val', $value);
		$results = $statement->execute();
		$results->finalize();
		$results = is_bool($results) ? $results : static::parseQuery($results)[0];
		$db->close();
		unset($db);
		return $results;
	}

	/**
	 * Get all Records from the database with matching property
	 *
	 * All children of this class will use this function
	 * Returns false if query returns no results
	 *
	 * @param string $prop the property to check
	 * @param mixed $value the value to check against
	 * @uses BaseRecord::validate() to validate the value for the specified property
	 * @uses BaseRecord::getTableName() to get or generate the class table name
	 * @uses BaseRecord::openDataBase() to open a database connection
	 * @uses BaseRecord::parseQuery() to parse SQLite3 results into single Record
	 * @see https://secure.php.net/manual/en/book.sqlite3.php for SQLite docs
	 * @throws Exception via BaseRecord::validate() if property validation fails
	 * @return BaseRecord|bool
	 */
	final public static function where($prop, $value)
	{
		// "Finish later, probably not crucial for this project,"
		// the developer said, wrongly
		static::validate($prop, $value);
		$table = static::getTableName();
		$db = static::openDataBase();
		$statement = $db->prepare("SELECT * FROM \"$table\" WHERE \"$prop\" = :val");
		$statement->bindValue(':val', $value);
		$results = $statement->execute();
		$results->finalize();
		$results = is_bool($results) ? $results : static::parseQuery($results);
		$db->close();
		unset($db);
		return $results;
	}

	/**
	 * Get Records from the database, ordered by parsed $params
	 *
	 * All children of this class will use this function
	 * Returns false if query returns no results
	 *
	 * @param string[]|string $params the rules by which to order
	 *      eg: ['rank', 'name DESC'] || "name ASC" || "rank ASC, name"
	 * @uses BaseRecord::parseOrderString() to parse input strings into formatted
	 *      order conditions
	 * @uses BaseRecord::getTableName() to get or generate the class table name
	 * @uses BaseRecord::openDataBase() to open a database connection
	 * @uses BaseRecord::parseQuery() to parse SQLite3 results into array of Records
	 * @see https://secure.php.net/manual/en/book.sqlite3.php for SQLite docs
	 * @throws InvalidArgumentException if $params is not a string or array
	 * @return BaseRecord[]|bool
	 */
	final public static function order($params)
	{
		$orderConds = [];
		if (is_array($params)) {
			foreach ($params as $orderStr) {
				if (!is_string($orderStr)) {
					throw new InvalidArgumentException('Parameter must be a string or array of strings', 1);
				}
				$orderConds = array_merge($orderConds, static::parseOrderString($orderStr));
			}
		} elseif (is_string($params)) {
			$orderConds = static::parseOrderString($params);
		} else {
			throw new InvalidArgumentException('Parameter must be a string or array of strings', 1);
		}
		$orderConds = implode(', ', $orderConds);
		$table = static::getTableName();
		$db = static::openDataBase();
		$statement = $db->prepare("SELECT * FROM \"$table\" ORDER BY $orderConds");
		$results = $statement->execute();
		$results->finalize();
		$results = is_bool($results) ? $results : static::parseQuery($results);
		$db->close();
		unset($db);
		return $results;
	}

	/**
	 * Get a set number of Records from the database
	 *
	 * Gets the first {$amount} of Records from the DB
	 * All children of this class will use this function
	 * Returns false if query returns no results
	 *
	 * @param int $amount the number of Records to pull from database
	 * @uses BaseRecord::getTableName() to get or generate the class table name
	 * @uses BaseRecord::openDataBase() to open a database connection
	 * @uses BaseRecord::parseQuery() to parse SQLite3 results into single Record
	 * @see https://secure.php.net/manual/en/book.sqlite3.php for SQLite docs
	 * @throws OutOfRangeException if $amount < 1 or is not and integer
	 * @return BaseRecord[]|bool
	 */
	final public static function take($amount)
	{
		if (!is_int($amount) || $amount < 1) {
			throw new OutOfRangeException('Take amount was non-integer or less than 0', 1);
		}
		$table = static::getTableName();
		$db = static::openDataBase();
		$statement = $db->prepare('SELECT * FROM "' . $table . '" LIMIT ' . $amount);
		$results = $statement->execute();
		$results->finalize();
		$results = is_bool($results) ? $results : static::parseQuery($results);
		$db->close();
		unset($db);
		return $results;
	}

	/**
	 * Save a Record instance to the database
	 *
	 * All children of this class will use this function
	 * Returns self
	 *
	 * @uses BaseRecord::validateSelf() to validate own data
	 * @uses BaseRecord::encode() to encode data for SQLite storage
	 * @uses BaseRecord::getTableName() to get or generate the class table name
	 * @uses BaseRecord::openDataBase() to open a database connection
	 * @uses BaseRecord::METADATA to filter out certain metadata columns
	 * @uses BaseRecord::COLUMNS to build query and bind data
	 * @uses BaseRecord::$data to pull data from
	 * @uses BaseRecord::$data['id] to determine whether to update or insert
	 * @see https://secure.php.net/manual/en/book.sqlite3.php for SQLite docs
	 * @throws Exception via BaseRecord::validate() if validation fails
	 * @return BaseRecord
	 */
	final public function save()
	{
		$this->validateSelf();
		$table = static::getTableName();
		$selectedColumns = static::COLUMNS;
		unset($selectedColumns['id'], $selectedColumns['created_at']);
		if (!isset($this->data['id'])) {
			unset($selectedColumns['updated_at']);
		}
		$selectedKeys = array_keys($selectedColumns);
		$placeholders = ':' . implode(', :', $selectedKeys);
		$encodedData = static::encode($this);
		$columns = "\"" . implode("\", \"", $selectedKeys) . "\"";
		$db = static::openDataBase();
		$statement = null;
		if (!isset($this->data['id'])) {
			$statement = $db->prepare("INSERT INTO \"$table\" ($columns) VALUES ($placeholders)");
		} else {
			$updates = [];
			foreach ($selectedKeys as $dataKey) {
				$updates[] = "\"$dataKey\" = :$dataKey";
			}
			if ($statement = $db->prepare("UPDATE \"$table\" SET " . implode(', ', $updates) . " WHERE \"id\" = :id")) {
				$statement->bindValue(':updated_at', date('Y-m-d H:i:s', time()));
				$statement->bindValue(':id', $this->data['id']);
			} else {
				debug_var($db->lastErrorMsg());
			}
		}
		foreach ($selectedColumns as $key => $manifest) {
			$statement->bindValue(":$key", $encodedData[$key]);
		}
		$statement->execute();
		$db->close();
		unset($db);
		return $this;
	}

	/**
	 * Delete a Record instance from the database
	 *
	 * All children of this class will use this function
	 * Returns self
	 *
	 * @uses BaseRecord::getTableName() to get or generate the class table name
	 * @uses BaseRecord::openDataBase() to open a database connection
	 * @uses BaseRecord::$data to get instance id
	 * @see https://secure.php.net/manual/en/book.sqlite3.php for SQLite docs
	 * @return BaseRecord
	 */
	final public function delete()
	{
		$table = static::getTableName();
		$db = static::openDataBase();
		$statement = $db->prepare("DELETE FROM \"$table\" WHERE \"id\" IS :id");
		$statement->bindValue(':id', $this->data['id']);
		$statement->execute();
		$db->close();
		unset($db);
		return $this;
	}

	/**
	 * Delete a Record from the database by id
	 *
	 * All children of this class will use this function
	 *
	 * @uses BaseRecord::validate() to validate the specified id
	 * @uses BaseRecord::getTableName() to get or generate the class table name
	 * @uses BaseRecord::openDataBase() to open a database connection
	 * @see https://secure.php.net/manual/en/book.sqlite3.php for SQLite docs
	 * @throws Exception via BaseRecord::validate() if validation fails
	 * @return bool
	 */
	final public static function destroy($id)
	{
		static::validate('id', $id);
		$table = static::getTableName();
		$db = self::openDataBase();
		$statement = $db->prepare("DELETE FROM \"$table\" WHERE \"id\" IS :id");
		$statement->bindValue(':id', $id);
		$statement->execute();
		$db->close();
		unset($db);
		return true;
	}

	/**
	 * Reset a Record to version stored in the database
	 *
	 * Returns refreshed Record or false
	 *
	 * @uses BaseRecord::$data to determine if a version exists in database
	 * @uses BaseRecord::mirror() to mirror the version retrieved from the database
	 * @used-by BaseRecord::save();
	 * @see https://secure.php.net/manual/en/book.sqlite3.php for SQLite docs
	 * @return BaseRecord|bool
	 */
	final public function refresh()
	{
		if (isset($this->data['id'])) {
			$this->mirror(static::find($this->id));
			return $this;
		}
		return false;
	}


	/**
	 * Perform more complex queries on Records in the database
	 *
	 * If any key in $params matches a column name, the corresponding value will
	 * be validated for that column. Returns false if query fails or returns no
	 * results.
	 *
	 * All children of this class will use this function
	 *
	 * @example query('SELECT * FROM "myTable" WHERE "id" < ?', [12]);
	 *      ~>  SELECT * FROM "myTable" WHERE "id" < 12
	 * @param string $queryString the string that forms the basis of the query
	 * @param mixed[] $params array of values to bind into the query string by key, optional
	 * @uses BaseRecord::validate() to validate the any key specified data
	 * @uses BaseRecord::getTableName() to get or generate the class table name
	 * @uses BaseRecord::openDataBase() to open a database connection
	 * @uses BaseRecord::parseQuery() to parse SQLite3 results into single Record
	 * @see https://secure.php.net/manual/en/book.sqlite3.php for SQLite docs
	 * @throws Exception via BaseRecord::validate() if property validation fails
	 * @return BaseRecord|bool
	 */
	final public static function query($queryString, $params = null)
	{
		$table = static::getTableName();
		$db = self::openDataBase();
		$statement = $db->prepare($queryString);
		if (is_array($params)) {
			foreach ($params as $key => $value) {
				if (array_key_exists($key, static::COLUMNS)) {
					static::validate($key, $value);
				}
				if (is_string($key)) {
					$key = ":$key";
				}
				if (is_array($value)) {
					$value = '(\'' . implode('\', \'', $value) . '\')';
				}
				$statement->bindValue($key, $value);
			}
		}
		$results = $statement->execute();
		$results->finalize();
		$results = is_bool($results) ? $results : static::parseQuery($results);
		$db->close();
		unset($db);
		return $results;
	}

	/**
	 * How to define a Record when JSON encoding
	 *
	 * Simply exposes protected property $data, override as needed
	 *
	 * @uses BaseRecord::$data to gather data from
	 * @return mixed[]
	 */
	public function jsonSerialize(): array
	{
		$class = get_class($this);
		$jsonData = [];
		foreach ($this->data as $key => $value) {
			$jsonData[$key] = $value;
		}
		return [$class => $jsonData];
	}

    //--------------------------------------------------------------------------
    // Private Methods
    //

	/**
	 * Get the name for the database table
	 *
	 * If a TABLE_NAME has not been set, converts called class name to all
	 * lowercase separated by underscores if necessary, and ends in 's' (plural)
	 *
	 * @uses BaseRecord::TABLE_NAME if set beforehand
	 * @used-by BaseRecord::migrate()
	 * @used-by BaseRecord::all()
	 * @used-by BaseRecord::find()
	 * @used-by BaseRecord::findBy()
	 * @used-by BaseRecord::order()
	 * @used-by BaseRecord::take()
	 * @used-by BaseRecord::save()
	 * @used-by BaseRecord::delete()
	 * @used-by BaseRecord::destroy()
	 * @return string
	 */
	final protected static function getTableName(): string
	{
		$static_constant = static::class . '::TABLE_NAME';
		if (defined($static_constant)) {
			return constant($static_constant);
		} else {
			return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', get_called_class())) . 's';
		}
	}

	/**
	 * Validate column names
	 *
	 * Must be all lowercase with underscores
	 *
	 * @param string $name the proposed column name
	 * @uses BaseRecord::TABLE_NAME if set beforehand
	 * @used-by BaseRecord::migrate()
	 * @return bool
	 */
	final protected static function validateColumnName($name): bool
	{
		return $name === strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $name));
	}

	/**
	 * Get a database connection
	 *
	 * If a global constant DB_NAME has not been set, opens/creates a database
	 * with default name of 'myDb'
	 *
	 * @uses DB_NAME if set beforehand
	 * @used-by BaseRecord::migrate()
	 * @used-by BaseRecord::all()
	 * @used-by BaseRecord::find()
	 * @used-by BaseRecord::findBy()
	 * @used-by BaseRecord::order()
	 * @used-by BaseRecord::take()
	 * @used-by BaseRecord::save()
	 * @used-by BaseRecord::delete()
	 * @used-by BaseRecord::destroy()
	 * @see https://secure.php.net/manual/en/book.sqlite3.php for SQLite docs
	 * @return SQLite3
	 */
	final protected static function openDataBase(): SQLite3
	{
		global $dbUpdateTimestamp;
		$dbName = defined('DB_NAME') ? DB_NAME : static::DEFAULT_DB_NAME;
		$path = defined('PROJECT_ROOT') ? PROJECT_ROOT . '/db' : __DIR__;
		$filePath = "$path/$dbName.sqlite3";
		$dbUpdateTimestamp = file_exists($filePath) ? filemtime($filePath) : time();
		$db = new SQLite3("$path/$dbName.sqlite3", SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
		$db->busyTimeout(5000);
		$db->exec('PRAGMA journal_mode = wal;');
		return $db;
	}

	/**
	 * Parse an SQLite3Result into an array of Records
	 *
	 * @param SQLite3Result $query the results to be parsed
	 * @uses BaseRecord::decode() to decode raw data before validation
	 * @uses BaseRecord::create() to instantiate a new Record
	 * @uses BaseRecord::setId() to set the id of the new Record
	 * @used-by BaseRecord::all()
	 * @used-by BaseRecord::find()
	 * @used-by BaseRecord::findBy()
	 * @used-by BaseRecord::order()
	 * @used-by BaseRecord::take()
	 * @see https://secure.php.net/manual/en/book.sqlite3.php for SQLite docs
	 * @return BaseRecord[]
	 */
	final protected static function parseQuery($query): array
	{
		$results = [];
		$row = $query->fetchArray(SQLITE3_ASSOC);
		while (is_array($row)) {
			$row = static::decode($row);
			$results[] = static::create($row)->setMeta($row['id'], $row['created_at'], $row['updated_at']);
			$row = $query->fetchArray(SQLITE3_ASSOC);
		}
		return $results;
	}

	/**
	 * Parse an string into an array of formatted order conditions
	 *
	 * @param string $string the string to be parsed
	 * @used-by BaseRecord::order()
	 * @see https://secure.php.net/manual/en/book.sqlite3.php for SQLite docs
	 * @throws InvalidArgumentException if a direction is invalid or a column
	 *      doesn't exist, or the string is improperly formatted
	 * @return string[]
	 */

	final protected static function parseOrderString($string): array
	{
		$results = [];
		$segments = explode(', ', $string);
		foreach ($segments as $segment) {
			$components = explode(' ', $segment);
			$column;
			$direction;
			switch (count($components)) {
				case 2:
					$direction = $components[1];
					if ($direction !== 'ASC' && $direction !== 'DESC') {
						throw new InvalidArgumentException('Direction "' . $direction . '" invalid', 1);
					}
				case 1:
					$direction = isset($direction) ? $direction : 'ASC';
					$column = $components[0];
					if (!array_key_exists($column, static::COLUMNS)) {
						throw new InvalidArgumentException('Column "' . $column . '" does not exist', 1);
					}
					break;
				default:
					throw new InvalidArgumentException('String param with unexpected number of components', 1);
					break;
			}
			$results[] = "$column $direction";
		}
		return $results;
	}

	/**
	 * Set the Record metadata
	 *
	 * This function is used only by the static function BaseRecord::parseQuery()
	 *
	 * @param int $id
	 * @param int $created_at
	 * @param int $updated_at
	 * @uses BaseRecord::validate() to validate parameters
	 * @uses BaseRecord::$data to store the validated metadata
	 * @used-by BaseRecord::parseQuery()
	 * @return BaseRecord
	 */
	final protected function setMeta($id, $created_at = null, $updated_at = null): BaseRecord
	{
		if (static::validate('id', $id)) {
			$this->data['id'] = $id;
		}
		if (static::validate('created_at', $created_at)) {
			$this->data['created_at'] = $created_at;
		}
		if (static::validate('updated_at', $updated_at)) {
			$this->data['updated_at'] = $updated_at;
		}
		return $this;
	}

	/**
	 * Validate all stored data
	 *
	 * @uses BaseRecord::$data to retrieve each stored property value
	 * @uses BaseRecord::validate() to validate each stored property value
	 * @used-by BaseRecord::save()
	 * @return void
	 */
	final protected function validateSelf(): void
	{
		foreach ($this->data as $prop => $value) {
			static::validate($prop, $value);
		}
	}

	/**
	 * Mirror another instance onto self
	 *
	 * @param BaseRecord $record the source to be mirrored from
	 * @uses BaseRecord::__get() retrieve stored property values
	 * @uses BaseRecord::$data to store each mirrored property value
	 * @used-by BaseRecord::reset()
	 * @throws InvalidArgumentException if $record is not an instance of the
	 *      same class as $this
	 * @return void
	 */
	final protected function mirror($record): void
	{
		$class = get_class($this);
		if (!is_a($record, $class) || is_subclass_of($record, $class)) {
			throw new InvalidArgumentException('Cannot mirror object of different type');
		}
		foreach ($this->data as $prop => $value) {
			$this->data[$prop] = $record->{$prop};
		}
	}

	/**
	 * Decode raw data recieved from database
	 *
	 * This is for basic decoding, override this as needed.
	 * Assumes BLOB types stored as json (see BaseRecord::encode()), BOOLEAN types
	 * stored as integers (SQLite default)
	 *
	 * @param mixed[] $data the raw data to be decoded, usually the result of a
	 *      call to SQLiteResult::fetchArray()
	 * @uses BaseRecord::COLUMNS to determine how to handle each piece of data
	 * @used-by BaseRecord::parseQuery()
	 * @see BaseRecord::encode()
	 * @see https://secure.php.net/manual/en/book.sqlite3.php for SQLite docs
	 * @return mixed[]
	 */
	public static function decode($data): array
	{
		foreach (static::COLUMNS as $prop => $manifest) {
			switch ($manifest['type']) {
				case 'BOOLEAN':
					$data[$prop] = $data[$prop] == 1 ? true : false;
					break;

				case 'BLOB':
					$data[$prop] = json_decode($data[$prop], true);
					break;

				case 'INT':
				case 'INTEGER':
					$data[$prop] = (int) $data[$prop];
					break;

				case 'REAL':
				case 'DOUBLE':
				case 'FLOAT':
					$data[$prop] = (float) $data[$prop];
					break;
				default:
					break;
			}
		}
		return $data;
	}

	/**
	 * Encode raw data to be stored in database
	 *
	 * This is for basic encoding, override this as needed.
	 * Converts BLOB types to json (see BaseRecord::decode())
	 *
	 * @param BaseRecord $record the record to encode
	 * @uses BaseRecord::COLUMNS to determine how to handle each piece of data
	 * @used-by BaseRecord::save()
	 * @see BaseRecord::decode()
	 * @see https://secure.php.net/manual/en/book.sqlite3.php for SQLite docs
	 * @throws InvalidArgumentException if the class of $record is not exactly
	 *      equal to the called class
	 * @return mixed[]
	 */
	public static function encode($record): array
	{
		$class = get_called_class();
		if (!is_a($record, $class) || is_subclass_of($record, $class)) {
			throw new InvalidArgumentException("Cannot validate this as a $class", 1);
		}
		$preparedData = [];
		foreach (static::COLUMNS as $prop => $manifest) {
			$preparedData[$prop] = $record->{$prop};
			switch ($manifest['type']) {
				case 'BLOB':
					$preparedData[$prop] = json_encode($record->{$prop});
					break;

				default:
					break;
			}
		}
		return $preparedData;
	}
}

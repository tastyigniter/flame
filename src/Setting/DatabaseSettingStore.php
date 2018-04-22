<?php

namespace Igniter\Flame\Setting;

use Illuminate\Database\Connection;
use Illuminate\Support\Arr;
use UnexpectedValueException;

class DatabaseSettingStore extends SettingStore
{
    /**
     * The database connection instance.
     * @var \Illuminate\Database\Connection
     */
    protected $connection;

    /**
     * The table to query from.
     * @var string
     */
    protected $table;

    /**
     * The key column name to query from.
     * @var string
     */
    protected $keyColumn;

    /**
     * The value column name to query from.
     * @var string
     */
    protected $valueColumn;

    /**
     * Any query constraints that should be applied.
     * @var \Closure|null
     */
    protected $queryConstraint;

    /**
     * Any extra columns that should be added to the rows.
     * @var array
     */
    protected $extraColumns = [];

    /**
     * @param \Illuminate\Database\Connection $connection
     * @param string $table
     * @param null $keyColumn
     * @param null $valueColumn
     */
    public function __construct(Connection $connection, $table = null, $keyColumn = null, $valueColumn = null)
    {
        $this->connection = $connection;
        $this->table = $table ?: 'settings';
        $this->keyColumn = $keyColumn ?: 'key';
        $this->valueColumn = $valueColumn ?: 'value';
    }

    /**
     * Set the table to query from.
     *
     * @param string $table
     */
    public function setTable($table)
    {
        $this->table = $table;
    }

    /**
     * Set the key column name to query from.
     *
     * @param $keyColumn
     */
    public function setKeyColumn($keyColumn)
    {
        $this->keyColumn = $keyColumn;
    }

    /**
     * Set the value column name to query from.
     *
     * @param $valueColumn
     */
    public function setValueColumn($valueColumn)
    {
        $this->valueColumn = $valueColumn;
    }

    /**
     * Set the query constraint.
     *
     * @param \Closure $callback
     */
    public function setConstraint(\Closure $callback)
    {
        $this->items = [];
        $this->loaded = FALSE;
        $this->queryConstraint = $callback;
    }

    /**
     * Set extra columns to be added to the rows.
     *
     * @param array $columns
     */
    public function setExtraColumns(array $columns)
    {
        $this->extraColumns = $columns;
    }

    /**
     * {@inheritdoc}
     */
    public function forget($key)
    {
        parent::forget($key);

        // because the database store cannot store empty arrays, remove empty
        // arrays to keep data consistent before and after saving
        $segments = explode('.', $key);
        array_pop($segments);

        while ($segments) {
            $segment = implode('.', $segments);

            // non-empty array - exit out of the loop
            if ($this->get($segment)) {
                break;
            }

            // remove the empty array and move on to the next segment
            $this->forget($segment);
            array_pop($segments);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $data)
    {
        $keysQuery = $this->newQuery();

        $keys = $keysQuery->pluck($this->valueColumn, $this->keyColumn);
        $insertData = array_dot($data);
        $updateData = [];
        $deleteKeys = [];

        foreach ($keys as $key => $sort) {
            if (isset($insertData[$key])) {
                if ($sort != $insertData[$key])
                    $updateData[$key] = $insertData[$key];
            }
            else {
                $deleteKeys[] = $key;
            }
            unset($insertData[$key]);
        }

        foreach ($updateData as $key => $value) {
            $this->newQuery()
                 ->where($this->keyColumn, '=', $key)
                 ->update([$this->valueColumn => $value]);
        }

        if ($insertData) {
            $this->newQuery(TRUE)
                 ->insert($this->prepareInsertData($insertData));
        }

        if ($deleteKeys) {
            $this->newQuery()
                 ->whereIn($this->keyColumn, $deleteKeys)
                 ->delete();
        }
    }

    /**
     * Transforms settings data into an array ready to be insterted into the
     * database. Call array_dot on a multidimensional array before passing it
     * into this method!
     *
     * @param  array $data Call array_dot on a multidimensional array before passing it into this method!
     *
     * @return array
     */
    protected function prepareInsertData(array $data)
    {
        $dbData = [];

        if ($this->extraColumns) {
            foreach ($data as $key => $value) {
                $dbData[] = array_merge(
                    $this->extraColumns,
                    [$this->keyColumn => $key, $this->valueColumn => $this->parseInsertKeyValue($value)]
                );
            }
        }
        else {
            foreach ($data as $key => $value) {
                $dbData[] = [$this->keyColumn => $key, $this->valueColumn => $this->parseInsertKeyValue($value)];
            }
        }

        return $dbData;
    }

    /**
     * {@inheritdoc}
     */
    protected function read()
    {
        return $this->parseReadData($this->newQuery()->get());
    }

    /**
     * Parse data coming from the database.
     *
     * @param  array $data
     *
     * @return array
     */
    public function parseReadData($data)
    {
        $results = [];

        foreach ($data as $row) {
            if (is_array($row)) {
                $key = $row[$this->keyColumn];
                $value = $this->parseKeyValue($row[$this->valueColumn]);
            }
            elseif (is_object($row)) {
                $key = $row->{$this->keyColumn};
                $value = $this->parseKeyValue($row->{$this->valueColumn});
            }
            else {
                throw new UnexpectedValueException('Expected array or object, got '.gettype($row));
            }

            Arr::set($results, $key, $value);
        }

        return $results;
    }

    /**
     * Prepend a value onto an array configuration value.
     *
     * @param  string $key
     * @param  mixed $value
     *
     * @return void
     */
    public function prepend($key, $value)
    {
        // TODO: Implement prepend() method.
    }

    /**
     * Push a value onto an array configuration value.
     *
     * @param  string $key
     * @param  mixed $value
     *
     * @return void
     */
    public function push($key, $value)
    {
        // TODO: Implement push() method.
    }

    /**
     * Create a new query builder instance.
     *
     * @param  $insert  boolean  Whether the query is an insert or not.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newQuery($insert = FALSE)
    {
        $query = $this->connection->table($this->table);

        if (!$insert) {
            foreach ($this->extraColumns as $key => $value) {
                $query->where($key, '=', $value);
            }
        }

        if ($this->queryConstraint !== null) {
            $callback = $this->queryConstraint;
            $callback($query, $insert);
        }

        return $query;
    }

    protected function parseKeyValue($value)
    {
        $_value = @unserialize($value);
        if ($_value === FALSE && $_value !== 'b:0;') {
            return $value;
        }

        return $_value;
    }

    protected function parseInsertKeyValue($value)
    {
        return is_scalar($value) ? $value : null;
    }
}

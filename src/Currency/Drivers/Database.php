<?php

namespace Igniter\Flame\Currency\Drivers;

use DateTime;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;

class Database extends AbstractDriver
{
    /**
     * Database manager instance.
     *
     * @var DatabaseManager
     */
    protected $database;

    /**
     * Create a new driver instance.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->database = app('db')->connection($this->config('connection'));
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $params)
    {
        // Ensure the currency doesn't already exist
        if ($this->find($params['code'], null) !== null) {
            return 'exists';
        }

        // Created at stamp
        $created = new DateTime('now');

        $params = array_merge([
            'currency_name'   => '',
            'currency_code'   => '',
            'currency_symbol' => '',
            'format'          => '',
            'currency_rate'   => 1,
            'currency_status' => 0,
//            'created_at' => $created,
            'date_modified'   => $created,
        ], $params);

        return $this->database->table($this->config('table'))->insert($params);
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        $collection = new Collection($this->database->table($this->config('table'))->get());

        return $collection->keyBy('currency_code')
                          ->map(function ($item) {

                              $format = $item->thousand_sign.'0'.$item->decimal_sign.str_repeat('0', $item->decimal_position);

                              return [
                                  'currency_id'     => $item->currency_id,
                                  'currency_name'   => $item->currency_name,
                                  'currency_code'   => strtoupper($item->currency_code),
                                  'currency_symbol' => $item->currency_symbol,
                                  'format'          => $item->symbol_position
                                      ? '1'.$format.$item->currency_symbol
                                      : $item->currency_symbol.'1'.$format,
                                  'currency_rate'   => $item->currency_rate,
                                  'currency_status' => $item->currency_status,
                                  'date_modified'   => $item->date_modified,
//                    'date_modified' => $item->date_modified,
                              ];
                          })
                          ->all();
    }

    /**
     * {@inheritdoc}
     */
    public function find($code, $active = 1)
    {
        $query = $this->database->table($this->config('table'))
                                ->where('currency_code', strtoupper($code));

        // Make active optional
        if (is_null($active) === FALSE) {
            $query->where('currency_status', $active);
        }

        return $query->first();
    }

    /**
     * {@inheritdoc}
     */
    public function update($code, array $attributes, DateTime $timestamp = null)
    {
        $table = $this->config('table');

        // Create timestamp
        if (empty($attributes['date_modified']) === TRUE) {
            $attributes['date_modified'] = new DateTime('now');
        }

        return $this->database->table($table)
                              ->where('currency_code', strtoupper($code))
                              ->update($attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($code)
    {
        $table = $this->config('table');

        return $this->database->table($table)
                              ->where('currency_code', strtoupper($code))
                              ->delete();
    }
}

<?php

namespace Igniter\Flame\Database;

use Igniter\Traits\DelegateToCI;
use Illuminate\Database\Capsule\Manager as CapsuleManager;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Events\Dispatcher;

/**
 * TastyIgniter Database Manager Class
 *
 * @package        Igniter\Flame\Database\Manager.php
 */
class Manager
{
    use DelegateToCI;

    /**
     * @var CapsuleManager
     */
    private static $connection;

    public static function init()
    {
        if (is_null(self::$connection)) {
            $capsule = new CapsuleManager(app());

            $capsule->addConnection(self::getConfiguration());

            $capsule->setEventDispatcher(new Dispatcher(app()));

            // Set the cache manager instance used by connections...
            // $capsule->setCacheManager(...);

            // Always return database row as array instead of object (optional)
//            $capsule->getConnection()->setFetchMode(\PDO::FETCH_ASSOC);
            $capsule->setFetchMode(\PDO::FETCH_ASSOC);

            // Setup the Eloquent ORM...
            $capsule->bootEloquent();

            // Make this Capsule instance available globally via static methods... (optional)
            $capsule->setAsGlobal();

            self::subscribeQueryLogEvent();
        }
    }

    protected static function getConfiguration()
    {
        $CI_DB = self::ci()->db;

        return [
            'driver'    => $CI_DB->dbdriver == 'mysqli' ? 'mysql' : $CI_DB->dbdriver,
            'host'      => $CI_DB->hostname,
            'database'  => $CI_DB->database,
            'username'  => $CI_DB->username,
            'password'  => $CI_DB->password,
            'charset'   => $CI_DB->char_set,
            'collation' => $CI_DB->dbcollat,
            'prefix'    => $CI_DB->dbprefix,
        ];
    }

    public static function subscribeQueryLogEvent()
    {
        $connection = CapsuleManager::connection();

        $connection->enableQueryLog();

        $connection->listen(function ($queryExecuted) {
            if (!$queryExecuted instanceof QueryExecuted)
                return null;

            $bindings = $queryExecuted->bindings;

            // Format binding data for sql insertion
            foreach ($bindings as $i => $binding) {
                if ($binding instanceof \DateTime) {
                    $bindings[$i] = $binding->format('\'Y-m-d H:i:s\'');
                }
                else if (is_string($binding)) {
                    $bindings[$i] = "'$binding'";
                }
            }

            // Insert bindings into query
            $query = str_replace(['%', '?'], ['%%', '%s'], $queryExecuted->sql);
            $query = vsprintf($query, $bindings);

            self::ci()->db->query_times[] = $queryExecuted->time;
            self::ci()->db->queries[] = $query;
        });
    }
}
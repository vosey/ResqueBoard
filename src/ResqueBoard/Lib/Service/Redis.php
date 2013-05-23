<?php
/**
 * Redis service class
 *
 * PHP version 5
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author        Wan Qi Chen <kami@kamisama.me>
 * @copyright     Copyright 2012, Wan Qi Chen <kami@kamisama.me>
 * @link          http://resqueboard.kamisama.me
 * @package       resqueboard
 * @subpackage    resqueboard.lib
 * @since         1.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace ResqueBoard\Lib\Service;

/**
 * Redis service class
 *
 * @subpackage      resqueboard.lib.service
 * @since            1.0.0
 * @author           Wan Qi Chen <kami@kamisama.me>
 */
class Redis
{
    use Loggable;

    public static $instance = null;

    public static $serviceInstance = null;

    public function __construct($settings)
    {
        $t = microtime(true);
        $redis = new \Redis();
        $redis->connect($settings['host'], $settings['port']);

        if (isset($settings['prefix'])) {
            $redis->setOption(\Redis::OPT_PREFIX, $settings['prefix'] . ':');
        }

        self::$serviceInstance = $redis;

        $queryTime = round((microtime(true) - $t) * 1000, 2);
        self::logQuery(
            array(
                'command' => 'CONNECTION to ' . $settings['host'] . ':' . $settings['port'],
                'time' => $queryTime
            )
        );
        self::$_totalTime += $queryTime;
        self::$_totalQueries++;
    }

    public static function init($settings)
    {
        if (self::$instance === null) {
            self::$instance = new Redis($settings);
        }

        return self::$instance;
    }

    public function pipeline($commands, $type = \Redis::PIPELINE)
    {
        $t = microtime(true);
        $redisPipeline = self::$serviceInstance->multi($type);
        foreach ($commands as $command) {
            call_user_func_array(array($redisPipeline, $command[0]), (array)$command[1]);
        }
        $results = $redisPipeline->exec();
        $queryTime = round((microtime(true) - $t) * 1000, 2);
        self::logQuery(
            array(
                'command' => array('PIPE' => $commands),
                'time' => $queryTime
            )
        );
        self::$_totalTime += $queryTime;
        self::$_totalQueries++;

        return $results;
    }
}

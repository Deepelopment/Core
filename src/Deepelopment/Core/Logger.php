<?php
/**
 * PHP Deepelopment Framework.
 *
 * @package Deepelopment/Core
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Core;

/**
 * Logger class.
 *
 * Usage example:
 * <code>
 * $logger = new \Deepelopment\Logger(
 *     array(
 *         array(
 *             // Level of reporting
 *             'level'           => Logger::ERROR | Logger::WARNING,
 *             // Maximum file size
 *             'maxFileSize'     => 0, // 2 * 1024 * 1024,
 *             // File path
 *             'path'            => '/path/to/file',
 *             // Flag specifying to rotate log file
 *             'rotate'          => FALSE,
 *             // Flag specifying to erase log file on start
 *             'eraseOnStart'    => FALSE,
 *         ),
 *         // ....
 *     ),
 * );
 * </code>
 *
 * @author deepeloper ({@see https://github.com/deepeloper})
 * @todo   Implement support of storage layers (not only files)
 */
class Logger
{
    /**#@+
     * Logging level
     */
    const NOTICE  = 0x01;
    const WARNING = 0x02;
    const ERROR   = 0x04;
    const DEBUG   = 0x08;
    const ALL     = 0x0F;

    /**#@-*/

    const FILE_ACCESS_MODE = 0777;

    /**
     * @var array
     */
    protected $config;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        foreach ($this->config as $config) {
            $path = $config['path'];
            if ($config['eraseOnStart'] && file_exists($path)) {
                unlink($path);
            }
        }
    }

    /**
     * Loggs message.
     *
     * @param  string $message
     * @param  int    $level
     * @return void
     */
    public function write($message, $level = self::DEBUG)
    {
        static
            $levels = array(
                self::NOTICE  => 'NOTICE ',
                self::WARNING => 'WARNING',
                self::ERROR   => 'ERROR  ',
                self::DEBUG   => 'DEBUG  '
            );

        $message = sprintf(
            "[ %s ] [ %s ] %s\n",
            date('Y-m-d H:i:s'),
            $levels[$level],
            $message
        );
        foreach ($this->config as $config) {
            $path = $config['path'];
            if (!($config['level'] & $level)) {
                continue;
            }
            $maxFileSize = $config['maxFileSize'];
            clearstatcache();
            if(
                $maxFileSize > 0 && file_exists($path) &&
                filesize($path) >= $maxFileSize
            ) {
                if ($config['rotate']) {
                    $backup = $path . '.bak';
                    if (file_exists($backup)) {
                        unlink($backup);
                    }
                    rename($path, $backup);
                } else {
                    unlink($path);
                }
            }
            file_put_contents($path, $message, FILE_APPEND);
            @chmod($path, self::FILE_ACCESS_MODE);
        }
    }
}

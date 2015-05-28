<?php
/**
 * PHP Deepelopment Framework
 *
 * @package Deepelopment
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Core;

use RuntimeException;

/**
 * Process lock class.
 *
 * <code>
 * use RuntimeException;
 * use Deepelopment\Core\ProcessLock;
 *
 * try {
 *     $lock = new ProcessLock(
 *         'path/to/lock',
 *         60 * 5, // 5 minutes
 *         TRUE
 *     );
 * } catch (RuntimeException $exception) {
 *     switch ($exception->getCode()) {
 *         case ProcessLock::PREV_LOCK_VALID:
 *             // Previous lock is valid, interrupt process.
 *             die;
 *         default:
 *             throw $exception;
 *     }
 * }
 *
 * // Some long time loop
 * while (TRUE) {
 *     // ...
 *     $lock->update();
 * }
 *
 * unset($lock);
 * </code>
 *
 * @package Deepelopment
 * @author  deepeloper ({@see https://github.com/deepeloper})
 */
class ProcessLock
{
    const PREV_LOCK_VALID          = 1;
    const CANNOT_DESTROY_PREV_LOCK = 2;
    const LOCK_EXISTS              = 3;
    const CANNOT_CREATE_LOCK       = 4;
    const CANNOT_DELETE_LOCK       = 5;
    const LOCK_DESTROYED           = 6;
    const LOCK_WRONG_PID           = 7;
    const CANNOT_UPDATE_LOCK       = 8;

    /**
     * Path to lock
     *
     * @var string
     */
    protected $path;

    /**
     * Process Id
     *
     * @var string
     */
    protected $pid;

    /**
     * @param  string $path         Path to lock
     * @param  string $ttl          Time to live for previous lock (in seconds)
     * @param  bool   $destroyPrev  Flag specifying to destroy previous lock if expired
     * @param  string $pid          Process Id
     * @throws RuntimeException  With codes
     *                           self::PREV_LOCK_VALID,
     *                           self::CANNOT_DESTROY_PREV_LOCK,
     *                           self::LOCK_EXISTS,
     *                           self::CANNOT_CREATE_LOCK
     */
    public function __construct($path, $ttl, $destroyPrev = FALSE, $pid = '')
    {
        $this->path = (string)$path;
        $this->pid  =
            '' === $pid
                ? mt_rand() . '.' . microtime(TRUE)
                : (string)$pid;

        if (file_exists($this->path)) {
            if ((time() - filemtime($this->path)) < $ttl) {
                throw new RuntimeException(
                    sprintf("Previous lock '%s' is still valid", $this->path),
                    self::PREV_LOCK_VALID
                );
            }
            if ($destroyPrev) {
                if (!@unlink($this->path)) {
                    throw new RuntimeException(
                        sprintf("Cannot destroy previous lock '%s'", $this->path),
                    self::CANNOT_DESTROY_PREV_LOCK
                    );
                }
            } else {
                throw new RuntimeException(
                    sprintf("Lock '%s' already exists", $this->path),
                    self::LOCK_EXISTS
                );
            }
        }
        if (!@file_put_contents($this->path, $this->pid)) {
            throw new RuntimeException(
                sprintf("Cannot create lock '%s'", $this->path),
                self::CANNOT_CREATE_LOCK
            );
        }
        @chmod($this->path, 0666);
    }

    /**
     * @throws RuntimeException  With code self::CANNOT_DELETE_LOCK
     */
    public function __destruct()
    {
        $this->validate();
        if (!@unlink($this->path)) {
            throw new RuntimeException(
                sprintf("Cannot delete lock '%s'", $this->path),
                self::CANNOT_DELETE_LOCK
            );
        }
    }

    /**
     * Validates lock presense and pid.
     *
     * @return void
     * @throws RuntimeException  With codes
     *                           self::LOCK_DESTROYED,
     *                           self::LOCK_WRONG_PID
     */
    public function validate()
    {
        if (!file_exists($this->path)) {
            throw new RuntimeException(
                sprintf("Lock '%s' destroyed", $this->path),
                self::LOCK_DESTROYED
            );
        }
        if (@file_get_contents($this->path) !== $this->pid) {
            throw new RuntimeException(
                sprintf("Lock '%s' contains other pid"),
                self::LOCK_WRONG_PID
            );
        }
    }

    /**
     * Update lock modification time.
     *
     * @return void
     * @throws RuntimeException  With code self::CANNOT_UPDATE_LOCK
     */
    public function update()
    {
        $this->validate();
        if (!@touch($this->path)) {
            throw new RuntimeException(
                sprintf("Cannot update lock '%s' time"),
                self::CANNOT_UPDATE_LOCK
            );
        }
    }
}

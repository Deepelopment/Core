<?php
/**
 * PHP Deepelopment Framework.
 *
 * @package Deepelopment/Core
 * @license Unlicense http://unlicense.org/
 */

### namespace Deepelopment\Core;

use InvalidArgumentException;
use RuntimeException;

/**
 * Entiry provider class.
 *
 * @author  deepeloper ({@see https://github.com/deepeloper})
 */
class EntityProvider
{
    /**
     * @var array
     */
    protected $entities = array();

    /**
     * @var string
     */
    protected $defaultId;

    /**
     * Pointer to default entity
     *
     * @var mixed
     */
    protected $defaultEntity;

    /**
     * Adds entity.
     *
     * @param  string $id
     * @param  mixed  &$entity
     * @param  bool   $asDefault  Flag specifying to use entity as default,
     *                            first added entity becames also default
     * @return void
     */
    public function add($id, &$entity, $asDefault = FALSE)
    {
        $this->validateId($id, FALSE);
        $this->entities[$id] = &$entity;
        if ($asDefault || 1 == sizeof($this->entities)) {
            $this->setDefaultId($id);
        }
    }

    /**
     * Sets default Id.
     *
     * @param  string $id
     * @return void
     * @throws RuntimeException
     */
    public function setDefaultId($id)
    {
        $this->validateId($id, TRUE);
        $this->defaultId = $id;
        $this->defaultEntity = &$this->entities[$id];
    }

    /**
     * Returns default.
     *
     * @return &mixed
     * @throws RuntimeException
     */
    public function &getDefault()
    {
        if (is_null($this->defaultId)) {
            throw new RuntimeException('No default entity found');
        }
        return $this->defaultEntity;
    }

    /**
     * Frees entity.
     *
     * If default entity will be deleted last added becames default.
     *
     * @param  string $id
     * @return void
     */
    public function free($id)
    {
        $this->validateId($id, TRUE);
        unset($this->entities[$id]);
        if ($id === $this->defaultId) {
            $qty = sizeof($this->entities);
            if ($qty) {
                $ids = array_keys($this->entities);
                $this->setDefaultId($ids[$qty - 1]);
            } else {
                $this->defaultId = NULL;
                $this->defaultEntity = NULL;
            }
        }
    }

    /**
     * Returns Id by entity.
     *
     * @return string
     * @throws RuntimeException
     */
    public function find($entity)
    {
        $id = array_search($entity, $this->entities, TRUE);
        if (FALSE === $id) {
            throw new RuntimeException('Passed entity not exists');
        }

        return $id;
    }

    /**
     * Validates id.
     *
     * @param  string $id
     * @param  bool   $shouldExist
     * @return void
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    protected function validateId($id, $shouldExist = FALSE)
    {
        if (!is_string($id)) {
            throw new InvalidArgumentException(
                'Passed entity Id is not a string'
            );
        }
        $exists = array_key_exists($id, $this->entities);
        $message =
            $shouldExist
                ? "Entity '%s' does not exist"
                : "Entity '%s' already exists";
        if ($shouldExist ? $exists : !$exists) {
            throw new RuntimeException(sprintf($message, $id));
        }
    }
}

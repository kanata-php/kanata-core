<?php

namespace Kanata\Drivers;

use Carbon\Carbon;
use Psr\SimpleCache\CacheInterface;
use Swoole\Table;
use Psr\SimpleCache\InvalidArgumentException;

class SessionTable implements CacheInterface
{
    const SESSION_KEY = 'kanata-session';

    protected static ?SessionTable $instance = null;

    protected static Table $table;

    private function __construct()
    {
        self::$table = $this->createTable();
    }

    public static function getInstance(): SessionTable
    {
        if (self::$instance === null) {
            self::$instance = new SessionTable;
        }
        return self::$instance;
    }

    public static function destroyInstance(): void
    {
        self::$table->destroy();
        self::$instance = null;
    }

    protected function createTable(): Table
    {
        $table = new Table(1024);
        $table->column('data', Table::TYPE_STRING, 1000);
        $table->column('ttl', Table::TYPE_INT, 8);
        $table->create();
        return $table;
    }

    public static function getTable(): Table
    {
        return self::$table;
    }

    /**
     * Fetches a value from the cache.
     *
     * @param string $key     The unique key of this item in the cache.
     * @param mixed  $default Default value to return if the key does not exist.
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     *
     * @throws InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->has($key)) {
            $value = self::$table->get($key);
            if (Carbon::now()->getTimestamp() < $value['ttl']) {
                return json_decode($value['data'], true);
            }
        }
        return $default;
    }

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string                 $key   The key of the item to store.
     * @param mixed                  $value The value of the item to store, must be serializable.
     * @param null|int|\DateInterval $ttl   Optional. The TTL value of this item. If no value is sent and
     *                                      the driver supports TTL then the library may set a default value
     *                                      for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        return self::$table->set($key, [
            'data' => json_encode($value),
            'ttl' => $ttl ?? Carbon::now()->addHours(4)->getTimestamp(),
        ]);
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool True if the item was successfully removed. False if there was an error.
     *
     * @throws InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function delete(string $key): bool
    {
        return self::$table->del($key);
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    public function clear(): bool
    {
        $result = self::$table->destroy();
        $this->createTable();
        return $result;
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable $keys    A list of keys that can obtained in a single operation.
     * @param mixed    $default Default value to return for keys that do not exist.
     *
     * @return iterable A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
     *
     * @throws InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $data = [];
        foreach ($keys as $key) {
            $data[] = $this->get($key);
        }
        return !empty($data) ? $data : $default;
    }

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable               $values A list of key => value pairs for a multiple-set operation.
     * @param null|int|\DateInterval $ttl    Optional. The TTL value of this item. If no value is sent and
     *                                       the driver supports TTL then the library may set a default value
     *                                       for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws InvalidArgumentException
     *   MUST be thrown if $values is neither an array nor a Traversable,
     *   or if any of the $values are not a legal value.
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        $inserted = [];
        $data = [];
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value)) {
                $this->deleteMultiple($inserted);
                return false;
            }
            $inserted[] = $key;
        }
        return true;
    }

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param iterable $keys A list of string-based keys to be deleted.
     *
     * @return bool True if the items were successfully removed. False if there was an error.
     *
     * @throws InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            if ($this->has($key)) {
                $this->delete($key);
            }
        }

        return true;
    }

    /**
     * Determines whether an item is present in the cache.
     *
     * NOTE: It is recommended that has() is only to be used for cache warming type purposes
     * and not to be used within your live applications operations for get/set, as this method
     * is subject to a race condition where your has() will return true and immediately after,
     * another script can remove it making the state of your app out of date.
     *
     * @param string $key The cache item key.
     *
     * @return bool
     *
     * @throws InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function has(string $key): bool
    {
        return self::$table->exists($key);
    }
}
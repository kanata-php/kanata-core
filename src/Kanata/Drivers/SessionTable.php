<?php

namespace Kanata\Drivers;

use Carbon\Carbon;
use Exception;
use Kanata\Drivers\Session\Filesystem;
use Kanata\Drivers\Session\Interfaces\SessionInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

class SessionTable implements CacheInterface
{
    const SESSION_KEY = 'kanata-session';

    protected static ?SessionTable $instance = null;

    protected static ?SessionInterface $driver = null;

    /**
     * @throws Exception
     */
    private function __construct()
    {
        self::$driver = $this->getDriver();
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
        self::$driver->destroy();
        self::$driver = null;
        self::$instance = null;
    }

    /**
     * @throws Exception
     */
    protected function getDriver(): SessionInterface
    {
        if (self::$driver !== null) {
            return self::$driver;
        }

        if ('file' === config('app.session-driver')) {
            return Filesystem::getInstance();
        }

        throw new Exception('Session driver not found');
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
            $value = json_decode(self::$driver->get($key), true);

            if (null === $value) {
                throw new Exception('Invalid session data');
            }

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
        return self::$driver->set($key, [
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
        return self::$driver->delete($key);
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    public function clear(): bool
    {
        $result = self::$driver->destroy();
        return $result;
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
        return self::$driver->exists($key);
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $this->get($key, $default);
        }

        return $data;
    }

    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $result = $this->set($key, $value, $ttl);

            if (!$result) {
                return false;
            }
        }

        return $result;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $result = $this->delete($key);

            if (!$result) {
                return false;
            }
        }

        return $result;
    }
}

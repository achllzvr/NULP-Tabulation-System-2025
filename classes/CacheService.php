<?php
/**
 * CacheService - very lightweight request-lifecycle cache only (non-persistent).
 */
class CacheService {
    private static array $store = [];
    public static function remember(string $key, callable $cb) {
        if (array_key_exists($key, self::$store)) return self::$store[$key];
        return self::$store[$key] = $cb();
    }
    public static function put(string $key, $value): void { self::$store[$key] = $value; }
    public static function get(string $key, $default=null){ return self::$store[$key] ?? $default; }
    public static function forget(string $prefix): void {
        foreach (array_keys(self::$store) as $k) {
            if (str_starts_with($k, $prefix)) unset(self::$store[$k]);
        }
    }
    public static function clear(): void { self::$store = []; }
}

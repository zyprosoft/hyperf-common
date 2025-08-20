<?php

declare(strict_types=1);

namespace ZYProSoft\Util;

use Hyperf\Utils\Str;

/**
 * 驼峰命名转换工具类
 * 提供灵活的数组键名驼峰转换功能，包含性能优化
 * 在Swoole环境下使用进程级别的静态缓存，确保缓存生效
 * 全静态方法设计，无需实例化，性能更优
 */
class CamelCaseUtil
{
    /**
     * 进程级别的静态缓存，在Swoole Worker进程中共享
     * 每个Worker进程都有自己的缓存实例，跨请求共享
     */
    private static array $processCache = [];

    /**
     * 进程缓存大小限制
     */
    private const MAX_CACHE_SIZE = 1000;

    /**
     * 将数组键名转换为驼峰命名
     * 
     * @param array $data 要转换的数据
     * @return array 转换后的数据
     */
    public static function convert(array $data): array
    {
        // 空数组直接返回
        if (empty($data)) {
            return $data;
        }

        $result = [];
        foreach ($data as $key => $value) {
            $newKey = self::processKey($key);
            
            // 处理嵌套数组
            if (is_array($value)) {
                $result[$newKey] = self::convert($value);
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    /**
     * 处理单个键名（使用进程级别缓存优化）
     * 
     * @param mixed $key 原始键名
     * @return mixed 处理后的键名
     */
    private static function processKey($key)
    {
        // 如果是字符串键
        if (is_string($key)) {
            // 检查是否为空字符串（保持空字符串键不变）
            if (empty($key)) {
                return $key;
            }

            // 检查进程级别缓存
            if (isset(self::$processCache[$key])) {
                return self::$processCache[$key];
            }

            // 转换为驼峰命名
            $camelKey = Str::camel($key);
            
            // 添加到进程缓存
            self::addToProcessCache($key, $camelKey);
            
            return $camelKey;
        }

        // 如果是数字键（保持数字索引不变）
        if (is_numeric($key)) {
            return $key;
        }

        // 其他类型保持原样
        return $key;
    }

    /**
     * 添加键名到进程缓存
     * 
     * @param string $originalKey 原始键名
     * @param string $camelKey 驼峰键名
     */
    private static function addToProcessCache(string $originalKey, string $camelKey): void
    {
        // 限制缓存大小，避免内存泄漏
        if (count(self::$processCache) >= self::MAX_CACHE_SIZE) {
            // 清除一半缓存，保持LRU特性
            $keys = array_keys(self::$processCache);
            $half = array_slice($keys, 0, self::MAX_CACHE_SIZE / 2);
            foreach ($half as $key) {
                unset(self::$processCache[$key]);
            }
        }
        
        self::$processCache[$originalKey] = $camelKey;
    }

    /**
     * 快速转换（使用默认配置）
     * 
     * @param array $data 要转换的数据
     * @return array 转换后的数据
     */
    public static function quick(array $data): array
    {
        return self::convert($data);
    }
}
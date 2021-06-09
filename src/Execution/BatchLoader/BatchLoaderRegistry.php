<?php

namespace Nuwave\Lighthouse\Execution\BatchLoader;

abstract class BatchLoaderRegistry
{
    /**
     * Active BatchLoader instances.
     *
     * @var array<string, object>
     */
    protected static $instances = [];

    /**
     * Return an instance of a BatchLoader for a specific field.
     *
     * @param  array<int|string>  $pathToField  Path to the GraphQL field from the root, is used as a key for BatchLoader instances
     * @param  callable(): object  $makeInstance  Function to instantiate the instance once
     * @return object The result of calling makeInstance
     *
     * @throws \Exception
     */
    public static function instance(array $pathToField, callable $makeInstance): object
    {
        // The path to the field serves as the unique key for the instance
        $instanceKey = static::instanceKey($pathToField);

        if (isset(self::$instances[$instanceKey])) {
            return self::$instances[$instanceKey];
        }

        return self::$instances[$instanceKey] = $makeInstance();
    }

    /**
     * Remove all stored BatchLoaders.
     *
     * This is called after Lighthouse has resolved a query, so multiple
     * queries can be handled in a single request/session.
     */
    public static function forgetInstances(): void
    {
        self::$instances = [];
    }

    /**
     * Generate a unique key for the instance, using the path in the query.
     *
     * @param  array<int|string>  $path
     */
    protected static function instanceKey(array $path): string
    {
        $significantPathSegments = array_filter(
            $path,
            static function ($segment): bool {
                // Ignore numeric path entries, as those signify a list of fields.
                // Combining the queries for those is the very purpose of the
                // batch loader, so they must not be included.
                return ! is_numeric($segment);
            }
        );

        // Using . as the separator would combine nested relations
        return implode('|', $significantPathSegments);
    }
}

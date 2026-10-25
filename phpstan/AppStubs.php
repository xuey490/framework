<?php

declare(strict_types=1);

/**
 * PHPStan-only stubs for application classes that live in the consuming project.
 */

namespace App\Middlewares {
    final class AuthMiddleware
    {
    }

    final class CacheMiddleware
    {
    }

    final class LogMiddleware
    {
    }

    final class PermissionMiddleware
    {
    }

    final class RoleMiddleware
    {
    }

    final class UserActionMiddleware
    {
    }

    final class ValidateMiddleware
    {
    }
}

namespace App\Services {
    final class ExcelExportService
    {
        /**
         * @param array<mixed> $params
         * @param callable(callable): void $chunkHandler
         * @return array<mixed>
         */
        public static function export(array $params, callable $chunkHandler): array
        {
            return [];
        }
    }

    final class PluginService
    {
        /**
         * @param array<mixed> $file
         * @return array<mixed>
         */
        public function uploadAndInstall(array $file): array
        {
            return [];
        }
    }
}

namespace App\Services\Casbin {
    use Casbin\Model\Model;
    use Casbin\Persist\Adapter;

    final class DatabaseAdapter implements Adapter
    {
        public function __construct(string $tableName = '', ?string $connection = null)
        {
        }

        public function loadPolicy(Model $model): void
        {
        }

        public function savePolicy(Model $model): void
        {
        }

        /**
         * @param array<int, string> $rule
         */
        public function addPolicy(string $sec, string $ptype, array $rule): void
        {
        }

        /**
         * @param array<int, string> $rule
         */
        public function removePolicy(string $sec, string $ptype, array $rule): void
        {
        }

        public function removeFilteredPolicy(string $sec, string $ptype, int $fieldIndex, string ...$fieldValues): void
        {
        }
    }
}

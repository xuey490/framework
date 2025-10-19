<?php

declare(strict_types=1);

/**
 * This file is part of Navaphp Framework.
 *
 * @link     https://github.com/xuey490/novaphp
 * @license  https://github.com/xuey490/novaphp/blob/main/LICENSE
 *
 * @Filename: %filename%
 * @Date: 2025-10-16
 * @Developer: xuey863toy
 * @Email: xuey863toy@gmail.com
 */

namespace Framework\Core;

use Framework\Attributes\Route as RouteAttribute;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class AttributeRouteLoader
{
    private string $controllerDir;

    private string $controllerNamespace;

    public function __construct(string $controllerDir, string $controllerNamespace = 'App\Controllers')
    {
        $this->controllerDir       = rtrim($controllerDir, '/');
        $this->controllerNamespace = rtrim($controllerNamespace, '\\');
    }

    /**
     * 扫描控制器目录并加载所有注解路由.
     */
    public function loadRoutes(): RouteCollection
    {
        $routes          = new RouteCollection();
        $controllerFiles = $this->scanDirectory($this->controllerDir);

        foreach ($controllerFiles as $file) {
            $className = $this->convertFileToClass($file);
            if (! class_exists($className)) {
                continue;
            }

            $refClass = new \ReflectionClass($className);
            if ($refClass->isAbstract()) {
                continue;
            }

            // --- 控制器级别的Route定义（用于继承 prefix / middleware / group） ---
            $classAttr = $this->getRouteAttribute($refClass);

            $classPrefix       = $classAttr?->prefix       ?? '';
            $classMiddleware   = $classAttr?->middleware   ?? [];
            $classGroup        = $classAttr?->group        ?? null;
            $classDefaults     = $classAttr?->defaults     ?? null;
            $classRequirements = $classAttr?->requirements ?? null;

            // --- 遍历所有方法 ---
            foreach ($refClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $methodAttr = $this->getRouteAttribute($method);
                if (! $methodAttr) {
                    // 自动生成默认路由：/demo/list
                    $autoPath = '/' . strtolower(str_replace('Controller', '', $refClass->getShortName()))
                        . '/' . $method->getName();

                    $route = new Route(
                        $autoPath,
                        defaults: [
                            '_controller' => "{$className}::{$method->getName()}",
                            '_group'      => $classGroup,
                            '_middleware' => $classMiddleware,
                        ],
                        methods: ['GET']
                    );

                    $autoName = strtolower(str_replace('\\', '_', $className)) . '_' . $method->getName();
                    $routes->add($autoName, $route);
                    continue;
                }

                // ====== 路由路径组装 ======
                $path = $this->joinPath($classPrefix, $methodAttr->path);

                // ====== 合并中间件（去重）======
                $classMiddleware  = $classAttr->middleware  ?? [];
                $methodMiddleware = $methodAttr->middleware ?? [];

                // 合并后去重，确保索引重排
                $mergedMiddleware = array_values(array_unique(array_merge(
                    (array) $classMiddleware,
                    (array) $methodMiddleware
                )));

                // ====== 合并分组（可用于前缀或后续逻辑） ======
                $group = $methodAttr->group ?? $classGroup ?? null;

                // ====== 构建路由对象 ======
                /*
                $route = new Route(
                    $path,
                    [
                        '_controller' => "{$className}::{$method->getName()}",
                    ],
                    [],
                    [
                        '_middleware' => $mergedMiddleware, // ✅ 正确存储中间件
                        '_group' => $group,
                    ],
                    '',
                    [],
                    $methodAttr->methods ?: ['GET']
                );
                */

                $route = new Route(
                    $path,
                    array_merge(['_controller' => "{$className}::{$method->getName()}"], $classDefaults),
                    $classRequirements,
                    [
                        '_middleware' => $mergedMiddleware, // ✅ 正确存储中间件
                        '_group'      => $group,
                    ],
                    '',
                    [],
                    $methodAttr->methods ?: ['GET']
                );

                // ====== 路由命名 ======
                $routeName = $methodAttr->name
                    ?? $this->generateRouteName($className, $method->getName());

                $routes->add($routeName, $route);
            }
        }

        return $routes;
    }

    /**
     * 从类或方法中提取 Route Attribute.
     */
    private function getRouteAttribute(\Reflector $ref): ?RouteAttribute
    {
        $attributes = $ref->getAttributes(RouteAttribute::class);

        return $attributes ? $attributes[0]->newInstance() : null;
    }

    /**
     * 扫描控制器目录，返回所有PHP文件.
     */
    private function scanDirectory(string $dir): array
    {
        $rii   = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        $files = [];
        foreach ($rii as $file) {
            if ($file->isDir()) {
                continue;
            }
            if (pathinfo($file->getFilename(), PATHINFO_EXTENSION) === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * 将文件路径转换为完整类名
     * 例：app/Controllers/Api/UserController.php → App\Controllers\Api\UserController.
     */
    private function convertFileToClass(string $file): string
    {
        $relative = str_replace($this->controllerDir, '', $file);
        $relative = trim(str_replace(['/', '.php'], ['\\', ''], $relative), '\\');

        return "{$this->controllerNamespace}\\{$relative}";
    }

    /**
     * 拼接控制器级别 prefix 与方法级别 path.
     */
    private function joinPath(string $prefix, string $path): string
    {
        $prefix = rtrim($prefix ?? '', '/');
        $path   = '/' . ltrim($path ?? '', '/');

        return $prefix . $path;
    }

    /**
     * 自动生成路由名称.
     */
    private function generateRouteName(string $class, string $method): string
    {
        $class = str_replace([$this->controllerNamespace . '\\', '\Controller'], '', $class);
        $class = strtolower(str_replace('\\', '.', $class));

        return "{$class}.{$method}";
    }
}

<?php

namespace Modules\Admin\Middleware;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Modules\Admin\Utils\Admin;
use Modules\Admin\Traits\UrlWhitelist;
use Modules\Admin\Utils\PermissionChecker;
use Modules\Admin\Entities\AdminPermission;

abstract class PermissionMiddleware
{
    use UrlWhitelist;

    /**
     * @var string
     */
    protected $middlewarePrefix = 'admin.permission:';
    /**
     * @var array url 白名单
     */
    protected $urlWhitelist = [];

    /**
     * Handle an incoming request.
     *
     * @param array $args
     *
     * @return mixed
     */
    public function handle(Request $request, \Closure $next, ...$args)
    {
        if (!empty($args) || $this->shouldPassThrough($request)) {
            return $next($request);
        }
//        if (!Admin::user()) {
//            PermissionChecker::error();
//        }

        if ($this->checkRoutePermission($request)) {
            return $next($request);
        }

        if (!Admin::user()->allPermissions()->first(
            function (AdminPermission $permission) use ($request) {
                return PermissionChecker::shouldPassThrough($permission, $request);
            })) {
            PermissionChecker::error();
        }

        return $next($request);
    }

    /**
     * 如果路由的中间件组中, 有以 'admin.permission:' 开头的, 说明是单独设置了权限, 要优先处理.
     *
     * @return bool
     */
    public function checkRoutePermission(Request $request)
    {
        if (!$middleware = collect($request->route()->middleware())->first(function ($middleware) {
            return Str::startsWith($middleware, $this->middlewarePrefix);
        })) {
            return false;
        }

        $args = explode(',', str_replace($this->middlewarePrefix, '', $middleware));

        $method = array_shift($args);

        if (!method_exists(PermissionChecker::class, $method)) {
            throw new \InvalidArgumentException("无效的权限检测方法 [ $method ]");
        }

        call_user_func_array([PermissionChecker::class, $method], [$args]);

        return true;
    }

    protected function urlWhitelist(): array
    {
        return array_map(function ($url) {
            return Admin::url($url);
        }, $this->urlWhitelist);
    }
}

<?php

$api = app('Dingo\Api\Routing\Router');

$params = [
    'prefix' => 'admin',
    'version' => 'v1.0',
    'namespace' => 'Modules\Admin\Controllers',
];

$mwNotLogin = ['middleware' => [
    'api.throttle',
    'cors',
],
    'limit' => config('api.rate_limits.sign.limit'),
    'expires' => config('api.rate_limits.sign.expires'),
];

$mwLogin = ['middleware' => [
    'api.throttle',
    'cors',
    'jwt.auth',
],
    'limit' => config('api.rate_limits.sign.limit'),
    'expires' => config('api.rate_limits.sign.expires'),
];

// 不需要登录的接口
$api->group(array_merge($params, $mwNotLogin), function ($api) {
    $api->group(['prefix' => '/auth'], function ($api) {
        // 后台登录
        $api->post('/login', 'AdminController@login')->name('auth.login');
    });
});

// 需要登录的接口
$api->group(array_merge($params, $mwLogin), function ($api) {
    $api->group(['prefix' => '/auth'], function ($api) {
        // 当前用户及其角色、权限
        $api->get('user1', 'AdminController@currentUser')->name('auth.current');
        $api->get('user2', 'AdminController@user')->name('auth.user');
        $api->put('user', 'AdminController@updateUser')->name('auth.update');
    });

    $api->resource('admins', 'AdminUserController');
    $api->resource('permissions', 'AdminPermissionController');
    $api->resource('roles', 'AdminRoleController');
});

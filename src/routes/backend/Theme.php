<?php

/**
 * Theme Routes
 */

Route::group(
    ['middleware' => ['auth:admin'], 'as' => 'backend.theme.', 'namespace' => '\App\Http\Controllers\Backend', 'prefix' => Config::get('project')['adminPrefix']], function () {
    }
);
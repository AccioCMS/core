<?php
Route::group(
    ['middleware' => ['auth:admin'], 'as' => 'backend.plugin.', 'namespace' => '\App\Http\Controllers\Backend', 'prefix' => Config::get('project')['adminPrefix']], function () {
        /**
         * GET
         */
    }
);
<?php

/**
 * Auth routes
 */
Route::group(['as' => 'backend.auth.','namespace' => 'App\Http\Controllers\Backend\Auth','prefix' => Config::get('project')['adminPrefix']],function () {

    /**
     * GET
     */
    Route::get('/login', 'LoginController@showLoginForm')->name('login');
    Route::post('/login-request', 'LoginController@login')->name('loginRequest');
});




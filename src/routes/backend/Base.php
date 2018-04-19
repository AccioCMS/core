<?php
/**
 * Base Admin routes
 */
Route::group(['middleware' => ['auth:admin'], 'as' => 'backend.base.', 'namespace' => '\App\Http\Controllers\Backend', 'prefix' => Config::get('project')['adminPrefix']], function (){

    /**
     * GET
     */
    Route::get('','GeneralController@index')->name('index');
    Route::get('/{lang}','GeneralController@index')->name('index.lang');
    Route::get('/logout-request','GeneralController@logoutUser')->name('logoutUser');
});

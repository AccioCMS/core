<?php

/**
 * Permissions Routes
 */

Route::group(['middleware' => ['auth:admin'], 'as' => 'backend.permissions.', 'namespace' => '\App\Http\Controllers\Backend', 'prefix' => Config::get('project')['adminPrefix']], function (){
    /**
     * GET
     */
    Route::get('/{lang}/permissions/{view}', 'PermissionController@index')->name('index');
    Route::get('/{lang}/json/permissions/users-groups', 'PermissionController@getUserGroups')->name('getUserGroups');
    Route::get('/{lang}/json/permissions/delete/{id}', 'PermissionController@delete')->name('delete');
    Route::get('/{lang}/permissions/{view}/{id}', 'PermissionController@single')->name('single');
    Route::get('/{lang}/json/permissions/get-permissions', 'PermissionController@getPermissions')->name('getPermissions');
    Route::get('/{lang}/json/permissions/get-all-permissions-options', 'PermissionController@getAllPermissionsOptions')->name('getAllPermissionsOptions');

    /**
     * POST
     */
    Route::post('/json/permissions/bulk-delete', 'PermissionController@bulkDelete')->name('bulkDelete');
    Route::post('/json/permissions/get-list', 'PermissionController@getList')->name('getList');
    Route::post('/json/permissions/get-list-values', 'PermissionController@getListValues')->name('getListValues');
    Route::post('/json/permissions/store', 'PermissionController@store')->name('store');
});
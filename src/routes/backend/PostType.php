<?php

/**
 * Post Types Routes
 */

Route::group(
    ['middleware' => ['auth:admin'], 'as' => 'backend.postType.', 'namespace' => '\App\Http\Controllers\Backend', 'prefix' => Config::get('project')['adminPrefix']], function () {
        /**
         * GET
         */
        Route::get('/{lang}/post-type/{view}', 'PostTypeController@index')->name('index');
        Route::get('/{lang}/post-type/{view}/{id}', 'PostTypeController@single')->name('single');
        Route::get('/{lang}/json/post-type/get-all', 'PostTypeController@getAll')->name('getAll');
        Route::get('/{lang}/json/post-type/delete/{id}', 'PostTypeController@delete')->name('delete');
        Route::get('/{lang}/json/post-type/details/{id}', 'PostTypeController@detailsJSON')->name('detailsJSON');
        Route::get('/{lang}/json/post-type/check-slug/{slug}', 'PostTypeController@getSlug')->name('getSlug');
        Route::get('/{lang}/json/search/post-type/{term}', 'PostTypeController@makeSearchParent')->name('makeSearchParent');
        Route::get('/{lang}/json/post-type/get-tables', 'PostTypeController@getTables')->name('getTables');
        Route::get('/{lang}/json/post-type/get-by-id/{id}', 'PostTypeController@findByID')->name('findByID');
        Route::get('/{lang}/json/post-type/get-by-slug/{slug}', 'PostTypeController@getBySlug')->name('getBySlug');
        Route::get('/{lang}/json/post-type/delete-field/{postTypeSlug}/{fieldSlug}', 'PostTypeController@deleteField')->name('deleteField');
        Route::get('/{lang}/post-type/json/menuPanelItems', 'PostTypeController@menuPanelItems')->name('menuPanelItems');

        /**
         * POST
         */
        Route::post('/json/post-type/bulk-delete', 'PostTypeController@bulkDelete')->name('bulkDelete');
        Route::post('/json/post-type/store', 'PostTypeController@store')->name('store');
        Route::post('/json/post-type/storeUpdate', 'PostTypeController@storeUpdate')->name('storeUpdate');
    }
);
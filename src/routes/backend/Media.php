<?php

/**
 * Media Routes
 */

Route::group(
    ['middleware' => ['auth:admin'], 'as' => 'backend.media.', 'namespace' => '\App\Http\Controllers\Backend', 'prefix' => Config::get('project')['adminPrefix']], function () {
        /**
         * GET
         */
        Route::get('/{lang}/media/{view}', 'MediaController@index')->name('index');
        Route::get('{lang}/media/json/get-list/{pagination}', 'MediaController@getList')->name('getList');

        /**
         * POST
         */
        Route::post('/media/json/store', 'MediaController@store')->name('store');
        Route::post('/media/json/edit', 'MediaController@edit')->name('edit');
        Route::post('/media/json/delete', 'MediaController@delete')->name('delete');
        Route::post('/media/json/assign-watermark', 'MediaController@assignWatermark')->name('assignWatermark');
        Route::post('/media/json/crop-image', 'MediaController@cropImage')->name('cropImage');
        Route::post('/media/json/search', 'MediaController@searchMedia')->name('search');
    }
);
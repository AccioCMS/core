<?php

/**
 * Categories Routes
 */

Route::group(['middleware' => ['auth:admin'], 'as' => 'backend.category.', 'namespace' => '\App\Http\Controllers\Backend', 'prefix' => Config::get('project')['adminPrefix']], function (){
    /**
     * GET
     */
    Route::get('/{lang}/json/category/get-all/{postTypeID}', 'CategoryController@getAllByPostType')->name('getAllByPostType');
    Route::get('/{lang}/json/category/get-tree/{postTypeID}', 'CategoryController@getTree')->name('getTree');
    Route::get('/{lang}/json/category/get-all', 'CategoryController@getAll')->name('getAll');
    Route::get('/{lang}/json/category/get-latest', 'CategoryController@getLatest')->name('getLatest');
    Route::get('/{lang}/json/category/delete/{id}', 'CategoryController@delete')->name('delete');
    Route::get('/{lang}/json/category/details/{id}', 'CategoryController@detailsJSON')->name('detailsJSON');
    Route::get('/{lang}/json/category/get-all-without-pagination', 'CategoryController@getAllWithoutPagination')->name('getAllWithoutPagination');
    Route::get('/{lang}/json/category/get-all-without-pagination-by-post-type/{postType}', 'CategoryController@getAllWithoutPaginationByPostType')->name('getAllWithoutPaginationByPostType');
    Route::get('/{lang}/json/category/check-slug/{postTypeID}/{slug}', 'CategoryController@getSlug')->name('getSlug');
    Route::get('/{lang}/json/category/get-post-type/{categoryID}', 'CategoryController@getPostType')->name('getPostType');
    Route::get('/{lang}/json/category/{postTypeID}/search/{term}', 'CategoryController@makeSearch')->name('makeSearch');
    Route::get('/{lang}/post-type/category/{postTypeID}/search/{term}', 'CategoryController@search')->name('search');

    Route::get('/{lang}/category/json/menuPanelItems', 'CategoryController@menuPanelItems')->name('menuPanelItems');

    /**
     * POST
     */
    Route::post('/json/category/bulk-delete', 'CategoryController@bulkDelete')->name('bulkDelete');
    Route::post('/json/category/store', 'CategoryController@store')->name('store');
    Route::post('/json/category/storeUpdate', 'CategoryController@storeUpdate')->name('storeUpdate');
    Route::post('/json/category/sort', 'CategoryController@sort')->name('sort');
});

/**
 * Frontend routes that are accessed via admin panel
 */

Route::group(['middleware' => ['auth'], 'as' => 'Backend.Categories.', 'namespace' => \App\Models\Theme::getNamespace()], function () {
    Route::get('json/posts/get-templates', 'CategoryController@menuLinkRoutes')->name('menuLinkRoutes');
});
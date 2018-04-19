<?php

/**
 * Tags Routes
 */

Route::group(['middleware' => ['auth:admin'], 'as' => 'backend.tag.', 'namespace' => '\App\Http\Controllers\Backend', 'prefix' => Config::get('project')['adminPrefix']], function (){
    /**
     * GET
     */
    Route::get('/{lang}/json/tags/get-all/{postTypeID}', 'TagController@getAllByPostType')->name('getAllByPostType');
    Route::get('/{lang}/json/tags/delete/{id}', 'TagController@delete')->name('delete');
    Route::get('/{lang}/json/tags/details/{id}', 'TagController@detailsJSON')->name('detailsJSON');
    Route::get('/{lang}/post-type/tags/{postTypeID}/search/{term}', 'TagController@search')->name('search');
    Route::get('/{lang}/json/tags/{postTypeID}/search/{term}', 'TagController@makeSearch')->name('makeSearch');
    Route::get('/{lang}/json/tags/get-all-without-pagination-by-post-type/{postType}', 'TagController@getAllWithoutPaginationByPostType')->name('getAllWithoutPaginationByPostType');
    Route::get('/{lang}/json/tags/check-slug/{postTypeID}/{slug}', 'TagController@getSlug')->name('getSlug');

    /**
     * POST
     */
    Route::post('/json/tags/bulk-delete', 'TagController@bulkDelete')->name('bulkDelete');
    Route::post('/json/tags/store', 'TagController@store')->name('store');
    Route::post('/json/tags/storeUpdate', 'TagController@storeUpdate')->name('storeUpdate');
    Route::post('/json/tags/sort', 'TagController@sort')->name('sort');
});
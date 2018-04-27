<?php

/**
 * Posts Routes
 */
Route::group(['middleware' => ['auth:admin'], 'as' => 'backend.post.', 'namespace' => '\App\Http\Controllers\Backend', 'prefix' => Config::get('project')['adminPrefix']], function (){
    /**
     * GET
     */
    Route::get('/{lang}/posts/{post_type}/{view}', 'PostController@postsIndex')->name('index');
    Route::get('/{lang}/posts/{post_type}/{view}/{id}', 'PostController@postsSingle')->name('single');
    Route::get('/{lang}/json/posts/details/{post_type}/{id}', 'PostController@detailsJSON')->name('detailsJSON');
    Route::get('/{lang}/json/posts/{post_type}/columns', 'PostController@getColumns')->name('getColumns');
    Route::get('/{lang}/json/posts/get-all/{post_type}', 'PostController@getAllPosts')->name('getAllPosts');
    Route::get('/{lang}/json/posts/get-all-of-category/{post_type}/{categoryID}', 'PostController@getAllPostsOfCategory')->name('getAllPostsOfCategory');
    Route::get('/{lang}/json/posts/delete/{post_type}/{id}', 'PostController@delete')->name('delete');
    Route::get('/{lang}/json/posts/advancedSearch/{post_type}', 'PostController@getAdvancedSearchFields')->name('getAdvancedSearchFields');
    Route::get('/{lang}/json/posts/get-all-posts-without-pagination/{post_type}', 'PostController@getAllPostsWithoutPagination')->name('getAllPostsWithoutPagination');
    Route::get('/{lang}/posts/search/{post_type}/{term}', 'PostController@search')->name('search');
    Route::get('/{lang}/json/posts/check-slug/{postType}/{slug}', 'PostController@getSlug')->name('getSlug');
    Route::get('/{lang}/json/posts/search/{post_type}/{term}', 'PostController@makeSearch')->name('makeSearch');
    Route::get('/{lang}/search-advanced/posts/{post_type}/{id}', 'PostController@searchAdvanced')->name('searchAdvanced');
    Route::get('/{lang}/post/json/menuPanelItems/{postTypeSlug}', 'PostController@menuPanelItems')->name('menuPanelItems');
    Route::get('/{lang}/post/json/get-data-for-create/{postTypeSlug}', 'PostController@getDataForCreate')->name('getDataForCreate');
    /**
     * POST
     */
    Route::post('/json/posts/bulk-delete', 'PostController@bulkDelete')->name('bulkDelete');
    Route::post('/json/posts/store', 'PostController@store')->name('store');
    Route::post('/json/posts/storeUpdate', 'PostController@storeUpdate')->name('storeUpdate');
    Route::post('/json/posts/advanced-search-results', 'PostController@advancedSearch')->name('advancedSearch');
});


/**
 * Frontend routes that are accessed via admin panel
 */

Route::group(['middleware' => ['auth'], 'as' => 'Backend.Posts.', 'namespace' => \App\Models\Theme::controllersNameSpace()], function () {
    Route::get('json/posts/get-templates', 'PostController@menuLinkRoutes')->name('menuLinkRoutes');
});
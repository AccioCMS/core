<?php

/**
 * Album Routes
 */

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['auth:admin'], 'as' => 'Backend.Album.', 'namespace' => '\App\Http\Controllers\Backend', 'prefix' => Config::get('project')['adminPrefix']], function (){
    /**
     * GET
     */
    Route::get('/{lang}/json/album/get-all/{pagination}', 'AlbumController@getAll')->name('getAll');
    Route::get('/{lang}/json/album/delete/{albumID}', 'AlbumController@delete')->name('delete');
    Route::get('/{lang}/json/album/details/{albumID}', 'AlbumController@details')->name('details');
    Route::get('/{lang}/json/album/get-album-relation/{mediaID}', 'AlbumController@getAlbumRelation')->name('getAlbumRelation');
    Route::get('{lang}/json/album/get-media-ids-of-album/{albumID}','AlbumController@getMediaIDsOfAlbum')->name('getMediaIDsOfAlbum');

    /**
     * POST
     */
    Route::post('/json/album/store', 'AlbumController@store');
});
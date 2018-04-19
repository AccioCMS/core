<?php

/**
 * Language Routes
 */

Route::group(['middleware' => ['auth:admin'], 'as' => 'backend.language.', 'namespace' => '\App\Http\Controllers\Backend', 'prefix' => Config::get('project')['adminPrefix']], function (){
    /**
     * GET
     */
    Route::get('/{lang}/language/{view}/{id}', 'LanguageController@single')->name('single');
    Route::get('/{lang}/language/{view}', 'LanguageController@index')->name('index');
    Route::get('/{lang}/json/language/get-all', 'LanguageController@getAll')->name('getAll');
    Route::get('/{lang}/json/language/delete/{id}', 'LanguageController@delete')->name('delete');
    Route::get('/{lang}/json/language/details/{id}', 'LanguageController@detailsJSON')->name('detailsJSON');
    Route::get('/{lang}/json/language/get-default-language', 'LanguageController@getDefaultLanguage')->name('getDefaultLanguage');

    /**
     * POST
     */
    Route::post('/json/language/store', 'LanguageController@store')->name('store');
    Route::post('/json/language/bulk-deletes', 'LanguageController@bulkDelete')->name('bulkDelete');
    Route::post('/json/language/storeUpdate', 'LanguageController@storeUpdate')->name('storeUpdate');
});
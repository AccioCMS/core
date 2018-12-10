<?php

/**
 * Custom Fields Routes
 */

use Illuminate\Support\Facades\Route;

Route::group(
    ['middleware' => ['auth:admin'], 'as' => 'backend.customField.', 'namespace' => '\App\Http\Controllers\Backend', 'prefix' => Config::get('project')['adminPrefix']], function () {
        /**
         * GET
         */
        Route::get('/{lang}/custom-fields/{view}', 'CustomFieldController@index')->name('index');
        Route::get('/{lang}/json/custom-fields/get-all', 'CustomFieldController@getAll')->name('getAll');
        Route::get('/{lang}/json/custom-fields/delete/{id}', 'CustomFieldController@delete')->name('delete');
        Route::get('/{lang}/custom-fields/{view}/{id}', 'CustomFieldController@single')->name('single');
        Route::get('/{lang}/json/custom-fields/details/{id}', 'CustomFieldController@detailsJSON')->name('detailsJSON');
        Route::get('/{lang}/json/custom-fields/get-slug/{slug}', 'CustomFieldController@getSlug')->name('getSlug');
        Route::get('/{lang}/json/custom-fields/generate-field-slug/{slug}/{id}/{keys}', 'CustomFieldController@generateFieldSlug')->name('generateFieldSlug');
        Route::get('/{lang}/json/custom-fields/get-by-app/{module}/{formType}/{id}/{postType}', 'CustomFieldController@getByApp')->name('getByApp');

        /**
         * POST
         */
        Route::post('/json/custom-fields/store', 'CustomFieldController@store')->name('store');
        Route::post('/json/custom-fields/bulk-delete', 'CustomFieldController@bulkDelete')->name('bulkDelete');
        Route::post('/json/custom-fields/storeUpdate', 'CustomFieldController@storeUpdate')->name('storeUpdate');
        Route::post('/json/custom-fields/get-table-data', 'CustomFieldController@getTableData')->name('getTableData');
        Route::post('/json/custom-fields/generate-field-slug-request', 'CustomFieldController@generateFieldSlugRequest')->name('generateFieldSlugRequest');
    }
);
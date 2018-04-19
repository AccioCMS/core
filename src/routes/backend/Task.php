<?php
Route::group(['middleware' => ['auth:admin'], 'as' => 'backend.task.', 'namespace' => '\App\Http\Controllers\Backend', 'prefix' => Config::get('project')['adminPrefix']], function (){

    /**
     * GET
     */
    Route::get('/{lang}/task/archive', 'TaskController@archive')->name('archive');
    Route::get('/{lang}/task/most-read-articles', 'TaskController@mostReadArticles')->name('mostReadArticles');
});
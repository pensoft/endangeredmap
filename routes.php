<?php

Route::group(['prefix' => 'api/endangered'], function () {
    Route::get('search', 'Pensoft\EndangeredMap\Controllers\ApiController@search');
    Route::options('search', 'Pensoft\EndangeredMap\Controllers\ApiController@options');

    Route::get('species', 'Pensoft\EndangeredMap\Controllers\ApiController@species');
    Route::options('species', 'Pensoft\EndangeredMap\Controllers\ApiController@options');

    Route::get('acronyms', 'Pensoft\EndangeredMap\Controllers\ApiController@acronyms');
    Route::options('acronyms', 'Pensoft\EndangeredMap\Controllers\ApiController@options');
});

<?php

Route::group(['middleware' => 'cors', 'namespace' => '\JiaLeo\Laravel\Core'], function () {
    Route::get('api/debug', 'Debuger@getLog');
});
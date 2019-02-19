<?php

//后台上传文件
Route::any('admin/upload/callback', 'Admin\UploadController@uploadCallback');

Route::group(['prefix' => 'admin','middleware' => ['JwtAuth','AdminCheck']],function(){
    Route::post('upload', 'Admin\UploadController@getUploadID');
    Route::post('files', 'Admin\UploadController@upload');
    Route::put('upload/localcomplete/{id}', 'Admin\UploadController@putLocalUploadComplete');
    Route::put('upload/cloudcomplete/{id}', 'Admin\UploadController@putCloudUploadComplete');
});
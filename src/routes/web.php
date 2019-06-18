<?php
Route::group(['namespace' => 'Rvwoens\Gompie\Http\Controllers', 'middleware' => ['web']], function(){
	Route::get('contact', 'DemoController@index');
	Route::post('contact', 'DemoController@sendMail')->name('contact');
});
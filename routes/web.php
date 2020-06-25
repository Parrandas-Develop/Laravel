<?php
//Cargando clases
use App\Http\Middleware\ApiAuthMiddleware;

//Rutas para el controlador de "userController"
Route::post('/usuario/registro', 'userController@register');
Route::post('/usuario/login', 'userController@login');
Route::put('/usuario/update', 'userController@update');
Route::post('/usuario/upload', 'userController@upload')->middleware(ApiAuthMiddleware::class);
Route::get('/usuario/getImage/{filename}', 'userController@getImage');
Route::get('/usuario/detail/{id}', 'userController@detail');

//Rutas para el controlador de "categoryController"
Route::resource('/categoria', 'categoryController');

//Rutas para el controlador del "POST" (Entradas)
Route::resource('/post', 'postController');
Route::post('/post/upload', 'postController@upload');
Route::get('post/image/{filename}', 'postController@getImage');
Route::get('post/category/{id}', 'postController@getPostsByCategory');
Route::get('post/user/{id}', 'postController@getPostsByUser');

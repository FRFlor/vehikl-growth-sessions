<?php

use App\Http\Middleware\AllowOnlyOwner;
use App\Http\Middleware\AuthenticateSlackApp;
use Illuminate\Support\Facades\Route;

Route::view('about','about')->name('about');
Route::get('login/github', 'Auth\LoginController@redirectToProvider')->name('oauth.login.redirect');
Route::get('oauth/callback', 'Auth\LoginController@handleProviderCallback')->name('oauth.login.callback');
Route::post('logout', 'Auth\LoginController@logout')->name('logout');

Route::view('/', 'home')->name('home');

Route::prefix('social_mobs')->middleware(AuthenticateSlackApp::class)->name('social_mobs.')->group(function() {
    Route::get('week', 'SocialMobController@week')->name('week');
    Route::get('day', 'SocialMobController@day')->name('day');
    Route::get('{social_mob}', 'SocialMobController@show')->name('show');
    Route::get('{social_mob}/edit', 'SocialMobController@edit')->middleware(['auth','can:update,social_mob'])->name('edit');
    Route::post('{social_mob}/join', 'SocialMobController@join')->middleware(['auth', 'can:join,social_mob'])->name('join');
    Route::post('{social_mob}/leave', 'SocialMobController@leave')->middleware(['auth', 'can:leave,social_mob'])->name('leave');
});
Route::resource('social_mobs', 'SocialMobController')->middleware('auth')->only(['store', 'update', 'destroy']);

Route::prefix('social_mobs/{social_mob}/comments')->name('social_mobs.comments.')->group(function() {
    Route::get('/', 'CommentController@index')->name('index');
});
Route::resource('social_mobs.comments', 'CommentController')->middleware('auth')->only(['store','update','destroy']);

Route::get('activity', 'ActivityController@index')->middleware('auth')->name('activity');

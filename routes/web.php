<?php

use App\Http\Controllers\Api\WeatherDataController;
use App\Http\Controllers\Api\WeatherStationsController;
use App\Http\Controllers\StationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StationController::class, 'index'])->name('home');
Route::post('/save-selection', [StationController::class, 'saveSelection'])->name('save.selection');

// API Routes for AEMET Weather Data
Route::prefix('api')->group(function () {
    // Weather Stations
    Route::get('/stations', [WeatherStationsController::class, 'index'])->name('api.stations.index');
    Route::get('/stations/nearest', [WeatherStationsController::class, 'nearest'])->name('api.stations.nearest');

    // Weather Data
    Route::get('/weather/recent', [WeatherDataController::class, 'recent'])->name('api.weather.recent');
    Route::get('/weather/station/{stationId}', [WeatherDataController::class, 'station'])->name('api.weather.station');
    Route::get('/weather/daily-climate', [WeatherDataController::class, 'dailyClimate'])->name('api.weather.daily-climate');
    Route::get('/weather/normals/{stationId}', [WeatherDataController::class, 'normals'])->name('api.weather.normals');
});

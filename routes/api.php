<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\AttendeeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/attendee/register', [AttendeeController::class, 'register']);
Route::post('/attendee/events', [AttendeeController::class, 'events']);

Route::post('/booking/book-event', [BookingController::class, 'bookEvent']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/events', [EventController::class, 'list']);
    Route::post('/event/create', [EventController::class, 'create']);
    Route::post('/event/update/{id}', [EventController::class, 'update']);
    Route::delete('/event/remove/{id}', [EventController::class, 'remove']);

});
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

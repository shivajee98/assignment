<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\UserController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/messageable', [MessageController::class, 'getMessageableUsers']);
    Route::post('/users', [UserController::class, 'store']);
    
    Route::get('/groups', [GroupController::class, 'index']);
    Route::post('/groups', [GroupController::class, 'store']);
    Route::get('/groups/{id}', [GroupController::class, 'show']);
    
    Route::get('/messages/conversations', [MessageController::class, 'getConversations']);
    Route::get('/messages/private/{userId}', [MessageController::class, 'getPrivateMessages']);
    Route::post('/messages/private/{userId}', [MessageController::class, 'sendPrivateMessage']);
    Route::get('/messages/group/{groupId}', [MessageController::class, 'getGroupMessages']);
    Route::post('/messages/group/{groupId}', [MessageController::class, 'sendGroupMessage']);
    Route::post('/messages/broadcast', [MessageController::class, 'broadcast']);
});

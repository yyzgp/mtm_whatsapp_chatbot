<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// WhatsApp Webhook
Route::get('/webhook/whatsapp', [WebhookController::class, 'verify']);
Route::post('/webhook/whatsapp', [WebhookController::class, 'handle']);

// Auth
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/push-token', [AuthController::class, 'updatePushToken']);

    // Conversations
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::get('/conversations/{id}', [ConversationController::class, 'show']);
    Route::get('/conversations/{id}/messages', [ConversationController::class, 'messages']);
    Route::post('/conversations/{id}/messages', [ConversationController::class, 'sendMessage']);
    Route::post('/conversations/{conversationId}/messages/{messageId}/react', [ConversationController::class, 'reactToMessage']);
    Route::patch('/conversations/{id}/status', [ConversationController::class, 'updateStatus']);
    Route::post('/conversations/{id}/toggle-ai', [ConversationController::class, 'toggleAi']);
    Route::get('/conversations/{id}/templates', [TemplateController::class, 'index']);
    Route::post('/conversations/{id}/templates/send', [TemplateController::class, 'send']);

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // Customers
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::get('/customers/{id}', [CustomerController::class, 'show']);
    Route::patch('/customers/{id}/status', [CustomerController::class, 'updateStatus']);

    // Employee / HR
    Route::prefix('employee')->group(function () {
        Route::get('/profile', [EmployeeController::class, 'profile']);
        Route::get('/leave-types', [EmployeeController::class, 'leaveTypes']);
        Route::get('/leave-balances', [EmployeeController::class, 'leaveBalances']);
        Route::get('/leave-applications', [EmployeeController::class, 'leaveApplications']);
        Route::post('/leave-applications', [EmployeeController::class, 'applyLeave']);
        Route::put('/leave-applications/{id}/cancel', [EmployeeController::class, 'cancelLeave']);
        Route::post('/clock-in', [EmployeeController::class, 'clockIn']);
        Route::post('/clock-out', [EmployeeController::class, 'clockOut']);
        Route::get('/attendance/today', [EmployeeController::class, 'todayAttendance']);
        Route::get('/attendance', [EmployeeController::class, 'attendanceHistory']);
    });
});

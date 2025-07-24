<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConversationController;

Route::post('/conversations/completions', [ConversationController::class, 'completion']);
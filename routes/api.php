<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TimeOffRequestController;

/*
|--------------------------------------------------------------------------
| Webhook Routes for Time Off Requests
|--------------------------------------------------------------------------
| All of these are POST endpoints because CognitoForms (and most webhook
| systems) send POST requests regardless of action type.
| Each endpoint will read the full JSON body and act accordingly.
*/

Route::post('/webhook/time-off/create',  [TimeOffRequestController::class, 'store']);   // form created
Route::post('/webhook/time-off/update',  [TimeOffRequestController::class, 'updateFromWebhook']); // form updated (approved/rejected)
Route::post('/webhook/time-off/delete',  [TimeOffRequestController::class, 'destroy']); // form deleted
Route::get('/webhook/time-off/all',     [TimeOffRequestController::class, 'index'])->middleware('excel_secret');   // return all flattened records

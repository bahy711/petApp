<?php

use App\Http\Controllers\petController;
use App\Http\Controllers\resetController;
use App\Http\Controllers\userController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('UpdatePassword', [userController::class, 'Update']);
    Route::get('UserData', [userController::class, 'UserData']);
    Route::post('UpdateUserData', [userController::class, 'UpdateUserData']);
    Route::get('Logout', [userController::class, 'LogOut']);

    // pet basic data
    Route::post('CreatePet', [petController::class, 'create']);
    Route::get('PetData', [petController::class, 'PetInfo']);
    Route::post('UpdatePetData', [petController::class, 'UpdatePetInfo']);
    Route::delete('DeletePet/{id}', [petController::class, 'DeletePet']);

    //health condition create
    Route::post('CreateHealthCondition', [petController::class, 'CreateHealthCondition']);
    Route::get('HealthData/{id}', [petController::class, 'HealthConditionData']);
    Route::post('UpdateHealthData', [petController::class, 'UpdateHealthCondition']);
    Route::delete('DeleteHealthData/{id}', [petController::class, 'deleteHealthCondition']);

    //Optional pet data create
    Route::post('CreatePetInfo', [petController::class, 'createOptionalPetInfo']);
    Route::get('optionalData/{id}', [petController::class, 'getPetOpData']);
    Route::post('updateOptional/{optionalId}', [petController::class, 'UpdateOptionalData']);
    Route::delete('deleteOptional/{id}', [petController::class, 'deleteOptional']);

    // daily activites
    Route::post('CreateExcercise', [petController::class, 'DailyExcercise']);
    Route::get('getExcercise/{id}/{date}', [petController::class, 'getExcercise']);
    Route::get('getExcercises/{id}', [petController::class, 'getExcercises']);
    Route::get('getExerciseUpadting/{id}/{pet_id}', [petController::class, 'petUpdateData']);
    Route::post('UpdateExcercise/{petE_id}/{id}', [petController::class, 'UpdateExcercise']);
    Route::delete('DeleteExercise/{id}', [petController::class, 'deleteExercise']);

    // daily activity food
    Route::post('FoodActivity', [petController::class, 'createFoodActivity']);
    Route::get('getFoodActivity/{id}', [petController::class, 'getFoodActivity']);
    Route::get('getFoodActivityByDate/{id}/{date}', [petController::class, 'getFoodActivityByDate']);
    Route::delete('DeleteFoodActivity/{id}', [petController::class, 'deleteFoodActivity']);


    // Usage streak & stats
    Route::get('CurrentStreak', [petController::class, 'CurrentStreak']);

});

Route::post('SignUp', [userController::class, 'Signup']);
Route::post('Login', [userController::class, 'doLogin']);
//FOR fogetting password
Route::post('reset', [resetController::class, 'forgotPassword']);
Route::post('reset-password', [resetController::class, 'reset']);

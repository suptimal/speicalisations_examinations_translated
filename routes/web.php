<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::prefix('specialisation-examination')->name('specialisation-examination.')->group(function() {
    Route::get('/{label}/{service}', [App\Http\Controllers\SpecialisationExaminationController::class, 'importForm'])->name('importForm');
    Route::post('/{label}/{service}/import', [App\Http\Controllers\SpecialisationExaminationController::class, 'import'])->name('import');
    Route::get('/{label}/{service}/export', [App\Http\Controllers\SpecialisationExaminationController::class, 'export'])->name('export');
});

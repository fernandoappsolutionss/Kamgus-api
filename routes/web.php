<?php

use Illuminate\Support\Facades\Route;
use App\Mail\NewUser;
use App\Mail\NewDriver;
use App\Mail\NewPasswordReset;
use App\Mail\NewCompany;
use App\Mail\StatusService;
use App\Mail\HomeReport;
use App\Mail\RequestTransaction;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {

    return view('welcome');

});

Route::get('/new/user/email', function () {

    // Mail::to(['josealiss21@gmail.com', 'estebanbetinalvarez@gmail.com', 'estebanbetinalvarez@live.com'])->send(new NewUser('Motivo', 'Mensaje'));

    return new NewUser('Motivo', 'Mensaje');
});


Route::get('/new/driver/email', function () {

    // Mail::to(['josealiss21@gmail.com', 'estebanbetinalvarez@gmail.com', 'estebanbetinalvarez@live.com'])->send(new NewDriver('Motivo', 'Mensaje'));

    return new NewDriver('Motivo', 'Mensaje');
});


Route::get('/new/company/email', function () {

    // Mail::to(['josealiss21@gmail.com', 'estebanbetinalvarez@gmail.com', 'estebanbetinalvarez@live.com'])->send(new MailNewCompany('Motivo', 'Mensaje'));

    return new NewCompany('Motivo', 'Mensaje');
});



Route::get('/new/password/reset/email', function () {

 return new NewPasswordReset('Motivo', 'Mensaje', 'https://www.google.com');

})->name('password.reset');


Route::get('/new/status/service/email', function () {

    // Mail::to(['josealiss21@gmail.com', 'estebanbetinalvarez@gmail.com', 'estebanbetinalvarez@live.com', 'sandraceciliaalvarezdominguez@gmail.com'])->send(new ActiveService('Motivo', 'Mensaje', 'Cancelado'));

    return new StatusService('Motivo', 'Mensaje', 'Activo');
});

Route::get('/new/home/report/email', function(){
    
    // Mail::to(['josealiss21@gmail.com', 'estebanbetinalvarez@gmail.com', 'estebanbetinalvarez@live.com', 'sandraceciliaalvarezdominguez@gmail.com'])->send(new HomeReport('Motivo', 'Mensaje'));

    return new HomeReport('Motivo', 'Mensaje');

});
Route::get('/new/driver/transaction/email', function(){
    
    // Mail::to(['josealiss21@gmail.com', 'estebanbetinalvarez@gmail.com', 'estebanbetinalvarez@live.com', 'sandraceciliaalvarezdominguez@gmail.com'])->send(new HomeReport('Motivo', 'Mensaje'));

    return new RequestTransaction("1.00", "User name", "transaction ID");

});
//Ruta usada para testear los comandos personalizados
Route::get('/test_command', function () {
    return Artisan::call('refresh:old_services');
    //return Artisan::call('schedule:run');

});
//Ruta usada para limpiar la cache del proyecto
Route::get('/generate_link', function () {
    //Artisan::call('storage:link');

    Artisan::call('config:cache');//Borra la cache del archivo config
    Artisan::call('config:clear');//Borra la cache del archivo config
    Artisan::call('route:clear');//Borra la cache del relacionada a las rutas
    Artisan::call('cache:clear');//Borra la cache de los registros de datos
});

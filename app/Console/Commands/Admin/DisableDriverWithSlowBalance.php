<?php

namespace App\Console\Commands\Admin;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use DB;

class DisableDriverWithSlowBalance extends Command 
{
    const DRIVER_ROLE = 1; // role de conductor
    const DRIVER_LIMIT = 25; // balance limite de conductores
    private $limitUser = "";
    //private $limitUser = " and idusuarios=2494"; // usar cuando necesite testear (reemplazar 2494 por el id del usuario a testear)
    //private $serviceLimit = " and idservicios = 4862";
    private $driverIdsList = [];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'driver:balance';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel all driver which have a slow balance';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        

        //"SELECT servicios.* from servicios where";
        $this->haveSlowBalance();
        if(count($this->driverIdsList) > 0){
            $this->disableDrivers();
        }
         
    }

    private function haveSlowBalance(){
          
        $drivers = User::where("userable_type", Driver::class)->get();
        $count = [];
        foreach ($drivers as $key => $driver) {
      
            $totalBalance = calculateDriverBalance($driver->id, DB::table("transactions"));
            if($totalBalance < self::DRIVER_LIMIT){
                array_push($count, $driver->id);
            }
        }
        $this->driverIdsList = $count;
        return count($count) > 0;
    }
    //Desabilita los conductores con balance inferior al Limite (DRIVER_LIMIT)
    private function disableDrivers(){
        $arr = $this->driverIdsList;
        /*
        $SQL = "";
        $arr = $this->driverIdsList;
        $in  = str_repeat('?,', count($arr) - 1) . '?';
        $sql = "UPDATE usuarios SET estado = 3 WHERE rol=? and estado NOT IN (0, 1) AND idusuarios IN ($in)";
        $stm = ->prepare($sql);
        $params = array_merge([self::DRIVER_ROLE], $arr);
        $stm->execute($params);
        $data = $stm->fetchAll();
        */
        return User::where([
                ["userable_type", "=", Driver::class],
            ])->whereIn("status", ["Activo"])
            ->whereIn("id", $arr)
            ->update(["status" => "Bloqueado"]);
    }
}

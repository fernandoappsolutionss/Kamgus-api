<?php

namespace App\Jobs;

use App\Mail\StatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mail;
class NotifyServiceStatusByEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $emails;
    private $serviceId;
    private $title;
    private $description;
    public function __construct($emails, $serviceId, $title, $description)
    {
        //
        $this->emails = $emails;
        $this->serviceId = $serviceId;
        $this->title = $title;
        $this->description = $description;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        Mail::to($this->emails)->send(new StatusService('Estado de servicio', "Hay un nuevo servicio disponible", "Pendiente"));
    }
}

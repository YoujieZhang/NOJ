<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\SubmissionModel;
use App\Http\Controllers\VirtualJudge\Submit;

class ProcessSubmission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    protected $all_data=[];

    /**
     * Create a new job instance.
     *
     * @return void
     */

    public function __construct($all_data)
    {
        $this->all_data=$all_data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        new Submit($this->all_data);
    }

    public function failed(Exception $exception)
    {
        $submissionModel = new SubmissionModel();
        $submissionModel->update_submission($this->all_data["sid"], ["verdict"=>"System Error"]);
    }
}

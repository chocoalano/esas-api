<?php

namespace App\Jobs;

use App\Models\AdministrationApp\UserTimeworkSchedule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class InsertUpdateScheduleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    /**
     * Create a new job instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        collect($this->data)->chunk(10)->each(function ($chunk) {
            foreach ($chunk->toArray() as $k) {
                UserTimeworkSchedule::updateOrCreate([
                    "user_id" => $k['user_id'],
                    "work_day" => $k['work_day'],
                ], [
                    "user_id" => $k['user_id'],
                    "time_work_id" => $k['time_work_id'],
                    "work_day" => $k['work_day'],
                ]);
            }
        });
    }
    public function failed(\Exception $exception)
    {
        Log::error('Job failed', ['exception' => $exception->getMessage()]);
        // Anda bisa melakukan tindakan lain jika terjadi kegagalan
    }
}

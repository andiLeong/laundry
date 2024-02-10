<?php

namespace App\Jobs;

use App\Models\OrderImage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UploadOrderImages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Filesystem $file;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected array $images,
        protected $userId,
        protected $orderId,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(Filesystem $fileManager): void
    {
        $this->file = $fileManager;
        $images = array_map(fn($image) => $this->upload($image), $this->images);
        OrderImage::put($images, $this->userId, $this->orderId);
    }

    public function upload($image)
    {
        $name = OrderImage::name($this->orderId, $image['extension']);
        $path = config('filesystems.disks.digitalocean.path') . '/order';
        $path = $this->file->putFileAs($path, $image['full_path'], $name, 'public');
        if (!$path) {
            throw new \Exception('Fail to upload the file');
        }
        return $path;
    }
}

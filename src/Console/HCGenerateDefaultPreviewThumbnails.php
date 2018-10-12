<?php

namespace HoneyComb\Resources\Console;

use HoneyComb\Resources\Services\HCResourceService;
use Illuminate\Console\Command;

class HCGenerateDefaultPreviewThumbnails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hc-resources:preview-thumbs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates preview thumbnails in storage public disk';
    /**
     * @var HCResourceService
     */
    private $resourceService;

    /**
     * Create a new command instance.
     *
     * @param HCResourceService $resourceService
     */
    public function __construct(HCResourceService $resourceService)
    {
        parent::__construct();
        $this->resourceService = $resourceService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): void
    {
       // $resources = $this->resourceService->getRepository()->makeQuery()->select('id', 'path', 'mime_type')->get();

       /* foreach ($resources as $resource) {
            $this->resourceService->createPreviewThumb($resource->id, $resource->path, $resource->mime_type, 'local');
        }*/
    }
}

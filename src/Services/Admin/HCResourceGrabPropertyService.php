<?php

declare(strict_types = 1);

namespace HoneyComb\Resources\Services\Admin;

use HoneyComb\Resources\Repositories\Admin\HCResourceGrabPropertyRepository;
use HoneyComb\Resources\Services\HCResourceService;
use HoneyComb\Resources\Services\HCResourceThumbService;
use Intervention\Image\Facades\Image;

class HCResourceGrabPropertyService
{
    /**
     * @var HCResourceGrabPropertyRepository
     */
    private $repository;
    /**
     * @var \HoneyComb\Resources\Services\HCResourceService
     */
    private $resourceService;
    /**
     * @var \HoneyComb\Resources\Services\HCResourceThumbService
     */
    private $thumbService;

    /**
     * HCResourceGrabPropertyService constructor.
     * @param HCResourceGrabPropertyRepository $repository
     * @param \HoneyComb\Resources\Services\HCResourceService $resourceService
     * @param \HoneyComb\Resources\Services\HCResourceThumbService $thumbService
     */
    public function __construct(
        HCResourceGrabPropertyRepository $repository,
        HCResourceService $resourceService,
        HCResourceThumbService $thumbService
    ) {
        $this->repository = $repository;
        $this->resourceService = $resourceService;
        $this->thumbService = $thumbService;
    }

    /**
     * @return HCResourceGrabPropertyRepository
     */
    public function getRepository(): HCResourceGrabPropertyRepository
    {
        return $this->repository;
    }

    /**
     * @param array $images
     */
    public function generateThumbs(array $images)
    {
        foreach ($images as $image) {

            $searchArray = [
                'resource_id' => $image['resource_id'],
                'source_type' => $image['source_type'],
                'source_id' => $image['source_id'],
                'thumbnail_id' => $image['thumbnail_id'],
            ];

            $record = $this->repository->findOneBy($searchArray);

            if (!$record) {

                $this->generateThumbnail($image);
                $this->repository->create($image);

            } elseif ($record->x == $image['x'] && $record->y === $image['y']) {
                continue;
            } else {
                $this->generateThumbnail($image);
                $this->repository->updateOrCreate($searchArray, $image);
            }
        }
    }

    public function generateThumbnail(array $data)
    {
        /** @var \HoneyComb\Resources\Models\HCResource $record */
        $record = $this->resourceService->getRepository()->find($data['resource_id']);
        $thumbnail = $this->thumbService->getRepository()->find($data['thumbnail_id']);

        $source = config('filesystems.disks.local.root') . DIRECTORY_SEPARATOR . $record->path;
        $destination = config('filesystems.disks.local.root') . '/public/'
            . $data['source_type'] . '/'
            . $data['source_id'] . '/'
            . $data['thumbnail_id'] . '/';

        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $destination .= $record->id . $record->extension;

        /** @var \Intervention\Image\Image $image */
        $image = Image::make($source);

        if ($image->width() < $image->height()) {
            $scale = $image->width() / $thumbnail->width;
        } else {
            $scale = $image->height() / $thumbnail->height;
        }

        $width = (integer)($thumbnail->width * $scale);
        $height = (integer)($thumbnail->height * $scale);

        if ($width > $image->width()) {
            $scale = $image->width() / $thumbnail->width;

            $width = (integer)($thumbnail->width * $scale);
            $height = (integer)($thumbnail->height * $scale);
        }

        if (is_null($data['x']) && is_null($data['y'])) {
            $data['x'] = -($thumbnail->width - $image->width() / $scale) * 0.5;
            $data['y'] = -($thumbnail->height - $image->height() / $scale) * 0.5;
        }

        $image->crop($width, $height, abs((integer)($data['x'] * $scale)), abs((integer)($data['y'] * $scale)));
        $image->resize($thumbnail->width, $thumbnail->height);
        $image->save($destination);
    }
}

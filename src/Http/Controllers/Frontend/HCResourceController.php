<?php
declare(strict_types = 1);

namespace HoneyComb\Resources\Http\Controllers\Frontend;

use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use File;
use HoneyComb\Core\Http\Controllers\HCBaseController;
use HoneyComb\Resources\Models\HCResource;
use Image;
use Intervention\Image\Constraint;

/**
 * Class HCResourceController
 * @package HoneyComb\Resources\Http\Controllers\Frontend
 */
class HCResourceController extends HCBaseController
{
    /**
     * Show resource
     *
     * @param null|string $id
     * @param int|null $width
     * @param int|null $height
     * @param bool|null $fit
     * @return mixed
     */
    public function show(string $id = null, int $width = 0, int $height = 0, bool $fit = false)
    {
        $storagePath = storage_path('app/');

        if (is_null($id)) {
            logger()->info(trans('HCResource::resources.file_missing', ['data' => $id]));
            exit;
        }

        // cache resource for 10 days
        $resource = \Cache::remember($id, 14400, function () use ($id) {
            return HCResource::find($id);
        });

        if (!$resource) {
            logger()->info(trans('HCResource::resources.file_missing', ['data' => $id]));
            exit;
        }

        if (!Storage::exists($resource->path)) {
            logger()->info(trans('HCResource::resources.file_missing', ['data' => $id . ' ' . $resource->path]));
            exit;
        }

        $cachePath = $this->generateResourceCacheLocation($resource->id, $width, $height, $fit) . $resource->extension;

        if (file_exists($cachePath)) {
            $resource->size = File::size($cachePath);
            $resource->path = $cachePath;
        } else {

            switch ($resource->mime_type) {
                case 'image/png' :
                case 'image/jpeg' :
                case 'image/jpg' :

                    if ($width != 0 && $height != 0) {

                        $this->createImage($storagePath . $resource->path, $cachePath, $width, $height, $fit);

                        $resource->size = File::size($cachePath);
                        $resource->path = $cachePath;
                    } else {
                        $resource->path = $storagePath . $resource->path;
                    }
                    break;

                case 'video/mp4' :

                    $previewPath = str_replace('-', '/', $resource->id);
                    $fullPreviewPath = $storagePath . 'video-previews/' . $previewPath;

                    $cachePath = $this->generateResourceCacheLocation($previewPath, $width, $height, $fit) . '.jpg';

                    if (file_exists($cachePath)) {
                        $resource->size = File::size($cachePath);
                        $resource->path = $cachePath;
                        $resource->mime_type = 'image/jpg';
                    } else {

                        if ($width != 0 && $height != 0) {

                            $videoPreview = $fullPreviewPath . '/preview_frame.jpg';

                            //TODO: generate 3-5 previews and take the one with largest size
                            $this->generateVideoPreview($resource, $storagePath, $previewPath);

                            createImage($videoPreview, $cachePath, $width, $height, $fit);

                            $resource->size = File::size($cachePath);
                            $resource->path = $cachePath;
                            $resource->mime_type = 'image/jpg';
                        } else {
                            $resource->path = $storagePath . $resource->path;
                        }
                    }
                    break;

                default:

                    $resource->path = $storagePath . $resource->path;
                    break;
            }
        }

        // Show resource
        header('Pragma: public');
        header('Cache-Control: max-age=86400');
        header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
        header('Content-Length: ' . $resource->size);
        header('Content-Disposition: inline;filename="' . $resource->original_name . '"');
        header('Content-Type: ' . $resource->mime_type);
        readfile($resource->path);

        exit;
    }

    /**
     * generating video preview
     *
     * @param HCResources $resource
     * @param string $storagePath
     * @param string $previewPath
     */
    private function generateVideoPreview(HCResources $resource, string $storagePath, string $previewPath): void
    {
        $fullPreviewPath = $storagePath . 'video-previews/' . $previewPath;

        if (!file_exists($fullPreviewPath)) {
            mkdir($fullPreviewPath, 0755, true);
        }

        $videoPreview = $fullPreviewPath . '/preview_frame.jpg';

        if (!file_exists($videoPreview)) {

            $videoPath = $storagePath . $resource->path;

            $ffmpeg = FFMpeg::create([
                'ffmpeg.binaries' => '/usr/bin/ffmpeg',
                'ffprobe.binaries' => '/usr/bin/ffprobe',
                'timeout' => 3600, // The timeout for the underlying process
                'ffmpeg.threads' => 12,   // The number of threads that FFMpeg should use
            ]);

            $video = $ffmpeg->open($storagePath . $resource->path);
            $duration = $video->getFFProbe()->format($videoPath)->get('duration');

            $video->frame(TimeCode::fromSeconds(rand(1, $duration)))
                ->save($videoPreview);

            $resource->mime_type = 'image/jpg';
            $resource->path = $videoPreview;
        }
    }

    /**
     * Generating resource cache location and name
     *
     * @param $id
     * @param int|null $width
     * @param int|null $height
     * @param null $fit
     * @return string
     */
    private function generateResourceCacheLocation($id, $width = 0, $height = 0, $fit = null): string
    {
        $path = storage_path('app/') . 'cache/' . str_replace('-', '/', $id) . '/';

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $path .= $width . '_' . $height;

        if ($fit) {
            $path .= '_fit';
        }

        return $path;
    }

    /**
     * Creating image based on provided data
     *
     * @param $source
     * @param $destination
     * @param int $width
     * @param int $height
     * @param bool $fit
     * @return bool
     */
    private function createImage($source, $destination, $width = 0, $height = 0, $fit = false): bool
    {
        if ($width == 0) {
            $width = null;
        }

        if ($height == 0) {
            $height = null;
        }

        /** @var \Intervention\Image\Image $image */
        $image = Image::make($source);

        if ($fit) {
            $image->fit($width, $height, function (Constraint $constraint) {
                $constraint->upsize();
            });
        } else {
            $image->resize($width, $height, function (Constraint $constraint) {
                $constraint->upsize();
                $constraint->aspectRatio();
            });
        }

        $image->save($destination);

        return true;
    }
}

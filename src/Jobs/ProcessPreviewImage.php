<?php
/**
 * @copyright 2018 innovationbase
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * Contact InnovationBase:
 * E-mail: hello@innovationbase.eu
 * https://innovationbase.eu
 */

namespace HoneyComb\Resources\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Intervention\Image\Facades\Image;

/**
 * Class ProcessImageThumbnail
 * @package HoneyComb\Resources\Jobs
 */
class ProcessPreviewImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * @var string
     */
    private $resourceId;
    /**
     * @var array
     */
    private $images;
    /**
     * @var string
     */
    private $destinationPath;

    /**
     * @var string
     */
    private $sourcePath;

    /**
     * Create a new job instance.
     *
     * @param array $image
     * @param array $resource
     * @param array $thumb
     */
    public function __construct(string $resourceId, array $images, string $destinationPath, string $sourcePath)
    {
        $this->resourceId = $resourceId;
        $this->images = $images;
        $this->destinationPath = $destinationPath;
        $this->sourcePath = $sourcePath;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        foreach ($this->images as $item) {
            $previewWidth = $item['width'];
            $previewHeight = $item['height'];
            $previewQuality = $item['quality'];

            $destination = $this->destinationPath . DIRECTORY_SEPARATOR . $previewWidth . 'x' . $previewHeight;
            if (!is_dir($destination)) {
                mkdir($destination, 0755, true);
            }
            $destination .= DIRECTORY_SEPARATOR . $this->resourceId . '.jpg';
            /** @var \Intervention\Image\Facades\Image $image */
            $image = Image::make($this->sourcePath);

            if ($image->width() < $image->height()) {
                $scale = $image->width() / $previewWidth;
            } else {
                $scale = $image->height() / $previewHeight;
            }

            $width = (integer)($previewWidth * $scale);
            $height = (integer)($previewHeight * $scale);

            if ($width > $image->width()) {
                $scale = $image->width() / $previewWidth;

                $width = (integer)($previewWidth * $scale);
                $height = (integer)($previewHeight * $scale);
            }

            $x = ($image->width() - $width) * 0.5;
            $y = ($image->height() - $height) * 0.5;

            $image->crop($width, $height, (integer)($x), (integer)($y));
            $image->resize($previewWidth, $previewHeight);

            $image->save($destination, $previewQuality);
            $image->destroy();
        }
        /*   $source = config('filesystems.disks.local.root') . DIRECTORY_SEPARATOR . $this->resource['path'];
           $destination = config('filesystems.disks.local.root') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR
               . $this->image['source_type'] . DIRECTORY_SEPARATOR
               . $this->image['source_id'] . DIRECTORY_SEPARATOR
               . $this->image['thumbnail_id'] . DIRECTORY_SEPARATOR;

           if (!is_dir($destination)) {
               mkdir($destination, 0755, true);
           }

           $destination .= $this->resource['id'] . $this->resource['extension'];

            /** @var \Intervention\Image\Image $image */
        /* $image = Image::make($source);
         $image->crop((integer)$this->image['width'], (integer)$this->image['height'], (integer)$this->image['x'], (integer)$this->image['y']);
         $image->resize($this->thumb['width'], $this->thumb['height']);
         $image->save($destination);
         $image->destroy();*/
    }
}

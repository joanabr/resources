<?php
/**
 * @copyright 2018 interactivesolutions
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
 * Contact InteractiveSolutions:
 * E-mail: hello@interactivesolutions.lt
 * http://www.interactivesolutions.lt
 */

declare(strict_types = 1);

namespace HoneyComb\Resources\Repositories;

use HoneyComb\Resources\Models\HCResource;
use HoneyComb\Core\Repositories\Traits\HCQueryBuilderTrait;
use HoneyComb\Starter\Repositories\HCBaseRepository;
use Illuminate\Http\UploadedFile;

/**
 * Class HCResourceRepository
 * @package HoneyComb\Resources\Repositories
 */
class HCResourceRepository extends HCBaseRepository
{
    use HCQueryBuilderTrait;

    /**
     * @return string
     */
    public function model(): string
    {
        return HCResource::class;
    }

    /**
     * Get file params
     *
     * @param $file
     * @return array
     */
    public function getFileParams(UploadedFile $file)
    {
        $params = [];

        if ($this->resourceID) {
            $params['id'] = $this->resourceID;
        } else {
            $params['id'] = Uuid::uuid4()->toString();
        }

        $params['original_name'] = $file->getClientOriginalName();
        $params['extension'] = '.' . $file->getClientOriginalExtension();
        $params['path'] = $this->uploadPath . $params['id'] . $params['extension'];
        $params['size'] = $file->getClientSize();
        $params['mime_type'] = $file->getClientMimeType();

        return $params;
    }

    /**
     * Soft deleting records
     * @param $ids
     */
    public function deleteSoft(array $ids): void
    {
        $records = $this->makeQuery()->whereIn('id', $ids)->get();

        foreach ($records as $record) {
            /** @var HCResource $record */
            $record->translations()->delete();
            $record->delete();
        }
    }

    /**
     * Restore soft deleted records
     *
     * @param array $ids
     * @return void
     */
    public function restore(array $ids): void
    {
        $records = $this->makeQuery()->withTrashed()->whereIn('id', $ids)->get();

        foreach ($records as $record) {
            /** @var HCResource $record */
            $record->translations()->restore();
            $record->restore();
        }
    }

    /**
     * Force delete records by given id
     *
     * @param array $ids
     * @return void
     * @throws \Exception
     */
    public function deleteForce(array $ids): void
    {
        $records = $this->makeQuery()->withTrashed()->whereIn('id', $ids)->get();

        foreach ($records as $record) {
            /** @var HCResource $record */
            $record->translations()->forceDelete();
            $record->forceDelete();
        }
    }
}

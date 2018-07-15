<?php
/**
 * @copyright 2017 interactivesolutions
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the 'Software'), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
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

namespace HoneyComb\Resources\Forms\Admin;

use HoneyComb\Resources\Http\Requests\HCResourceThumbRequest;
use HoneyComb\Resources\Repositories\HCResourceGrabPropertyRepository;
use HoneyComb\Resources\Repositories\HCResourceThumbRepository;
use HoneyComb\Starter\Forms\HCBaseForm;

/**
 * Class HCResourceGrabPropertyForm
 * @package HoneyComb\Resources\Forms\Admin
 */
class HCResourceGrabPropertyForm extends HCBaseForm
{
    /**
     * @var \HoneyComb\Resources\Repositories\HCResourceThumbRepository
     */
    private $thumbRepository;

    /**
     * @var string
     */
    private $sourceType;

    /**
     * @var string
     */
    private $sourceId;

    /**
     * @var \HoneyComb\Resources\Repositories\HCResourceGrabPropertyRepository
     */
    private $grabPropertyRepository;

    /**
     * @var array|\Illuminate\Http\Request|string
     */
    private $resourceId;

    /**
     * HCResourceGrabPropertyForm constructor.
     * @param \HoneyComb\Resources\Repositories\HCResourceThumbRepository $thumbRepository
     * @param \HoneyComb\Resources\Repositories\HCResourceGrabPropertyRepository $grabPropertyRepository
     */
    public function __construct(
        HCResourceThumbRepository $thumbRepository,
        HCResourceGrabPropertyRepository $grabPropertyRepository
    ) {
        $this->sourceType = request('source_type');
        $this->sourceId = request('source_id');
        $this->resourceId = request('resource_id');

        $this->thumbRepository = $thumbRepository;
        $this->grabPropertyRepository = $grabPropertyRepository;
    }

    /**
     * Creating form
     *
     * @param bool $edit
     * @return array
     * @throws \Illuminate\Container\EntryNotFoundException
     * @throws \Exception
     */
    public function createForm(bool $edit = false): array
    {
        if (!$this->sourceId || !$this->sourceType || !$this->resourceId) {
            throw new \Exception('source_id, source_type and resource_id are required');
        }

        $form = [
            'storageUrl' => route('admin.api.resource.grab.property'),
            'buttons' => [
                'submit' => [
                    'label' => $this->getSubmitLabel($edit),
                ],
            ],
            'structure' => $this->getStructure($edit),
        ];

        if ($this->multiLanguage) {
            $form['availableLanguages'] = getHCContentLanguages();
        }

        return $form;
    }

    /**
     * @param string $prefix
     * @return array
     */
    public function getStructureNew(string $prefix): array
    {
        return [
            $prefix . 'images' => [
                'type' => 'imageGrabber',
                'availableThumbs' => $this->thumbRepository->getOptions(new HCResourceThumbRequest()),
                'grabbed' => $this->grabPropertyRepository->getGrabbed($this->sourceType, $this->sourceId,
                    $this->resourceId),
                'original' => [
                    'source_id' => $this->sourceId,
                    'source_type' => $this->sourceType,
                    'resource_id' => $this->resourceId,
                ]
            ],
        ];
    }

    /**
     * @param string $prefix
     * @return array
     */
    public function getStructureEdit(string $prefix): array
    {
        return $this->getStructureNew($prefix);
    }
}

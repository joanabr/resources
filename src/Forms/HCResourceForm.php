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

namespace HoneyComb\Resources\Forms;

use HoneyComb\Starter\Forms\HCBaseForm;

/**
 * Class HCResourceAuthorForm
 * @package HoneyComb\Resources\Forms
 */
class HCResourceForm extends HCBaseForm
{
    /**
     * @var bool
     */
    protected $multiLanguage = true;

    /**
     * Creating form
     *
     * @param bool $edit
     * @return array
     * @throws \Illuminate\Container\EntryNotFoundException
     */
    public function createForm(bool $edit = false): array
    {
        $form = [
            'storageUrl' => route('admin.api.resource'),
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
        return [];
    }

    /**
     * @param string $prefix
     * @return array
     */
    public function getStructureEdit(string $prefix): array
    {
        return [
            $prefix . 'author_id' =>
                [
                    'type' => 'singleLine',
                    'label' => trans('HCResource::resource.author'),
                ],

            $prefix . 'translations.label' =>
                [
                    'type' => 'singleLine',
                    'label' => trans('HCResource::resource.label'),
                    'multiLanguage' => 1,
                    'required' => 1,
                ],

            $prefix . 'translations.caption' =>
                [
                    'type' => 'textArea',
                    'label' => trans('HCResource::resource.caption'),
                    'multiLanguage' => 1,
                ],

            $prefix . 'translations.alt_text' =>
                [
                    'type' => 'singleLine',
                    'label' => trans('HCResource::resource.alt_text'),
                    'multiLanguage' => 1,
                ],

            $prefix . 'translations.description' =>
                [
                    'type' => 'textArea',
                    'label' => trans('HCResource::resource.description'),
                    'multiLanguage' => 1,
                ],
        ];
    }
}

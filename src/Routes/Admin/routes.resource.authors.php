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

Route::prefix(config('hc.admin_url'))
    ->namespace('Admin')
    ->middleware(['web', 'auth'])
    ->group(function () {

        Route::get('resource/author', 'HCResourceAuthorController@index')
                    ->name('admin.resource.author.index')
                    ->middleware('acl:honey_comb_resources_resource_author_admin_list');


        Route::prefix('api/resource/author')->group(function () {

    Route::get('/', 'HCResourceAuthorController@getListPaginate')
                ->name('admin.api.resource.author')
                ->middleware('acl:honey_comb_resources_resource_author_admin_list');


Route::get('list', 'HCResourceAuthorController@getList')
                    ->name('admin.api.resource.author.list')
                    ->middleware('acl:honey_comb_resources_resource_author_admin_list');

Route::post('/', 'HCResourceAuthorController@store')
    ->name('admin.api.resource.author.create')
    ->middleware('acl:honey_comb_resources_resource_author_admin_create');

Route::delete('/', 'HCResourceAuthorController@deleteSoft')
    ->name('admin.api.resource.author.delete')
    ->middleware('acl:honey_comb_resources_resource_author_admin_delete');

Route::delete('force', 'HCResourceAuthorController@deleteForce')
    ->name('admin.api.resource.author.delete.force')
    ->middleware('acl:honey_comb_resources_resource_author_admin_delete_force');

Route::post('restore', 'HCResourceAuthorController@restore')
    ->name('admin.api.resource.author.restore')
    ->middleware('acl:honey_comb_resources_resource_author_admin_restore');


    Route::prefix('{id}')->group(function () {

    Route::get('/', 'HCResourceAuthorController@getById')
    ->name('admin.api.resource.author.single')
    ->middleware('acl:honey_comb_resources_resource_author_admin_list');

Route::put('/', 'HCResourceAuthorController@update')
    ->name('admin.api.resource.author.update')
    ->middleware('acl:honey_comb_resources_resource_author_admin_update');

Route::patch('/', 'HCResourceAuthorController@patch')
    ->name('admin.api.resource.author.patch')
    ->middleware('acl:honey_comb_resources_resource_author_admin_update');


});
});

    });

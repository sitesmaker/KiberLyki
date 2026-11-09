<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Media;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SeoAdminController extends Controller
{
    public function edit(Request $request, SeoService $service)
    {
        $request->validate(['page' => 'nullable|string|max:150']);
        $targets = $service->targets();
        $selected = $request->query('page', 'page:home');
        abort_unless(isset($targets[$selected]), 404);

        return view('admin.seo', [
            'targets' => $targets, 'selected' => $selected, 'target' => $targets[$selected],
            'values' => $service->pageSettings($selected), 'settings' => $service->settings(),
            'automatic' => $service->forPath($targets[$selected]['path'], true),
        ]);
    }

    public function save(Request $request, SeoService $service)
    {
        $data = $request->validate([
            'target' => ['required', 'string', Rule::in(array_keys($service->targets()))],
            'title' => 'nullable|string|max:200', 'description' => 'nullable|string|max:500',
            'ogTitle' => 'nullable|string|max:200', 'ogDescription' => 'nullable|string|max:500',
            'indexing' => 'required|in:index,noindex',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120', 'remove_image' => 'nullable|boolean',
        ]);
        $values = array_replace($service->pageSettings($data['target']), collect($data)->except(['image', 'remove_image'])->all());
        if ($request->boolean('remove_image')) {
            $values['image'] = null;
        }
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $values['image'] = Media::create(['user_id' => $request->user()->id, 'url' => '/storage/'.$file->store('seo', 'public'), 'mime' => $file->getMimeType(), 'alternativeText' => $file->getClientOriginalName()])->toArray();
        }
        Content::updateOrCreate(['kind' => 'seo-page', 'documentId' => 'seo-page-'.hash('sha256', $data['target'])], ['payload' => $values, 'publishedAt' => now()]);

        return redirect('/admin/seo?page='.rawurlencode($data['target']))->with('status', 'SEO страницы сохранено');
    }

    public function settings(Request $request, SeoService $service)
    {
        $data = $request->validate([
            'siteName' => 'required|string|max:100',
            'indexing' => 'required|in:index,noindex',
        ]);
        $values = array_replace($service->settings(), $data);
        unset($values['siteUrl']);
        Content::updateOrCreate(['kind' => 'seo'], ['payload' => $values, 'publishedAt' => now()]);

        return redirect('/admin/seo')->with('status', 'Общие настройки сохранены');
    }
}

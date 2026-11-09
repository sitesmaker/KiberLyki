<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\PlayerProfile;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $r)
    {
        return ['data' => $r->user()->profile?->load('avatar')];
    }

    public function save(Request $r)
    {
        $v = $r->validate(['data.nickname' => 'required|string|max:100', 'data.firstName' => 'nullable|string|max:100', 'data.lastName' => 'nullable|string|max:100', 'data.country' => 'nullable|string|max:100', 'data.bio' => 'nullable|string|max:5000', 'data.avatar' => 'nullable|integer|exists:media,id']);
        $d = $v['data'];
        if (isset($d['avatar'])) {
            abort_unless(Media::whereKey($d['avatar'])->where('user_id', $r->user()->id)->where('mime', 'like', 'image/%')->exists(), 403, 'Выберите своё изображение');
            $d['avatar_id'] = $d['avatar'];
            unset($d['avatar']);
        } elseif (array_key_exists('avatar', $d)) {
            $d['avatar_id'] = null;
            unset($d['avatar']);
        }

        return ['data' => PlayerProfile::updateOrCreate(['user_id' => $r->user()->id], $d)->load('avatar')];
    }

    public function upload(Request $r)
    {
        $r->validate(['files' => 'required|file|mimes:jpg,jpeg,png,webp,gif|max:5120']);
        $f = $r->file('files');
        $path = $f->store('avatars', 'public');

        return [Media::create(['user_id' => $r->user()->id, 'url' => '/storage/'.$path, 'mime' => $f->getMimeType(), 'alternativeText' => $f->getClientOriginalName()])];
    }
}

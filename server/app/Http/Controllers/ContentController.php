<?php

namespace App\Http\Controllers;

use App\Models\Content;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public static function view(Content $c): array
    {
        $payload = $c->kind === 'staffs' ? \Illuminate\Support\Arr::only($c->payload, ['firstName', 'lastName', 'specialization', 'intro', 'description', 'sortOrder', 'photo']) : $c->payload;

        return array_merge($payload, ['id' => $c->id, 'documentId' => $c->documentId, 'createdAt' => $c->createdAt?->toISOString(), 'publishedAt' => $c->publishedAt?->toISOString()]);
    }

    public function index(Request $r, string $kind)
    {
        $query = Content::where('kind', $kind)->whereNotNull('publishedAt');
        if ($slug = $r->input('filters.slug.$eq')) {
            $query->where('payload->slug', $slug);
        }
        if (in_array($kind, ['about', 'first-screen', 'setting'])) {
            return ['data' => ($c = $query->first()) ? self::view($c) : null];
        }
        $page = max(1, (int) $r->input('pagination.page', 1));
        $size = max(1, min(100, (int) $r->input('pagination.pageSize', 25)));
        if ($kind === 'staffs') {
            $query->orderByRaw('COALESCE('.$query->getQuery()->getGrammar()->wrap('payload->sortOrder').', 0)')->orderBy('payload->lastName');
        } else {
            $query->orderByDesc('createdAt');
        }
        $items = $query->paginate($size, ['*'], 'page', $page);

        return ['data' => $items->getCollection()->map(fn ($c) => self::view($c)), 'meta' => ['pagination' => ['page' => $page, 'pageSize' => $size, 'pageCount' => $items->lastPage(), 'total' => $items->total()]]];
    }

    public function article(string $id)
    {
        $c = Content::where('kind', 'articles')->where('documentId', $id)->whereNotNull('publishedAt')->firstOrFail();

        return ['data' => self::view($c)];
    }

    public function staff(string $id)
    {
        return ['data' => self::view(Content::where('kind', 'staffs')->where('documentId', $id)->whereNotNull('publishedAt')->firstOrFail())];
    }
}

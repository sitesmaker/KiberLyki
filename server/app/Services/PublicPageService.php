<?php

namespace App\Services;

use App\Http\Controllers\ContentController;
use App\Models\Content;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Http\Request;

class PublicPageService
{
    public function render(Request $request, array $seo): ?string
    {
        $path = '/'.trim($request->path(), '/');
        $data = ['seo' => $seo, 'page' => null];
        if ($path === '/') {
            $data += [
                'hero' => $this->single('first-screen'), 'about' => $this->single('about'),
                'people' => Content::where('kind', 'staffs')->whereNotNull('publishedAt')->get()->sortBy(fn ($person) => sprintf('%05d', $person->payload['sortOrder'] ?? 0).($person->payload['lastName'] ?? ''))->map(fn ($person) => ContentController::view($person))->values(),
                'teams' => Team::where('isActive', true)->with(['logo', 'discipline'])->orderBy('name')->limit(4)->get(),
                'tournaments' => $this->tournaments()->whereIn('phase', ['registration', 'active', 'check_in'])->get()->filter(fn ($t) => $t->phase !== 'registration' || ($t->registrationStartsAt?->lte(now()) && $t->registrationEndsAt?->gte(now())))->sortBy(fn ($t) => array_search($t->phase, ['registration', 'active', 'check_in']).($t->startsAt?->format('YmdHis') ?? ''))->values(),
            ];
            $data['page'] = 'home';
        } elseif ($path === '/articles') {
            $data['page'] = 'articles';
            $data['articles'] = Content::where('kind', 'articles')->whereNotNull('publishedAt')->orderByDesc('createdAt')->paginate(12)->withPath('/articles')->withQueryString();
        } elseif (preg_match('~^/articles/([^/]+)$~', $path, $match)) {
            $article = Content::where('kind', 'articles')->whereNotNull('publishedAt')->where(fn ($q) => $q->where('payload->slug', rawurldecode($match[1]))->orWhere('documentId', rawurldecode($match[1])))->first();
            $data['page'] = $article ? 'article' : 'not-found';
            $data['article'] = $article ? ContentController::view($article) : null;
        } elseif ($path === '/tournaments') {
            $data['page'] = 'tournaments';
            $data['tournaments'] = $this->tournaments()->get();
        } elseif (preg_match('~^/tournaments/([^/]+)$~', $path, $match)) {
            $tournament = $this->tournaments()->where('documentId', rawurldecode($match[1]))->first();
            $data['page'] = $tournament ? 'tournament' : 'not-found';
            $data['tournament'] = $tournament?->load(['matches.teamA', 'matches.teamB', 'matches.winner', 'registrations' => fn ($q) => $q->where('registrationStatus', 'approved')->with('team')]);
        } else {
            return null;
        }

        return view('public.layout', $data)->render();
    }

    private function single(string $kind): array
    {
        return Content::where('kind', $kind)->whereNotNull('publishedAt')->first()?->payload ?? [];
    }

    private function tournaments()
    {
        return Tournament::whereNotNull('publishedAt')->with(['discipline', 'cover'])->orderBy('startsAt');
    }
}

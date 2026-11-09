<?php

namespace App\Services;

use App\Models\Content;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Support\Str;

class SeoService
{
    public function targets(): array
    {
        $targets = [];
        foreach (config('seo.pages') as $key => $page) {
            $targets['page:'.$key] = ['label' => $page['label'], 'path' => $page['path'], 'group' => 'Основные страницы'];
        }
        foreach (Tournament::whereNotNull('publishedAt')->orderByDesc('startsAt')->get() as $tournament) {
            $targets['tournament:'.$tournament->documentId] = ['label' => $tournament->title, 'path' => '/tournaments/'.rawurlencode($tournament->documentId), 'group' => 'Турниры'];
        }
        foreach (Content::where('kind', 'articles')->whereNotNull('publishedAt')->orderByDesc('publishedAt')->get() as $article) {
            $targets['article:'.$article->documentId] = ['label' => $article->payload['title'] ?? 'Новость', 'path' => '/articles/'.rawurlencode(($article->payload['slug'] ?? null) ?: $article->documentId), 'group' => 'Новости'];
        }

        return $targets;
    }

    public function pageSettings(string $target): array
    {
        return Content::where('kind', 'seo-page')->where('payload->target', $target)->first()?->payload ?? [];
    }

    public function settings(): array
    {
        $defaults = [
            'siteName' => 'КиберЛуки', 'indexing' => 'index',
            'title' => config('seo.pages.home.title'), 'description' => config('seo.pages.home.description'),
        ];
        foreach (config('seo.pages') as $key => $page) {
            $defaults[$key.'Title'] = $page['title'];
            $defaults[$key.'Description'] = $page['description'];
        }

        return array_replace($defaults, Content::where('kind', 'seo')->first()?->payload ?? [], ['siteUrl' => SiteUrl::origin()]);
    }

    public function indexable(array $settings): bool
    {
        $host = strtolower(parse_url($settings['siteUrl'], PHP_URL_HOST) ?? '');

        return $settings['indexing'] === 'index' && $host !== '' && ! in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1']) && ! str_ends_with($host, '.localhost') && ! str_ends_with($host, '.test');
    }

    private function description(?string $value, string $fallback): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return $text !== '' ? Str::limit($text, 250, '…') : $fallback;
    }

    private function image(?string $url): ?string
    {
        return $url ? (str_starts_with($url, 'http') ? $url : SiteUrl::origin().'/'.ltrim($url, '/')) : null;
    }

    public function forPath(string $path, bool $automatic = false): array
    {
        $settings = $this->settings();
        $path = '/'.trim(parse_url($path, PHP_URL_PATH) ?: '/', '/');
        $base = rtrim($settings['siteUrl'], '/');
        $seo = [
            'path' => $path, 'title' => $settings['title'], 'description' => $settings['description'],
            'canonical' => $base.$path, 'siteName' => $settings['siteName'],
            'image' => $this->image($settings['image']['url'] ?? null), 'type' => 'website',
            'robots' => 'noindex, follow', 'status' => 200, 'structuredData' => null,
        ];
        $found = false;
        $overrides = [];
        $target = null;
        $excluded = $path === '/teams';
        if (preg_match('~^/club/([^/]+)$~', $path, $personMatch)) {
            $person = Content::where('kind', 'staffs')->where('documentId', rawurldecode($personMatch[1]))->whereNotNull('publishedAt')->first();
            if ($person) {
                $seo['title'] = trim(($person->payload['firstName'] ?? '').' '.($person->payload['lastName'] ?? '')).' · '.$settings['siteName'];
                $seo['description'] = $this->description($person->payload['intro'] ?? $person->payload['description'] ?? null, $settings['description']);
                $excluded = true;
            }
        }
        foreach (config('seo.pages') as $key => $page) {
            if ($path === $page['path']) {
                $seo['title'] = $settings[$key.'Title'] ?: $settings['title'];
                $seo['description'] = $settings[$key.'Description'] ?: $settings['description'];
                $found = true;
                $target = 'page:'.$key;
                break;
            }
        }
        if (preg_match('~^/(articles|tournaments|teams)/([^/]+)$~', $path, $matches)) {
            $id = rawurldecode($matches[2]);
            if ($matches[1] === 'articles') {
                $record = Content::where('kind', 'articles')->whereNotNull('publishedAt')->where(fn ($q) => $q->where('payload->slug', $id)->orWhere('documentId', $id))->first();
                if ($record) {
                    $data = $record->payload;
                    $seo['title'] = ($data['seoTitle'] ?? null) ?: ($data['title'].' · '.$settings['siteName']);
                    $seo['description'] = ($data['seoDescription'] ?? null) ?: $this->description($data['description'] ?? $data['content'] ?? null, $settings['description']);
                    $seo['canonical'] = $base.'/articles/'.rawurlencode(($data['slug'] ?? null) ?: $record->documentId);
                    $seo['image'] = $this->image($data['cover']['url'] ?? null) ?: $seo['image'];
                    $seo['type'] = 'article';
                    $seo['structuredData'] = ['@context' => 'https://schema.org', '@type' => 'Article', 'headline' => $data['title'], 'datePublished' => $record->publishedAt->toISOString(), 'dateModified' => ($record->updatedAt ?? $record->publishedAt)->toISOString(), 'mainEntityOfPage' => $seo['canonical'], 'publisher' => ['@type' => 'Organization', 'name' => $settings['siteName']]];
                    if ($seo['image']) {
                        $seo['structuredData']['image'] = $seo['image'];
                    }
                    if (! empty($data['author'])) {
                        $seo['structuredData']['author'] = ['@type' => 'Person', 'name' => $data['author']];
                    }
                    $found = true;
                    $target = 'article:'.$record->documentId;
                }
            } elseif ($matches[1] === 'tournaments') {
                $record = Tournament::where('documentId', $id)->whereNotNull('publishedAt')->with('cover')->first();
                if ($record) {
                    $seo['title'] = $record->seoTitle ?: $record->title.' · '.$settings['siteName'];
                    $seo['description'] = $record->seoDescription ?: $this->description($record->description, $settings['description']);
                    $seo['image'] = $this->image($record->cover?->url) ?: $seo['image'];
                    $found = true;
                    $target = 'tournament:'.$record->documentId;
                }
            } else {
                $record = Team::where('documentId', $id)->where('isActive', true)->with('logo')->first();
                if ($record) {
                    $seo['title'] = $record->name.' — команда · '.$settings['siteName'];
                    $seo['description'] = $this->description($record->description, 'Состав команды '.$record->name.' на платформе '.$settings['siteName'].'.');
                    $seo['image'] = null;
                    $excluded = true;
                }
            }
        }
        if ($found) {
            $overrides = $automatic ? [] : $this->pageSettings($target);
            $seo['title'] = ($overrides['title'] ?? null) ?: $seo['title'];
            $seo['description'] = ($overrides['description'] ?? null) ?: $seo['description'];
            $seo['image'] = $this->image($overrides['image']['url'] ?? null) ?: $seo['image'];
            $seo['robots'] = $this->indexable($settings) && ($overrides['indexing'] ?? 'index') !== 'noindex' ? 'index, follow' : 'noindex, follow';
            if (! $seo['structuredData']) {
                $seo['structuredData'] = ['@context' => 'https://schema.org', '@type' => $path === '/' ? 'WebSite' : 'WebPage', 'name' => $seo['title'], 'description' => $seo['description'], 'url' => $seo['canonical'], 'inLanguage' => 'ru-RU'];
            }
        } elseif ($excluded) {
            if ($path === '/teams') {
                $seo['title'] = 'Команды · '.$settings['siteName'];
            }
        } elseif (! preg_match('~^/(login|cabinet|admin)(/|$)~', $path)) {
            $seo['status'] = 404;
            $seo['title'] = 'Страница не найдена · '.$settings['siteName'];
            $seo['description'] = 'Запрошенная страница не найдена.';
        } else {
            $seo['title'] = 'Личный кабинет · '.$settings['siteName'];
        }

        $seo['ogTitle'] = ($overrides['ogTitle'] ?? null) ?: $seo['title'];
        $seo['ogDescription'] = ($overrides['ogDescription'] ?? null) ?: $seo['description'];

        return $seo;
    }

    public function sitemapUrls(): array
    {
        $settings = $this->settings();
        if (! $this->indexable($settings)) {
            return [];
        }
        $base = rtrim($settings['siteUrl'], '/');
        $urls = array_map(fn ($page) => ['loc' => $base.$page['path']], array_values(config('seo.pages')));
        foreach (Content::where('kind', 'articles')->whereNotNull('publishedAt')->get() as $article) {
            $urls[] = ['loc' => $base.'/articles/'.rawurlencode(($article->payload['slug'] ?? null) ?: $article->documentId), 'lastmod' => $article->updatedAt?->toAtomString()];
        }
        foreach (Tournament::whereNotNull('publishedAt')->get() as $tournament) {
            $urls[] = ['loc' => $base.'/tournaments/'.rawurlencode($tournament->documentId), 'lastmod' => $tournament->updatedAt?->toAtomString()];
        }
        $excluded = Content::where('kind', 'seo-page')->get()->filter(fn ($record) => ($record->payload['indexing'] ?? 'index') === 'noindex')->pluck('payload.target')->all();
        $targets = $this->targets();
        $excludedUrls = array_map(fn ($target) => isset($targets[$target]) ? $base.$targets[$target]['path'] : '', $excluded);

        return collect($urls)->reject(fn ($url) => in_array($url['loc'], $excludedUrls, true))->unique('loc')->values()->all();
    }
}

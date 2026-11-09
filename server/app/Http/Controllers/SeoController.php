<?php

namespace App\Http\Controllers;

use App\Services\SeoService;
use Illuminate\Http\Request;

class SeoController extends Controller
{
    public function metadata(Request $request, SeoService $service)
    {
        $data = $request->validate(['path' => 'required|string|max:2048|starts_with:/']);
        $seo = $service->forPath($data['path']);

        return ['data' => $seo, 'head' => view('seo-head', compact('seo'))->render()];
    }

    public function robots(SeoService $service)
    {
        $settings = $service->settings();
        $text = "User-agent: *\n";
        if (! $service->indexable($settings)) {
            $text .= "Disallow: /\n";
        } else {
            $text .= "Disallow: /admin\nDisallow: /cabinet\nDisallow: /login\nDisallow: /api/\nSitemap: ".rtrim($settings['siteUrl'], '/')."/sitemap.xml\n";
        }

        return response($text)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function sitemap(SeoService $service)
    {
        $xml = new \XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        foreach ($service->sitemapUrls() as $url) {
            $xml->startElement('url');
            $xml->writeElement('loc', $url['loc']);
            if (! empty($url['lastmod'])) {
                $xml->writeElement('lastmod', $url['lastmod']);
            }
            $xml->endElement();
        }
        $xml->endElement();
        $xml->endDocument();

        return response($xml->outputMemory())->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function frontend(Request $request, SeoService $service)
    {
        $path = public_path('site/index.html');
        abort_unless(is_file($path), 503, 'Сначала соберите React: cd client && npm run build');
        $seo = $service->forPath('/'.$request->path());
        if ($request->is('articles') && (int) $request->query('page', 1) > 1) {
            $seo['canonical'] .= '?page='.(int) $request->query('page');
            $seo['title'] .= ' — Страница '.(int) $request->query('page');
        }
        $html = file_get_contents($path);
        $html = preg_replace('~<title>.*?</title>|<meta\s+name="description"[^>]*>~is', '', $html);
        $html = str_replace('</head>', view('seo-head', compact('seo'))->render().'</head>', $html);
        $content = app(\App\Services\PublicPageService::class)->render($request, $seo);
        if ($content !== null) {
            $html = str_replace('<div id="root"></div>', '<div id="root" data-server-page>'.$content.'</div>', $html);
        }

        return response($html, $seo['status'])->header('Content-Type', 'text/html; charset=UTF-8');
    }
}

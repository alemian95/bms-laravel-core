<?php

namespace App\Services;

use fivefilters\Readability\Configuration;
use fivefilters\Readability\ParseException;
use fivefilters\Readability\Readability;
use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Support\Facades\Http;

class ArticleContentParser
{
    private const USER_AGENT = 'Mozilla/5.0 (compatible; BMSBookmarkBot/1.0; +https://example.com/bot)';

    /**
     * @return array{content_html: string|null, content_text: string|null}
     */
    public function parse(string $url): array
    {
        $response = Http::withUserAgent(self::USER_AGENT)
            ->timeout(15)
            ->withOptions(['allow_redirects' => true])
            ->get($url);

        if (! $response->successful() || trim($response->body()) === '') {
            return ['content_html' => null, 'content_text' => null];
        }

        $readability = new Readability(new Configuration(['originalURL' => $url]));

        try {
            if (! $readability->parse($response->body())) {
                return ['content_html' => null, 'content_text' => null];
            }
        } catch (ParseException) {
            return ['content_html' => null, 'content_text' => null];
        }

        $rawHtml = $readability->getContent();

        if ($rawHtml === null || trim($rawHtml) === '') {
            return ['content_html' => null, 'content_text' => null];
        }

        $cleanHtml = $this->sanitize($rawHtml);
        $text = $this->toPlainText($cleanHtml);

        return [
            'content_html' => $cleanHtml,
            'content_text' => $text,
        ];
    }

    private function sanitize(string $html): string
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', storage_path('app/htmlpurifier'));
        $config->set('HTML.Allowed', 'p,br,strong,em,b,i,u,h1,h2,h3,h4,h5,h6,blockquote,pre,code,ul,ol,li,a[href|title|rel|target],img[src|alt|title|width|height],table,thead,tbody,tr,th,td,hr,span,div');
        $config->set('HTML.TargetBlank', true);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('Core.RemoveInvalidImg', true);

        return (new HTMLPurifier($config))->purify($html);
    }

    private function toPlainText(string $html): string
    {
        $decoded = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/', ' ', $decoded) ?? '');
    }
}

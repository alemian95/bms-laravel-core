<?php

use App\Services\ArticleContentParser;
use Illuminate\Support\Facades\Http;

test('extracts article content and strips unsafe tags', function () {
    $html = <<<'HTML'
        <html>
            <head><title>Test</title></head>
            <body>
                <article>
                    <h1>The Great Article</h1>
                    <p>First paragraph with <strong>bold</strong>.</p>
                    <p>Second paragraph about testing. It needs to be long enough for Readability to keep it as relevant content so we add more words here.</p>
                    <script>alert('pwned');</script>
                    <p onclick="hack()">Third paragraph with inline handler that should be stripped by the purifier.</p>
                </article>
            </body>
        </html>
    HTML;

    Http::fake([
        'example.com/*' => Http::response($html, 200, ['Content-Type' => 'text/html']),
    ]);

    $result = (new ArticleContentParser)->parse('https://example.com/article');

    expect($result['content_html'])->toBeString()
        ->and($result['content_html'])->not->toContain('<script')
        ->and($result['content_html'])->not->toContain('onclick')
        ->and($result['content_html'])->toContain('<p>')
        ->and($result['content_text'])->toBeString()
        ->and($result['content_text'])->not->toContain('<')
        ->and($result['content_text'])->toContain('First paragraph');
});

test('returns nulls on non-successful response', function () {
    Http::fake([
        '*' => Http::response('', 500),
    ]);

    $result = (new ArticleContentParser)->parse('https://example.com/broken');

    expect($result)->toBe([
        'content_html' => null,
        'content_text' => null,
    ]);
});

test('returns nulls on empty body', function () {
    Http::fake([
        '*' => Http::response('', 200),
    ]);

    $result = (new ArticleContentParser)->parse('https://example.com/empty');

    expect($result['content_html'])->toBeNull()
        ->and($result['content_text'])->toBeNull();
});

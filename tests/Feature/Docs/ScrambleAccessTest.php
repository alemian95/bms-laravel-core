<?php

use Illuminate\Support\Facades\Gate;

it('serves the Swagger UI in non-production environments', function () {
    $response = $this->get('/docs/api');

    $response->assertOk();
});

it('serves a valid OpenAPI document with the v1 endpoints', function () {
    $response = $this->getJson('/docs/api.json');

    $response->assertOk();

    $document = $response->json();

    expect($document)
        ->toHaveKey('openapi')
        ->toHaveKey('paths')
        ->and(array_keys($document['paths']))
        ->toContain('/v1/login')
        ->toContain('/v1/logout')
        ->toContain('/v1/user');
});

it('declares a bearer http security scheme', function () {
    $response = $this->getJson('/docs/api.json');

    $document = $response->json();

    expect($document['components']['securitySchemes'] ?? [])
        ->not->toBeEmpty()
        ->and($document['components']['securitySchemes'])
        ->toHaveKey('http')
        ->and($document['components']['securitySchemes']['http']['scheme'])
        ->toBe('bearer');
});

it('forbids docs access when the viewApiDocs gate denies', function () {
    Gate::define('viewApiDocs', fn ($user = null) => false);

    $this->get('/docs/api')->assertForbidden();
    $this->getJson('/docs/api.json')->assertForbidden();
});

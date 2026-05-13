<?php

require_once __DIR__.'/../vendor/autoload.php';

use Bnomei\Handlebars;

test('construct', function () {
    $hbs = new Handlebars;
    expect($hbs)->toBeInstanceOf(Handlebars::class);

    // trigger flush
    $hbs = new Handlebars([
        'debug' => true,
    ]);
});
test('option', function () {
    $hbs = new Handlebars;
    expect($hbs->option())->toBeArray();
    expect($hbs->option('resolve-content-queries'))->toBeFalse();

    $hbs = new Handlebars([
        'debug' => true,
    ]);
    expect($hbs->option('render'))->toBeFalse();
});
test('name', function () {
    $hbs = new Handlebars;
    $name = $hbs->name('/site/templates/default.'.$hbs->option('extension-input'));
    expect('default')->toEqual($name);
});
test('file', function () {
    $hbs = new Handlebars;

    $this->assertStringContainsString(
        '/site/templates/render-unto.'.$hbs->option('extension-input'),
        $hbs->file('render-unto')
    );
});
test('fields to value', function () {
    $hbs = new Handlebars;
    $data = [
        'titleFromValue' => page('home')->title(),
        'titleFromField' => page('home')->title()->value(),
    ];
    expect($hbs->fieldsToValue($data))->toHaveCount(2);
});
test('content queries are not resolved by default', function () {
    kirby()->impersonate(null);

    $payload = '{{ kirby.impersonate("kirby").id }} {{ kirby.user.id }}';
    $hbs = new Handlebars([
        'resolve-content-queries' => false,
    ]);

    try {
        $resolved = $hbs->resolveQueries(
            ['payload' => $payload],
            [
                'kirby' => kirby(),
                'site' => site(),
                'page' => page('home'),
            ]
        );

        expect($resolved['payload'])->toEqual($payload);
        expect(kirby()->user()?->id())->not->toEqual('kirby');
    } finally {
        kirby()->impersonate(null);
    }
});
test('configured queries resolve without parsing content strings', function () {
    $page = page('home');
    $payload = '{{ kirby.version }}';
    $hbs = new Handlebars([
        'queries' => [
            'page.title',
            'page.url',
        ],
        'resolve-content-queries' => false,
    ]);
    $params = [
        'kirby' => kirby(),
        'site' => site(),
        'page' => $page,
    ];

    $resolved = $hbs->resolveQueries(
        $hbs->addQueries(['payload' => $payload, 'page' => $page], $params),
        $params
    );

    expect($resolved['payload'])->toEqual($payload);
    expect($resolved['page']['title'])->toEqual('Home');
    expect($resolved['page']['url'])->toEqual($page->url());
});
test('handlebars', function () {
    $hbs = new Handlebars;
    $render = $hbs->handlebars('default', [
        'title' => 'Home',
        'c' => 'Cassia',
        'counting' => [
            ['label' => 1],
            ['label' => 2],
            ['label' => 3],
        ],
    ]);
    $this->assertStringContainsString('Home of <i>Cassia</i>.', $render);

    $render = $hbs->handlebars('call-a-partial', [
        'cake' => 'Pizza',
    ]);
    $this->assertStringContainsString('Piece of Pizza', $render);
});

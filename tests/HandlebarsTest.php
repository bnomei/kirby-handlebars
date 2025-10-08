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
    expect($hbs->option())->toHaveCount(6);

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

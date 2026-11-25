<?php

use Bnomei\HandlebarsTemplate;

test('construct', function () {
    $hbsT = new HandlebarsTemplate('default');
    expect($hbsT)->toBeInstanceOf(HandlebarsTemplate::class);
});
test('render', function () {
    $hbsT = new HandlebarsTemplate('default');
    $render = $hbsT->render(['title' => 'Swamp', 'c' => 'Crocodile']);
    expect($render)->toBeString();
    expect($render)->toStartWith('Swamp of <i>Crocodile</i>');
});
test('extension', function () {
    $hbsT = new HandlebarsTemplate('default');
    expect($hbsT->extension())->toEqual('hbs');
});
test('file', function () {
    $hbsT = new HandlebarsTemplate('default');
    expect($hbsT->file())->toEqual(kirby()->roots()->templates().'/default.hbs');
});

test('the template component does not claim a missing representation', function () {
    expect(kirby()->template('render-unto')->exists())->toBeTrue()
        ->and(kirby()->template('render-unto', 'json')->exists())->toBeFalse();
});

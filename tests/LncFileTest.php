<?php

require_once __DIR__.'/../vendor/autoload.php';

use Bnomei\LncFile;
use Kirby\Toolkit\F;

beforeEach(function () {
    $this->target = kirby()->roots()->cache().'/plugins/bnomei/handlebars/default.php';

    $this->default = kirby()->roots()->templates().'/default.hbs';
});
afterEach(function () {
    F::remove($this->target);
});
test('construct', function () {
    $file = new LncFile([
        'source' => '',
    ]);
    expect($file)->toBeInstanceOf(LncFile::class);
});
test('php', function () {
    $file = new LncFile([
        'source' => $this->default,
        'target' => $this->target,
        'modified' => F::modified($this->default),
        'partial' => false,
        'name' => 'default',
        'lnc' => true,
    ]);
    expect($file->php())->toBeInstanceOf(Closure::class);
});
test('hbs', function () {
    $file = new LncFile([
        'source' => $this->default,
        'modified' => F::modified($this->default),
    ]);

    expect($file->hbs())->toStartWith('{{ title }} of <i>{{ c }}</i>.');
});
test('to array', function () {
    $file = new LncFile([
        'source' => $this->default,
        'modified' => F::modified($this->default),
    ]);
    expect($file->toArray())->toBeArray();
    expect($file->toArray())->toHaveCount(3);
});
test('modified', function () {
    F::write($this->default, F::read($this->default));
    // aka touch
    $file = new LncFile([
        'source' => $this->default,
        'target' => $this->target,
        'modified' => F::modified($this->default) - 50,
    ]);
    expect($file->needsUpdate())->toBeTrue();
});
test('not modified', function () {
    F::write($this->target, 'tmp');
    $file = new LncFile([
        'source' => $this->default,
        'target' => $this->target,
        'modified' => F::modified($this->default),
    ]);
    expect($file->needsUpdate())->toBeFalse();
});

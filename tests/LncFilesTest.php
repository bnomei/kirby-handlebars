<?php

require_once __DIR__.'/../vendor/autoload.php';

use Bnomei\LncFiles;
use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;
use Kirby\Toolkit\Str;

beforeEach(function () {
    $files = new LncFiles;
    $files->flush();
});
test('construct', function () {
    $files = new LncFiles([]);
    expect($files)->toBeInstanceOf(LncFiles::class);
});
test('static', function () {
    $files = LncFiles::singleton();
    expect($files)->toBeInstanceOf(LncFiles::class);
});
test('option', function () {
    $files = new LncFiles(['debug' => true]);
    expect($files->option('debug'))->toBeTrue();

    expect($files->option())->toBeArray();
});
test('filter dir by extension', function () {
    $files = new LncFiles;
    $hbs = $files->filterDirByExtension(
        (string) $files->option('dir-partials'),
        (string) $files->option('extension-input')
    );
    expect($hbs)->toBeArray();
    expect($hbs)->toHaveCount(1);
    $this->assertStringContainsString(
        '.'.(string) $files->option('extension-input'),
        $hbs[0]
    );
});
test('compile', function () {
    $files = LncFiles::singleton();
    $load = $files->load();

    foreach ($load as $lncFile) {
        if ($lncFile->needsUpdate() && ! $lncFile->partial()) {
            $h = $lncFile->hbs();
            if (Str::contains($h, '{{>')) {
                continue;
            }

            //                $this->assertTrue($h);
            $php = $files->compile($lncFile);
            expect($php)->toStartWith('<?php ');
        }
    }
});
test('load', function () {
    $files = new LncFiles;
    expect($files->option('files'))->toBeTrue();

    $scan = $files->load();
    expect($scan)->toBeArray();
    expect($scan)->toHaveCount(5);
});
test('write', function () {
    $files = new LncFiles;
    expect($files->option('files'))->toBeTrue();
    $scan = $files->load();
    expect($files->write($scan))->toBeTrue();

    $files = new LncFiles([
        'files' => false,
    ]);
    expect($files->option('files'))->toBeFalse();
    $scan = $files->load();
    expect($files->write($scan))->toBeFalse();
});
test('load from cache', function () {
    $files = new LncFiles;
    expect($files->option('files'))->toBeTrue();

    $files->write($files->load());
    $files->load();
});
test('flush', function () {
    $files = new LncFiles;
    F::write($files->lncCacheRoot().'/test.tmp', 'test');
    $files->flush();
    expect(count(Dir::files($files->lncCacheRoot())) === 0)->toBeTrue();
});
test('modified', function () {
    $files = new LncFiles;
    expect(count(Dir::files($files->option('dir-partials'))))->toEqual(2);

    $modified = $files->modified(
        $files->filterDirByExtension(
            (string) $files->option('dir-partials'),
            (string) $files->option('extension-input')
        )
    );
    expect($modified)->toBeString();

    $hbsModified = F::modified($files->option('dir-partials').'/piece-of-cake.hbs');
    expect(hash('xxh3', implode(['LncFilesSalt', $hbsModified])))->toEqual($modified);
});
test('hbs of partial', function () {
    $files = new LncFiles([
        'debug' => true,
    ]);
    $files->registerAllTemplates();

    expect($files->hbsOfPartial('piece-of-cake'))->toEqual(F::read($files->option('dir-partials').'/piece-of-cake.hbs'));

    expect($files->hbsOfPartial('does-not-exist'))->toEqual('');
});
test('lnc file', function () {
    $files = new LncFiles([
        'debug' => true,
    ]);
    $files->registerAllTemplates();

    expect($files->lncFile('render-unto'))->toEqual($files->lncCacheRoot().'/render-unto.'.$files->option('extension-output'));

    expect($files->lncFile('does-not-exist'))->toEqual($files->lncCacheRoot().'/default.'.$files->option('extension-output'));
});
test('hbs file', function () {
    $files = new LncFiles([
        'debug' => true,
    ]);
    $files->registerAllTemplates();

    expect($files->hbsFile('render-unto'))->toEqual(kirby()->roots()->templates().'/render-unto.'.$files->option('extension-input'));

    expect($files->hbsFile('does-not-exist'))->toEqual(kirby()->roots()->templates().'/default.'.$files->option('extension-input'));
});
test('precompiled template', function () {
    $files = LncFiles::singleton();
    $files->registerAllTemplates();

    expect($files->precompiledTemplate('default'))->toBeInstanceOf(Closure::class);

    expect(
        // default template
        $files->precompiledTemplate('doesnotexist')
    )->toBeInstanceOf(Closure::class);
});
test('register all templates', function () {
    $files = new LncFiles;
    $files->registerAllTemplates();
    expect($files->files())->toBeArray();
    expect($files->files())->toHaveCount(5);

    // $this->assertTrue($files->files());
    // TODO: checks
});
test('lnc cache root', function () {
    $files = new LncFiles;
    expect($files->lncCacheRoot())->toMatch("/.*\/site\/cache\/.*\/bnomei\/handlebars/");
});
test('target', function () {
    $files = new LncFiles;
    expect($files->target('default'))->toEqual($files->lncCacheRoot().'/default.'.$files->option('extension-output'));

    expect($files->target('piece-of-cake', true))->toEqual($files->lncCacheRoot().'/@piece-of-cake.'.$files->option('extension-output'));
});
test('writes only once', function () {
    $singleton = LncFiles::singleton();
    $hbsFileOfDefault = $singleton->hbsFile('default');
    $lncFileOfDefault = $singleton->lncFile('default');
    $options = $singleton->options();
    expect($options['lnc'])->toBeTrue();
    $singleton->flush();
    $this->assertFileDoesNotExist($singleton->lncFile('default'));

    // simulate request #1, will write files
    // var_dump('#1');
    $files = new LncFiles($options);
    expect($files->files())->toHaveCount(0);
    $this->assertFileDoesNotExist($lncFileOfDefault);
    $files->registerAllTemplates();
    expect($files->lncFile('default'))->toBeFile();
    expect($files->files())->toHaveCount(5);

    $default = $files->precompiledTemplate('default');
    $modified = F::modified($files->lncFile('default'));

    // simulate request #2, will load and not write
    sleep(2);

    // make modified check possible
    // var_dump('#2');
    $files = new LncFiles($options);
    $files->registerAllTemplates();
    expect(Dir::read($files->lncCacheRoot()))->toHaveCount(6);
    $default = $files->precompiledTemplate('default');
    expect(F::modified($files->lncFile('default')))->toEqual($modified);

    // simulate request #3, but hbs file changed so will write again
    // var_dump('#3');
    F::write($hbsFileOfDefault, F::read($hbsFileOfDefault));
    sleep(2);
    // make modified check possible
    $files = new LncFiles($options);
    $files->registerAllTemplates();
    $this->assertNotEquals($modified, F::modified($files->lncFile('default')));
});

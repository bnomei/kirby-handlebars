<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Bnomei\LncFiles;
use Kirby\Cms\Dir;
use Kirby\Toolkit\F;
use Kirby\Toolkit\Str;
use LightnCandy\LightnCandy;
use PHPUnit\Framework\TestCase;

class LncFilesTest extends TestCase
{
    public function setUp(): void
    {
        $files = new LncFiles();
        $files->flush();
    }

    public function testConstruct()
    {
        $files = new LncFiles([]);
        $this->assertInstanceOf(LncFiles::class, $files);
    }

    public function testStatic()
    {
        $files = LncFiles::singleton();
        $this->assertInstanceOf(LncFiles::class, $files);
    }

    public function testOption()
    {
        $files = new LncFiles(['debug' => true]);
        $this->assertTrue($files->option('debug'));

        $this->assertIsArray($files->option());
    }

    public function testCompileOptions()
    {
        $files = new LncFiles(['compile-flags' => function () {
            return LightnCandy::FLAG_ELSE;
        }]);
        $this->assertCount(2, $files->compileOptions());
        $this->assertEquals(16777216, $files->compileOptions()['flags']);

        $files = new LncFiles(['compile-flags' => function () {
            return LightnCandy::FLAG_ELSE | LightnCandy::FLAG_NOESCAPE;
        }]);
        $this->assertEquals(83886080, $files->compileOptions()['flags']);
    }

    public function testFilterDirByExtension()
    {
        $files = new LncFiles();
        $hbs = $files->filterDirByExtension(
            (string)$files->option('dir-partials'),
            (string)$files->option('extension-input')
        );
        $this->assertIsArray($hbs);
        $this->assertCount(1, $hbs);
        $this->assertStringContainsString(
            '.'.(string)$files->option('extension-input'),
            $hbs[0]
        );
    }

    public function testCompile()
    {
        $files = LncFiles::singleton();
        $load = $files->load();

        foreach ($load as $lncFile) {
            if ($lncFile->needsUpdate() && !$lncFile->partial()) {
                $h = $lncFile->hbs();
                if (Str::contains($h, '{{>')) {
                    continue;
                }

//                $this->assertTrue($h);
                $php = $files->compile($lncFile);
                $this->assertStringStartsWith('use \LightnCandy\Runtime as LR;', $php);
                $lncFile->php($php);
            }
        }
    }

    public function testLoad()
    {
        $files = new LncFiles();
        $this->assertTrue($files->option('files'));

        $scan = $files->load();
        $this->assertIsArray($scan);
        $this->assertCount(5, $scan);
    }

    public function testWrite()
    {
        $files = new LncFiles();
        $this->assertTrue($files->option('files'));
        $scan = $files->load();
        $this->assertTrue($files->write($scan));

        $files = new LncFiles([
            'files' => false,
        ]);
        $this->assertFalse($files->option('files'));
        $scan = $files->load();
        $this->assertFalse($files->write($scan));
    }

    public function testLoadFromCache()
    {
        $files = new LncFiles();
        $this->assertTrue($files->option('files'));

        $files->write($files->load());
        $files->load();
    }

    public function testFlush()
    {
        $files = new LncFiles();
        F::write($files->lncCacheRoot() . '/test.tmp', 'test');
        $files->flush();
        $this->assertTrue(Dir::isEmpty($files->lncCacheRoot()));
    }

    public function testModified()
    {
        $files = new LncFiles();
        $this->assertEquals(2, count(Dir::files($files->option('dir-partials'))));

        $modified = $files->modified(
            $files->filterDirByExtension(
                (string)$files->option('dir-partials'),
                (string)$files->option('extension-input')
            )
        );
        $this->assertIsString($modified);

        $hbsModified = F::modified($files->option('dir-partials') . '/piece-of-cake.hbs');
        $this->assertEquals($modified, strval(crc32(implode(['LncFilesSalt', $hbsModified]))));
    }

    public function testHbsOfPartial()
    {
        $files = new LncFiles([
            'debug' => true,
        ]);
        $files->registerAllTemplates();

        $this->assertEquals(
            F::read($files->option('dir-partials') . '/piece-of-cake.hbs'),
            $files->hbsOfPartial('piece-of-cake')
        );

        $this->assertEquals(
            '',
            $files->hbsOfPartial('does-not-exist')
        );
    }

    public function testLncFile()
    {
        $files = new LncFiles([
            'debug' => true,
        ]);
        $files->registerAllTemplates();

        $this->assertEquals(
            $files->lncCacheRoot() . '/render-unto.' . $files->option('extension-output'),
            $files->lncFile('render-unto')
        );

        $this->assertEquals(
            $files->lncCacheRoot() . '/default.' . $files->option('extension-output'),
            $files->lncFile('does-not-exist')
        );
    }

    public function testHbsFile()
    {
        $files = new LncFiles([
            'debug' => true,
        ]);
        $files->registerAllTemplates();

        $this->assertEquals(
            kirby()->roots()->templates() . '/render-unto.' . $files->option('extension-input'),
            $files->hbsFile('render-unto')
        );

        $this->assertEquals(
            kirby()->roots()->templates() . '/default.' . $files->option('extension-input'),
            $files->hbsFile('does-not-exist')
        );
    }

    public function testPrecompiledTemplate()
    {
        $files = LncFiles::singleton();
        $files->registerAllTemplates();

        $this->assertStringStartsWith(
            'use \LightnCandy\Runtime as LR;',
            $files->precompiledTemplate('default')
        );

        $this->assertStringStartsWith(
            'use \LightnCandy\Runtime as LR;', // default template
            $files->precompiledTemplate('doesnotexist')
        );
    }

    public function testRegisterAllTemplates()
    {
        $files = new LncFiles();
        $files->registerAllTemplates();
        $this->assertIsArray($files->files());
        $this->assertCount(5, $files->files());

        // $this->assertTrue($files->files());
        // TODO: checks
    }

    public function testLncCacheRoot()
    {
        $files = new LncFiles();
        $this->assertEquals(
            __DIR__ . '/site/cache/plugins/bnomei/handlebars/lnc',
            $files->lncCacheRoot()
        );
    }

    public function testTarget()
    {
        $files = new LncFiles();
        $this->assertEquals(
            $files->lncCacheRoot() . '/default.' . $files->option('extension-output'),
            $files->target('default')
        );

        $this->assertEquals(
            $files->lncCacheRoot() . '/@piece-of-cake.' . $files->option('extension-output'),
            $files->target('piece-of-cake', true)
        );
    }

    public function testWritesOnlyOnce()
    {
        $singleton = LncFiles::singleton();
        $hbsFileOfDefault = $singleton->hbsFile('default');
        $lncFileOfDefault = $singleton->lncFile('default');
        $options = $singleton->options();
        $this->assertTrue($options['lnc']);
        $singleton->flush();
        $this->assertFileDoesNotExist($singleton->lncFile('default'));

        // simulate request #1, will write files
        //var_dump('#1');
        $files = new LncFiles($options);
        $this->assertCount(0, $files->files());
        $this->assertFileDoesNotExist($lncFileOfDefault);
        $files->registerAllTemplates();
        $this->assertFileExists($files->lncFile('default'));
        $this->assertCount(5, $files->files());

        $default = $files->precompiledTemplate('default');
        $modified = F::modified($files->lncFile('default'));

        // simulate request #2, will load and not write
        sleep(2); // make modified check possible
        //var_dump('#2');
        $files = new LncFiles($options);
        $files->registerAllTemplates();
        $this->assertCount(5, Dir::read($files->lncCacheRoot()));
        $default = $files->precompiledTemplate('default');
        $this->assertEquals($modified, F::modified($files->lncFile('default')));

        // simulate request #3, but hbs file changed so will write again
        //var_dump('#3');
        F::write($hbsFileOfDefault, F::read($hbsFileOfDefault));
        sleep(2); // make modified check possible
        $files = new LncFiles($options);
        $files->registerAllTemplates();
        $this->assertNotEquals($modified, F::modified($files->lncFile('default')));
    }
}

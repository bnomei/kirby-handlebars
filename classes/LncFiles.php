<?php

declare(strict_types=1);

namespace Bnomei;

use Closure;
use DevTheorem\Handlebars\Context;
use DevTheorem\Handlebars\Options;
use Exception;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;
use Kirby\Toolkit\A;

final class LncFiles
{
    /**
     * @var array<LncFile>
     */
    private array $files;

    private ?string $modified = null;

    private array $options;

    public function __construct(array $options = [])
    {
        $defaults = [
            'debug' => option('debug'),
            'extension-input' => option('bnomei.handlebars.extension-input'),
            'extension-output' => option('bnomei.handlebars.extension-output'),
            'files' => option('bnomei.handlebars.files'),
            'lnc' => option('bnomei.handlebars.lnc'),
            'dir-templates' => option('bnomei.handlebars.dir-templates'),
            'dir-partials' => option('bnomei.handlebars.dir-partials'),
        ];
        $this->options = array_merge($defaults, $options);

        //        $this->options['files'] = $this->options['files'] && ! $this->options['debug'];
        //        $this->options['lnc'] = $this->options['lnc'] && ! $this->options['debug'];

        foreach ($this->options as $key => $call) {
            if ($call instanceof Closure) {
                $this->options[$key] = $call();
            }
        }

        if ($this->option('debug')) {
            $this->flush();
        }

        $this->files = [];
    }

    public function option(?string $key = null): mixed
    {
        if ($key) {
            return A::get($this->options, $key);
        }

        return $this->options;
    }

    public function options(): array
    {
        return $this->options;
    }

    public function hbsOfPartial(string $name): string
    {
        foreach ($this->files as $lncFile) {
            if ($lncFile->partial() && $lncFile->name() === $name) {
                return $lncFile->hbs();
            }
        }

        return '';
    }

    public function filterDirByExtension(string $dir, string $extension): array
    {
        $result = [];
        foreach (Dir::files($dir, null, true) as $file) {
            if (F::extension($file) === $extension) {
                $result[] = $file;
            }
        }

        return $result;
    }

    public function modified(array $files = []): string
    {
        if ($this->modified) {
            return $this->modified;
        }
        if (count($files) === 0) {
            $files = array_merge(
                $this->filterDirByExtension(
                    strval($this->option('dir-templates')),
                    strval($this->option('extension-input'))
                ),
                $this->filterDirByExtension(
                    strval($this->option('dir-partials')),
                    strval($this->option('extension-input'))
                )
            );
        }

        $modified = ['LncFilesSalt'];
        foreach ($files as $file) {
            $modified[] = F::modified($file);
        }

        $this->modified = hash('xxh3', implode($modified));

        return $this->modified;
    }

    public function scan(): array
    {
        $files = [];
        $dirs = [
            [$this->option('dir-templates'), false],
            [$this->option('dir-partials'), true],
        ];

        foreach ($dirs as $dir) {
            $templates = $this->filterDirByExtension(
                strval($dir[0]),
                strval($this->option('extension-input'))
            );
            // first get all
            foreach ($templates as $file) {
                $name = basename($file, '.'.strval($this->option('extension-input')));
                // ignore all files starting with _ (like fractals.build _preview.hbs)
                if (str_starts_with($name, '_')) {
                    continue;
                }
                $files[] = new LncFile([
                    'name' => $name,
                    'source' => $file,
                    'target' => $this->target($file, $dir[1]),
                    'partial' => $dir[1],
                    'modified' => F::modified($file),
                    'lnc' => $this->option('lnc'),
                ]);
            }
        }

        return $files;
    }

    public function compile(LncFile $lncFile): string
    {
        return '<?php '.\DevTheorem\Handlebars\Handlebars::precompile(
            $lncFile->hbs(),
            new Options(
                partialResolver: function (Context $context, string $name) {
                    return $this->hbsOfPartial($name);
                }
            )
        );
    }

    public function target(string $file, bool $partial = false): string
    {
        $path = [
            $this->lncCacheRoot(),
            DIRECTORY_SEPARATOR,
            ($partial ? '@' : ''),
            basename($file, '.'.strval($this->option('extension-input'))),
            '.'.strval($this->option('extension-output')),
        ];

        return implode($path);
    }

    public function load(): array
    {
        $files = [];

        if ($this->option('files')) {
            $files = kirby()->cache('bnomei.handlebars.files')->get(
                $this->modified(),
                []
            );
            $files = array_map(function ($file) {
                return new LncFile($file);
            }, $files);
        }
        if (count($files)) {
            return $files;
        }

        return $this->scan();
    }

    public function write(array $files): bool
    {
        if (! $this->option('files')) {
            return false;
        }

        return kirby()->cache('bnomei.handlebars.files')->set(
            $this->modified(),
            array_map(function ($file) {
                return $file->toArray();
            }, $files)
        );
    }

    public function registerAllTemplates(): array
    {
        $this->files = $this->load();

        $anyPartialNeedsUpdate = false;
        foreach ($this->files as $lncFile) {
            if ($lncFile->partial() && $lncFile->needsUpdate()) {
                $anyPartialNeedsUpdate = true;
                $lncFile->writePartial();
            }
        }

        foreach ($this->files as $lncFile) {
            if (! $lncFile->partial() && ($anyPartialNeedsUpdate || $lncFile->needsUpdate())) {
                F::write($lncFile->target(), $this->compile($lncFile));
            }
        }

        $this->write($this->files);

        return $this->files;
    }

    public function lncFile(string $name): string
    {
        foreach ($this->files as $lncFile) {
            if ($lncFile->name() === $name) {
                return $lncFile->target();
            }
        }
        if ($name === 'default') {
            throw new InvalidArgumentException; // @codeCoverageIgnore
        }

        return $this->lncFile('default');
    }

    public function hbsFile(string $name): string
    {
        foreach ($this->files as $lncFile) {
            if ($lncFile->name() === $name) {
                return F::realpath($lncFile->source());
            }
        }
        if ($name === 'default') {
            throw new InvalidArgumentException; // @codeCoverageIgnore
        }

        return $this->hbsFile('default');
    }

    public function precompiledTemplate(string $name): Closure
    {
        /** @var LncFile $lncFile */
        foreach ($this->files as $lncFile) {
            if ($lncFile->partial() === false && $lncFile->name() === $name) {
                return $lncFile->php();
            }
        }

        return $this->precompiledTemplate('default');
    }

    public function lncCacheRoot(): string
    {
        return kirby()->cache('bnomei.handlebars')->root(); // @phpstan-ignore-line
    }

    public function flush(): void
    {
        try {
            kirby()->cache('bnomei.handlebars.files')->flush();

            foreach (Dir::read($this->lncCacheRoot()) as $file) {
                $file = $this->lncCacheRoot().'/'.$file;
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        } catch (Exception $e) {
            //
        }
    }

    /**
     * @return array<LncFile>
     */
    public function files(): array
    {
        return $this->files;
    }

    private static ?LncFiles $singleton = null;

    public static function singleton(array $options = []): LncFiles
    {
        if (self::$singleton === null) {
            self::$singleton = new self($options);
            self::$singleton->registerAllTemplates();
        }

        return self::$singleton;
    }
}

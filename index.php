<?php

use Bnomei\Handlebars;
use Kirby\Template\Template;

@include_once __DIR__.'/vendor/autoload.php';

Kirby::plugin('bnomei/handlebars', [
    'options' => [
        'component' => true,

        'dir-templates' => function () {
            return kirby()->roots()->templates(); // @phpstan-ignore-line
        },
        'dir-partials' => function () {
            $templates = option('bnomei.handlebars.dir-templates');
            if (is_callable($templates)) {
                $templates = $templates();
            }

            return $templates.DIRECTORY_SEPARATOR.'partials';
        },

        'extension-input' => 'hbs', // or 'mustache' etc.
        'extension-output' => 'php', // or 'lnc' etc.

        // ALLOW creation...
        'cache' => true, // to get the root folder
        'cache.files' => true, // creates a plugin cache called 'files'
        'cache.lnc' => true, // creates a plugin cache called 'lnc'
        // actually used config
        'files' => true,
        'lnc' => true,

        'queries' => [
            'site.title',
            'site.url',
            'page.id',
            'page.uuid',
            'page.title',
            'page.text',
            'page.url',
            'page.slug',
            'page.template',
        ],
    ],
    'components' => [
        'template' => function (Kirby\Cms\App $kirby, string $name, string $type = 'html', string $defaultType = 'html') {
            if (option('bnomei.handlebars.component')) {
                return new Bnomei\HandlebarsTemplate($name, $type);
            }

            return new Template($name, $type);
        },
    ],
    'pageMethods' => [
        'handlebars' => function ($template = null, $data = []) {
            return (new Handlebars)->render(
                $template ?? $this->template(),
                $this->controller($data),
                null,
                null,
                true
            );
        },
    ],
]);

if (! class_exists('Bnomei\Handlebars')) {
    require_once __DIR__.'/classes/Handlebars.php';
}

if (! function_exists('handlebars')) {
    function handlebars(string $template, array $data = [], bool $return = false): string
    {
        return (new Handlebars)->render($template, $data, null, null, $return);
    }
}

if (! function_exists('hbs')) {
    function hbs(string $template, array $data = [], bool $return = false): string
    {
        return handlebars($template, $data, $return);
    }
}

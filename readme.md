# Kirby Handlebars / Mustache

[![Kirby 5](https://flat.badgen.net/badge/Kirby/5?color=ECC748)](https://getkirby.com)
![PHP 8.2](https://flat.badgen.net/badge/PHP/8.2?color=4E5B93&icon=php&label)
![Release](https://flat.badgen.net/packagist/v/bnomei/kirby-handlebars?color=ae81ff&icon=github&label)
![Downloads](https://flat.badgen.net/packagist/dt/bnomei/kirby-handlebars?color=272822&icon=github&label)
[![Discord](https://flat.badgen.net/badge/discord/bnomei?color=7289da&icon=discord&label)](https://discordapp.com/users/bnomei)
[![Buymecoffee](https://flat.badgen.net/badge/icon/donate?icon=buymeacoffee&color=FF813F&label)](https://www.buymeacoffee.com/bnomei)

Kirby Plugin for semantic templates with Handlebars and Mustache

## Installation

- unzip [master.zip](https://github.com/bnomei/kirby-handlebars/archive/master.zip) as folder
  `site/plugins/kirby-handlebars` or
- `git submodule add https://github.com/bnomei/kirby-handlebars.git site/plugins/kirby-handlebars` or
- `composer require bnomei/kirby-handlebars`

## Templates

You can can have handlebars and php templates in your `/site/templates` folder. The plugin will be used as template component for all files with `hbs` extension and default back to Kirbys core template component for the ones written in php.

## Partials

Partials are stored at `/site/template/partials`. Think of them as reusable blocks injected into your handlebars templates **before** they get compiled.

**/site/templates/partials/piece-of-cake.hbs**
```handlebars
Piece of {{ cake }}
```

**/site/templates/call-a-partial.hbs**
```handlebars
{{> piece-of-cake }}
```

**/content/pizza/call-a-partial.txt**
```markdown
Title: 🍕
```

**/site/controllers/call-a-partial.php**
```php
<?php
return function ($site, $page, $kirby) {
    return ['cake' => $page->title()]; // cake => 🍕
};
```

**localhost:80/pizza**
```html
Piece of 🍕
```

## Data 

For the template to render your content, it needs to know which data to use as variables and list in handlebars. This plugin gives you several ways to provide this data, and you can use them all at once. The data from queries will be merged (recursively) with data from controllers and models.

### Built-in Queries

The plugin has a few queries built-in. This allows you to use the queried result directly in your handlebar templates. For example, you can use `{{ page.title }}` in any handlebar template without having to define that value yourself. You can override these in the `bnomei.handlebars.queries` option.

```handlebars
<article id="{{ page.slug }}">
  <h2>{{ page.title }}</h2>
  {{ page.text }}
</article>
```

```handlebars
{{ site.title }}
{{ site.url }}
{{ page.id }}
{{ page.title }}
{{ page.text }}
{{ page.url }}
{{ page.slug }}
{{ page.template }}
```

### Automatic parsing of queries in all fields

You can also use queries when providing data from controllers or models. Further more any plain string will be parsed as a query. This allows you to write queries in textarea fields as well.

**/content/blog/unesco-heritage/post.txt**
```yaml
Title: Unesco heritage
----
Date: 2017-12-07
----
Art: twirling
----
Text:
On {{ page.date.toDate('d.m.Y') }} the Neapolitan art of pizza {{ page.art }} 
has joined Unesco’s list of intangible heritage.
```

**/site/templates/post.hbs**
```handlebars
<article id="{{ page.slug }}">
  <h2>{{ page.title }}</h2>
  {{ page.text }}
</article>
```

**localhost:80/blog/unesco-heritage**

```html
<article id="unesco-heritage">
  <h2>Unesco heritage</h2>
  <p>On 07.12.2020 the Neapolitan art of pizza twirling 
has joined Unesco’s list of intangible heritage.</p>
</article>
```

### Controllers

**/content/home/home.txt**
```markdown
Title: Home
```

**/site/controllers/home.php**
```php
<?php
return function ($site, $page, $kirby) {
    return [
        'c'=> 'Cassia',
        'counting' => [
            ['label' => 1],
            ['label' => 2],
            ['label' => 3],
        ],
    ];
};
```

**/site/templates/default.hbs**
```handlebars
{{ page.title }} of <i>{{ c }}</i>.
<ul>
{{# counting }}<li>{{ label }}</li>{{/ counting }}
</ul>
```

### Models: Method

**/site/models/home.php**
```php
<?php
class HomePage extends Page
{
    public function handlebarsData(): array
    {
        return [
            'c'=> 'Cassia',
            'counting' => [
                ['label' => 1],
                ['label' => 2],
                ['label' => 3],
            ],
        ];
    }
}
```

### Models: Extend model from Plugin and define exported methods

```php
<?php
class HomePage extends \Bnomei\HandlebarsPage
{
    // declare what public methods to export
    public static $handlebarsData = [
        'c',        // $this->c()
        'counting'  // $this->counting()
    ];

    public function c(): string
    {
        return 'Cassia';
    }
 
    public function counting(): array
    {
        return [
            ['label' => 1],
            ['label' => 2],
            ['label' => 3],
        ];
    }
}
```

## handlebars()/hbs() helper

Maybe you just need to parse some handlebars when you create your data, or you do not use the template component at all. For the later disable the component with the `bnomei.handlebars.component` config setting and just use the provided helper functions. The `hbs()`/`handlebars()` take the same parameters as the Kirby `snippet()` function. This means they **echo** the result by default but take a third function parameter to enforce returning the value.

**/site/templates/render-unto.hbs**
```handlebars
Render unto {{ c }} the things that are {{ c }}'s, and unto {{ g }} the things that are {{ g }}'s.
```

**/site/templates/non-hbs-template.php**
```php
<?php
// echo template 'render-unto'
// data from site/controllers/home.php merged with custom array
hbs('render-unto', ['c'=> 'Caesar', 'g'=>'God']);
```
> Render unto Caesar the things that are Caesar's, and unto God the things that are God's.

```php
// return template 'render-unto'
$string = hbs('render-unto', ['c' => 'Cat', 'g' => 'Dog'], true);
echo $string;
```

> Render unto Cat the things that are Cat's, and unto Dog the things that are Dog's.

## Settings

| bnomei.handlebars.        | Default    | Description               |            
|---------------------------|------------|---------------------------|
| component | `true`     | if `false` no templating will be handled by this plugin and you need to use the `hbs()`/`handlebars()` functions. |
| dir-templates | `callback` | returning `kirby()->roots()->templates()` |
| dir-partials | `callback` | returning `kirby()->roots()->templates().'/partials'` |
| extension-input | `hbs`      | |
| extension-output | `php`      | hbs compiled to php |
| queries | `[...]`    | an array of predefined queries you can use in your templates |

## Dependencies

- https://github.com/devtheorem/php-handlebars

## Disclaimer

This plugin is provided "as is" with no guarantee. Use it at your own risk and always test it yourself before using it
in a production environment. If you find any issues,
please [create a new issue](https://github.com/bnomei/kirby-handlebars/issues/new).

## License

[MIT](https://opensource.org/licenses/MIT)

It is discouraged to use this plugin in any project that promotes racism, sexism, homophobia, animal abuse, violence or
any other form of hate speech.

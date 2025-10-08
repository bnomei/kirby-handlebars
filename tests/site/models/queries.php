<?php

declare(strict_types=1);

use Bnomei\HandlebarsPage;

class QueriesPage extends HandlebarsPage
{
    /*
    public function handlebarsData(): array
    {
        return [
            'toppings' => [
                ['name' => 'cheese'],
                ['name' => 'salat'],
                ['name' => 'ketchup'],
                ['name' => 'onions'],
            ],

            'k3v' => '{{ kirby.version }}',
            'indexcount' => '{{ site.index.count }}', // query to execute

            'textWithQuery' => "Some field value {{ page.myfield }} at {{ page.date.toDate('c') }}",
            'kirbytextWithQuery' => $this->text()->kirbytext(),
        ];
    }
    */

    public static array $handlebarsData = [
        'toppings',
        'k3v',
        'indexcount',
        'textWithQuery',
        'kirbytextWithQuery',
    ];

    public function toppings(): array
    {
        return [
            ['name' => 'cheese'],
            ['name' => 'salat'],
            ['name' => 'ketchup'],
            ['name' => 'onions'],
        ];
    }

    public function k3v(): string
    {
        return '{{ kirby.version }}';
    }

    public function indexcount(): string
    {
        return '{{ site.index.count }}';
    }

    public function textWithQuery(): string
    {
        return "Some field value {{ page.myfield }} at {{ page.date.toDate('c') }}";
    }

    public function kirbytextWithQuery(): \Kirby\Content\Field
    {
        return $this->text()->kirbytext();
    }
}

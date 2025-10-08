<?php

return function ($site, $page, $kirby) {
    return [
        'title' => $page->title()->value(), // Home
        'c' => 'Cassia',
        'counting' => [
            ['label' => 1],
            ['label' => 2],
            ['label' => 3],
        ],
    ];
};

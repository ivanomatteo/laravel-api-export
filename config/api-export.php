<?php

return [
    'onlyNamed' => false,

    'nameAllowRules' => [
        // 'foo',
        // 'bar.baz*',
        // '*foo.bar',
    ],
    'nameDenyRules' => [
        'debugbar',
        'debugbar.*',
        'telescope',
        'telescope.*',
        'horizon',
        'horizon.*',
    ],

    'uriAllowRules' => [

    ],
    'uriDenyRules' => [
        'telescope',
        'telescope/*',
    ],
];

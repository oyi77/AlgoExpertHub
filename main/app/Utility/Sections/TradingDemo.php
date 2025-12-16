<?php

namespace App\Utility\Sections;

use App\Utility\Schema;

class TradingDemo
{
    use Schema;

    public $has_element = false;
    public $has_content = true;

    public $image_upload_ids = [];

    public $fields = [
        'trading-landing' => [
            'title' => 'Text',
            'description' => 'Textarea',
        ]
    ];

    public $classes = [
        'trading-landing' => [
            'title' => 'col-md-12',
            'description' => 'col-md-12',
        ]
    ];

    public $validation = [
        'trading-landing' => [
            'title' => 'required',
            'description' => 'required',
        ]
    ];
}


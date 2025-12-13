<?php

namespace App\Utility\Sections;

use App\Utility\Schema;

class FooterCta
{
    use Schema;

    public $has_element = false;
    public $has_content = true;

    public $image_upload_ids = [];

    public $fields = [
        'trading-landing' => [
            'title' => 'Text',
            'subtitle' => 'Text',
            'button_text' => 'Text',
            'button_text_link' => 'Text',
        ]
    ];

    public $classes = [
        'trading-landing' => [
            'title' => 'col-md-12',
            'subtitle' => 'col-md-12',
            'button_text' => 'col-md-6',
            'button_text_link' => 'col-md-6',
        ]
    ];

    public $validation = [
        'trading-landing' => [
            'title' => 'required',
            'subtitle' => 'nullable',
            'button_text' => 'required',
            'button_text_link' => 'required',
        ]
    ];
}


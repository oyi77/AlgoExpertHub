<?php

namespace App\Utility\Sections;

use App\Utility\Schema;

class Hero
{
    use Schema;

    public $has_element = false;
    public $has_content = true;

    public $image_upload_ids = [];

    public $fields = [
        'trading-landing' => [
            'badge_text' => 'Text',
            'title' => 'Text',
            'description' => 'Textarea',
            'button_text' => 'Text',
            'button_text_link' => 'Text',
        ]
    ];

    public $classes = [
        'trading-landing' => [
            'badge_text' => 'col-md-12',
            'title' => 'col-md-12',
            'description' => 'col-md-12',
            'button_text' => 'col-md-6',
            'button_text_link' => 'col-md-6',
        ]
    ];

    public $validation = [
        'trading-landing' => [
            'badge_text' => 'nullable',
            'title' => 'required',
            'description' => 'required',
            'button_text' => 'required',
            'button_text_link' => 'required',
        ]
    ];
}


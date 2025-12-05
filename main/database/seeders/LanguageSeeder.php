<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run()
    {
        $languages = [
            ['name' => 'English', 'code' => 'en', 'status' => 0], // 0 = default
            ['name' => 'Spanish', 'code' => 'es', 'status' => 1],
            ['name' => 'Indonesia', 'code' => 'id', 'status' => 1],
            ['name' => 'French', 'code' => 'fr', 'status' => 1],
            ['name' => 'German', 'code' => 'de', 'status' => 1],
            ['name' => 'Chinese', 'code' => 'zh', 'status' => 1],
            ['name' => 'Japanese', 'code' => 'ja', 'status' => 1],
            ['name' => 'Arabic', 'code' => 'ar', 'status' => 1],
        ];

        foreach ($languages as $lang) {
            Language::firstOrCreate(
                ['code' => $lang['code']],
                $lang
            );
        }
    }
}


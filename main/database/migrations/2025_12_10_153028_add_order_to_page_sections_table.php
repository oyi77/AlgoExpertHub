<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('page_sections', function (Blueprint $table) {
            $table->integer('order')->default(0)->after('sections');
        });

        // Set order for existing sections based on seeder order
        $pageOrders = [
            'home' => ['banner', 'about', 'benefits', 'how_works', 'plans', 'trade', 'referral', 'team', 'testimonial', 'blog'],
            'about' => ['about', 'overview', 'how_works', 'team'],
            'packages' => ['plans'],
            'contact' => ['contact'],
            'blog' => ['blog']
        ];

        foreach ($pageOrders as $slug => $sections) {
            $page = \App\Models\Page::where('slug', $slug)->first();
            if ($page) {
                foreach ($sections as $index => $sectionName) {
                    \DB::table('page_sections')
                        ->where('page_id', $page->id)
                        ->where('sections', $sectionName)
                        ->update(['order' => $index + 1]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('page_sections', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};

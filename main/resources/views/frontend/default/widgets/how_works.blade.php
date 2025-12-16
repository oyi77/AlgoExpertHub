<section class="work-section sp_pt_120 sp_pb_120">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7 text-center">
                <div class="sp_theme_top">
                    <div class="sp_theme_top_caption"><i class="fas fa-bolt"></i> {{ Config::trans($content->section_header) }}</div>
                    <h2 class="sp_theme_top_title"><?= Config::colorText(optional($content)->title, optional($content)->color_text_for_title) ?></h2>
                </div>
            </div>
        </div>

        <div class="row gy-4 justify-content-center">
            @php
                $uniqueElements = collect($element)
                    ->unique(function ($item) {
                        return $item->content->title ?? $item->id;
                    })
                    ->sortBy('id')
                    ->values();
            @endphp
            @foreach ($uniqueElements as $item)
                <div class="col-lg-4 col-md-6">
                    <div class="sp_work_item card-modern text-center">
                        <div class="sp_work_number badge-modern badge-primary mb-3 mx-auto">
                            {{$loop->iteration}}
                        </div>
                        <div class="sp_work_content">
                            <h4 class="title">{{Config::trans($item->content->title)}}</h4>
                            <p class="mt-2">{{Config::trans($item->content->description)}}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
</section>
<!-- how work section end -->

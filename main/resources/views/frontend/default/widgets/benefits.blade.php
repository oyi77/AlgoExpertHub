@php
    $chunks = $element->chunk(3);
    $left = isset($chunks[0]) ? $chunks[0] : [];
    $right = isset($chunks[1]) ? $chunks[1] : [];
@endphp

<section class="benefit-section sp_pt_120 sp_pb_120">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7 text-center">
                <div class="sp_theme_top">
                    <div class="sp_theme_top_caption"><i class="fas fa-bolt"></i> {{ Config::trans($content->section_header) }}</div>
                    <h2 class="sp_theme_top_title">
                        <?= Config::trans($content->title) ?>
                    </h2>
                </div>
            </div>
        </div>
        <div class="row gy-4 align-items-center">
            <div class="col-xl-4 col-md-6">
                @foreach ($left as $item)
                    <div class="sp_benefit_item card-modern mb-4">
                        <div class="sp_benefit_icon">
                            @if($item->content->icon)
                                <i class="{{ $item->content->icon}}"></i>
                            @elseif($item->content->image_one)
                                <img src="{{ Config::getFile('benefits', $item->content->image_one) }}" alt="{{ Config::trans($item->content->title) }}" loading="lazy">
                            @endif
                        </div>
                        <div class="sp_benefit_content">
                            <h4 class="title">{{ Config::trans($item->content->title)}}</h4>
                            <p class="mt-2">{{ Config::trans($item->content->description)}}</p>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="col-lg-4 d-xl-block d-none">
                <div class="sp_benefit_thumb">
                    <img src="{{ Config::getFile('benefits', $content->image_one) }}" alt="Benefits" loading="lazy">
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                @foreach ($right as $item)
                    <div class="sp_benefit_item card-modern mb-4">
                        <div class="sp_benefit_icon">
                            @if($item->content->icon)
                                <i class="{{ $item->content->icon}}"></i>
                            @elseif($item->content->image_one)
                                <img src="{{ Config::getFile('benefits', $item->content->image_one) }}" alt="{{ Config::trans($item->content->title) }}" loading="lazy">
                            @endif
                        </div>
                        <div class="sp_benefit_content">
                            <h4 class="title">{{ Config::trans($item->content->title)}}</h4>
                            <p class="mt-2">{{ Config::trans($item->content->description)}}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
<!-- benefit section end -->

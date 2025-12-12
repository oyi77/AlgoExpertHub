
<section class="about-section sp_pt_120 sp_pb_120">
    <div class="about-el"><img src="{{ Config::getFile('about', $content->image_two ?? '') }}" alt="about line image">
    </div>
    <div class="container">
        <div class="row gy-5 align-items-center justify-content-between">
            <div class="col-lg-7 ps-lg-5 wow fadeInUp" data-wow-duration="0.5s" data-wow-delay="0.5s">
                <h2 class="sp_theme_top_title">
                    <?= Config::colorText(optional($content)->title, optional($content)->color_text_for_title) ?>
                </h2>
                <p class="fs-lg mt-3"><?= Config::trans($content->description) ?></p>
                <ul class="sp_check_list mt-4">
                    @if ($content && isset($content->repeater))
                        @if (is_array($content->repeater))
                            @foreach ($content->repeater as $repeater)
                                <li>{{ Config::trans($repeater->repeater ?? '') }}</li>
                            @endforeach
                        @elseif (is_string($content->repeater) && !empty($content->repeater))
                            <li>{{ Config::trans($content->repeater) }}</li>
                        @endif
                    @endif
                </ul>
                <a href="{{ optional($content)->button_link ?? '' }}" class="btn sp_theme_btn mt-4">{{ Config::trans($content->button_text) }}</a>
            </div>
            
            <div class="col-lg-5">
                <div class="about-thumb">
                    <img src="{{ Config::getFile('about', $content->image_one ?? '') }}" alt="image">
                </div>
            </div>
        </div>
    </div>
</section>
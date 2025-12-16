<section class="testimonial-section sp_pt_120 sp_pb_120">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7 text-center">
                <div class="sp_theme_top">
                    <div class="sp_theme_top_caption"><i class="fas fa-bolt"></i> {{ Config::trans($content->section_header) }}</div>
                    <h2 class="sp_theme_top_title">
                        <?= Config::colorText(optional($content)->title, optional($content)->color_text_for_title) ?>
                    </h2>
                </div>
            </div>
        </div>
    </div>
    <div class="sp_testimonial_area mt-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-10">
                    <div class="sp_testimonial_item">
                        <div class="sp_testimonial_thumb_area">
                            <div class="sp_testimonial_thumb_slider">
                                @foreach ($element as $item)
                                    <div class="sp_testimonial_thumb_slide {{ $loop->first ? 'active' : '' }}">
                                        <div class="sp_testimonial_thumb">
                                            <img src="{{ Config::getFile('testimonial', $item->content->image_one) }}" alt="{{ $item->content->client_name }}" loading="lazy">
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="testi-prev" aria-label="Previous testimonial"><i class="las la-angle-left"></i></button>
                            <button type="button" class="testi-next" aria-label="Next testimonial"><i class="las la-angle-right"></i></button>
                        </div>
                        <div class="sp_testimonial_content_area">
                            <div class="sp_testimonial_content_slider">
                                @foreach ($element as $item)
                                    <div class="sp_testimonial_content {{ $loop->first ? 'active' : '' }}" style="{{ $loop->first ? 'display: block;' : 'display: none;' }}">
                                        <div class="card-modern p-4">
                                            <div class="d-flex flex-wrap align-items-end justify-content-md-start justify-content-center mb-3">
                                                <h4 class="name me-3 mb-0">{{ $item->content->client_name }}</h4>
                                                <span class="sp_site_color badge-modern badge-primary">{{ $item->content->designation }}</span>
                                            </div>
                                            <p class="mt-3 mb-0">
                                                {{ Config::trans($item->content->description) }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- testimonial section end -->

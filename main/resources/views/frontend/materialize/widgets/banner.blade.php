@php
    $banner = Config::builder('banner');
@endphp
 
<!-- banner section start -->
@if($banner && $banner->content)
<section class="sp_banner">
    <div class="sp_banner_bottom_shape" aria-hidden="true"></div>
    <div class="container">
        <div class="row justify-content-between align-items-center">
            <div class="col-lg-6">
                <h2 class="sp_banner_title" data-animate="fadeInUp">
                    <?= Config::trans($banner->content->title ?? '') ?>
                </h2>
                <ul class="sp_check_list mt-4" data-animate="fadeInUp">
                    @if(isset($banner->content->repeater) && is_array($banner->content->repeater))
                    @foreach ($banner->content->repeater as $item)
                            <li><?= Config::trans($item->repeater ?? '') ?> </li>
                    @endforeach
                    @endif
                </ul>
                <a href="{{ $banner->content->button_text_link ?? '#' }}" class="btn btn-primary btn-lg mt-5 focus-ring" data-animate="fadeInUp">{{ Config::trans($banner->content->button_text ?? '')}}</a>
            </div>
            <div class="col-lg-5">
                <div class="sp_banner_thumb" data-animate="fadeInUp">
                    <img src="{{ Config::getFile('banner', $banner->content->image_one ?? '') }}" class="sp_banner_img" alt="{{ Config::trans($banner->content->title ?? 'Banner') }}" loading="eager">
                </div>
            </div>
        </div>
    </div>
</section>
@endif
<!-- banner section end -->

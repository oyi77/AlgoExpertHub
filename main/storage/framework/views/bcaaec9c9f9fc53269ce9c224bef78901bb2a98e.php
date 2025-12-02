<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="<?php echo e($page->seo_description ?? Config::config()->seo_description); ?>" />
    <meta name="keywords" content="<?php echo e(implode(',', $page->seo_keywords ?? Config::config()->seo_tags)); ?> ">
    <title><?php echo e(Config::config()->appname); ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Poppins:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="<?php echo e(optional(Config::config()->fonts)->heading_font_url); ?>">
    <link rel="stylesheet" href="<?php echo e(optional(Config::config()->fonts)->paragraph_font_url); ?>">
    <link rel="shortcut icon" type="image/png" href="<?php echo e(Config::getFile('icon', Config::config()->favicon, true)); ?>">
    <link rel="stylesheet" href="<?php echo e(Config::cssLib('frontend', 'lib/bootstrap.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(Config::cssLib('frontend', 'all.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(Config::cssLib('frontend', 'line-awesome.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(Config::cssLib('frontend', 'lib/slick.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(Config::cssLib('frontend', 'lib/odometer.css')); ?>">

    <?php
        $alertType = optional(Config::config())->alert ?? 'sweetalert';
    ?>
    <?php if($alertType === 'izi'): ?>
        <link rel="stylesheet" href="<?php echo e(Config::cssLib('frontend', 'izitoast.min.css')); ?>">
    <?php elseif($alertType === 'toast'): ?>
        <link href="<?php echo e(Config::cssLib('frontend', 'toastr.min.css')); ?>" rel="stylesheet">
    <?php else: ?>
        <link href="<?php echo e(Config::cssLib('frontend', 'sweetalert.min.css')); ?>" rel="stylesheet">
    <?php endif; ?>

    <link href="<?php echo e(Config::cssLib('frontend', 'main.css')); ?>?v=20251202" rel="stylesheet">

    <link href="<?php echo e(Config::cssLib('frontend', 'responsive.css')); ?>?v=20251202" rel="stylesheet">

    <?php
        $heading = optional(Config::config()->fonts)->heading_font_family ?? 'DM Sans';
        $paragraph = optional(Config::config()->fonts)->paragraph_font_family ?? 'Poppins';
    ?>

    <style>
        :root {
            --h-font: <?=$heading ?>;
            --p-font: <?=$paragraph ?>;
            --display-font: "DM Sans", sans-serif;
            --body-font: "Poppins", sans-serif;
        }
    </style>

    <?php echo $__env->yieldPushContent('external-css'); ?>

    <?php echo $__env->yieldPushContent('style'); ?>
</head>

<body>
    <?php if(Config::config()->preloader_status): ?>
        <div class="preloader-holder">
            <div class="preloader">
                <div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div>
            </div>
        </div>
    <?php endif; ?>

    <?php if(Config::config()->analytics_status): ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo e(Config::config()->analytics_key); ?>"></script>
        <script>
            'use strict'
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }
            gtag("js", new Date());
            gtag("config", "<?php echo e(Config::config()->analytics_key); ?>");
        </script>
    <?php endif; ?>

    <?php if(Config::config()->allow_modal): ?>
        <?php echo $__env->make('cookie-consent::index', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php endif; ?>

    <div class="body-content-area">
        <?php if(request()->routeIs('home')): ?>
            <?php echo $__env->make(Config::theme() . 'widgets.banner', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php endif; ?>

        <?php echo $__env->make(Config::theme() . 'layout.header', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <?php if(!request()->routeIs('home')): ?>
            <?php echo $__env->make(Config::theme() . 'widgets.breadcrumb', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php endif; ?>

        <?php echo $__env->yieldContent('content'); ?>
    </div>

    <?php echo $__env->make(Config::theme() . 'widgets.footer', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <script src="<?php echo e(Config::jsLib('frontend', 'lib/jquery.min.js')); ?>"></script>
    <script src="<?php echo e(Config::jsLib('frontend', 'lib/bootstrap.bundle.min.js')); ?>"></script>
    <script src="<?php echo e(Config::jsLib('frontend', 'lib/slick.min.js')); ?>"></script>
    <script src="<?php echo e(Config::jsLib('frontend', 'lib/wow.min.js')); ?>"></script>
    <script src="<?php echo e(Config::jsLib('frontend', 'lib/jquery.paroller.min.js')); ?>"></script>
    <script src="<?php echo e(Config::jsLib('frontend', 'lib/TweenMax.min.js')); ?>"></script>
    <script src="<?php echo e(Config::jsLib('frontend', 'lib/odometer.min.js')); ?>"></script>
    <script src="<?php echo e(Config::jsLib('frontend', 'lib/viewport.jquery.js')); ?>"></script>

    <?php if(optional(Config::config())->alert ?? 'sweetalert' === 'izi'): ?>
        <script src="<?php echo e(Config::jsLib('frontend', 'izitoast.min.js')); ?>"></script>
    <?php elseif(optional(Config::config())->alert ?? 'sweetalert' === 'toast'): ?>
        <script src="<?php echo e(Config::jsLib('frontend', 'toastr.min.js')); ?>"></script>
    <?php else: ?>
        <script src="<?php echo e(Config::jsLib('frontend', 'sweetalert.min.js')); ?>"></script>
    <?php endif; ?>

    <script src="<?php echo e(Config::jsLib('frontend', 'main.js')); ?>"></script>

    <?php echo $__env->yieldPushContent('script'); ?>

    <?php if(Config::config()->twak_allow): ?>
        <script type="text/javascript">
            var Tawk_API = Tawk_API || {},
                Tawk_LoadStart = new Date();
            (function() {
                var s1 = document.createElement("script"),
                    s0 = document.getElementsByTagName("script")[0];
                s1.async = true;
                s1.src = "<?php echo e(Config::config()->twak_key); ?>";
                s1.charset = 'UTF-8';
                s1.setAttribute('crossorigin', '*');
                s0.parentNode.insertBefore(s1, s0);
            })();
        </script>
    <?php endif; ?>

    <script>
        $(function() {
            'use strict'
            $(document).on('submit', '#subscribe', function(e) {
                e.preventDefault();
                const email = $('.subscribe-email').val();
                var url = "<?php echo e(route('subscribe')); ?>";
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: {
                        email: email,
                        _token: "<?php echo e(csrf_token()); ?>"
                    },
                    success: (response) => {

                        $('.subscribe-email').val('');

                        <?php echo $__env->make(Config::theme() . 'layout.ajax_alert', [
                            'message' => 'Successfully Subscribe',
                            'message_error' => '',
                        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

                    },
                    error: () => {

                        <?php if(optional(Config::config())->alert ?? 'sweetalert' === 'izi'): ?>
                            iziToast.error({
                                position: 'topRight',
                                message: "Email is Required",
                            });
                        <?php elseif(optional(Config::config())->alert ?? 'sweetalert' === 'toast'): ?>
                            toastr.error("Email is Required", {
                                positionClass: "toast-top-right"

                            })
                        <?php else: ?>
                            Swal.fire({
                                icon: 'error',
                                title: "Email is Required"
                            })
                        <?php endif; ?>
                    }
                })
            });

            var url = "<?php echo e(route('change-language')); ?>";

            $(".changeLang").on('change', function() {
               
                if ($(this).val() == '') {
                    return false;
                }
                window.location.href = url + "?lang=" + $(this).val();
            });
        })
    </script>
    <?php echo $__env->make('alert', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
</body>
</html><?php /**PATH /home1/algotrad/public_html/main/resources/views/frontend/blue/layout/master.blade.php ENDPATH**/ ?>
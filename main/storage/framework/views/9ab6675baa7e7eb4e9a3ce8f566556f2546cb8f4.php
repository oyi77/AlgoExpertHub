<?php
    $singleElement = Config::builder('contact');
    $socials = Config::builder('socials', true);
?>

<header class="sp_header">
    <div class="sp_header_main">
        <div class="sp_container">
            <nav class="navbar navbar-expand-xl p-0 align-items-center">
                <a class="site-logo site-title" href="<?php echo e(route('home')); ?>">
                    <img src="<?php echo e(Config::getFile('logo', Config::config()->logo)); ?>" alt="logo">
                </a>
                <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse"
                    data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false"
                    aria-label="Toggle navigation">
                    <span class="menu-toggle"></span>
                </button>
                <div class="collapse navbar-collapse mt-lg-0 mt-3" id="mainNavbar">
                    <ul class="nav navbar-nav sp_site_menu m-auto">
                        <?= Config::navbarMenus() ?>
                    </ul>

                    
                    <div class="navbar-action">
                        <select class="custom-select-form  rounded changeLang nav-link me-3">
                            <?php $__currentLoopData = Config::languages(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $language): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($language->code); ?>" <?php echo e(Config::languageSelection($language->code)); ?>>
                                    <?php echo e(__(ucwords($language->name))); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php if(auth()->guard()->check()): ?>
                            <a href="<?php echo e(route('user.dashboard')); ?>" class="btn sp_theme_btn btn-sm"><?php echo e(__('Dashboard')); ?>

                                <i class="las la-long-arrow-alt-right ms-2"></i></a>
                        <?php else: ?>
                            <a href="<?php echo e(route('user.login')); ?>" class="btn sp_theme_btn btn-sm me-3"><i class="far fa-user-circle me-2"></i> <?php echo e(__('Sign In')); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </nav>
        </div>
    </div><!-- header-bottom end -->
</header>
<!-- header-section end  -->


<aside class="fixed-auth-sidebar">
    <ul class="auth-sidebar-nav">
        <li><a href="#0" class="active">Register</a></li>
        <li><a href="<?php echo e(route('user.login')); ?>">Log In</a></li>
    </ul>

    <form action="<?php echo e(route('user.register')); ?>" method="POST" class="fixed-auth-form">
        <?php echo csrf_field(); ?>
        <div class="row">
            <div class="col-sm-12">
                <label><?php echo e(__(' Username')); ?></label>
                <div class="sp_input_icon_field mb-3">
                    <input type="text" class="form-control <?php $__errorArgs = ['username', 'registration'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>  is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                        name="username" value="<?php echo e(old('username')); ?>" id="username" placeholder="<?php echo e(__('User Name')); ?>">
                    <i class="las la-user"></i>
                    <?php $__errorArgs = ['username', 'registration'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="invalid-feedback">
                            <?php echo e(__($message)); ?>

                        </div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>
            <div class="col-sm-12">
                <label><?php echo e(__('Phone Number')); ?></label>
                <div class="sp_input_icon_field mb-3">
                    <input type="tel" name="phone"
                        class="form-control <?php $__errorArgs = ['phone', 'registration'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>  is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                        placeholder="Phone number">
                    <i class="las la-phone"></i>
                    <?php $__errorArgs = ['phone', 'registration'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="invalid-feedback">
                            <?php echo e(__($message)); ?>

                        </div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

            </div>
            <div class="col-lg-12">
                <label><?php echo e(__('Email')); ?></label>
                <div class="sp_input_icon_field mb-3">
                    <input type="Email" class="form-control <?php $__errorArgs = ['email', 'registration'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>  is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                        name="email" value="<?php echo e(old('email')); ?>" id="email" placeholder="<?php echo e(__('Email')); ?>">
                    <i class="las la-envelope"></i>
                    <?php $__errorArgs = ['email', 'registration'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="invalid-feedback">
                            <?php echo e(__($message)); ?>

                        </div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>
            <div class="col-sm-12">
                <label><?php echo e(__('Password')); ?></label>
                <div class="sp_input_icon_field mb-3">
                    <input type="password" class="form-control <?php $__errorArgs = ['password', 'registration'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>  is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                        name="password" id="password" placeholder="<?php echo e(__('Password')); ?>">
                    <i class="las la-eye" id="togglePassword"></i>
                    <?php $__errorArgs = ['password', 'registration'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="invalid-feedback">
                            <?php echo e(__($message)); ?>

                        </div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

            </div>
            <div class="col-sm-12">
                <label><?php echo e(__('Confirm password')); ?></label>
                <div class="sp_input_icon_field mb-3">
                    <input type="password" class="form-control" name="password_confirmation" id="password_confirmation"
                        placeholder="<?php echo e(__('Confirm Password')); ?>">
                    <i class="las la-eye" id="confirmTogglePassword"></i>
                </div>
            </div>

            <?php if(Config::config()->allow_recaptcha == 1): ?>
                <div class="col-md-12 my-3">
                    <script src="https://www.google.com/recaptcha/api.js"></script>
                    <div class="g-recaptcha" data-sitekey="<?php echo e(Config::config()->recaptcha_key); ?>" data-callback="verifyCaptcha">
                    </div>
                    <div id="g-recaptcha-error"></div>
                </div>
            <?php endif; ?>
        </div>
        <div class="mt-4">
            <button type="submit" class="btn sp_theme_btn w-100"><?php echo e(__('Sign Up')); ?></button>
        </div>
        <p class="text-center mt-4 text-white"><?php echo e(__('Already have an account')); ?> ? <a href="<?php echo e(route('user.login')); ?>" class="sp_site_color"><?php echo e(__('Login')); ?></a></p>
    </form>
</aside>

<?php $__env->startPush('script'); ?>
    <script>
        "use strict";


        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function(e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('la-eye-slash');
        });


        const togglePasswordConfirm = document.querySelector('#confirmTogglePassword');
        const passwordConfirm = document.querySelector('#password_confirmation');

        togglePasswordConfirm.addEventListener('click', function(e) {
            const type = passwordConfirm.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordConfirm.setAttribute('type', type);
            this.classList.toggle('la-eye-slash');
        });


        function submitUserForm() {
            var response = grecaptcha.getResponse();
            if (response.length == 0) {
                document.getElementById('g-recaptcha-error').innerHTML =
                    "<span class='sp_text_danger'><?php echo e(__('Captcha field is required.')); ?></span>";
                return false;
            }
            return true;
        }

        function verifyCaptcha() {
            document.getElementById('g-recaptcha-error').innerHTML = '';
        }
    </script>
<?php $__env->stopPush(); ?><?php /**PATH /home1/algotrad/public_html/main/resources/views/frontend/blue/layout/header.blade.php ENDPATH**/ ?>
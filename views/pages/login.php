<?php
$errors = $errors ?? [];
$old = $old ?? [];
$success = $success ?? '';
$action = $action ?? 'login';
$cp_step = $old['cp_step'] ?? 1; // 1 = nhập identifier, 2 = nhập mật khẩu
$fp_step = $old['fp_step'] ?? 1; // 1 = nhập identifier, 2 = nhập OTP
?>
<section class="auth-shell min-h-[calc(100vh-4rem)] flex items-center justify-center py-12 bg-gradient-to-br from-primary/5 to-secondary/5">
    <div class="auth-ambient" aria-hidden="true">
        <span class="win w1"></span><span class="win w2"></span><span class="win w3"></span>
        <span class="win w4"></span><span class="win w5"></span><span class="win w6"></span>
    </div>

    <div class="relative z-10 w-full max-w-md px-4">
        <div class="auth-card bg-white rounded-2xl shadow-2xl p-8 border border-gray-100">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-primary/10 rounded-2xl mb-4 auth-pop">
                    <span class="material-symbols-outlined text-primary text-3xl">login</span>
                </div>
                <h1 class="text-3xl font-bold gradient-text mb-2">Đăng nhập</h1>
                <p class="text-gray-500">Nhập số điện thoại hoặc email để đăng nhập.</p>
            </div>

            <?php if ($success !== ''): ?>
                <div class="auth-alert mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg flex items-start gap-2">
                    <span class="material-symbols-outlined mt-0.5">check_circle</span>
                    <span><?= e($success) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors['general'])): ?>
                <div class="auth-alert mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-start gap-2">
                    <span class="material-symbols-outlined mt-0.5">error</span>
                    <span><?= e($errors['general']) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors['otp_error'])): ?>
                <div class="auth-alert mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-start gap-2">
                    <span class="material-symbols-outlined mt-0.5">error</span>
                    <span><?= e($errors['otp_error']) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors['otp_success'])): ?>
                <div class="auth-alert mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg flex items-start gap-2">
                    <span class="material-symbols-outlined mt-0.5">check_circle</span>
                    <span><?= e($errors['otp_success']) ?></span>
                </div>
            <?php endif; ?>

            <!-- FORM ĐĂNG NH��P -->
            <?php if ($action === 'login' || empty($action)): ?>
                <form method="POST" class="space-y-4" data-login-form <?= !empty($errors) ? 'data-shake' : '' ?>>
    <?= csrf_field() ?>
                    <input type="hidden" name="auth_action" value="login">
                    
                    <div class="auth-field">
                        <label for="login_identifier" class="block text-sm font-semibold mb-2">Số điện thoại hoặc email <span class="text-red-500">*</span></label>
                        <input
                            id="login_identifier"
                            type="text"
                            name="identifier"
                            required
                            value="<?= e($old['identifier'] ?? '') ?>"
                            placeholder="0328528757 hoặc example@email.com"
                            class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary outline-none transition <?= !empty($errors['identifier']) ? 'border-red-300 bg-red-50' : 'border-gray-200' ?>"
                            aria-describedby="identifier_error"
                            autocomplete="username"
                        >
                        <p id="identifier_error" class="field-error mt-2 text-sm text-red-600 <?= empty($errors['identifier']) ? 'hidden' : '' ?>">
                            <?= e($errors['identifier'] ?? '') ?>
                        </p>
                    </div>

                    <div class="auth-field">
                        <label for="login_password" class="block text-sm font-semibold mb-2">Mật khẩu <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input
                                id="login_password"
                                type="password"
                                name="password"
                                required
                                class="w-full px-4 py-3 pr-12 border rounded-lg focus:ring-2 focus:ring-primary outline-none transition <?= !empty($errors['password']) ? 'border-red-300 bg-red-50' : 'border-gray-200' ?>"
                                aria-describedby="password_error"
                            >
                            <button
                                type="button"
                                class="toggle-password absolute right-3 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary rounded transition-colors"
                                aria-label="Hiện mật khẩu"
                                aria-pressed="false"
                            >
                                <svg class="icon-eye w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg class="icon-eye-off hidden w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M9.9 4.24A9.1 9.1 0 0 1 12 4c6.5 0 10 7 10 7a13.2 13.2 0 0 1-1.67 2.68"/>
                                    <path d="M6.6 6.6C3.6 8.3 2 12 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.6"/>
                                    <path d="M14.12 14.12A3 3 0 1 1 9.9 9.9"/><line x1="2" y1="2" x2="22" y2="22"/>
                                </svg>
                            </button>
                        </div>
                        <?php if (!empty($errors['password'])): ?>
                            <p class="mt-2 text-sm text-red-600"><?= e($errors['password']) ?></p>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="auth-btn w-full py-3 bg-primary text-white rounded-lg font-semibold hover:bg-opacity-90 transition transform hover:scale-[1.02] active:scale-[0.99] shadow-lg">
                        Đăng nhập
                    </button>
                </form>

                <div class="mt-4 grid grid-cols-2 gap-3">
                    <button type="button" class="auth-btn w-full py-3 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition" data-action="change_password" data-step="1">
                        Đổi mật khẩu
                    </button>
                    <button type="button" class="auth-btn w-full py-3 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition" data-action="forgot_password" data-step="1">
                        Quên mật khẩu
                    </button>
                </div>

            <!-- FORM Đ��I M��T KH��U - B����C 1: NH��P IDENTIFIER -->
            <?php elseif ($action === 'change_password' && $cp_step === 1): ?>
                <form method="POST" class="space-y-4" data-change-form-step1 <?= !empty($errors['identifier']) ? 'data-shake' : '' ?>>
    <?= csrf_field() ?>
                    <input type="hidden" name="auth_action" value="change_password">
                    <input type="hidden" name="cp_step" value="1">
                    
                    <div class="auth-field">
                        <label for="cp_identifier" class="block text-sm font-semibold mb-2">Số điện thoại hoặc email <span class="text-red-500">*</span></label>
                        <input
                            id="cp_identifier"
                            type="text"
                            name="identifier"
                            required
                            value="<?= e($old['identifier'] ?? '') ?>"
                            placeholder="0328528757 hoặc example@email.com"
                            class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary outline-none transition <?= !empty($errors['identifier']) ? 'border-red-300 bg-red-50' : 'border-gray-200' ?>"
                            aria-describedby="cp_identifier_error"
                            autocomplete="username"
                        >
                        <p id="cp_identifier_error" class="field-error mt-2 text-sm text-red-600 <?= empty($errors['identifier']) ? 'hidden' : '' ?>">
                            <?= e($errors['identifier'] ?? '') ?>
                        </p>
                    </div>

                    <?php if (!empty($old['show_register_link'])): ?>
                        <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-lg flex items-center gap-2">
                            <span class="material-symbols-outlined">info</span>
                            <span>Tài khoản này chưa tồn tại. </span>
                            <a href="<?= BASE_URL ?>?page=register&prefill_identifier=<?= urlencode($old['identifier']) ?>" class="text-primary font-semibold hover:underline">Đăng ký ngay</a>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="auth-btn w-full py-3 bg-primary text-white rounded-lg font-semibold hover:bg-opacity-90 transition transform hover:scale-[1.02] active:scale-[0.99] shadow-lg">
                        Tiếp tục
                    </button>
                </form>

                <div class="mt-4 text-center">
                    <a href="<?= BASE_URL ?>?page=login" class="text-gray-500 hover:text-primary text-sm">Quay lại đăng nhập</a>
                </div>

            <!-- FORM Đ��I M��T KH��U - B����C 2: NH��P M��T KH��U C��/M��I -->
            <?php elseif ($action === 'change_password' && $cp_step === 2): ?>
                <form method="POST" class="space-y-4" data-change-form-step2 <?= !empty($errors) ? 'data-shake' : '' ?>>
    <?= csrf_field() ?>
                    <input type="hidden" name="auth_action" value="change_password">
                    <input type="hidden" name="cp_step" value="2">
                    <input type="hidden" name="identifier" value="<?= e($old['identifier'] ?? '') ?>">
                    
                    <div class="auth-field">
                        <label for="cp_identifier_display" class="block text-sm font-semibold mb-2">Tài khoản</label>
                        <input
                            id="cp_identifier_display"
                            type="text"
                            value="<?= e($old['identifier'] ?? '') ?>"
                            readonly
                            class="w-full px-4 py-3 border rounded-lg bg-gray-50 text-gray-600 cursor-not-allowed"
                        >
                    </div>

                    <?php if (!empty($old['show_register_link'])): ?>
                        <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-lg flex items-center gap-2">
                            <span class="material-symbols-outlined">info</span>
                            <span>Tài khoản này chưa tồn tại. </span>
                            <a href="<?= BASE_URL ?>?page=register&prefill_identifier=<?= urlencode($old['identifier']) ?>" class="text-primary font-semibold hover:underline">Đăng ký ngay</a>
                        </div>
                    <?php endif; ?>

                    <div class="auth-field">
                        <label for="cp_old_password" class="block text-sm font-semibold mb-2">Mật khẩu cũ <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input
                                id="cp_old_password"
                                type="password"
                                name="password"
                                required
                                class="w-full px-4 py-3 pr-12 border rounded-lg focus:ring-2 focus:ring-primary outline-none transition <?= !empty($errors['old_password']) ? 'border-red-300 bg-red-50' : 'border-gray-200' ?>"
                                aria-describedby="cp_old_password_error"
                            >
                            <button
                                type="button"
                                class="toggle-password absolute right-3 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary rounded transition-colors"
                                aria-label="Hiện mật khẩu"
                                aria-pressed="false"
                            >
                                <svg class="icon-eye w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg class="icon-eye-off hidden w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M9.9 4.24A9.1 9.1 0 0 1 12 4c6.5 0 10 7 10 7a13.2 13.2 0 0 1-1.67 2.68"/>
                                    <path d="M6.6 6.6C3.6 8.3 2 12 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.6"/>
                                    <path d="M14.12 14.12A3 3 0 1 1 9.9 9.9"/><line x1="2" y1="2" x2="22" y2="22"/>
                                </svg>
                            </button>
                        </div>
                        <?php if (!empty($errors['old_password'])): ?>
                            <p class="mt-2 text-sm text-red-600"><?= e($errors['old_password']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="auth-field">
                        <label for="cp_new_password" class="block text-sm font-semibold mb-2 mt-4">Mật khẩu mới <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input
                                id="cp_new_password"
                                type="password"
                                name="new_password"
                                required
                                minlength="6"
                                class="w-full px-4 py-3 pr-12 border rounded-lg focus:ring-2 focus:ring-primary outline-none transition <?= !empty($errors['new_password']) ? 'border-red-300 bg-red-50' : 'border-gray-200' ?>"
                                aria-describedby="cp_new_password_error"
                            >
                            <button
                                type="button"
                                class="toggle-password absolute right-3 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary rounded transition-colors"
                                aria-label="Hiện mật khẩu"
                                aria-pressed="false"
                            >
                                <svg class="icon-eye w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg class="icon-eye-off hidden w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M9.9 4.24A9.1 9.1 0 0 1 12 4c6.5 0 10 7 10 7a13.2 13.2 0 0 1-1.67 2.68"/>
                                    <path d="M6.6 6.6C3.6 8.3 2 12 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.6"/>
                                    <path d="M14.12 14.12A3 3 0 1 1 9.9 9.9"/><line x1="2" y1="2" x2="22" y2="22"/>
                                </svg>
                            </button>
                        </div>
                        <?php if (!empty($errors['new_password'])): ?>
                            <p class="mt-2 text-sm text-red-600"><?= e($errors['new_password']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="auth-field">
                        <label for="cp_confirm_password" class="block text-sm font-semibold mb-2 mt-4">Xác nhận mật khẩu mới <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input
                                id="cp_confirm_password"
                                type="password"
                                name="confirm_password"
                                required
                                minlength="6"
                                class="w-full px-4 py-3 pr-12 border rounded-lg focus:ring-2 focus:ring-primary outline-none transition <?= !empty($errors['confirm_password']) ? 'border-red-300 bg-red-50' : 'border-gray-200' ?>"
                                aria-describedby="cp_confirm_password_error"
                            >
                            <button
                                type="button"
                                class="toggle-password absolute right-3 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary rounded transition-colors"
                                aria-label="Hiện mật khẩu"
                                aria-pressed="false"
                            >
                                <svg class="icon-eye w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg class="icon-eye-off hidden w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M9.9 4.24A9.1 9.1 0 0 1 12 4c6.5 0 10 7 10 7a13.2 13.2 0 0 1-1.67 2.68"/>
                                    <path d="M6.6 6.6C3.6 8.3 2 12 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.6"/>
                                    <path d="M14.12 14.12A3 3 0 1 1 9.9 9.9"/><line x1="2" y1="2" x2="22" y2="22"/>
                                </svg>
                            </button>
                        </div>
                        <?php if (!empty($errors['confirm_password'])): ?>
                            <p class="mt-2 text-sm text-red-600"><?= e($errors['confirm_password']) ?></p>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="auth-btn w-full py-3 bg-primary text-white rounded-lg font-semibold hover:bg-opacity-90 transition transform hover:scale-[1.02] active:scale-[0.99] shadow-lg">
                        Xác nhận đổi mật khẩu
                    </button>
                </form>

                <div class="mt-4 text-center">
                    <a href="<?= BASE_URL ?>?page=login" class="text-gray-500 hover:text-primary text-sm">Quay lại đăng nhập</a>
                </div>

            <!-- FORM QU��N M��T KH��U - B����C 1: NH��P IDENTIFIER -->
            <?php elseif ($action === 'forgot_password' && $fp_step === 1): ?>
                <form method="POST" class="space-y-4" data-forgot-form-step1 <?= !empty($errors['identifier']) ? 'data-shake' : '' ?>>
    <?= csrf_field() ?>
                    <input type="hidden" name="auth_action" value="forgot_password">
                    <input type="hidden" name="fp_step" value="1">
                    
                    <div class="auth-field">
                        <label for="fp_identifier" class="block text-sm font-semibold mb-2">Số điện thoại hoặc email <span class="text-red-500">*</span></label>
                        <input
                            id="fp_identifier"
                            type="text"
                            name="identifier"
                            required
                            value="<?= e($old['identifier'] ?? '') ?>"
                            placeholder="0328528757 hoặc example@email.com"
                            class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary outline-none transition <?= !empty($errors['identifier']) ? 'border-red-300 bg-red-50' : 'border-gray-200' ?>"
                            aria-describedby="fp_identifier_error"
                            autocomplete="username"
                        >
                        <p id="fp_identifier_error" class="field-error mt-2 text-sm text-red-600 <?= empty($errors['identifier']) ? 'hidden' : '' ?>">
                            <?= e($errors['identifier'] ?? '') ?>
                        </p>
                    </div>

                    <?php if (!empty($old['show_register_link'])): ?>
                        <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-lg flex items-center gap-2">
                            <span class="material-symbols-outlined">info</span>
                            <span>Tài khoản này chưa tồn tại. </span>
                            <a href="<?= BASE_URL ?>?page=register&prefill_identifier=<?= urlencode($old['identifier']) ?>" class="text-primary font-semibold hover:underline">Đăng ký ngay</a>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="auth-btn w-full py-3 bg-primary text-white rounded-lg font-semibold hover:bg-opacity-90 transition transform hover:scale-[1.02] active:scale-[0.99] shadow-lg">
                        Gửi mã OTP
                    </button>
                </form>

                <div class="mt-4 text-center">
                    <a href="<?= BASE_URL ?>?page=login" class="text-gray-500 hover:text-primary text-sm">Quay lại đăng nhập</a>
                </div>

            <!-- FORM QU��N M��T KH��U - B����C 2: NH��P OTP (INLINE, KH��NG REDIRECT) -->
            <?php elseif ($action === 'forgot_password' && $fp_step === 2): ?>
                <?php if (!empty($errors['no_email'])): ?>
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                        <p class="font-semibold mb-2">Tài khoản này chưa được cập nhật email. Vui lòng liên hệ chủ trọ để được cấp lại mật khẩu.</p>
                        <?php if (!empty($old['contact_phone'])): ?>
                            <a href="tel:<?= e(str_replace(' ', '', $old['contact_phone'])) ?>" class="auth-btn w-full py-3 bg-primary text-white rounded-lg font-semibold hover:bg-opacity-90 transition flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined">call</span>
                                <span>Liên hệ ngay: <?= e($old['contact_phone']) ?></span>
                            </a>
                        <?php else: ?>
                            <p class="text-sm">Vui lòng liên hệ chủ trọ qua thông tin trên trang web.</p>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>?page=login" class="block mt-3 text-center text-gray-500 hover:text-primary text-sm">Quay lại</a>
                    </div>
                <?php elseif (!empty($errors['otp_send_failed'])): ?>
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                        <p class="font-semibold mb-2">Gửi OTP thất bại.</p>
                        <?php if (!empty($old['contact_phone'])): ?>
                            <a href="tel:<?= e(str_replace(' ', '', $old['contact_phone'])) ?>" class="auth-btn w-full py-3 bg-primary text-white rounded-lg font-semibold hover:bg-opacity-90 transition flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined">call</span>
                                <span>Liên hệ ngay: <?= e($old['contact_phone']) ?></span>
                            </a>
                        <?php else: ?>
                            <p class="text-sm">Vui lòng liên hệ chủ trọ qua thông tin trên trang web.</p>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>?page=login" class="block mt-3 text-center text-gray-500 hover:text-primary text-sm">Quay lại</a>
                    </div>
                <?php else: ?>
                    <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg flex items-start gap-2">
                        <span class="material-symbols-outlined mt-0.5">check_circle</span>
                        <span>Mã OTP đã được gửi đến email <strong><?= e($old['otp_sent_email'] ?? 'đăng ký của tài khoản') ?></strong>.</span>
                    </div>

                    <form method="POST" class="space-y-4" data-otp-form <?= !empty($errors['otp']) ? 'data-shake' : '' ?>>
    <?= csrf_field() ?>
                        <input type="hidden" name="auth_action" value="forgot_password">
                        <input type="hidden" name="fp_step" value="2">
                        <input type="hidden" name="identifier" value="<?= e($old['identifier'] ?? '') ?>">
                        
                        <div class="auth-field">
                            <label for="fp_otp_input" class="block text-sm font-semibold mb-2">Mã OTP <span class="text-red-500">*</span></label>
                            <input
                                id="fp_otp_input"
                                type="text"
                                name="otp"
                                required
                                maxlength="4"
                                pattern="\d{4}"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary outline-none transition text-center text-2xl tracking-widest <?= !empty($errors['otp']) ? 'border-red-300 bg-red-50' : 'border-gray-200' ?>"
                                aria-describedby="fp_otp_error"
                                placeholder="••••"
                            >
                            <p id="fp_otp_error" class="field-error mt-2 text-sm text-red-600 <?= empty($errors['otp']) ? 'hidden' : '' ?>">
                                <?= e($errors['otp'] ?? '') ?>
                            </p>
                        </div>

                        <button type="submit" class="auth-btn w-full py-3 bg-primary text-white rounded-lg font-semibold hover:bg-opacity-90 transition transform hover:scale-[1.02] active:scale-[0.99] shadow-lg">
                            Xác thực OTP
                        </button>
                    </form>

                    <div class="mt-4 text-center">
                        <form method="POST" action="<?= BASE_URL ?>?page=resend-otp" class="inline" id="fpResendForm">
    <?= csrf_field() ?>
                            <button type="submit" class="text-primary hover:underline text-sm font-medium" id="fpResendBtn" disabled>
                                Gửi lại mã OTP sau <span id="fpResendCountdown">02:00</span>
                            </button>
                        </form>
                    </div>

                    <?php if (!empty($errors['otp_info'])): ?>
                        <div class="mt-2 text-center text-sm text-yellow-600">
                            <?= e($errors['otp_info']) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($old['otp_resent'])): ?>
                        <div class="mt-2 text-center text-sm text-green-600">
                            Mã OTP mới đã được gửi lại.
                        </div>
                    <?php endif; ?>

                    <div class="mt-4 text-center">
                        <a href="<?= BASE_URL ?>?page=login" class="text-gray-500 hover:text-primary text-sm">Quay lại đăng nhập</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="mt-6 p-4 bg-blue-50 rounded-lg text-xs text-blue-900">
                <p class="font-semibold mb-1">Tài khoản demo (mật khẩu: 123456):</p>
                <p>Admin: admin@nhatroxanh.vn</p>
                <p>Tenant: tenant01@example.com</p>
            </div>

            <div class="mt-6 text-center text-sm text-gray-600">
                Chưa có tài khoản?
                <a href="<?= BASE_URL ?>?page=register" class="font-semibold text-primary hover:underline">Đăng ký ngay</a>
            </div>
        </div>
    </div>
</section>

<style>
  .auth-shell{ position:relative; overflow:hidden; }
  .auth-ambient{
    position:absolute; inset:0; pointer-events:none; z-index:0;
    background-image:
      linear-gradient(rgba(14,122,100,.05) 1px, transparent 1px),
      linear-gradient(90deg, rgba(14,122,100,.05) 1px, transparent 1px);
    background-size:46px 46px;
    -webkit-mask-image:radial-gradient(120% 90% at 50% 30%, #000 30%, transparent 80%);
            mask-image:radial-gradient(120% 90% at 50% 30%, #000 30%, transparent 80%);
  }
  .auth-ambient .win{
    position:absolute; width:64px; height:42px; border-radius:8px;
    background:linear-gradient(150deg, rgba(94,234,212,.18), rgba(14,122,100,.10));
    border:1px solid rgba(14,122,100,.12);
    box-shadow:0 0 22px rgba(94,234,212,.12) inset;
    animation:winDrift 11s ease-in-out infinite;
  }
  .w1{ top:14%; left:10%; animation-delay:0s; } .w2{ top:22%; right:12%; animation-delay:-2s; }
  .w3{ top:62%; left:16%; animation-delay:-4s; } .w4{ top:70%; right:18%; animation-delay:-6s; }
  .w5{ top:40%; left:6%; animation-delay:-3s; } .w6{ top:48%; right:7%; animation-delay:-8s; }
  @keyframes winDrift{ 0%,100%{ transform:translateY(0); opacity:.45; } 50%{ transform:translateY(-14px); opacity:.9; } }

  .auth-card{ animation:authIn .6s cubic-bezier(.2,.7,.2,1) both; }
  .auth-pop{ animation:popIn .7s cubic-bezier(.2,.8,.2,1) .15s both; }
  @keyframes authIn{ from{ opacity:0; transform:translateY(18px) scale(.98); } to{ opacity:1; transform:none; } }
  @keyframes popIn{ 0%{ transform:scale(.4); opacity:0; } 60%{ transform:scale(1.12); } 100%{ transform:scale(1); opacity:1; } }
  .auth-card.is-shake{ animation:shake .5s cubic-bezier(.36,.07,.19,.97) both; }
  @keyframes shake{ 10%,90%{ transform:translateX(-2px); } 20%,80%{ transform:translateX(4px); } 30%,50%,70%{ transform:translateX(-7px); } 40%,60%{ transform:translateX(7px); } }
  .auth-alert{ animation:alertIn .4s ease both; }
  @keyframes alertIn{ from{ opacity:0; transform:translateY(-6px); } to{ opacity:1; transform:none; } }

  @media (prefers-reduced-motion:reduce){
    .auth-ambient .win,.auth-card,.auth-pop,.auth-card.is-shake,.auth-alert{ animation:none !important; }
  }
</style>

<script>
(function () {
  document.querySelectorAll('.toggle-password').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = btn.parentElement.querySelector('input');
      if (!input) return;

      var wasHidden = input.type === 'password';
      input.type = wasHidden ? 'text' : 'password';
      btn.querySelector('.icon-eye').classList.toggle('hidden', wasHidden);
      btn.querySelector('.icon-eye-off').classList.toggle('hidden', !wasHidden);
      btn.setAttribute('aria-pressed', String(wasHidden));
      btn.setAttribute('aria-label', wasHidden ? '��n mật khẩu' : 'Hiện mật khẩu');
    });
  });

  // Action buttons - handle step 1 for change_password and forgot_password
  document.querySelectorAll('[data-action]').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var action = this.getAttribute('data-action');
      var step = this.getAttribute('data-step') || '1';
      var form = document.querySelector('form[data-login-form]');
      if (form) {
        var hiddenAction = form.querySelector('input[name="auth_action"]');
        var hiddenStep = form.querySelector('input[name="' + action + '_step"]') || form.querySelector('input[name="cp_step"]') || form.querySelector('input[name="fp_step"]');
        if (hiddenAction) {
          hiddenAction.value = action;
        }
        // Add step hidden input if not exists
        if (!hiddenStep) {
          var stepInput = document.createElement('input');
          stepInput.type = 'hidden';
          stepInput.name = action === 'change_password' ? 'cp_step' : 'fp_step';
          stepInput.value = step;
          form.appendChild(stepInput);
        } else {
          hiddenStep.value = step;
        }
        form.submit();
      }
    });
  });

  // Card shake
  var card = document.querySelector('.auth-card');
  if (card && card.querySelector('form[data-shake]')) {
    requestAnimationFrame(function () {
      card.classList.add('is-shake');
    });
  }

  // OTP input auto-format for forgot password step 2
  var fpOtpInput = document.getElementById('fp_otp_input');
  if (fpOtpInput) {
    fpOtpInput.focus();
    fpOtpInput.addEventListener('input', function() {
      this.value = this.value.replace(/\D/g, '').slice(0, 4);
    });
    
    fpOtpInput.addEventListener('paste', function(e) {
      e.preventDefault();
      var text = (e.clipboardData || window.clipboardData).getData('text');
      text = text.replace(/\D/g, '').slice(0, 4);
      this.value = text;
      if (text.length === 4) {
        this.form?.querySelector('button[type="submit"]')?.click();
      }
    });
  }

  // Resend countdown for forgot password step 2 (2 minutes = 120 seconds)
  var fpCountdownEl = document.getElementById('fpResendCountdown');
  var fpResendBtn = document.getElementById('fpResendBtn');
  var fpResendForm = document.getElementById('fpResendForm');

  if (fpCountdownEl && fpResendBtn && fpResendForm) {
    var totalSeconds = 120; // 2 minutes
    var seconds = totalSeconds;
    fpResendBtn.disabled = true;

    var timer = setInterval(function() {
      seconds--;
      var mins = Math.floor(seconds / 60);
      var secs = seconds % 60;
      fpCountdownEl.textContent = String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
      if (seconds <= 0) {
        clearInterval(timer);
        fpResendBtn.disabled = false;
        fpResendBtn.innerHTML = 'Gửi lại mã OTP';
      }
    }, 1000);

    // Handle resend form submit - prevent default, submit via AJAX to update countdown without page reload
    fpResendForm.addEventListener('submit', function(e) {
      if (fpResendBtn.disabled) {
        e.preventDefault();
        return;
      }
      // Let it submit normally - server will handle and redirect back with new countdown
    });
  }
})();
</script>
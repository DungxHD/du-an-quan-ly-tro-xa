<?php
$errors = $errors ?? [];
$old = $old ?? [];
$success = $success ?? '';
$action = $action ?? 'login';
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
                    <button type="button" class="auth-btn w-full py-3 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition" data-action="change_password">
                        Đổi mật khẩu
                    </button>
                    <button type="button" class="auth-btn w-full py-3 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition" data-action="forgot_password">
                        Quên mật khẩu
                    </button>
                </div>

            <!-- FORM Đ��I M��T KH��U -->
            <?php elseif ($action === 'change_password'): ?>
                <form method="POST" class="space-y-4" data-change-form <?= !empty($errors) ? 'data-shake' : '' ?>>
    <?= csrf_field() ?>
                    <input type="hidden" name="auth_action" value="change_password">
                    
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

                    <div class="auth-field" id="cp_password_fields" style="display: <?= empty($errors['identifier']) && !empty($old['identifier']) ? 'block' : 'none' ?>;">
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

            <!-- FORM QU��N M��T KH��U -->
            <?php elseif ($action === 'forgot_password'): ?>
                <form method="POST" class="space-y-4" data-forgot-form <?= !empty($errors) ? 'data-shake' : '' ?>>
    <?= csrf_field() ?>
                    <input type="hidden" name="auth_action" value="forgot_password">
                    
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

                    <?php if (!empty($errors['no_email'])): ?>
                        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                            <p class="font-semibold mb-2">Tài khoản của bạn chưa đăng ký email. Vui lòng liên hệ chủ trọ để được cấp lại mật khẩu.</p>
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
                    <?php elseif (!empty($errors['otp_sent']) || !empty($errors['show_otp_form'])): ?>
                        <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg flex items-start gap-2">
                            <span class="material-symbols-outlined mt-0.5">check_circle</span>
                            <span>Mã OTP đã được gửi đến email đăng ký của tài khoản.</span>
                        </div>
                        <a href="<?= BASE_URL ?>?page=verify-otp" class="auth-btn w-full py-3 bg-primary text-white rounded-lg font-semibold hover:bg-opacity-90 transition transform hover:scale-[1.02] active:scale-[0.99] shadow-lg">
                            Nhập mã OTP
                        </a>
                        <?php if (!empty($errors['otp_resend_wait'])): ?>
                            <div class="mt-3 text-center text-sm text-gray-500">
                                Gửi lại sau <span id="fp_resend_countdown"><?= (int)$errors['otp_resend_wait'] ?></span> giây
                            </div>
                        <?php elseif (!empty($errors['otp_max_daily'])): ?>
                            <div class="mt-3 text-center text-sm text-red-600">
                                Đã gửi tối đa 5 lần trong 24h. Vui lòng thử lại sau hoặc liên hệ chủ trọ.
                            </div>
                        <?php else: ?>
                            <div class="mt-3 text-center">
                                <form method="POST" action="<?= BASE_URL ?>?page=resend-otp" class="inline">
    <?= csrf_field() ?>
                                    <button type="submit" class="text-primary hover:underline text-sm font-medium">Gửi lại mã OTP</button>
                                </form>
                            </div>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>?page=login" class="block mt-3 text-center text-gray-500 hover:text-primary text-sm">Quay lại đăng nhập</a>
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
                        <button type="submit" class="auth-btn w-full py-3 bg-primary text-white rounded-lg font-semibold hover:bg-opacity-90 transition transform hover:scale-[1.02] active:scale-[0.99] shadow-lg">
                            Gửi mã OTP
                        </button>
                    <?php endif; ?>
                </form>

                <div class="mt-4 text-center">
                    <a href="<?= BASE_URL ?>?page=login" class="text-gray-500 hover:text-primary text-sm">Quay lại đăng nhập</a>
                </div>
            <?php endif; ?>

            <div class="mt-6 p-4 bg-blue-50 rounded-lg text-xs text-blue-900">
                <p class="font-semibold mb-1">Tài khoản demo (mật khẩu: password):</p>
                <p>Admin: admin@nhatroa.vn</p>
                <p>Tenant: tenant1@gmail.com</p>
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

  // Action buttons
  document.querySelectorAll('[data-action]').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var action = this.getAttribute('data-action');
      var form = document.querySelector('form[data-login-form], form[data-change-form], form[data-forgot-form]');
      if (form) {
        var hiddenInput = form.querySelector('input[name="auth_action"]');
        if (hiddenInput) {
          hiddenInput.value = action;
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

  // Resend countdown for forgot password
  var fpCountdown = document.getElementById('fp_resend_countdown');
  if (fpCountdown) {
    var seconds = parseInt(fpCountdown.textContent, 10);
    var timer = setInterval(function() {
      seconds--;
      fpCountdown.textContent = seconds;
      if (seconds <= 0) {
        clearInterval(timer);
        fpCountdown.parentElement.innerHTML = '<form method="POST" action="<?= BASE_URL ?>?page=resend-otp" class="inline"><input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>"><button type="submit" class="text-primary hover:underline text-sm font-medium">Gửi lại mã OTP</button></form>';
      }
    }, 1000);
  }
})();
</script>
<?php
$errors = $errors ?? [];
$success = $success ?? '';
?>
<section class="auth-shell min-h-[calc(100vh-4rem)] flex items-center justify-center py-12 bg-gradient-to-br from-primary/5 to-secondary/5">
    <div class="auth-ambient" aria-hidden="true">
        <span class="win w1"></span><span class="win w2"></span><span class="win w3"></span>
        <span class="win w4"></span><span class="win w5"></span><span class="win w6"></span>
    </div>

    <div class="relative z-10 w-full max-w-md px-4">
        <div class="auth-card bg-white rounded-3xl shadow-card p-8 border border-gray-100">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-primary to-secondary shadow-card rounded-2xl mb-4 auth-pop">
                    <span class="material-symbols-outlined text-white text-3xl">lock</span>
                </div>
                <h1 class="text-3xl font-bold gradient-text mb-2">Xác thực mã OTP</h1>
                <p class="text-gray-500">Mã OTP 4 chữ số đã được gửi đến email đăng ký của tài khoản.</p>
            </div>

            <?php if (!empty($errors['otp_sent'])): ?>
                <div class="auth-alert mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg flex items-start gap-2">
                    <span class="material-symbols-outlined mt-0.5">check_circle</span>
                    <span>Mã OTP đã được gửi đến email đăng ký của tài khoản.</span>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors['otp_resend_wait'])): ?>
                <div class="auth-alert mb-4 p-4 bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-lg flex items-start gap-2" id="resendWaitAlert">
                    <span class="material-symbols-outlined mt-0.5">schedule</span>
                    <span>Vui lòng chờ <span id="resendCountdown"><?= (int)$errors['otp_resend_wait'] ?></span> giây để gửi lại mã OTP.</span>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors['otp_max_daily'])): ?>
                <div class="auth-alert mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-start gap-2">
                    <span class="material-symbols-outlined mt-0.5">error</span>
                    <span>Bạn đã gửi OTP tối đa 5 lần trong 24 giờ. Vui lòng thử lại sau hoặc liên hệ chủ trọ.</span>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors['otp_send_failed'])): ?>
                <div class="auth-alert mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-start gap-2">
                    <span class="material-symbols-outlined mt-0.5">error</span>
                    <span>Gửi OTP thất bại. Vui lòng liên hệ chủ trọ.</span>
                </div>
                <?php if (!empty($old['contact_phone'])): ?>
                    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 text-blue-700 rounded-lg">
                        <p class="font-semibold mb-2">Liên hệ chủ trọ để được hỗ trợ:</p>
                        <a href="tel:<?= e(str_replace(' ', '', $old['contact_phone'])) ?>" class="text-blue-600 hover:underline"><?= e($old['contact_phone']) ?></a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (!empty($errors['no_email'])): ?>
                <div class="auth-alert mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-start gap-2">
                    <span class="material-symbols-outlined mt-0.5">error</span>
                    <span>Tài khoản của bạn chưa đăng ký email. Vui lòng liên hệ chủ trọ để được cấp lại mật khẩu.</span>
                </div>
                <?php if (!empty($old['contact_phone'])): ?>
                    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 text-blue-700 rounded-lg">
                        <p class="font-semibold mb-2">Liên hệ chủ trọ:</p>
                        <a href="tel:<?= e(str_replace(' ', '', $old['contact_phone'])) ?>" class="text-blue-600 hover:underline flex items-center gap-2">
                            <span class="material-symbols-outlined">call</span>
                            <span><?= e($old['contact_phone']) ?></span>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 text-blue-700 rounded-lg">
                        <p>Vui lòng liên hệ chủ trọ qua thông tin trên trang web để được hỗ trợ.</p>
                    </div>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>?page=login" class="auth-btn w-full py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition">
                    Quay lại
                </a>
            <?php else: ?>
                <form method="POST" class="space-y-4" <?= !empty($errors['otp']) ? 'data-shake' : '' ?>>
    <?= csrf_field() ?>
                    <div class="auth-field">
                        <label for="otp_input" class="block text-sm font-semibold mb-2">Mã OTP <span class="text-red-500">*</span></label>
                        <input
                            id="otp_input"
                            type="text"
                            name="otp"
                            required
                            maxlength="4"
                            pattern="\d{4}"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary outline-none transition text-center text-2xl tracking-widest <?= !empty($errors['otp']) ? 'border-red-300 bg-red-50' : 'border-gray-200' ?>"
                            aria-describedby="otp_error"
                            placeholder="••••"
                        >
                        <p id="otp_error" class="field-error mt-2 text-sm text-red-600 <?= empty($errors['otp']) ? 'hidden' : '' ?>">
                            <?= e($errors['otp'] ?? '') ?>
                        </p>
                    </div>

                    <?php if (!empty($errors['otp_verified'])): ?>
                        <div class="auth-alert mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg flex items-start gap-2">
                            <span class="material-symbols-outlined mt-0.5">check_circle</span>
                            <span>Xác thực OTP thành công. Vui lòng đặt mật khẩu mới.</span>
                        </div>
                        <a href="<?= BASE_URL ?>?page=reset-password" class="auth-btn w-full py-3 bg-primary text-white rounded-lg font-semibold hover:bg-opacity-90 transition transform hover:scale-[1.02] active:scale-[0.99] shadow-lg">
                            Đặt mật khẩu mới
                        </a>
                    <?php else: ?>
                        <button type="submit" class="auth-btn w-full py-3 bg-primary text-white rounded-lg font-semibold hover:bg-opacity-90 transition transform hover:scale-[1.02] active:scale-[0.99] shadow-lg">
                            Xác thực
                        </button>
                    <?php endif; ?>
                </form>

                <div class="mt-4 text-center">
                    <form method="POST" action="<?= BASE_URL ?>?page=resend-otp" class="inline">
    <?= csrf_field() ?>
                        <button type="submit" class="text-primary hover:underline text-sm font-medium" id="resendBtn" disabled>
                            Gửi lại mã OTP
                        </button>
                    </form>
                </div>

                <div class="mt-4 text-center">
                    <a href="<?= BASE_URL ?>?page=login" class="text-gray-500 hover:text-primary text-sm">Quay lại đăng nhập</a>
                </div>
            <?php endif; ?>
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
  // Auto-focus OTP input
  var otpInput = document.getElementById('otp_input');
  if (otpInput) {
    otpInput.focus();
  }

  // Resend countdown
  var countdownEl = document.getElementById('resendCountdown');
  var resendBtn = document.getElementById('resendBtn');
  var alertEl = document.getElementById('resendWaitAlert');

  if (countdownEl && resendBtn) {
    var seconds = parseInt(countdownEl.textContent, 10);
    resendBtn.disabled = true;
    
    var timer = setInterval(function() {
      seconds--;
      countdownEl.textContent = seconds;
      if (seconds <= 0) {
        clearInterval(timer);
        resendBtn.disabled = false;
        if (alertEl) alertEl.style.display = 'none';
      }
    }, 1000);
  }

  // OTP input auto-format
  if (otpInput) {
    otpInput.addEventListener('input', function() {
      this.value = this.value.replace(/\D/g, '').slice(0, 4);
    });
    
    otpInput.addEventListener('paste', function(e) {
      e.preventDefault();
      var text = (e.clipboardData || window.clipboardData).getData('text');
      text = text.replace(/\D/g, '').slice(0, 4);
      this.value = text;
      if (text.length === 4) {
        this.form?.querySelector('button[type="submit"]')?.click();
      }
    });
  }
})();
</script>
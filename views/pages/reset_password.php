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
        <div class="auth-card bg-white rounded-2xl shadow-2xl p-8 border border-gray-100">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-primary/10 rounded-2xl mb-4 auth-pop">
                    <span class="material-symbols-outlined text-primary text-3xl">lock_reset</span>
                </div>
                <h1 class="text-3xl font-bold gradient-text mb-2">Đặt lại mật khẩu</h1>
                <p class="text-gray-500">Nhập mật khẩu mới cho tài khoản của bạn.</p>
            </div>

            <form method="POST" class="space-y-4" data-reset-form novalidate <?= !empty($errors) ? 'data-shake' : '' ?>>
<?= csrf_field() ?>
                <div class="auth-field">
                    <label for="reset_password" class="block text-sm font-semibold mb-2">Mật khẩu mới <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input
                            id="reset_password"
                            type="password"
                            name="new_password"
                            required
                            minlength="6"
                            class="w-full px-4 py-3 pr-12 border rounded-lg focus:ring-2 focus:ring-primary outline-none transition <?= !empty($errors['new_password']) ? 'border-red-300 bg-red-50' : 'border-gray-200' ?>"
                            aria-describedby="new_password_error"
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
                    <p id="new_password_error" class="field-error mt-2 text-sm text-red-600 <?= empty($errors['new_password']) ? 'hidden' : '' ?>">
                        <?= e($errors['new_password'] ?? '') ?>
                    </p>
                    <div class="pw-meter" id="pwMeter" hidden>
                        <div class="pw-bars"><i></i><i></i><i></i><i></i></div>
                        <span class="pw-label"></span>
                    </div>
                </div>

                <div class="auth-field">
                    <label for="reset_confirm" class="block text-sm font-semibold mb-2">Xác nhận mật khẩu mới <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input
                            id="reset_confirm"
                            type="password"
                            name="confirm_password"
                            required
                            minlength="6"
                            class="w-full px-4 py-3 pr-12 border rounded-lg focus:ring-2 focus:ring-primary outline-none transition <?= !empty($errors['confirm_password']) ? 'border-red-300 bg-red-50' : 'border-gray-200' ?>"
                            aria-describedby="confirm_error"
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
                    <p id="confirm_error" class="field-error mt-2 text-sm text-red-600 <?= empty($errors['confirm_password']) ? 'hidden' : '' ?>">
                        <?= e($errors['confirm_password'] ?? '') ?>
                    </p>
                    <div class="pw-match" id="pwMatch" hidden></div>
                </div>

                <button type="submit" class="auth-btn w-full py-3 bg-primary text-white rounded-lg font-semibold hover:bg-opacity-90 transition transform hover:scale-[1.02] active:scale-[0.99] shadow-lg">
                    Đặt lại mật khẩu
                </button>
            </form>

            <div class="mt-6 text-center text-sm text-gray-600">
                <a href="<?= BASE_URL ?>?page=login" class="text-primary font-semibold hover:underline">Quay lại đăng nhập</a>
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

  .pw-meter{ margin-top:10px; }
  .pw-bars{ display:flex; gap:5px; }
  .pw-bars i{ height:5px; flex:1; border-radius:999px; background:#e5e7eb; transition:background .3s ease; }
  .pw-label{ display:block; margin-top:6px; font-size:12px; font-weight:600; color:#9ca3af; transition:color .3s ease; }
  .pw-meter[data-level="1"] .pw-bars i:nth-child(-n+1){ background:#ef4444; }
  .pw-meter[data-level="2"] .pw-bars i:nth-child(-n+2){ background:#f59e0b; }
  .pw-meter[data-level="3"] .pw-bars i:nth-child(-n+3){ background:#eab308; }
  .pw-meter[data-level="4"] .pw-bars i{ background:#10b981; }
  .pw-meter[data-level="1"] .pw-label{ color:#ef4444; }
  .pw-meter[data-level="2"] .pw-label{ color:#f59e0b; }
  .pw-meter[data-level="3"] .pw-label{ color:#ca8a04; }
  .pw-meter[data-level="4"] .pw-label{ color:#10b981; }

  .pw-match{ display:flex; align-items:center; gap:6px; margin-top:8px; font-size:12px; font-weight:600; min-height:18px; }
  .pw-match .material-symbols-outlined{ font-size:16px; }
  .pw-match.ok{ color:#10b981; }
  .pw-match.no{ color:#ef4444; }
  #reset_confirm.match-ok{ border-color:#10b981; }
  #reset_confirm.match-no{ border-color:#ef4444; }

  @media (prefers-reduced-motion:reduce){
    .auth-ambient .win,.auth-card,.auth-pop,.auth-card.is-shake{ animation:none !important; }
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

  var form = document.querySelector('[data-reset-form]');
  var passwordInput = document.getElementById('reset_password');
  var confirmInput = document.getElementById('reset_confirm');
  var meter = document.getElementById('pwMeter');
  var meterLabel = meter ? meter.querySelector('.pw-label') : null;
  var matchBox = document.getElementById('pwMatch');
  var strengthWords = ['', 'Rất yếu', 'Yếu', 'Trung bình', 'Mạnh'];

  function setFieldError(input, message) {
    if (!input) return;
    var box = document.getElementById(input.id + '_error');
    if (!box) return;

    box.textContent = message;
    box.classList.toggle('hidden', !message);
    input.classList.toggle('border-red-300', !!message);
    input.classList.toggle('bg-red-50', !!message);
  }

  function scorePassword(value) {
    var score = 0;
    if (value.length >= 6) score++;
    if (value.length >= 8) score++;
    if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score++;
    if (/\d/.test(value) || /[^A-Za-z0-9]/.test(value)) score++;
    return Math.min(score, 4);
  }

  function updatePasswordMeter() {
    if (!passwordInput || !meter || !meterLabel) return;

    if (!passwordInput.value) {
      meter.hidden = true;
      meter.removeAttribute('data-level');
      return;
    }

    var level = Math.max(1, scorePassword(passwordInput.value));
    meter.hidden = false;
    meter.setAttribute('data-level', String(level));
    meterLabel.textContent = strengthWords[level];
  }

  function updatePasswordMatch() {
    if (!confirmInput || !matchBox) return;

    if (!confirmInput.value) {
      matchBox.hidden = true;
      matchBox.className = 'pw-match';
      confirmInput.classList.remove('match-ok', 'match-no');
      return;
    }

    matchBox.hidden = false;
    if (confirmInput.value === passwordInput.value) {
      matchBox.className = 'pw-match ok';
      matchBox.innerHTML = '<span class="material-symbols-outlined">check_circle</span> Mật khẩu khớp';
      confirmInput.classList.add('match-ok');
      confirmInput.classList.remove('match-no');
    } else {
      matchBox.className = 'pw-match no';
      matchBox.innerHTML = '<span class="material-symbols-outlined">cancel</span> Mật khẩu không khớp';
      confirmInput.classList.add('match-no');
      confirmInput.classList.remove('match-ok');
    }
  }

  if (passwordInput) {
    passwordInput.addEventListener('input', function () {
      updatePasswordMeter();
      updatePasswordMatch();
    });
  }

  if (confirmInput) {
    confirmInput.addEventListener('input', updatePasswordMatch);
  }

  if (form) {
    form.addEventListener('submit', function (event) {
      var hasError = false;

      [passwordInput, confirmInput].forEach(function (input) {
        if (!input) return;
        setFieldError(input, '');
      });

      if (passwordInput) {
        if (!passwordInput.value) {
          setFieldError(passwordInput, 'Vui lòng nhập mật khẩu mới.');
          hasError = true;
        } else if (passwordInput.value.length < 6) {
          setFieldError(passwordInput, 'Mật khẩu mới phải có ít nhất 6 ký tự.');
          hasError = true;
        }
      }

      if (confirmInput) {
        if (!confirmInput.value) {
          setFieldError(confirmInput, 'Vui lòng xác nhận mật khẩu mới.');
          hasError = true;
        } else if (confirmInput.value !== passwordInput?.value) {
          setFieldError(confirmInput, 'Xác nhận mật khẩu chưa khớp.');
          hasError = true;
        }
      }

      if (hasError) {
        event.preventDefault();
      }
    });
  }

  updatePasswordMeter();
  updatePasswordMatch();

  var card = document.querySelector('.auth-card');
  if (card && card.querySelector('form[data-shake]')) {
    requestAnimationFrame(function () {
      card.classList.add('is-shake');
    });
  }
})();
</script>
<?php
$errors = $errors ?? [];
$old = $old ?? [];
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
                    <span class="material-symbols-outlined text-primary text-3xl">person_add</span>
                </div>
                <h1 class="text-3xl font-bold gradient-text mb-2">Đăng ký tài khoản</h1>
                <p class="text-gray-500">Tạo tài khoản người thuê để xem phòng trống và theo dõi thông tin thuê phòng sau này.</p>
            </div>

            <form method="POST" class="space-y-4" data-register-form novalidate <?= !empty($errors) ? 'data-shake' : '' ?>>
<?= csrf_field() ?>
                <div class="auth-field">
                    <label for="register_full_name" class="block text-sm font-semibold mb-2">Họ và tên <span class="text-red-500">*</span></label>
                    <input
                        id="register_full_name"
                        type="text"
                        name="full_name"
                        required
                        maxlength="100"
                        value="<?= e($old['full_name'] ?? '') ?>"
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary outline-none transition <?= !empty($errors['full_name']) ? 'border-red-300 bg-red-50' : 'border-gray-200' ?>"
                        aria-describedby="full_name_error"
                    >
                    <p id="full_name_error" class="field-error mt-2 text-sm text-red-600 <?= empty($errors['full_name']) ? 'hidden' : '' ?>">
                        <?= e($errors['full_name'] ?? '') ?>
                    </p>
                </div>

                <div class="auth-field">
                    <label for="register_email" class="block text-sm font-semibold mb-2">Email (Không bắt buộc)</label>
                    <input
                        id="register_email"
                        type="email"
                        name="email"
                        value="<?= e($old['email'] ?? '') ?>"
                        placeholder="example@email.com"
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary outline-none transition <?= !empty($errors['email']) ? 'border-red-300 bg-red-50' : 'border-gray-200' ?>"
                        aria-describedby="email_error"
                    >
                    <p id="email_error" class="field-error mt-2 text-sm text-red-600 <?= empty($errors['email']) ? 'hidden' : '' ?>">
                        <?= e($errors['email'] ?? '') ?>
                    </p>
                    <p class="field-hint mt-1 text-xs text-gray-500" id="email_hint">Email không bắt buộc. Nếu nhập, phải đúng định dạng.</p>
                </div>

                <div class="auth-field">
                    <label for="register_phone" class="block text-sm font-semibold mb-2">Số điện thoại <span class="text-red-500">*</span></label>
                    <input
                        id="register_phone"
                        type="tel"
                        name="phone"
                        required
                        value="<?= e($old['phone'] ?? '') ?>"
                        placeholder="0328528757 hoặc +84328528757"
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary outline-none transition <?= !empty($errors['phone']) ? 'border-red-300 bg-red-50' : 'border-gray-200' ?>"
                        aria-describedby="phone_error"
                        inputmode="tel"
                        autocomplete="tel"
                    >
                    <p id="phone_error" class="field-error mt-2 text-sm text-red-600 <?= empty($errors['phone']) ? 'hidden' : '' ?>">
                        <?= e($errors['phone'] ?? '') ?>
                    </p>
                    <p class="field-hint mt-1 text-xs text-gray-500" id="phone_hint">Chỉ số, khoảng trắng, +84 ở đầu. Không dấu gạch ngang, ngoặc, chữ cái.</p>
                </div>

                <div class="auth-field">
                    <label for="reg_password" class="block text-sm font-semibold mb-2">Mật khẩu <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input
                            id="reg_password"
                            type="password"
                            name="password"
                            required
                            minlength="6"
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
                    <p id="password_error" class="field-error mt-2 text-sm text-red-600 <?= empty($errors['password']) ? 'hidden' : '' ?>">
                        <?= e($errors['password'] ?? '') ?>
                    </p>
                    <div class="pw-meter" id="pwMeter" hidden>
                        <div class="pw-bars"><i></i><i></i><i></i><i></i></div>
                        <span class="pw-label"></span>
                    </div>
                </div>

                <div class="auth-field">
                    <label for="reg_confirm" class="block text-sm font-semibold mb-2">Xác nhận mật khẩu <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input
                            id="reg_confirm"
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

                <?php if (!empty($errors['general'])): ?>
                    <div class="auth-alert p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-start gap-2">
                        <span class="material-symbols-outlined mt-0.5">error</span>
                        <span><?= e($errors['general']) ?></span>
                    </div>
                <?php endif; ?>

                <button type="submit" class="auth-btn w-full py-3 bg-primary text-white rounded-lg font-semibold hover:bg-opacity-90 transition transform hover:scale-[1.02] active:scale-[0.99] shadow-lg" id="registerBtn" disabled>
                    Đăng ký
                </button>
            </form>

            <div class="mt-6 text-center text-sm text-gray-600">
                Đã có tài khoản?
                <a href="<?= BASE_URL ?>?page=login" class="text-primary font-semibold hover:underline">Đăng nhập</a>
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
  #reg_confirm.match-ok{ border-color:#10b981; }
  #reg_confirm.match-no{ border-color:#ef4444; }

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
      btn.setAttribute('aria-label', wasHidden ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
    });
  });

  var form = document.querySelector('[data-register-form]');
  var passwordInput = document.getElementById('reg_password');
  var confirmInput = document.getElementById('reg_confirm');
  var meter = document.getElementById('pwMeter');
  var meterLabel = meter ? meter.querySelector('.pw-label') : null;
  var matchBox = document.getElementById('pwMatch');
  var registerBtn = document.getElementById('registerBtn');
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

function validateEmailStrict(email) {
    if (!email) return { valid: true }; // Email không bắt buộc
    email = email.trim();
    if (email.length > 254) return { valid: false, message: 'Email không được vượt quá 254 ký tự.' };
    if (email.split('@').length !== 2) return { valid: false, message: 'Email phải có đúng một dấu @.' };
    if (email.includes(' ')) return { valid: false, message: 'Email không được chứa khoảng trắng.' };

    var parts = email.split('@');
    var localPart = parts[0];
    var domain = parts[1];

    if (!localPart || !domain) return { valid: false, message: 'Email không hợp lệ.' };
    
    // Local-part checks (phần trước @)
    if (localPart.length > 64) return { valid: false, message: 'Phần trước @ không được vượt quá 64 ký tự.' };
    if (localPart[0] === '.' || localPart[localPart.length - 1] === '.') return { valid: false, message: 'Phần trước @ không được bắt đầu hoặc kết thúc bằng dấu chấm.' };
    if (localPart.includes('..')) return { valid: false, message: 'Phần trước @ không được có hai dấu chấm liên tiếp.' };
    if (!/^[A-Za-z0-9._%+-]+$/.test(localPart)) return { valid: false, message: 'Phần trước @ chứa ký tự không hợp lệ. Chỉ cho phép chữ, số, . _ % + -' };

    // Domain checks (phần sau @)
    if (domain.length > 255) return { valid: false, message: 'Tên miền không được vượt quá 255 ký tự.' };
    if (!domain.includes('.')) return { valid: false, message: 'Tên miền phải có ít nhất một dấu chấm.' };
    if (domain[0] === '.' || domain[domain.length - 1] === '.') return { valid: false, message: 'Tên miền không được bắt đầu hoặc kết thúc bằng dấu chấm.' };
    if (domain.includes('..')) return { valid: false, message: 'Tên miền không được có hai dấu chấm liên tiếp.' };
    
    // Check each domain label (parts separated by dots)
    var domainLabels = domain.split('.');
    for (var i = 0; i < domainLabels.length; i++) {
        var label = domainLabels[i];
        if (!label) return { valid: false, message: 'Tên miền có nhãn rỗng.' };
        if (label.length > 63) return { valid: false, message: 'Nhãn tên miền không được vượt quá 63 ký tự.' };
        if (label[0] === '-' || label[label.length - 1] === '-') return { valid: false, message: 'Nhãn tên miền không được bắt đầu hoặc kết thúc bằng dấu gạch ngang.' };
        if (!/^[A-Za-z0-9-]+$/.test(label)) return { valid: false, message: 'Tên miền chứa ký tự không hợp lệ. Chỉ cho phép chữ, số, dấu gạch ngang.' };
    }
    
    // TLD check (last label)
    var tld = domainLabels[domainLabels.length - 1];
    if (!/^[a-zA-Z]{2,63}$/.test(tld)) return { valid: false, message: 'Đuôi tên miền (TLD) phải từ 2-63 ký tự chữ.' };
    if (domain.toLowerCase() === 'localhost') return { valid: false, message: 'Không chấp nhận localhost.' };

    return { valid: true };
  }

  function normalizePhoneInput(rawPhone) {
    if (!rawPhone) return null;
    var phone = rawPhone.replace(/\s+/g, '');
    if (!/^[0-9+]+$/.test(phone)) return null;
    var plusPos = phone.indexOf('+');
    if (plusPos !== -1 && plusPos !== 0) return null;
    
    if (phone.startsWith('+84')) {
      var suffix = phone.substring(3);
      if (suffix.length !== 9 || !/^\d+$/.test(suffix)) return null;
      if (suffix[0] === '0') return null;
      return '0' + suffix;
    }
    
    if (phone.startsWith('84') && !phone.startsWith('+')) {
      var suffix = phone.substring(2);
      if (suffix.length !== 9 || !/^\d+$/.test(suffix)) return null;
      if (suffix[0] === '0') return null;
      return '0' + suffix;
    }
    
    if (phone.startsWith('0')) {
      if (phone.length !== 10 || !/^\d+$/.test(phone)) return null;
      return phone;
    }
    
    return null;
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

  function checkFormValidity() {
    if (!form) return;
    
    var fullNameInput = document.getElementById('register_full_name');
    var emailInput = document.getElementById('register_email');
    var phoneInput = document.getElementById('register_phone');
    
    var hasError = false;
    
    // Full name
    if (fullNameInput && !fullNameInput.value.trim()) {
      hasError = true;
    } else if (fullNameInput && fullNameInput.value.length > 100) {
      hasError = true;
    } else if (fullNameInput && !/^[\p{L}\p{N}\s\-'\.]+$/u.test(fullNameInput.value.trim())) {
      hasError = true;
    }
    
    // Email
    if (emailInput && emailInput.value.trim()) {
      var emailResult = validateEmailStrict(emailInput.value);
      if (!emailResult.valid) hasError = true;
    }
    
    // Phone
    if (phoneInput && phoneInput.value.trim()) {
      var normalized = normalizePhoneInput(phoneInput.value);
      if (!normalized) hasError = true;
    } else if (phoneInput && !phoneInput.value.trim()) {
      hasError = true;
    }
    
    // Password
    if (passwordInput && (!passwordInput.value || passwordInput.value.length < 6)) {
      hasError = true;
    } else if (passwordInput && !/[A-Za-z]/.test(passwordInput.value)) {
      hasError = true;
    } else if (passwordInput && !/\d/.test(passwordInput.value)) {
      hasError = true;
    }
    
    // Confirm
    if (confirmInput && (!confirmInput.value || confirmInput.value !== passwordInput?.value)) {
      hasError = true;
    }
    
    registerBtn.disabled = hasError;
  }

  function validateFieldOnBlur(input) {
    if (!input) return;
    
    if (input.id === 'register_full_name') {
      if (!input.value.trim()) {
        setFieldError(input, 'Vui lòng nhập họ và tên.');
      } else if (input.value.length > 100) {
        setFieldError(input, 'Họ và tên không được vượt quá 100 ký tự.');
      } else if (!/^[\p{L}\p{N}\s\-'\.]+$/u.test(input.value.trim())) {
        setFieldError(input, 'Họ và tên chứa ký tự không hợp lệ. Chỉ cho phép chữ, số, khoảng trắng, dấu gạch ngang, dấu chấm, dấu nháy đơn.');
      } else {
        setFieldError(input, '');
      }
    } else if (input.id === 'register_email') {
      if (input.value.trim()) {
        var result = validateEmailStrict(input.value);
        setFieldError(input, result.valid ? '' : result.message);
      } else {
        setFieldError(input, '');
      }
    } else if (input.id === 'register_phone') {
      if (!input.value.trim()) {
        setFieldError(input, 'Vui lòng nhập số điện thoại.');
      } else {
        var normalized = normalizePhoneInput(input.value);
        if (!normalized) {
          // More specific error messages
          var raw = input.value.trim();
          if (!/^[0-9\s+]+$/.test(raw)) {
            setFieldError(input, 'Số điện thoại chỉ được chứa số, khoảng trắng và dấu +.');
          } else if (raw.startsWith('+') && raw.indexOf('+') !== 0) {
            setFieldError(input, 'Dấu + chỉ được phép ở đầu số.');
          } else if (raw.startsWith('+84')) {
            var suffix = raw.substring(3);
            if (suffix.length !== 9 || !/^\d+$/.test(suffix) || suffix[0] === '0') {
              setFieldError(input, 'Số điện thoại +84 không hợp lệ. Phải có 9 số sau +84 và số đầu không được là 0.');
            }
          } else if (raw.startsWith('84') && !raw.startsWith('+')) {
            var suffix = raw.substring(2);
            if (suffix.length !== 9 || !/^\d+$/.test(suffix) || suffix[0] === '0') {
              setFieldError(input, 'Số điện thoại 84 không hợp lệ. Phải có 9 số sau 84 và số đầu không được là 0.');
            }
          } else if (raw.startsWith('0')) {
            if (raw.length !== 10 || !/^\d+$/.test(raw)) {
              setFieldError(input, 'Số điện thoại 0xxxxxxxxx phải có đúng 10 chữ số.');
            }
          } else {
            setFieldError(input, 'Số điện thoại phải bắt đầu bằng 0, +84 hoặc 84.');
          }
        } else {
          setFieldError(input, '');
        }
      }
    } else if (input.id === 'reg_password') {
      if (!input.value) {
        setFieldError(input, 'Vui lòng nhập mật khẩu.');
      } else if (input.value.length < 6) {
        setFieldError(input, 'Mật khẩu phải có ít nhất 6 ký tự.');
      } else if (!/[A-Za-z]/.test(input.value)) {
        setFieldError(input, 'Mật khẩu phải chứa ít nhất 1 chữ cái.');
      } else if (!/\d/.test(input.value)) {
        setFieldError(input, 'Mật khẩu phải chứa ít nhất 1 số.');
      } else {
        setFieldError(input, '');
      }
    } else if (input.id === 'reg_confirm') {
      if (!input.value) {
        setFieldError(input, 'Vui lòng xác nhận mật khẩu.');
      } else if (input.value !== passwordInput?.value) {
        setFieldError(input, 'Xác nhận mật khẩu chưa khớp.');
      } else {
        setFieldError(input, '');
      }
    }
    
    checkFormValidity();
  }

  // Input event listeners for real-time validation
  var fullNameInput = document.getElementById('register_full_name');
  var emailInput = document.getElementById('register_email');
  var phoneInput = document.getElementById('register_phone');
  
  [fullNameInput, emailInput, phoneInput].forEach(function(input) {
    if (input) {
      var debounceTimer;
      input.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
          validateFieldOnBlur(input);
        }, 700);
      });
      input.addEventListener('blur', function() {
        clearTimeout(debounceTimer);
        validateFieldOnBlur(input);
      });
    }
  });

  if (passwordInput) {
    passwordInput.addEventListener('input', function () {
      updatePasswordMeter();
      updatePasswordMatch();
      checkFormValidity();
    });
  }

  if (confirmInput) {
    confirmInput.addEventListener('input', function() {
      updatePasswordMatch();
      checkFormValidity();
    });
  }

  if (form) {
    form.addEventListener('submit', function (event) {
      var hasError = false;

      [
        fullNameInput,
        emailInput,
        phoneInput,
        passwordInput,
        confirmInput
      ].forEach(function (input) {
        if (!input) return;
        setFieldError(input, '');
      });

      if (fullNameInput && !fullNameInput.value.trim()) {
        setFieldError(fullNameInput, 'Vui lòng nhập họ và tên.');
        hasError = true;
      } else if (fullNameInput && fullNameInput.value.length > 100) {
        setFieldError(fullNameInput, 'Họ và tên không được vượt quá 100 ký tự.');
        hasError = true;
      } else if (fullNameInput && !/^[\p{L}\p{N}\s\-'\.]+$/u.test(fullNameInput.value.trim())) {
        setFieldError(fullNameInput, 'Họ và tên chứa ký tự không hợp lệ. Chỉ cho phép chữ, số, khoảng trắng, dấu gạch ngang, dấu chấm, dấu nháy đơn.');
        hasError = true;
      }

      if (emailInput && emailInput.value.trim()) {
        var emailResult = validateEmailStrict(emailInput.value);
        if (!emailResult.valid) {
          setFieldError(emailInput, emailResult.message);
          hasError = true;
        }
      }

      if (phoneInput && !phoneInput.value.trim()) {
        setFieldError(phoneInput, 'Vui lòng nhập số điện thoại.');
        hasError = true;
      } else if (phoneInput && phoneInput.value.trim()) {
        var normalized = normalizePhoneInput(phoneInput.value);
        if (!normalized) {
          setFieldError(phoneInput, 'Số điện thoại không hợp lệ. Chỉ chấp nhận số, khoảng trắng, +84 ở đầu. Không dấu gạch ngang, ngoặc, chữ cái.');
          hasError = true;
        }
      }

      if (passwordInput) {
        if (!passwordInput.value) {
          setFieldError(passwordInput, 'Vui lòng nhập mật khẩu.');
          hasError = true;
        } else if (passwordInput.value.length < 6) {
          setFieldError(passwordInput, 'Mật khẩu phải có ít nhất 6 ký tự.');
          hasError = true;
        } else if (!/[A-Za-z]/.test(passwordInput.value)) {
          setFieldError(passwordInput, 'Mật khẩu phải chứa ít nhất 1 chữ cái.');
          hasError = true;
        } else if (!/\d/.test(passwordInput.value)) {
          setFieldError(passwordInput, 'Mật khẩu phải chứa ít nhất 1 số.');
          hasError = true;
        }
      }

      if (confirmInput) {
        if (!confirmInput.value) {
          setFieldError(confirmInput, 'Vui lòng xác nhận mật khẩu.');
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

  // Initial check
  checkFormValidity();
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
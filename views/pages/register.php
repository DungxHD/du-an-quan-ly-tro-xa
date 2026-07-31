<!-- Màn hình đăng ký: 2 ô mật khẩu đều có mắt + thước độ mạnh + báo khớp realtime. -->
<section class="auth-shell min-h-[calc(100vh-4rem)] flex items-center justify-center py-12 bg-gradient-to-br from-primary/5 to-secondary/5">

    <div class="auth-ambient" aria-hidden="true">
        <span class="win w1"></span><span class="win w2"></span><span class="win w3"></span>
        <span class="win w4"></span><span class="win w5"></span><span class="win w6"></span>
    </div>

    <div class="relative w-full max-w-md px-4 reveal-scale">
        <div class="auth-card bg-white rounded-2xl shadow-2xl p-8 border border-gray-100">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-primary/10 rounded-2xl mb-4 auth-pop">
                    <span class="material-symbols-outlined text-primary text-3xl">person_add</span>
                </div>
                <h1 class="text-3xl font-bold gradient-text mb-2">Đăng ký</h1>
                <p class="text-gray-500">Tạo tài khoản để trải nghiệm NhaTroA</p>
            </div>

            <?php if (!empty($error)): ?>
            <div class="auth-alert mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-center gap-2">
                <span class="material-symbols-outlined">error</span>
                <?= e($error) ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
            <div class="auth-alert mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg flex items-center gap-2">
                <span class="material-symbols-outlined">check_circle</span>
                <?= e($success) ?>
            </div>
            <?php endif; ?>

            <form method="POST" data-validate class="space-y-4" <?= !empty($error) ? 'data-shake' : '' ?>>
                <div class="auth-field">
                    <label class="block text-sm font-semibold mb-2">Họ và tên *</label>
                    <input type="text" name="full_name" required
                           class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none transition"
                           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                </div>

                <div class="auth-field">
                    <label class="block text-sm font-semibold mb-2">Email *</label>
                    <input type="email" name="email" required
                           class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none transition"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="auth-field">
                    <label class="block text-sm font-semibold mb-2">Số điện thoại</label>
                    <input type="tel" name="phone"
                           class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none transition"
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>

                <!-- ===== Ô MẬT KHẨU 1 + MẮT + THƯỚC ĐỘ MẠNH ===== -->
                <div class="auth-field">
                    <label class="block text-sm font-semibold mb-2">Mật khẩu * (tối thiểu 6 ký tự)</label>
                    <div class="relative">
                        <input type="password" name="password" id="reg_password" required minlength="6"
                               class="w-full px-4 py-3 pr-12 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none transition">
                        <button type="button"
                                class="toggle-password absolute right-3 top-[25px] -translate-y-1/2 p-1 text-gray-400 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary rounded transition-colors"
                                aria-label="Hiện mật khẩu" aria-pressed="false">
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
                    <!-- thước độ mạnh: nằm NGOÀI .relative nên không làm lệch mắt -->
                    <div class="pw-meter" id="pwMeter" hidden>
                        <div class="pw-bars"><i></i><i></i><i></i><i></i></div>
                        <span class="pw-label"></span>
                    </div>
                </div>
                <!-- ============================================== -->

                <!-- ===== Ô XÁC NHẬN + MẮT + BÁO KHỚP ===== -->
                <div class="auth-field">
                    <label class="block text-sm font-semibold mb-2">Xác nhận mật khẩu *</label>
                    <div class="relative">
                        <input type="password" name="confirm_password" id="reg_confirm" required minlength="6"
                               class="w-full px-4 py-3 pr-12 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none transition">
                        <button type="button"
                                class="toggle-password absolute right-3 top-[25px] -translate-y-1/2 p-1 text-gray-400 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary rounded transition-colors"
                                aria-label="Hiện mật khẩu" aria-pressed="false">
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
                    <!-- báo khớp: nằm NGOÀI .relative nên không làm lệch mắt -->
                    <div class="pw-match" id="pwMatch" hidden></div>
                </div>
                <!-- ======================================== -->

                <button type="submit" class="auth-btn w-full py-3 bg-primary text-white rounded-lg font-semibold hover:bg-opacity-90 transition transform hover:scale-[1.02] active:scale-[0.99] shadow-lg">Đăng ký</button>
            </form>

            <div class="mt-6 text-center text-sm">
                <p class="text-gray-600">Đã có tài khoản?
                    <a href="<?= BASE_URL ?>?page=login" class="text-primary font-semibold hover:underline">Đăng nhập</a>
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ===== STYLE + JS dùng chung cho form đăng ký ===== -->
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
  .w1{ top:14%; left:10%; animation-delay:0s; }   .w2{ top:22%; right:12%; animation-delay:-2s; }
  .w3{ top:62%; left:16%; animation-delay:-4s; }  .w4{ top:70%; right:18%; animation-delay:-6s; }
  .w5{ top:40%; left:6%;  animation-delay:-3s; }  .w6{ top:48%; right:7%; animation-delay:-8s; }
  @keyframes winDrift{ 0%,100%{ transform:translateY(0); opacity:.45; } 50%{ transform:translateY(-14px); opacity:.9; } }

  .auth-card{ animation:authIn .6s cubic-bezier(.2,.7,.2,1) both; }
  .auth-pop{ animation:popIn .7s cubic-bezier(.2,.8,.2,1) .15s both; }
  @keyframes authIn{ from{ opacity:0; transform:translateY(18px) scale(.98); } to{ opacity:1; transform:none; } }
  @keyframes popIn{ 0%{ transform:scale(.4); opacity:0; } 60%{ transform:scale(1.12); } 100%{ transform:scale(1); opacity:1; } }

  .auth-card.is-shake{ animation:shake .5s cubic-bezier(.36,.07,.19,.97) both; }
  @keyframes shake{ 10%,90%{ transform:translateX(-2px); } 20%,80%{ transform:translateX(4px); }
                    30%,50%,70%{ transform:translateX(-7px); } 40%,60%{ transform:translateX(7px); } }
  .auth-alert{ animation:alertIn .4s ease both; }
  @keyframes alertIn{ from{ opacity:0; transform:translateY(-6px); } to{ opacity:1; transform:none; } }

  /* thước độ mạnh mật khẩu */
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

  /* báo khớp / không khớp */
  .pw-match{ display:flex; align-items:center; gap:6px; margin-top:8px; font-size:12px; font-weight:600; min-height:18px; }
  .pw-match .material-symbols-outlined{ font-size:16px; }
  .pw-match.ok{ color:#10b981; }
  .pw-match.no{ color:#ef4444; }
  /* đổi viền ô xác nhận theo trạng thái khớp */
  #reg_confirm.match-ok{ border-color:#10b981; }
  #reg_confirm.match-no{ border-color:#ef4444; }

  @media (prefers-reduced-motion:reduce){
    .auth-ambient .win,.auth-card,.auth-pop,.auth-card.is-shake,.auth-alert{ animation:none !important; }
  }
</style>

<script>
(function () {
  /* --- Hiện / ẩn mật khẩu (chạy cho cả 2 ô) --- */
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
      var svg = btn.querySelector(wasHidden ? '.icon-eye-off' : '.icon-eye');
      if (svg && svg.animate) svg.animate(
        [{ transform:'scale(.55)', opacity:.3 }, { transform:'scale(1.18)', opacity:1 }, { transform:'scale(1)' }],
        { duration:260, easing:'cubic-bezier(.2,.8,.2,1)' });
    });
  });

  /* --- Thước đo độ mạnh mật khẩu (realtime) --- */
  var pw      = document.getElementById('reg_password');
  var confirm = document.getElementById('reg_confirm');
  var meter   = document.getElementById('pwMeter');
  var label   = meter ? meter.querySelector('.pw-label') : null;
  var matchBox= document.getElementById('pwMatch');
  var WORDS   = ['', 'Rất yếu', 'Yếu', 'Trung bình', 'Mạnh'];

  function score(v) {
    var s = 0;
    if (v.length >= 8)  s++;
    if (/[A-Z]/.test(v) && /[a-z]/.test(v)) s++;
    if (/\d/.test(v))   s++;
    if (/[^A-Za-z0-9]/.test(v)) s++;
    if (v.length >= 12 && s >= 3) s = 4;   // dài + đa dạng → mạnh
    return Math.min(s, 4);
  }
  if (pw && meter) {
    pw.addEventListener('input', function () {
      var v = pw.value;
      if (!v) { meter.hidden = true; meter.removeAttribute('data-level'); checkMatch(); return; }
      var lv = Math.max(1, score(v));      // có chữ thì tối thiểu mức 1
      meter.hidden = false;
      meter.setAttribute('data-level', lv);
      label.textContent = WORDS[lv];
      checkMatch();                         // đổi mật khẩu thì kiểm tra lại khớp
    });
  }

  /* --- Báo khớp mật khẩu (realtime, chỉ hiện khi đã gõ ô xác nhận) --- */
  function checkMatch() {
    if (!confirm || !matchBox) return;
    var c = confirm.value;
    if (!c) { matchBox.hidden = true; matchBox.className = 'pw-match'; confirm.classList.remove('match-ok','match-no'); return; }
    matchBox.hidden = false;
    if (c === pw.value) {
      matchBox.className = 'pw-match ok';
      matchBox.innerHTML = '<span class="material-symbols-outlined">check_circle</span> Mật khẩu khớp';
      confirm.classList.add('match-ok'); confirm.classList.remove('match-no');
    } else {
      matchBox.className = 'pw-match no';
      matchBox.innerHTML = '<span class="material-symbols-outlined">cancel</span> Mật khẩu không khớp';
      confirm.classList.add('match-no'); confirm.classList.remove('match-ok');
    }
  }
  if (confirm) confirm.addEventListener('input', checkMatch);

  /* --- Lắc card khi server trả lỗi --- */
  var card = document.querySelector('.auth-card');
  if (card && card.querySelector('form[data-shake]')) {
    requestAnimationFrame(function(){ card.classList.add('is-shake'); });
  }
})();
</script>
<?php
$errors = $errors ?? [];
$old = $old ?? [];
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
                    <span class="material-symbols-outlined text-primary text-3xl">login</span>
                </div>
                <h1 class="text-3xl font-bold gradient-text mb-2">Đăng nhập</h1>
                <p class="text-gray-500">Đăng nhập để quản lý tài khoản và theo dõi thông tin phòng phù hợp với bạn.</p>
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

            <form method="POST" class="space-y-4" <?= !empty($errors) ? 'data-shake' : '' ?>>
                <div class="auth-field">
                    <label for="login_email" class="block text-sm font-semibold mb-2">Email</label>
                    <input
                        id="login_email"
                        type="email"
                        name="email"
                        required
                        value="<?= e($old['email'] ?? '') ?>"
                        placeholder="example@email.com"
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary outline-none transition <?= !empty($errors['email']) ? 'border-red-300 bg-red-50' : 'border-gray-200' ?>"
                    >
                    <?php if (!empty($errors['email'])): ?>
                        <p class="mt-2 text-sm text-red-600"><?= e($errors['email']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="auth-field">
                    <label for="login_password" class="block text-sm font-semibold mb-2">Mật khẩu</label>
                    <div class="relative">
                        <input
                            id="login_password"
                            type="password"
                            name="password"
                            required
                            class="w-full px-4 py-3 pr-12 border rounded-lg focus:ring-2 focus:ring-primary outline-none transition <?= !empty($errors['password']) ? 'border-red-300 bg-red-50' : 'border-gray-200' ?>"
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

            <div class="mt-6 p-4 bg-blue-50 rounded-lg text-xs text-blue-900">
                <p class="font-semibold mb-1">Tài khoản demo (mật khẩu: 123456):</p>
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
      btn.setAttribute('aria-label', wasHidden ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
    });
  });

  var card = document.querySelector('.auth-card');
  if (card && card.querySelector('form[data-shake]')) {
    requestAnimationFrame(function () {
      card.classList.add('is-shake');
    });
  }
})();
</script>

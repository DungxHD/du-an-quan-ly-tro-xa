<!-- Màn hình đăng nhập: nền "mặt bằng phòng" sống + card có entrance + con mắt chống lệch. -->
<section class="auth-shell min-h-[calc(100vh-4rem)] flex items-center justify-center py-12 bg-gradient-to-br from-primary/5 to-secondary/5">

    <!-- ===== Lớp nền ambient (chỉ trang trí, không cản thao tác) ===== -->
    <div class="auth-ambient" aria-hidden="true">
        <span class="win w1"></span><span class="win w2"></span><span class="win w3"></span>
        <span class="win w4"></span><span class="win w5"></span><span class="win w6"></span>
    </div>

    <div class="relative w-full max-w-md px-4">
        <div class="auth-card bg-white rounded-2xl shadow-2xl p-8 border border-gray-100">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-primary/10 rounded-2xl mb-4 auth-pop">
                    <span class="material-symbols-outlined text-primary text-3xl">login</span>
                </div>
                <h1 class="text-3xl font-bold gradient-text mb-2">Đăng nhập</h1>
                <p class="text-gray-500">Chào mừng bạn quay trở lại với trải nghiệm quản lý trọ mượt hơn</p>
            </div>

            <?php if (!empty($error)): ?>
            <div class="auth-alert mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-center gap-2">
                <span class="material-symbols-outlined">error</span>
                <?= e($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4" <?= !empty($error) ? 'data-shake' : '' ?>>
                <div class="auth-field">
                    <label class="block text-sm font-semibold mb-2">Email</label>
                    <input type="email" name="email" required
                           class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none transition"
                           placeholder="example@email.com">
                </div>

                <!-- ===== Ô MẬT KHẨU + CON MẮT (đã fix lệch: top-[25px]) ===== -->
                <div class="auth-field">
                    <label class="block text-sm font-semibold mb-2">Mật khẩu</label>
                    <div class="relative">
                        <input type="password" name="password" required
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
                </div>
                <!-- ===================================================== -->

                <button type="submit" class="auth-btn w-full py-3 bg-primary text-white rounded-lg font-semibold hover:bg-opacity-90 transition transform hover:scale-[1.02] active:scale-[0.99] shadow-lg">Đăng nhập</button>
            </form>

            <div class="mt-6 p-4 bg-blue-50 rounded-lg text-xs">
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

<!-- ===== STYLE + JS dùng chung cho form đăng nhập ===== -->
<style>
  /* nền lưới mặt bằng phòng + các ô cửa sổ trôi nhẹ (rất mờ, không cản click) */
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

  /* card vào cảnh + icon nảy + nút bấm phản hồi */
  .auth-card{ animation:authIn .6s cubic-bezier(.2,.7,.2,1) both; }
  .auth-pop{ animation:popIn .7s cubic-bezier(.2,.8,.2,1) .15s both; }
  @keyframes authIn{ from{ opacity:0; transform:translateY(18px) scale(.98); } to{ opacity:1; transform:none; } }
  @keyframes popIn{ 0%{ transform:scale(.4); opacity:0; } 60%{ transform:scale(1.12); } 100%{ transform:scale(1); opacity:1; } }

  /* lắc nhẹ khi có lỗi server */
  .auth-card.is-shake{ animation:shake .5s cubic-bezier(.36,.07,.19,.97) both; }
  @keyframes shake{ 10%,90%{ transform:translateX(-2px); } 20%,80%{ transform:translateX(4px); }
                    30%,50%,70%{ transform:translateX(-7px); } 40%,60%{ transform:translateX(7px); } }
  .auth-alert{ animation:alertIn .4s ease both; }
  @keyframes alertIn{ from{ opacity:0; transform:translateY(-6px); } to{ opacity:1; transform:none; } }

  @media (prefers-reduced-motion:reduce){
    .auth-ambient .win,.auth-card,.auth-pop,.auth-card.is-shake,.auth-alert{ animation:none !important; }
  }
</style>

<script>
(function () {
  /* --- Hiện / ẩn mật khẩu: 1 vòng chạy cho mọi nút .toggle-password --- */
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
      /* micro-interaction: icon nẩy khi bấm */
      var svg = btn.querySelector(wasHidden ? '.icon-eye-off' : '.icon-eye');
      if (svg && svg.animate) svg.animate(
        [{ transform:'scale(.55)', opacity:.3 }, { transform:'scale(1.18)', opacity:1 }, { transform:'scale(1)' }],
        { duration:260, easing:'cubic-bezier(.2,.8,.2,1)' });
    });
  });

  /* --- Nếu server trả lỗi: lắc card 1 lần để người dùng chú ý --- */
  var card = document.querySelector('.auth-card');
  if (card && card.querySelector('form[data-shake]')) {
    requestAnimationFrame(function(){ card.classList.add('is-shake'); });
  }
})();
</script>
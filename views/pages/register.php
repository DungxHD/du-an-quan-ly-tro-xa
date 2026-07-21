<!-- Màn hình đăng ký theo hướng UI-first: form rõ ràng, ít bước, dễ quay lại login. -->
<section class="min-h-[calc(100vh-4rem)] flex items-center justify-center py-12 bg-gradient-to-br from-primary/5 to-secondary/5">
    <div class="w-full max-w-md px-4 reveal-scale">
        <div class="bg-white rounded-2xl shadow-2xl p-8 border border-gray-100">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-primary/10 rounded-2xl mb-4">
                    <span class="material-symbols-outlined text-primary text-3xl">person_add</span>
                </div>
                <h1 class="text-3xl font-bold gradient-text mb-2">Đăng ký</h1>
                <p class="text-gray-500">Tạo tài khoản để trải nghiệm NhaTroA</p>
            </div>
            
            <?php if (!empty($error)): ?>
            <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-center gap-2">
                <span class="material-symbols-outlined">error</span>
                <?= e($error) ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
            <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg flex items-center gap-2">
                <span class="material-symbols-outlined">check_circle</span>
                <?= e($success) ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" data-validate class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold mb-2">Họ và tên *</label>
                    <input type="text" name="full_name" required 
                           class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none"
                           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2">Email *</label>
                    <input type="email" name="email" required 
                           class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2">Số điện thoại</label>
                    <input type="tel" name="phone" 
                           class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none"
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2">Mật khẩu * (tối thiểu 6 ký tự)</label>
                    <input type="password" name="password" required minlength="6"
                           class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2">Xác nhận mật khẩu *</label>
                    <input type="password" name="confirm_password" required minlength="6"
                           class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                </div>
                
                <button type="submit" class="w-full py-3 bg-primary text-white rounded-lg font-semibold hover:bg-opacity-90 transition transform hover:scale-[1.02] shadow-lg">
                    Đăng ký
                </button>
            </form>
            
            <div class="mt-6 text-center text-sm">
                <p class="text-gray-600">Đã có tài khoản? 
                    <a href="<?= BASE_URL ?>?page=login" class="text-primary font-semibold hover:underline">Đăng nhập</a>
                </p>
            </div>
        </div>
    </div>
</section>

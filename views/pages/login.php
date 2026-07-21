<!-- Khối đăng nhập tối giản để ưu tiên thao tác vào thẳng dashboard phù hợp vai trò. -->
<section class="min-h-[calc(100vh-4rem)] flex items-center justify-center py-12 bg-gradient-to-br from-primary/5 to-secondary/5">
    <div class="w-full max-w-md px-4">
        <div class="bg-white rounded-2xl shadow-2xl p-8 border">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold gradient-text mb-2">Đăng nhập</h1>
                <p class="text-gray-500">Chào mừng bạn quay trở lại với trải nghiệm quản lý trọ mượt hơn</p>
            </div>
            <?php if (!empty($error)): ?>
            <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg"><?= e($error) ?></div>
            <?php endif; ?>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold mb-2">Email</label>
                    <input type="email" name="email" required class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary outline-none" placeholder="example@email.com">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Mật khẩu</label>
                    <input type="password" name="password" required class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary outline-none">
                </div>
                <button type="submit" class="w-full py-3 bg-primary text-white rounded-lg font-semibold hover:scale-[1.02] transition shadow-lg">Đăng nhập</button>
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

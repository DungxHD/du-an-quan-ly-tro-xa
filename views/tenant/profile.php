<?php
$siteName = RoomModel::getSetting('site_name', 'NhaTroA');
$panelTheme = 'tenant';
$panelActive = 'profile';
$panelTitle = $siteName . ' - Cư dân';
$panelSubtitle = 'Cập nhật hồ sơ và bảo mật tài khoản';
$panelTopLink = ['label' => 'Trang chủ', 'url' => BASE_URL . '?page=home'];
$panelWelcome = 'Xin chào, ' . ($_SESSION['full_name'] ?? 'Cư dân');
require BASE_PATH . 'views/layouts/panel_header.php';
?>
        <div class="max-w-2xl mx-auto">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6">
                <div>
                    <h2 class="text-3xl font-bold">Hồ sơ cá nhân</h2>
                    <p class="text-gray-500 mt-2">Cập nhật thông tin cá nhân của bạn.</p>
                </div>
            </div>
            
            <?php if (!empty($success)): ?>
            <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg flex items-center gap-2">
                <span class="material-symbols-outlined">check_circle</span>
                <?= $success ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
            <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-center gap-2">
                <span class="material-symbols-outlined">error</span>
                <?= $error ?>
            </div>
            <?php endif; ?>
            
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                <div class="flex items-center gap-4 mb-8 pb-8 border-b border-gray-100">
                    <div class="w-20 h-20 bg-gradient-to-br from-primary to-secondary rounded-full flex items-center justify-center text-white text-3xl font-bold">
                        <?= e(mb_substr((string)($user['full_name'] ?? 'U'), 0, 1)) ?>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold"><?= e($user['full_name'] ?? '') ?></h3>
                        <p class="text-gray-500"><?= e($user['email']) ?></p>
                    </div>
                </div>
                
                <!-- Form hồ sơ chỉ xử lý thông tin cơ bản. -->
                <form method="POST" data-profile-form class="space-y-5">
<?= csrf_field() ?>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Họ và tên</label>
                        <input type="text" name="full_name" id="profile_full_name" required
                               value="<?= e($user['full_name'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none">
                        <p id="profile_full_name_error" class="mt-2 text-sm text-red-600 hidden"></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2">Email</label>
                        <input type="email" value="<?= e($user['email']) ?>" disabled
                               class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50">
                        <p class="text-xs text-gray-500 mt-1">Email không thể thay đổi</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2">Số điện thoại</label>
                        <input type="tel" name="phone" id="profile_phone"
                               value="<?= e($user['phone'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none"
                               placeholder="Nhập số điện thoại đang sử dụng">
                        <p id="profile_phone_error" class="mt-2 text-sm text-red-600 hidden"></p>
                    </div>
                    
                    <hr class="my-6">
                    
                    <div>
                        <h4 class="font-bold text-lg">Đổi mật khẩu</h4>
                        <p class="text-sm text-gray-500 mt-1">Để trống nếu chưa muốn đổi. Khi đổi mật khẩu, bạn phải nhập lại mật khẩu hiện tại.</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2">Mật khẩu hiện tại</label>
                        <input type="password" name="current_password" id="profile_current_password"
                               class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none"
                               placeholder="Chỉ nhập khi muốn đổi mật khẩu">
                        <p id="profile_current_password_error" class="mt-2 text-sm text-red-600 hidden"></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2">Mật khẩu mới</label>
                        <input type="password" name="new_password" id="profile_new_password" minlength="6"
                               class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary outline-none"
                               placeholder="Tối thiểu 6 ký tự, gồm ít nhất 1 chữ cái và 1 số">
                        <p id="profile_new_password_error" class="mt-2 text-sm text-red-600 hidden"></p>
                    </div>
                    
                    <button type="submit" class="w-full py-3 bg-primary text-white rounded-lg font-semibold hover:bg-opacity-90 transition transform hover:scale-[1.02]">
                        Lưu thay đổi
                    </button>
                </form>
            </div>
        </div>
<script src="<?= BASE_URL ?>assets/js/account-validators.js"></script>
<script>
(function () {
    var form = document.querySelector('[data-profile-form]');
    if (!form) return;

    var fullNameInput = document.getElementById('profile_full_name');
    var phoneInput = document.getElementById('profile_phone');
    var currentPasswordInput = document.getElementById('profile_current_password');
    var newPasswordInput = document.getElementById('profile_new_password');

    function setFieldError(input, message) {
        if (!input) return;
        var box = document.getElementById(input.id + '_error');
        if (!box) return;
        box.textContent = message;
        box.classList.toggle('hidden', !message);
        input.classList.toggle('border-red-300', !!message);
        input.classList.toggle('bg-red-50', !!message);
    }

    function validateField(input) {
        if (!input) return;
        if (input === fullNameInput) {
            setFieldError(input, validateFullName(input.value));
        } else if (input === phoneInput) {
            if (!input.value.trim()) {
                setFieldError(input, '');
            } else if (!normalizePhoneInput(input.value)) {
                setFieldError(input, 'Số điện thoại không hợp lệ. Chỉ chấp nhận số, khoảng trắng, +84 ở đầu. Không dấu gạch ngang, ngoặc, chữ cái.');
            } else {
                setFieldError(input, '');
            }
        } else if (input === newPasswordInput) {
            if (input.value) {
                setFieldError(input, validatePassword(input.value));
            } else {
                setFieldError(input, '');
            }
        }
    }

    [fullNameInput, phoneInput, newPasswordInput].forEach(function (input) {
        if (input) {
            input.addEventListener('input', function () { validateField(input); });
            input.addEventListener('blur', function () { validateField(input); });
        }
    });

    form.addEventListener('submit', function (event) {
        var hasError = false;

        [fullNameInput, phoneInput, currentPasswordInput, newPasswordInput].forEach(function (input) {
            if (input) setFieldError(input, '');
        });

        var fullNameError = validateFullName(fullNameInput ? fullNameInput.value : '');
        if (fullNameError) {
            setFieldError(fullNameInput, fullNameError);
            hasError = true;
        }

        if (phoneInput && phoneInput.value.trim() && !normalizePhoneInput(phoneInput.value)) {
            setFieldError(phoneInput, 'Số điện thoại không hợp lệ. Chỉ chấp nhận số, khoảng trắng, +84 ở đầu. Không dấu gạch ngang, ngoặc, chữ cái.');
            hasError = true;
        }

        if (newPasswordInput && newPasswordInput.value) {
            var passwordError = validatePassword(newPasswordInput.value);
            if (passwordError) {
                setFieldError(newPasswordInput, passwordError);
                hasError = true;
            } else if (!currentPasswordInput || !currentPasswordInput.value) {
                setFieldError(currentPasswordInput, 'Vui lòng nhập mật khẩu hiện tại để xác nhận đổi mật khẩu.');
                hasError = true;
            }
        }

        if (hasError) {
            event.preventDefault();
            return;
        }
        if (!confirm('Xác nhận lưu thay đổi hồ sơ?')) {
            event.preventDefault();
        }
    });
})();
</script>
<?php require BASE_PATH . 'views/layouts/panel_footer.php'; ?>

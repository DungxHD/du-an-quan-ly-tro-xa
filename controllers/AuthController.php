<?php
class AuthController extends BaseController
{
    /**
     * Tính điểm đến sau đăng nhập dựa trên vai trò và trạng thái gán phòng hiện tại.
     */
    private function resolveAuthenticatedRedirect($user)
    {
        if ((int)($user['role'] ?? 0) === 1) {
            return 'admin';
        }

        return !empty($user['room_id']) ? 'tenant' : 'rooms';
    }

    /**
     * Kiểm tra rate limit đăng nhập dựa trên identifier đã chuẩn hóa.
     * Gi��i hạn 5 lần thử sai trong 5 phút.
     */
    private function checkLoginRateLimit($identifier)
    {
        $key = 'login_attempts_' . md5($identifier . ($_SERVER['REMOTE_ADDR'] ?? ''));
        $attempts = $_SESSION[$key] ?? ['count' => 0, 'first_attempt' => time()];

        if (time() - $attempts['first_attempt'] > 300) {
            $attempts = ['count' => 0, 'first_attempt' => time()];
        }

        $_SESSION[$key] = $attempts;

        return $attempts['count'] < 5;
    }

    /**
     * Ghi nhận lần đăng nhập thất bại.
     */
    private function recordFailedLogin($identifier)
    {
        $key = 'login_attempts_' . md5($identifier . ($_SERVER['REMOTE_ADDR'] ?? ''));
        $attempts = $_SESSION[$key] ?? ['count' => 0, 'first_attempt' => time()];

        if (time() - $attempts['first_attempt'] > 300) {
            $attempts = ['count' => 0, 'first_attempt' => time()];
        }

        $attempts['count']++;
        $_SESSION[$key] = $attempts;
    }

    /**
     * Reset rate limit sau khi đăng nhập thành công.
     */
    private function resetLoginRateLimit($identifier)
    {
        $key = 'login_attempts_' . md5($identifier . ($_SERVER['REMOTE_ADDR'] ?? ''));
        unset($_SESSION[$key]);
    }

    /**
     * Đăng nhập:
     * - Hỗ trợ đăng nhập bằng email hoặc số điện thoại (identifier).
     * - Không tiết lộ tài khoản có tồn tại hay không.
     * - Chỉ tạo session khi xác thực mật khẩu thành công.
     * - Điều hướng đúng dashboard theo role và room_id.
     */
    public function login()
    {
        if (isset($_SESSION['user_id'])) {
            redirectTo((int)($_SESSION['role'] ?? 0) === 1 ? 'admin' : (!empty($_SESSION['room_id']) ? 'tenant' : 'rooms'));
        }

        $errors = [];
        $old = ['identifier' => ''];
        $success = pullFlash('auth_success');
        $action = $_POST['auth_action'] ?? ($_GET['auth_action'] ?? '');

        // Xử lý POST cho login/change-password/forgot-password
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $identifier = trim($_POST['identifier'] ?? '');
            $password = $_POST['password'] ?? '';
            $action = $_POST['auth_action'] ?? 'login';
            $old['identifier'] = $identifier;

            if ($action === 'login') {
                $this->handleLogin($identifier, $password, $errors, $old);
            } elseif ($action === 'change_password') {
                $this->handleChangePassword($identifier, $password, $_POST['new_password'] ?? '', $_POST['confirm_password'] ?? '', $errors, $old);
            } elseif ($action === 'forgot_password') {
                $this->handleForgotPassword($identifier, $errors, $old);
            }
        }

        $pageTitle = 'Đăng nhập - ' . RoomModel::getSetting('site_name', 'NhaTroA');
        $this->renderPublic('views/pages/login.php', compact('errors', 'old', 'success', 'action'), 'login', $pageTitle);
    }

    /**
     * Xử lý đăng nhập.
     */
    private function handleLogin($identifier, $password, &$errors, &$old)
    {
        if ($identifier === '') {
            $errors['identifier'] = 'Vui lòng nhập số điện thoại hoặc email.';
        }

        if ($password === '') {
            $errors['password'] = 'Vui lòng nhập mật khẩu.';
        }

        if (empty($errors)) {
            $normalizedIdentifier = $this->normalizeIdentifierForRateLimit($identifier);

            if (!$this->checkLoginRateLimit($normalizedIdentifier)) {
                $errors['general'] = 'Quá nhiều lần thử đăng nhập. Vui lòng thử lại sau 5 phút.';
            } else {
                $user = $this->findUserByIdentifier($identifier);

                if (!$user || !password_verify($password, $user['password'])) {
                    $this->recordFailedLogin($normalizedIdentifier);
                    $errors['general'] = 'Số điện thoại/email hoặc mật khẩu không đúng.';
                } else {
                    $this->resetLoginRateLimit($normalizedIdentifier);

                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = (int)$user['role'];
                    $_SESSION['room_id'] = $user['room_id'] ?? null;
                    redirectTo($this->resolveAuthenticatedRedirect($user));
                }
            }
        }
    }

    /**
     * Xử lý đổi mật khẩu từ form đăng nhập.
     */
    private function handleChangePassword($identifier, $oldPassword, $newPassword, $confirmPassword, &$errors, &$old)
    {
        if ($identifier === '') {
            $errors['identifier'] = 'Vui lòng nhập số điện thoại hoặc email.';
            return;
        }

        $normalizedIdentifier = $this->normalizeIdentifierForRateLimit($identifier);
        $user = $this->findUserByIdentifier($identifier);

        if (!$user) {
            $errors['identifier'] = 'Tài khoản này chưa tồn tại.';
            $old['show_register_link'] = true;
            $old['identifier'] = $identifier;
            return;
        }

        if ($oldPassword === '') {
            $errors['old_password'] = 'Vui lòng nhập mật khẩu cũ.';
        } elseif (!password_verify($oldPassword, $user['password'])) {
            $errors['old_password'] = 'Mật khẩu cũ không đúng.';
        }

        if ($newPassword === '') {
            $errors['new_password'] = 'Vui lòng nhập mật khẩu mới.';
        } elseif (strlen($newPassword) < 6) {
            $errors['new_password'] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
        }

        if ($confirmPassword === '') {
            $errors['confirm_password'] = 'Vui lòng xác nhận mật khẩu mới.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Xác nhận mật khẩu chưa khớp.';
        }

        if (empty($errors)) {
            UserModel::update((int)$user['id'], ['password' => $newPassword]);
            setFlash('auth_success', 'Đổi mật khẩu thành công. Vui lòng đăng nhập lại.');
            redirectTo('login');
        }
    }

    /**
     * Xử lý quên mật khẩu.
     */
    private function handleForgotPassword($identifier, &$errors, &$old)
    {
        if ($identifier === '') {
            $errors['identifier'] = 'Vui lòng nhập số điện thoại hoặc email.';
            return;
        }

        $user = $this->findUserByIdentifier($identifier);

        if (!$user) {
            $errors['identifier'] = 'Tài khoản này chưa tồn tại.';
            $old['show_register_link'] = true;
            $old['identifier'] = $identifier;
            return;
        }

        // Lưu user_id vào session để bước tiếp theo dùng
        $_SESSION['reset_user_id'] = (int)$user['id'];
        $_SESSION['reset_identifier'] = $identifier;

        // Nếu không có email -> hiển thị liên hệ admin
        if (empty($user['email'])) {
            $errors['no_email'] = true;
            $old['contact_phone'] = RoomModel::getSetting('contact_phone', '');
            return;
        }

        // Có email -> gửi OTP
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $rateLimit = PasswordResetModel::checkSendRateLimit((int)$user['id'], $ip);

        if (!$rateLimit['allowed']) {
            if ($rateLimit['reason'] === 'resend_wait') {
                $errors['otp_resend_wait'] = $rateLimit['wait_seconds'];
            } elseif ($rateLimit['reason'] === 'max_daily') {
                $errors['otp_max_daily'] = true;
            }
            $errors['show_otp_form'] = true;
            return;
        }

        $otp = PasswordResetModel::createOtp((int)$user['id'], $ip);
        PasswordResetModel::recordSendAttempt((int)$user['id'], $ip);

        // Gửi email OTP
        $sent = Mailer::sendOtpEmail($user['email'], $otp, $user['full_name']);

        if (!$sent) {
            $errors['otp_send_failed'] = true;
            $old['contact_phone'] = RoomModel::getSetting('contact_phone', '');
            $errors['show_otp_form'] = true;
            return;
        }

        $errors['otp_sent'] = true;
        $errors['show_otp_form'] = true;
    }

    /**
     * Xử lý xác thực OTP.
     */
    public function verifyOtp()
    {
        if (!isset($_SESSION['reset_user_id'])) {
            redirectTo('login');
        }

        $errors = [];
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $otpInput = trim($_POST['otp'] ?? '');

            if ($otpInput === '') {
                $errors['otp'] = 'Vui lòng nhập mã OTP.';
            } elseif (!preg_match('/^\d{4}$/', $otpInput)) {
                $errors['otp'] = 'Mã OTP phải là 4 chữ số.';
            }

            if (empty($errors)) {
                $result = PasswordResetModel::verifyOtp($_SESSION['reset_user_id'], $otpInput);

                if ($result === true) {
                    $_SESSION['otp_verified'] = true;
                    $errors['otp_verified'] = true;
                } elseif ($result === 'expired') {
                    $errors['otp'] = 'Mã OTP đã hết hạn. Vui lòng gửi lại mã mới.';
                } elseif ($result === 'invalid') {
                    $errors['otp'] = 'Mã OTP không đúng.';
                } elseif ($result === 'max_attempts') {
                    $errors['otp'] = 'Mã OTP không còn hợp lệ. Vui lòng gửi lại mã mới.';
                }
            }
        }

        $pageTitle = 'Xác thực OTP - ' . RoomModel::getSetting('site_name', 'NhaTroA');
        $this->renderPublic('views/pages/verify_otp.php', compact('errors', 'success'), 'verify_otp', $pageTitle);
    }

    /**
     * Xử lý đặt lại mật khẩu sau khi OTP đúng.
     */
    public function resetPassword()
    {
        if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['otp_verified'])) {
            redirectTo('login');
        }

        $errors = [];
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($newPassword === '') {
                $errors['new_password'] = 'Vui lòng nhập mật khẩu mới.';
            } elseif (strlen($newPassword) < 6) {
                $errors['new_password'] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
            }

            if ($confirmPassword === '') {
                $errors['confirm_password'] = 'Vui lòng xác nhận mật khẩu mới.';
            } elseif ($newPassword !== $confirmPassword) {
                $errors['confirm_password'] = 'Xác nhận mật khẩu chưa khớp.';
            }

            if (empty($errors)) {
                PasswordResetModel::updatePassword($_SESSION['reset_user_id'], $newPassword);
                unset($_SESSION['reset_user_id'], $_SESSION['reset_identifier'], $_SESSION['otp_verified']);
                setFlash('auth_success', 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập.');
                redirectTo('login');
            }
        }

        $pageTitle = 'Đặt lại mật khẩu - ' . RoomModel::getSetting('site_name', 'NhaTroA');
        $this->renderPublic('views/pages/reset_password.php', compact('errors', 'success'), 'reset_password', $pageTitle);
    }

    /**
     * Gửi lại OTP.
     */
    public function resendOtp()
    {
        if (!isset($_SESSION['reset_user_id'])) {
            redirectTo('login');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $userId = $_SESSION['reset_user_id'];
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';

            $rateLimit = PasswordResetModel::checkSendRateLimit($userId, $ip);

            if (!$rateLimit['allowed']) {
                if ($rateLimit['reason'] === 'resend_wait') {
                    setFlash('otp_error', 'Vui lòng chờ ' . $rateLimit['wait_seconds'] . ' giây để gửi lại mã OTP.');
                } elseif ($rateLimit['reason'] === 'max_daily') {
                    setFlash('otp_error', 'Bạn đã gửi OTP tối đa 5 lần trong 24 giờ. Vui lòng thử lại sau hoặc liên hệ chủ trọ.');
                }
                redirectTo('verify-otp');
            }

            $otp = PasswordResetModel::createOtp($userId, $ip);
            PasswordResetModel::recordSendAttempt($userId, $ip);

            // Lấy email user
            $user = UserModel::getById($userId);
            if ($user && !empty($user['email'])) {
                $sent = Mailer::sendOtpEmail($user['email'], $otp, $user['full_name']);
                if (!$sent) {
                    setFlash('otp_error', 'Gửi OTP thất bại. Vui lòng liên hệ chủ trọ.');
                    redirectTo('verify-otp');
                }
            }

            setFlash('otp_success', 'Mã OTP mới đã được gửi đến email của bạn.');
            redirectTo('verify-otp');
        }

        redirectTo('verify-otp');
    }

    /**
     * Đăng ký tài khoản tenant mới.
     */
    public function register()
    {
        if (isset($_SESSION['user_id'])) {
            redirectTo((int)($_SESSION['role'] ?? 0) === 1 ? 'admin' : (!empty($_SESSION['room_id']) ? 'tenant' : 'rooms'));
        }

        $errors = [];
        $old = [
            'full_name' => '',
            'email' => '',
            'phone' => '',
        ];

        // Prefill từ identifier nếu có (từ luồng change password/forgot password)
        if (isset($_GET['prefill_identifier'])) {
            $identifier = $_GET['prefill_identifier'];
            if (str_contains($identifier, '@')) {
                $old['email'] = $identifier;
            } else {
                $normalized = UserModel::normalizePhone($identifier);
                if ($normalized) {
                    $old['phone'] = $normalized;
                }
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            $old = [
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone,
            ];

            // Validate họ tên
            if ($fullName === '') {
                $errors['full_name'] = 'Vui lòng nhập họ và tên.';
            } elseif (mb_strlen($fullName) > 100) {
                $errors['full_name'] = 'Họ và tên không được vượt quá 100 ký tự.';
            }

            // Validate email (không bắt buộc)
            if ($email !== '') {
                if (!UserModel::validateEmailStrict($email)) {
                    $errors['email'] = 'Email không đúng định dạng.';
                } elseif (UserModel::emailExists($email)) {
                    $errors['email'] = 'Email đã được sử dụng.';
                }
            }

            // Validate phone
            if ($phone === '') {
                $errors['phone'] = 'Vui lòng nhập số điện thoại.';
            } else {
                $normalizedPhone = UserModel::normalizePhone($phone);
                if (!$normalizedPhone) {
                    $errors['phone'] = 'Số điện thoại không hợp lệ. Chỉ chấp nhận số, dấu cộng ở đầu (+84), không dấu gạch ngang, ngoặc, chữ cái.';
                } elseif (UserModel::phoneExists($normalizedPhone)) {
                    $errors['phone'] = 'Số điện thoại đã được sử dụng.';
                } else {
                    $old['phone'] = $normalizedPhone; // Lưu phone đã chuẩn hóa
                }
            }

            // Validate password
            if ($password === '') {
                $errors['password'] = 'Vui lòng nhập mật khẩu.';
            } elseif (strlen($password) < 6) {
                $errors['password'] = 'Mật khẩu phải có ít nhất 6 ký tự.';
            }

            if ($confirmPassword === '') {
                $errors['confirm_password'] = 'Vui lòng xác nhận mật khẩu.';
            } elseif ($password !== $confirmPassword) {
                $errors['confirm_password'] = 'Xác nhận mật khẩu chưa khớp.';
            }

            if (empty($errors)) {
                try {
                    $normalizedPhone = UserModel::normalizePhone($phone);
                    UserModel::create([
                        'full_name' => $fullName,
                        'email' => $email !== '' ? mb_strtolower($email) : null,
                        'phone' => $normalizedPhone,
                        'password' => $password,
                        'role' => 0,
                        'room_id' => null,
                    ]);
                } catch (Throwable $e) {
                    // Xử lý l��i duplicate key từ database
                    if (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'duplicate key')) {
                        if (str_contains($e->getMessage(), 'phone')) {
                            $errors['phone'] = 'Số điện thoại đã được sử dụng.';
                        } else {
                            $errors['email'] = 'Email đã được sử dụng.';
                        }
                    } else {
                        $errors['general'] = 'Đã có l��i xảy ra. Vui lòng thử lại.';
                    }
                }
            }

            if (empty($errors)) {
                setFlash('auth_success', 'Tạo tài khoản thành công. Vui lòng đăng nhập.');
                redirectTo('login');
            }
        }

        $pageTitle = 'Đăng ký - ' . RoomModel::getSetting('site_name', 'NhaTroA');
        $this->renderPublic('views/pages/register.php', compact('errors', 'old'), 'register', $pageTitle);
    }

    /**
     * Đăng xuất.
     */
    public function logout()
    {
        $_SESSION = [];
        session_destroy();
        redirectTo('home');
    }

    /**
     * Chuẩn hóa identifier cho rate limit.
     * Nếu có @ -> lowercase email.
     * Nếu không -> normalize phone.
     */
    private function normalizeIdentifierForRateLimit($identifier)
    {
        $identifier = trim((string)$identifier);
        if (str_contains($identifier, '@')) {
            return mb_strtolower($identifier);
        }
        $phone = UserModel::normalizePhone($identifier);
        return $phone ?? $identifier;
    }

    /**
     * Tìm user theo identifier (email hoặc phone).
     */
    private function findUserByIdentifier($identifier)
    {
        $identifier = trim((string)$identifier);
        if (str_contains($identifier, '@')) {
            $email = mb_strtolower($identifier);
            return UserModel::findByEmail($email);
        }

        $phone = UserModel::normalizePhone($identifier);
        if (!$phone) {
            return null;
        }

        return UserModel::findByPhone($phone);
    }
}
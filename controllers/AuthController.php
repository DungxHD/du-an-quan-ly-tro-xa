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
        $action = $_GET['auth_action'] ?? ($_POST['auth_action'] ?? '');
        $cp_step = (int)($_GET['cp_step'] ?? $_POST['cp_step'] ?? 1);
        $fp_step = (int)($_GET['fp_step'] ?? $_POST['fp_step'] ?? 1);

        // Lấy flash messages từ resendOtp
        $otpError = pullFlash('otp_error');
        $otpSuccess = pullFlash('otp_success');
        if ($otpError) {
            $errors['otp_info'] = $otpError;
        }
        if ($otpSuccess) {
            $old['otp_resent'] = true;
        }

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
                if ($cp_step === 1) {
                    $this->handleChangePasswordStep1($identifier, $errors, $old);
                } else {
                    $this->handleChangePasswordStep2($identifier, $password, $_POST['new_password'] ?? '', $_POST['confirm_password'] ?? '', $errors, $old);
                }
            } elseif ($action === 'forgot_password') {
                if ($fp_step === 1) {
                    $this->handleForgotPasswordStep1($identifier, $errors, $old);
                } else {
                    $this->handleForgotPasswordStep2($identifier, trim($_POST['otp'] ?? ''), $errors, $old);
                }
            }
        }

        // Chỉ set mặc định nếu handler chưa set (handler sẽ set cp_step/fp_step khi cần chuyển bước)
        if (!isset($old['cp_step'])) $old['cp_step'] = $cp_step;
        if (!isset($old['fp_step'])) $old['fp_step'] = $fp_step;

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
     * Xử lý đổi mật khẩu - Bước 1: Validate identifier.
     */
    private function handleChangePasswordStep1($identifier, &$errors, &$old)
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

        // Identifier hợp lệ, chuyển sang bước 2
        $old['cp_step'] = 2;
        $old['identifier'] = $identifier;
    }

    /**
     * Xử lý đổi mật khẩu - Bước 2: Validate mật khẩu cũ/mới.
     */
    private function handleChangePasswordStep2($identifier, $oldPassword, $newPassword, $confirmPassword, &$errors, &$old)
    {
        if ($identifier === '') {
            $errors['identifier'] = 'Vui lòng nhập số điện thoại hoặc email.';
            $old['cp_step'] = 1;
            return;
        }

        $user = $this->findUserByIdentifier($identifier);

        if (!$user) {
            $errors['identifier'] = 'Tài khoản này chưa tồn tại.';
            $old['show_register_link'] = true;
            $old['identifier'] = $identifier;
            $old['cp_step'] = 1;
            return;
        }

        if ($oldPassword === '') {
            $errors['old_password'] = 'Vui lòng nhập mật khẩu cũ.';
        } elseif (!password_verify($oldPassword, $user['password'])) {
            $errors['old_password'] = 'Mật khẩu cũ không đúng.';
        }

        $newPasswordError = UserModel::validatePassword($newPassword, 'mật khẩu mới');
        if ($newPasswordError !== '') {
            $errors['new_password'] = $newPasswordError;
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

        // Gi�� lại step 2 để hiển thị form mật khẩu
        $old['cp_step'] = 2;
        $old['identifier'] = $identifier;
    }

    /**
     * Xử lý quên mật khẩu - Bước 1: Validate identifier, gửi OTP.
     * Chỉ gửi OTP qua email. Nếu nhập phone -> tìm user bằng phone -> check email.
     */
    private function handleForgotPasswordStep1($identifier, &$errors, &$old)
    {
        if ($identifier === '') {
            $errors['identifier'] = 'Vui lòng nhập số điện thoại hoặc email.';
            return;
        }

        $isEmail = str_contains($identifier, '@');
        $user = null;

        if ($isEmail) {
            // Tìm bằng email
            $user = UserModel::findByEmail(mb_strtolower(trim($identifier)));
        } else {
            // Tìm bằng phone (chuẩn hóa trước)
            $normalizedPhone = UserModel::normalizePhone($identifier);
            if (!$normalizedPhone) {
                $errors['identifier'] = 'Số điện thoại không hợp lệ. Chỉ chấp nhận số, khoảng trắng, +84 ở đầu.';
                return;
            }
            $user = UserModel::findByPhone($normalizedPhone);
        }

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
            $old['fp_step'] = 2;
            $old['identifier'] = $identifier;
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
            $old['fp_step'] = 2;
            $old['identifier'] = $identifier;
            return;
        }

        $otp = PasswordResetModel::createOtp((int)$user['id'], $ip);
        PasswordResetModel::recordSendAttempt((int)$user['id'], $ip);

        // Gửi email OTP
        $sent = Mailer::sendOtpEmail($user['email'], $otp, $user['full_name']);

        if (!$sent) {
            $errors['otp_send_failed'] = true;
            $old['contact_phone'] = RoomModel::getSetting('contact_phone', '');
            $old['fp_step'] = 2;
            $old['identifier'] = $identifier;
            return;
        }

        // Gửi thành công -> chuyển sang bước 2 (nhập OTP), lưu email để hiển thị
        $old['fp_step'] = 2;
        $old['identifier'] = $identifier;
        $old['otp_sent_email'] = $this->maskEmail($user['email']);
        $errors['otp_sent'] = true;
    }

    /**
     * Mask email để hiển thị an toàn: a***@domain.com
     */
    private function maskEmail($email)
    {
        if (!$email || !str_contains($email, '@')) return $email;
        [$local, $domain] = explode('@', $email, 2);
        if (strlen($local) <= 2) {
            $maskedLocal = str_repeat('*', strlen($local));
        } else {
            $maskedLocal = $local[0] . str_repeat('*', strlen($local) - 2) . $local[strlen($local) - 1];
        }
        return $maskedLocal . '@' . $domain;
    }

    /**
     * Xử lý quên mật khẩu - Bước 2: Xác thực OTP (inline trên cùng trang).
     */
    private function handleForgotPasswordStep2($identifier, $otpInput, &$errors, &$old)
    {
        if (!isset($_SESSION['reset_user_id'])) {
            redirectTo('login');
        }

        if ($otpInput === '') {
            $errors['otp'] = 'Vui lòng nhập mã OTP.';
        } elseif (!preg_match('/^\d{4}$/', $otpInput)) {
            $errors['otp'] = 'Mã OTP phải là 4 chữ số.';
        }

        if (empty($errors)) {
            $result = PasswordResetModel::verifyOtp($_SESSION['reset_user_id'], $otpInput);

            if ($result === true) {
                $_SESSION['otp_verified'] = true;
                // Chuyển sang trang reset-password
                redirectTo('reset-password');
            } elseif ($result === 'expired') {
                $errors['otp'] = 'Mã OTP đã hết hạn. Vui lòng gửi lại mã mới.';
            } elseif ($result === 'invalid') {
                $errors['otp'] = 'Mã OTP không đúng.';
            } elseif ($result === 'max_attempts') {
                $errors['otp'] = 'Mã OTP không còn hợp lệ. Vui lòng gửi lại mã mới.';
            }
        }

        // Gi�� lại step 2 để hiển thị form OTP
        $old['fp_step'] = 2;
        $old['identifier'] = $identifier;
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

            $newPasswordError = UserModel::validatePassword($newPassword, 'mật khẩu mới');
            if ($newPasswordError !== '') {
                $errors['new_password'] = $newPasswordError;
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
     * Gửi lại OTP - redirect về form quên mật khẩu step 2.
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
                redirectTo('login', ['auth_action' => 'forgot_password', 'fp_step' => 2]);
            }

            $otp = PasswordResetModel::createOtp($userId, $ip);
            PasswordResetModel::recordSendAttempt($userId, $ip);

            // Lấy email user
            $user = UserModel::getById($userId);
            if ($user && !empty($user['email'])) {
                $sent = Mailer::sendOtpEmail($user['email'], $otp, $user['full_name']);
                if (!$sent) {
                    setFlash('otp_error', 'Gửi OTP thất bại. Vui lòng liên hệ chủ trọ.');
                    redirectTo('login', ['auth_action' => 'forgot_password', 'fp_step' => 2]);
                }
            }

            setFlash('otp_success', 'Mã OTP mới đã được gửi đến email của bạn.');
            redirectTo('login', ['auth_action' => 'forgot_password', 'fp_step' => 2]);
        }

        redirectTo('login', ['auth_action' => 'forgot_password', 'fp_step' => 2]);
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

            // Validate họ tên - bắt buộc, max 100 ký tự, không chỉ khoảng trắng, chỉ cho phép chữ/số/khoảng trắng/'-'/'.'
            $fullNameError = UserModel::validateFullName($fullName);
            if ($fullNameError !== '') {
                $errors['full_name'] = $fullNameError;
            }

            // Validate email - không bắt buộc, nhưng nếu nhập thì phải đúng format strict
            if ($email !== '') {
                if (!UserModel::validateEmailStrict($email)) {
                    $errors['email'] = 'Email không đúng định dạng.';
                } elseif (UserModel::emailExists($email)) {
                    $errors['email'] = 'Email đã được sử dụng.';
                }
            }

            // Validate phone - bắt buộc
            if ($phone === '') {
                $errors['phone'] = 'Vui lòng nhập số điện thoại.';
            } else {
                $normalizedPhone = UserModel::normalizePhone($phone);
                if (!$normalizedPhone) {
                    $errors['phone'] = 'Số điện thoại không hợp lệ. Chỉ chấp nhận: 0xxxxxxxxx (10 số), +84xxxxxxxxx (9 số sau +84, số đầu không phải 0), 84xxxxxxxxx (9 số sau 84, số đầu không phải 0).';
                } elseif (UserModel::phoneExists($normalizedPhone)) {
                    $errors['phone'] = 'Số điện thoại đã được sử dụng.';
                } else {
                    $old['phone'] = $normalizedPhone;
                }
            }

            // Validate password - bắt buộc, min 6 ký tự, có ít nhất 1 chữ và 1 số
            $passwordError = UserModel::validatePassword($password);
            if ($passwordError !== '') {
                $errors['password'] = $passwordError;
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
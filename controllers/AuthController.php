<?php
/**
 * AuthController - Xử lý xác thực: đăng nhập, đăng ký, đăng xuất, đổi mật khẩu, quên mật khẩu (OTP email)
 */
class AuthController extends BaseController
{
    // ==========================================
    // PUBLIC ACTIONS
    // ==========================================

    /**
     * Trang đăng nhập / đổi mật khẩu / quên mật khẩu (multi-step trên cùng 1 trang)
     * GET: hiển thị form | POST: xử lý action (login, change_password, forgot_password)
     */
    public function login(): void
    {
        // Đã đăng nhập -> redirect đúng dashboard
        if (isset($_SESSION['user_id'])) {
            $this->redirectAuthenticated();
        }

        $errors = [];
        $old    = ['identifier' => '', 'cp_step' => 1, 'fp_step' => 1];
        $success = pullFlash('auth_success');
        $action = $_GET['auth_action'] ?? ($_POST['auth_action'] ?? 'login');
        $cp_step = (int)($_GET['cp_step'] ?? $_POST['cp_step'] ?? 1);
        $fp_step = (int)($_GET['fp_step'] ?? $_POST['fp_step'] ?? 1);

        // Flash từ resendOtp
        if ($otpError = pullFlash('otp_error')) {
            $errors['otp_info'] = $otpError;
        }
        if ($otpSuccess = pullFlash('otp_success')) {
            $old['otp_resent'] = true;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $identifier = trim($_POST['identifier'] ?? '');
            $password   = $_POST['password'] ?? '';
            $action     = $_POST['auth_action'] ?? 'login';
            $old['identifier'] = $identifier;

            match ($action) {
                'login'           => $this->handleLogin($identifier, $password, $errors, $old),
                'change_password' => $cp_step === 1
                    ? $this->handleChangePasswordStep1($identifier, $errors, $old)
                    : $this->handleChangePasswordStep2($identifier, $password, $_POST['new_password'] ?? '', $_POST['confirm_password'] ?? '', $errors, $old),
                'forgot_password' => $fp_step === 1
                    ? $this->handleForgotPasswordStep1($identifier, $errors, $old)
                    : $this->handleForgotPasswordStep2($identifier, trim($_POST['otp'] ?? ''), $errors, $old),
                default => null,
            };
        }

        // Giữ step nếu handler chưa set
        $old['cp_step'] = $old['cp_step'] ?? $cp_step;
        $old['fp_step'] = $old['fp_step'] ?? $fp_step;

        $pageTitle = 'Đăng nhập - ' . RoomModel::getSetting('site_name', 'NhaTroA');
        $this->renderPublic('views/pages/login.php', compact('errors', 'old', 'success', 'action'), 'login', $pageTitle);
    }

    /**
     * Xác thực OTP (trang riêng verify_otp.php)
     */
    public function verifyOtp(): void
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
                $this->processOtpResult($result, $errors);
            }
        }

        $pageTitle = 'Xác thực OTP - ' . RoomModel::getSetting('site_name', 'NhaTroA');
        $this->renderPublic('views/pages/verify_otp.php', compact('errors', 'success'), 'verify_otp', $pageTitle);
    }

    /**
     * Đặt lại mật khẩu sau khi OTP đúng
     */
    public function resetPassword(): void
    {
        if (!isset($_SESSION['reset_user_id'], $_SESSION['otp_verified'])) {
            redirectTo('login');
        }

        $errors = [];
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $newPassword     = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            $errors = $this->validateNewPassword($newPassword, $confirmPassword);

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
     * Gửi lại OTP (AJAX/form POST -> redirect về login step 2)
     */
    public function resendOtp(): void
    {
        if (!isset($_SESSION['reset_user_id'])) {
            redirectTo('login');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $userId = $_SESSION['reset_user_id'];
            $ip     = $_SERVER['REMOTE_ADDR'] ?? '';

            $rateLimit = PasswordResetModel::checkSendRateLimit($userId, $ip);
            if (!$rateLimit['allowed']) {
                $this->setResendError($rateLimit);
                redirectTo('login', ['auth_action' => 'forgot_password', 'fp_step' => 2]);
            }

            $otp = PasswordResetModel::createOtp($userId, $ip);
            PasswordResetModel::recordSendAttempt($userId, $ip);

            $user = UserModel::getById($userId);
            if ($user && !empty($user['email'])) {
                if (!Mailer::sendOtpEmail($user['email'], $otp, $user['full_name'])) {
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
     * Đăng ký tài khoản tenant mới
     */
    public function register(): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirectAuthenticated();
        }

        $errors = [];
        $old = ['full_name' => '', 'email' => '', 'phone' => ''];

        // Prefill từ identifier (luồng đổi mật khẩu/quên mật khẩu)
        if (isset($_GET['prefill_identifier'])) {
            $identifier = $_GET['prefill_identifier'];
            if (str_contains($identifier, '@')) {
                $old['email'] = $identifier;
            } else {
                $normalized = UserModel::normalizePhone($identifier);
                if ($normalized) $old['phone'] = $normalized;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $fullName = trim($_POST['full_name'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $phone    = trim($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm  = $_POST['confirm_password'] ?? '';

            $old = compact('fullName', 'email', 'phone');

            $errors = $this->validateRegister($fullName, $email, $phone, $password, $confirm);

            if (empty($errors)) {
                try {
                    $normalizedPhone = UserModel::normalizePhone($phone);
                    UserModel::create([
                        'full_name' => $fullName,
                        'email'     => $email !== '' ? mb_strtolower($email) : null,
                        'phone'     => $normalizedPhone,
                        'password'  => $password,
                        'role'      => 0,
                        'room_id'   => null,
                    ]);
                    setFlash('auth_success', 'Tạo tài khoản thành công. Vui lòng đăng nhập.');
                    redirectTo('login');
                } catch (Throwable $e) {
                    $this->handleRegisterDbError($e, $errors);
                }
            }
        }

        $pageTitle = 'Đăng ký - ' . RoomModel::getSetting('site_name', 'NhaTroA');
        $this->renderPublic('views/pages/register.php', compact('errors', 'old'), 'register', $pageTitle);
    }

    /**
     * Đăng xuất
     */
    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        redirectTo('home');
    }

    // ==========================================
    // LOGIN HANDLERS
    // ==========================================

    private function handleLogin(string $identifier, string $password, array &$errors, array &$old): void
    {
        if ($identifier === '') $errors['identifier'] = 'Vui lòng nhập số điện thoại hoặc email.';
        if ($password === '')   $errors['password']   = 'Vui lòng nhập mật khẩu.';

        if (!empty($errors)) return;

        $normalized = $this->normalizeIdentifierForRateLimit($identifier);

        if (!$this->checkLoginRateLimit($normalized)) {
            $errors['general'] = 'Quá nhiều lần thử đăng nhập. Vui lòng thử lại sau 5 phút.';
            return;
        }

        $user = $this->findUserByIdentifier($identifier);
        if (!$user || !password_verify($password, $user['password'])) {
            $this->recordFailedLogin($normalized);
            $errors['general'] = 'Số điện thoại/email hoặc mật khẩu không đúng.';
            return;
        }

        // Thành công
        $this->resetLoginRateLimit($normalized);
        $this->createUserSession($user);
        $this->redirectAuthenticated($user);
    }

    // ==========================================
    // CHANGE PASSWORD HANDLERS (2 steps)
    // ==========================================

    private function handleChangePasswordStep1(string $identifier, array &$errors, array &$old): void
    {
        if ($identifier === '') {
            $errors['identifier'] = 'Vui lòng nhập số điện thoại hoặc email.';
            return;
        }

        $user = $this->findUserByIdentifier($identifier);
        if (!$user) {
            $errors['identifier'] = 'Tài khoản này chưa tồn tại.';
            $old['show_register_link'] = true;
            return;
        }

        $old['cp_step'] = 2;
    }

    private function handleChangePasswordStep2(string $identifier, string $oldPass, string $newPass, string $confirmPass, array &$errors, array &$old): void
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
            $old['cp_step'] = 1;
            return;
        }

        if ($oldPass === '')           $errors['old_password']     = 'Vui lòng nhập mật khẩu cũ.';
        elseif (!password_verify($oldPass, $user['password'])) $errors['old_password'] = 'Mật khẩu cũ không đúng.';

        $newPassError = UserModel::validatePassword($newPass, 'mật khẩu mới');
        if ($newPassError) $errors['new_password'] = $newPassError;

        if ($confirmPass === '')       $errors['confirm_password'] = 'Vui lòng xác nhận mật khẩu mới.';
        elseif ($newPass !== $confirmPass) $errors['confirm_password'] = 'Xác nhận mật khẩu chưa khớp.';

        if (!empty($errors)) {
            $old['cp_step'] = 2;
            return;
        }

        UserModel::update((int)$user['id'], ['password' => $newPass]);
        setFlash('auth_success', 'Đổi mật khẩu thành công. Vui lòng đăng nhập lại.');
        redirectTo('login');
    }

    // ==========================================
    // FORGOT PASSWORD HANDLERS (2 steps)
    // ==========================================

    private function handleForgotPasswordStep1(string $identifier, array &$errors, array &$old): void
    {
        if ($identifier === '') {
            $errors['identifier'] = 'Vui lòng nhập số điện thoại hoặc email.';
            return;
        }

        $isEmail = str_contains($identifier, '@');
        if ($isEmail) {
            $user = UserModel::findByEmail(mb_strtolower(trim($identifier)));
        } else {
            $normalized = UserModel::normalizePhone($identifier);
            $user = $normalized ? UserModel::findByPhone($normalized) : null;
            if (!$user) {
                $errors['identifier'] = 'Số điện thoại không hợp lệ. Chỉ chấp nhận số, khoảng trắng, +84 ở đầu.';
                return;
            }
        }

        if (!$user) {
            $errors['identifier'] = 'Tài khoản này chưa tồn tại.';
            $old['show_register_link'] = true;
            return;
        }

        $_SESSION['reset_user_id']      = (int)$user['id'];
        $_SESSION['reset_identifier']   = $identifier;

        if (empty($user['email'])) {
            $errors['no_email'] = true;
            $old['contact_phone'] = RoomModel::getSetting('contact_phone', '');
            $old['fp_step'] = 2;
            return;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $rateLimit = PasswordResetModel::checkSendRateLimit((int)$user['id'], $ip);
        if (!$rateLimit['allowed']) {
            $this->setRateLimitError($rateLimit, $errors);
            $old['fp_step'] = 2;
            return;
        }

        $otp = PasswordResetModel::createOtp((int)$user['id'], $ip);
        PasswordResetModel::recordSendAttempt((int)$user['id'], $ip);

        if (!Mailer::sendOtpEmail($user['email'], $otp, $user['full_name'])) {
            $errors['otp_send_failed'] = true;
            $old['contact_phone'] = RoomModel::getSetting('contact_phone', '');
            $old['fp_step'] = 2;
            return;
        }

        $old['fp_step'] = 2;
        $old['otp_sent_email'] = $this->maskEmail($user['email']);
        $errors['otp_sent'] = true;
    }

    private function handleForgotPasswordStep2(string $identifier, string $otpInput, array &$errors, array &$old): void
    {
        if (!isset($_SESSION['reset_user_id'])) {
            redirectTo('login');
        }

        if ($otpInput === '')        $errors['otp'] = 'Vui lòng nhập mã OTP.';
        elseif (!preg_match('/^\d{4}$/', $otpInput)) $errors['otp'] = 'Mã OTP phải là 4 chữ số.';

        if (empty($errors)) {
            $result = PasswordResetModel::verifyOtp($_SESSION['reset_user_id'], $otpInput);
            $this->processOtpResult($result, $errors);
        }

        $old['fp_step'] = 2;
    }

    // ==========================================
    // PRIVATE HELPERS - RATE LIMIT & SESSION
    // ==========================================

    private function checkLoginRateLimit(string $key): bool
    {
        $attempts = $_SESSION[$key] ?? ['count' => 0, 'first' => time()];
        if (time() - $attempts['first'] > 300) $attempts = ['count' => 0, 'first' => time()];
        $_SESSION[$key] = $attempts;
        return $attempts['count'] < 5;
    }

    private function recordFailedLogin(string $key): void
    {
        $attempts = $_SESSION[$key] ?? ['count' => 0, 'first' => time()];
        if (time() - $attempts['first'] > 300) $attempts = ['count' => 0, 'first' => time()];
        $_SESSION[$key] = ['count' => $attempts['count'] + 1, 'first' => $attempts['first']];
    }

    private function resetLoginRateLimit(string $key): void
    {
        unset($_SESSION[$key]);
    }

    private function normalizeIdentifierForRateLimit(string $identifier): string
    {
        $identifier = trim($identifier);
        return str_contains($identifier, '@') ? mb_strtolower($identifier) : (UserModel::normalizePhone($identifier) ?? $identifier);
    }

    private function findUserByIdentifier(string $identifier): ?array
    {
        $identifier = trim($identifier);
        return str_contains($identifier, '@')
            ? UserModel::findByEmail(mb_strtolower($identifier))
            : (UserModel::normalizePhone($identifier) ? UserModel::findByPhone(UserModel::normalizePhone($identifier)) : null);
    }

    // ==========================================
    // PRIVATE HELPERS - PASSWORD RESET FLOW
    // ==========================================

    private function processOtpResult($result, array &$errors): void
    {
        match ($result) {
            true          => $_SESSION['otp_verified'] = true,
            'expired'     => $errors['otp'] = 'Mã OTP đã hết hạn. Vui lòng gửi lại mã mới.',
            'invalid'     => $errors['otp'] = 'Mã OTP không đúng.',
            'max_attempts'=> $errors['otp'] = 'Mã OTP không còn hợp lệ. Vui lòng gửi lại mã mới.',
            default       => $errors['otp'] = 'Mã OTP không hợp lệ.',
        };
    }

    private function validateNewPassword(string $newPass, string $confirmPass): array
    {
        $errors = [];
        $newPassError = UserModel::validatePassword($newPass, 'mật khẩu mới');
        if ($newPassError) $errors['new_password'] = $newPassError;
        if ($confirmPass === '') $errors['confirm_password'] = 'Vui lòng xác nhận mật khẩu mới.';
        elseif ($newPass !== $confirmPass) $errors['confirm_password'] = 'Xác nhận mật khẩu chưa khớp.';
        return $errors;
    }

    private function setRateLimitError(array $rateLimit, array &$errors): void
    {
        if ($rateLimit['reason'] === 'resend_wait') {
            $errors['otp_resend_wait'] = $rateLimit['wait_seconds'];
        } elseif ($rateLimit['reason'] === 'max_daily') {
            $errors['otp_max_daily'] = true;
        }
    }

    private function setResendError(array $rateLimit): void
    {
        if ($rateLimit['reason'] === 'resend_wait') {
            setFlash('otp_error', 'Vui lòng chờ ' . $rateLimit['wait_seconds'] . ' giây để gửi lại mã OTP.');
        } elseif ($rateLimit['reason'] === 'max_daily') {
            setFlash('otp_error', 'Bạn đã gửi OTP tối đa 5 lần trong 24 giờ. Vui lòng thử lại sau hoặc liên hệ chủ trọ.');
        }
    }

    private function maskEmail(string $email): string
    {
        if (!$email || !str_contains($email, '@')) return $email;
        [$local, $domain] = explode('@', $email, 2);
        return strlen($local) <= 2
            ? str_repeat('*', strlen($local)) . '@' . $domain
            : $local[0] . str_repeat('*', strlen($local) - 2) . $local[-1] . '@' . $domain;
    }

    // ==========================================
    // REGISTER HELPERS
    // ==========================================

    private function validateRegister(string $fullName, string $email, string $phone, string $password, string $confirm): array
    {
        $errors = [];

        if ($fnErr = UserModel::validateFullName($fullName))          $errors['full_name'] = $fnErr;
        if ($email !== '' && !UserModel::validateEmailStrict($email)) $errors['email'] = 'Email không đúng định dạng.';
        if ($email !== '' && UserModel::emailExists($email))          $errors['email'] = 'Email đã được sử dụng.';

        if ($phone === '') $errors['phone'] = 'Vui lòng nhập số điện thoại.';
        else {
            $normalized = UserModel::normalizePhone($phone);
            if (!$normalized) $errors['phone'] = 'Số điện thoại không hợp lệ. Chỉ chấp nhận: 0xxxxxxxxx (10 số), +84xxxxxxxxx (9 số sau +84, số đầu không phải 0), 84xxxxxxxxx (9 số sau 84, số đầu không phải 0).';
            elseif (UserModel::phoneExists($normalized)) $errors['phone'] = 'Số điện thoại đã được sử dụng.';
        }

        if ($passErr = UserModel::validatePassword($password)) $errors['password'] = $passErr;
        if ($confirm === '')       $errors['confirm_password'] = 'Vui lòng xác nhận mật khẩu.';
        elseif ($password !== $confirm) $errors['confirm_password'] = 'Xác nhận mật khẩu chưa khớp.';

        return $errors;
    }

    private function handleRegisterDbError(Throwable $e, array &$errors): void
    {
        $msg = $e->getMessage();
        if (str_contains($msg, 'Duplicate entry') || str_contains($msg, 'duplicate key')) {
            $errors[str_contains($msg, 'phone') ? 'phone' : 'email'] = str_contains($msg, 'phone')
                ? 'Số điện thoại đã được sử dụng.'
                : 'Email đã được sử dụng.';
        } else {
            $errors['general'] = 'Đã có lỗi xảy ra. Vui lòng thử lại.';
        }
    }

    // ==========================================
    // SESSION & REDIRECT HELPERS
    // ==========================================

    private function createUserSession(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id']   = (int)$user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email']     = $user['email'];
        $_SESSION['role']      = (int)$user['role'];
        $_SESSION['room_id']   = $user['room_id'] ?? null;
    }
    }
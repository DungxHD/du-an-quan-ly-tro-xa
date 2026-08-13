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
     * Kiểm tra rate limit đăng nhập.
     * Giới hạn 5 lần thử sai trong 5 phút.
     */
    private function checkLoginRateLimit($email)
    {
        $key = 'login_attempts_' . md5($email . ($_SERVER['REMOTE_ADDR'] ?? ''));
        $attempts = $_SESSION[$key] ?? ['count' => 0, 'first_attempt' => time()];

        // Reset nếu đã quá 5 phút
        if (time() - $attempts['first_attempt'] > 300) {
            $attempts = ['count' => 0, 'first_attempt' => time()];
        }

        $_SESSION[$key] = $attempts;

        return $attempts['count'] < 5;
    }

    /**
     * Ghi nhận lần đăng nhập thất bại.
     */
    private function recordFailedLogin($email)
    {
        $key = 'login_attempts_' . md5($email . ($_SERVER['REMOTE_ADDR'] ?? ''));
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
    private function resetLoginRateLimit($email)
    {
        $key = 'login_attempts_' . md5($email . ($_SERVER['REMOTE_ADDR'] ?? ''));
        unset($_SESSION[$key]);
    }

    /**
     * Đăng nhập:
     * - Không tiết lộ email có tồn tại hay không.
     * - Chỉ tạo session khi xác thực mật khẩu thành công.
     * - Điều hướng đúng dashboard theo role và room_id.
     */
    public function login()
    {
        if (isset($_SESSION['user_id'])) { redirectTo((int)($_SESSION['role'] ?? 0) === 1 ? 'admin' : (!empty($_SESSION['room_id']) ? 'tenant' : 'rooms')); }

        $errors = [];
        $old = ['email' => ''];
        $success = pullFlash('auth_success');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
             verify_csrf();
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $old['email'] = $email;

            if ($email === '') {
                $errors['email'] = 'Vui lòng nhập email.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Email chưa đúng định dạng.';
            }

            if ($password === '') {
                $errors['password'] = 'Vui lòng nhập mật khẩu.';
            }

            if (empty($errors)) {
                // BƯỚC 1: Kiểm tra có bị giới hạn không (đã thử sai 5 lần trong 5 phút?)
                if (!$this->checkLoginRateLimit($email)) {
                    // Nếu đã thử quá 5 lần → chặn, không cho thử tiếp
                    $errors['general'] = 'Quá nhiều lần thử đăng nhập. Vui lòng thử lại sau 5 phút.';
                } else {
                    // BƯỚC 2: Chưa bị giới hạn → kiểm tra email/password bình thường
                    $user = UserModel::findByEmail($email);

                    if (!$user || !password_verify($password, $user['password'])) {
                        // BƯỚC 2a: Sai email hoặc mật khẩu → ghi nhận thêm 1 lần thất bại
                        $this->recordFailedLogin($email);
                        $errors['general'] = 'Sai email hoặc mật khẩu.';
                    } else {
                        // BƯỚC 2b: Đúng email và mật khẩu → reset bộ đếm thất bại
                        $this->resetLoginRateLimit($email);

                        // Tạo session mới (giữ nguyên phần này, không đổi)
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

        $pageTitle = 'Đăng nhập - ' . RoomModel::getSetting('site_name', 'NhaTroA');
        $this->renderPublic('views/pages/login.php', compact('errors', 'old', 'success'), 'login', $pageTitle);
    }

    /**
     * Đăng ký tài khoản tenant mới:
     * - Validate cả client/server.
     * - Kiểm tra email trùng bằng bảng users.
     * - Không ghi cột status vì schema mới đã loại bỏ.
     */
    public function register()
    {
        if (isset($_SESSION['user_id'])) { redirectTo((int)($_SESSION['role'] ?? 0) === 1 ? 'admin' : (!empty($_SESSION['room_id']) ? 'tenant' : 'rooms')); }

        $errors = [];
        $old = [
            'full_name' => '',
            'email' => '',
            'phone' => '',
        ];

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

            if ($fullName === '') {
                $errors['full_name'] = 'Vui lòng nhập họ và tên.';
            } elseif (mb_strlen($fullName) > 100) {
                $errors['full_name'] = 'Họ và tên không được vượt quá 100 ký tự.';
            }

            if ($email === '') {
                $errors['email'] = 'Vui lòng nhập email.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Email chưa đúng định dạng.';
            } elseif (mb_strlen($email) > 150) {
                $errors['email'] = 'Email không được vượt quá 150 ký tự.';
            } elseif (UserModel::emailExists($email)) {
                $errors['email'] = 'Email đã được sử dụng';
            }

            if ($phone === '') {
                $errors['phone'] = 'Vui lòng nhập số điện thoại.';
            } elseif (mb_strlen($phone) > 20) {
                $errors['phone'] = 'Số điện thoại không được vượt quá 20 ký tự.';
            } elseif (!preg_match('/^[0-9+\-\s]{8,15}$/', $phone)) {
                $errors['phone'] = 'Số điện thoại chưa đúng định dạng.';
            }

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
                    UserModel::create([
                        'full_name' => $fullName,
                        'email' => $email,
                        'phone' => $phone,
                        'password' => $password,
                        'role' => 0,
                        'room_id' => null,
                    ]);
                } catch (Throwable $e) {
                    $errors['email'] = 'Email đã được sử dụng';
                }
            }

            if (empty($errors)) {
                setFlash('auth_success', 'Tạo tài khoản thành công');
                redirectTo('login');
            }
        }

        $pageTitle = 'Đăng ký - ' . RoomModel::getSetting('site_name', 'NhaTroA');
        $this->renderPublic('views/pages/register.php', compact('errors', 'old'), 'register', $pageTitle);
    }

    public function logout()
    {
        $_SESSION = [];
        session_destroy();
        redirectTo('home');
    }
}

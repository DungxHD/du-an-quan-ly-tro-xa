<?php
class AuthController extends BaseController {
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
     * Đăng nhập:
     * - Không tiết lộ email có tồn tại hay không.
     * - Chỉ tạo session khi xác thực mật khẩu thành công.
     * - Điều hướng đúng dashboard theo role và room_id.
     */
    public function login() {
        $errors = [];
        $old = ['email' => ''];
        $success = pullFlash('auth_success');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                $user = UserModel::findByEmail($email);
                if (!$user || !password_verify($password, $user['password'])) {
                    $errors['general'] = 'Sai email hoặc mật khẩu.';
                } else {
                    // Tạo session mới sau khi đăng nhập để giảm rủi ro session fixation.
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

        $pageTitle = 'Đăng nhập - ' . RoomModel::getSetting('site_name', 'NhaTroA');
        $this->renderPublic('views/pages/login.php', compact('errors', 'old', 'success'), 'login', $pageTitle);
    }

    /**
     * Đăng ký tài khoản tenant mới:
     * - Validate cả client/server.
     * - Kiểm tra email trùng bằng bảng users.
     * - Không ghi cột status vì schema mới đã loại bỏ.
     */
    public function register() {
        $errors = [];
        $old = [
            'full_name' => '',
            'email' => '',
            'phone' => '',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            }

            if ($email === '') {
                $errors['email'] = 'Vui lòng nhập email.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Email chưa đúng định dạng.';
            } elseif (UserModel::emailExists($email)) {
                $errors['email'] = 'Email đã được sử dụng';
            }

            if ($phone === '') {
                $errors['phone'] = 'Vui lòng nhập số điện thoại.';
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
                UserModel::create([
                    'full_name' => $fullName,
                    'email' => $email,
                    'phone' => $phone,
                    'password' => $password,
                    'role' => 0,
                    'room_id' => null,
                ]);

                setFlash('auth_success', 'Tạo tài khoản thành công');
                redirectTo('login');
            }
        }

        $pageTitle = 'Đăng ký - ' . RoomModel::getSetting('site_name', 'NhaTroA');
        $this->renderPublic('views/pages/register.php', compact('errors', 'old'), 'register', $pageTitle);
    }
    
    public function logout() {
        $_SESSION = [];
        session_destroy();
        redirectTo('home');
    }
}

<?php
class AuthController extends BaseController {
    /**
     * Đăng nhập nhẹ, ưu tiên điều hướng đúng vai trò để UI vào thẳng dashboard.
     */
    public function login() {
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            if (empty($email) || empty($password)) {
                $error = 'Vui lòng nhập đầy đủ thông tin!';
            } else {
                $user = RoomModel::findUserByEmail($email);
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    if ($user['role'] == 1) {
                        header('Location: ' . BASE_URL . '?page=admin');
                    } else {
                        header('Location: ' . BASE_URL . '?page=tenant');
                    }
                    exit;
                } else {
                    $error = 'Email hoặc mật khẩu không đúng!';
                }
            }
        }
        $pageTitle = 'Đăng nhập - ' . RoomModel::getSetting('site_name', 'NhaTroA');
        $this->renderPublic('views/pages/login.php', compact('error'), 'login', $pageTitle);
    }

    public function register() {
        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($fullName === '' || $email === '' || $password === '') {
                $error = 'Vui lòng nhập đầy đủ các trường bắt buộc.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Email chưa đúng định dạng.';
            } elseif (strlen($password) < 6) {
                $error = 'Mật khẩu phải có ít nhất 6 ký tự.';
            } elseif ($password !== $confirmPassword) {
                $error = 'Xác nhận mật khẩu chưa khớp.';
            } elseif (UserModel::findByEmail($email)) {
                $error = 'Email này đã tồn tại trong hệ thống.';
            } else {
                UserModel::create([
                    'full_name' => $fullName,
                    'email' => $email,
                    'phone' => $phone,
                    'password' => $password,
                    'role' => 0,
                ]);

                $success = 'Tạo tài khoản thành công. Bạn có thể đăng nhập ngay.';
                $_POST = [];
            }
        }

        $pageTitle = 'Đăng ký - ' . RoomModel::getSetting('site_name', 'NhaTroA');
        $this->renderPublic('views/pages/register.php', compact('error', 'success'), 'register', $pageTitle);
    }
    
    public function logout() {
        session_destroy();
        header('Location: ' . BASE_URL . '?page=home');
        exit;
    }
}

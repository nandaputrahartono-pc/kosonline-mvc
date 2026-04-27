<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AdminModel;
use App\Models\UserModel;

final class AuthController extends Controller
{
    private UserModel $userModel;
    private AdminModel $adminModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->adminModel = new AdminModel();
    }

    public function login(): void
    {
        if ($this->isPost() && isset($_POST['login'])) {
            $identifier = trim((string) $_POST['identifier']);
            $password = (string) $_POST['password'];

            // 1. Cek tabel Admin (menggunakan username)
            $admin = $this->adminModel->findByCredentials($identifier, $password);
            
            if ($admin !== null) {
                $_SESSION['status'] = 'login_admin';
                $_SESSION['admin_name'] = $admin['nama_lengkap'] ?? $admin['username'];

                set_flash('success', 'Login Berhasil');
                redirect_to('/admin/dashboard');
            }

            // 2. Cek tabel Users (menggunakan email)
            $user = $this->userModel->findByCredentials($identifier, $password);

            if ($user !== null) {
                $_SESSION['id_user'] = $user['id_user'];
                $_SESSION['nama'] = $user['nama_lengkap'];
                $_SESSION['status'] = 'login_user';

                set_flash('success', 'Login Berhasil! Selamat datang.');
                redirect_to('/member/dashboard');
            }

            // 3. Jika keduanya gagal
            set_flash('error', 'Username/Email atau Password Salah!');
            redirect_to('/login');
        }

        $this->render('auth/login', [
            'successMessage' => flash('success'),
            'errorMessage' => flash('error'),
        ]);
    }

    public function memberLogout(): void
    {
        $this->destroySession();
        redirect_to('/login');
    }

    public function adminLogout(): void
    {
        $this->destroySession();
        redirect_to('/login');
    }

    /**
     * Hapus session dan cookie secara aman.
     */
    private function destroySession(): void
    {
        session_unset();

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool) $params['secure'],
                (bool) $params['httponly']
            );
        }

        session_destroy();
    }
}

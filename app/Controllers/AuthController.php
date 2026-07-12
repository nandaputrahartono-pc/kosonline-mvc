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
            $identifier = trim((string) ($_POST['identifier'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            $fieldErrors = [];
            if ($identifier === '') {
                $fieldErrors['identifier'] = 'Email atau username wajib diisi.';
            }

            if ($password === '') {
                $fieldErrors['password'] = 'Password wajib diisi.';
            }

            if ($fieldErrors !== []) {
                $this->failLogin($fieldErrors, $identifier);
            }

            $admin = $this->adminModel->findByUsername($identifier);
            if ($admin !== null) {
                if (!$this->adminModel->isPasswordValid($admin, $password)) {
                    $this->failLogin(['password' => 'Password admin tidak sesuai.'], $identifier);
                }

                $admin = $this->adminModel->findByCredentials($identifier, $password);
                if ($admin === null) {
                    set_flash('error', 'Login admin gagal. Silakan coba lagi.');
                    redirect_to('/login');
                }

                session_regenerate_id(true);
                $_SESSION['status'] = 'login_admin';
                $_SESSION['admin_name'] = $admin['nama_lengkap'] ?? $admin['username'];

                set_flash('success', 'Login Berhasil');
                redirect_to('/admin/dashboard');
            }

            $user = $this->userModel->findByIdentifier($identifier);
            if ($user === null) {
                $this->failLogin(['identifier' => 'Email atau username tidak terdaftar.'], $identifier);
            }

            if (!$this->userModel->isPasswordValid($user, $password)) {
                $this->failLogin(['password' => 'Password tidak sesuai untuk akun ini.'], $identifier);
            }

            $user = $this->userModel->findByCredentials($identifier, $password);
            if ($user === null) {
                set_flash('error', 'Login gagal. Silakan coba lagi.');
                redirect_to('/login');
            }

            session_regenerate_id(true);
            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['nama'] = $user['nama_lengkap'];
            $_SESSION['foto_profil'] = $user['foto_profil'] ?? 'default.jpg';
            $_SESSION['status'] = 'login_user';

            set_flash('success', 'Login Berhasil! Selamat datang.');
            redirect_to('/member/dashboard');
        }

        $loginErrors = [
            'identifier' => flash('login_error_identifier'),
            'password' => flash('login_error_password'),
        ];
        $oldIdentifier = (string) old('identifier', '');
        clear_old_input();

        $this->render('auth/login', [
            'successMessage' => flash('success'),
            'errorMessage' => flash('error'),
            'loginErrors' => $loginErrors,
            'oldIdentifier' => $oldIdentifier,
        ]);
    }

    public function register(): void
    {
        if ($this->isPost() && isset($_POST['register'])) {
            $namaLengkap = trim((string) ($_POST['nama_lengkap'] ?? ''));
            $username = strtolower(trim((string) ($_POST['username'] ?? '')));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $password = (string) ($_POST['password'] ?? '');
            $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');
            $noHp = trim((string) ($_POST['no_hp'] ?? ''));

            if ($namaLengkap === '' || $username === '' || $noHp === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                set_flash('error', 'Nama, username, email valid, dan nomor handphone wajib diisi.');
                redirect_to('/login#register');
            }

            if (!preg_match('/^[0-9]{8,15}$/', $noHp)) {
                set_flash('error', 'Nomor handphone hanya boleh angka (8–15 digit).');
                redirect_to('/login#register');
            }

            if (!preg_match('/^[a-z0-9_]{3,30}$/', $username)) {
                set_flash('error', 'Username hanya boleh huruf kecil, angka, underscore, minimal 3 karakter.');
                redirect_to('/login#register');
            }

            if ($this->userModel->usernameExists($username)) {
                set_flash('error', 'Username sudah dipakai, coba username lain.');
                redirect_to('/login#register');
            }

            if ($this->userModel->emailExists($email)) {
                set_flash('error', 'Email sudah terdaftar!');
                redirect_to('/login#register');
            }

            if (strlen($password) < 6) {
                set_flash('error', 'Password minimal 6 karakter!');
                redirect_to('/login#register');
            }

            if (!hash_equals($password, $passwordConfirmation)) {
                set_flash('error', 'Konfirmasi password tidak sama.');
                redirect_to('/login#register');
            }

            $data = [
                'nama_lengkap' => $namaLengkap,
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'no_hp' => $noHp,
                'foto_profil' => 'default.jpg',
            ];

            try {
                $userId = $this->userModel->create($data);
                
                if ($userId > 0) {
                    set_flash('success', 'Registrasi berhasil! Silakan login.');
                    redirect_to('/login');
                } else {
                    set_flash('error', 'Gagal membuat akun, silakan coba lagi.');
                    redirect_to('/login#register');
                }
            } catch (\Throwable $throwable) {
                set_flash('error', 'Terjadi kesalahan sistem.');
                redirect_to('/login#register');
            }
        } else {
            redirect_to('/login');
        }
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

    private function failLogin(array $fieldErrors, string $identifier): never
    {
        remember_old_input(['identifier' => $identifier]);

        foreach ($fieldErrors as $field => $message) {
            set_flash('login_error_' . $field, (string) $message);
        }

        redirect_to('/login');
    }
}

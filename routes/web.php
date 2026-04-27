<?php

declare(strict_types=1);

use App\Controllers\AdminDashboardController;
use App\Controllers\AdminKostController;
use App\Controllers\AdminLocationController;
use App\Controllers\AdminMessageController;
use App\Controllers\AdminPaymentController;
use App\Controllers\AdminRoomController;
use App\Controllers\AdminUserController;
use App\Controllers\AuthController;
use App\Controllers\ContactController;
use App\Controllers\HomeController;
use App\Controllers\MapController;
use App\Controllers\MemberController;
use App\Controllers\RoomController;
use App\Core\Router;

/** @var Router $router */

$router->get('/', [HomeController::class, 'index']);
$router->get('/rooms', [RoomController::class, 'index']);
$router->get('/contact', [ContactController::class, 'index']);
$router->post('/contact', [ContactController::class, 'index']);
$router->get('/map', [MapController::class, 'index']);

// Unified Login Route
$router->get('/login', [AuthController::class, 'login']);
$router->post('/login', [AuthController::class, 'login']);

// Logout Routes
$router->get('/logout', [AuthController::class, 'memberLogout']);
$router->get('/admin/logout', [AuthController::class, 'adminLogout']);

$router->get('/member/dashboard', [MemberController::class, 'dashboard']);
$router->post('/member/dashboard', [MemberController::class, 'dashboard']);

$router->get('/admin', [AdminDashboardController::class, 'index']);
$router->get('/admin/dashboard', [AdminDashboardController::class, 'dashboard']);

$router->get('/admin/kost/create', [AdminKostController::class, 'create']);
$router->post('/admin/kost/create', [AdminKostController::class, 'create']);
$router->get('/admin/kost/edit', [AdminKostController::class, 'edit']);
$router->post('/admin/kost/edit', [AdminKostController::class, 'edit']);
$router->get('/admin/kost/delete', [AdminKostController::class, 'delete']);

$router->get('/admin/rooms/create', [AdminRoomController::class, 'create']);
$router->post('/admin/rooms/create', [AdminRoomController::class, 'create']);
$router->get('/admin/rooms/edit', [AdminRoomController::class, 'edit']);
$router->post('/admin/rooms/edit', [AdminRoomController::class, 'edit']);
$router->get('/admin/rooms/delete', [AdminRoomController::class, 'delete']);
$router->get('/admin/rooms/toggle-status', [AdminRoomController::class, 'toggleStatus']);

$router->get('/admin/users/create', [AdminUserController::class, 'create']);
$router->post('/admin/users/create', [AdminUserController::class, 'create']);
$router->get('/admin/users/edit', [AdminUserController::class, 'edit']);
$router->post('/admin/users/edit', [AdminUserController::class, 'edit']);
$router->get('/admin/users/delete', [AdminUserController::class, 'delete']);

$router->get('/admin/locations/edit', [AdminLocationController::class, 'edit']);
$router->post('/admin/locations/edit', [AdminLocationController::class, 'edit']);
$router->get('/admin/payments/update', [AdminPaymentController::class, 'update']);
$router->get('/admin/messages/delete', [AdminMessageController::class, 'delete']);

// Redirect old admin login to unified login
$router->get('/admin/login', static fn (): never => redirect_to('/login'));
$router->post('/admin/login', static fn (): never => redirect_to('/login'));

// Legacy aliases while keeping a single front controller.
$router->get('/admin.php', static fn (): never => redirect_to('/admin/dashboard'));
$router->get('/login.php', static fn (): never => redirect_to('/login'));
$router->get('/kamar.php', static fn (): never => redirect_to('/rooms'));
$router->get('/menu/kamar.php', static fn (): never => redirect_to('/rooms'));
$router->get('/menu/contact.php', static fn (): never => redirect_to('/contact'));
$router->get('/menu/lainnya.php', static fn (): never => redirect_to('/map'));
$router->get('/anggota/anggota.php', static fn (): never => redirect_to('/member/dashboard'));
$router->get('/admin/index.php', static fn (): never => redirect_to('/login'));
$router->get('/admin/admin.php', static fn (): never => redirect_to('/admin/dashboard'));

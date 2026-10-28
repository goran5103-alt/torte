<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Auth;
use App\Db;
use App\Shop;
use App\View;

final class AuthController extends BaseController
{
    // Prijava i SSO ne traže ulogovanog korisnika; ostale akcije (nalog, odjava) traže.
    protected static function isPublic(): bool
    {
        return in_array(request_path(), ['/admin/login', '/admin/sso'], true);
    }

    public function form(array $p): void
    {
        if (Auth::user() && (int)Auth::user()['shop_id'] === Shop::id()) {
            redirect('/admin');
        }
        View::render('admin/login', ['shop' => Shop::current(), 'error' => null, 'email' => '']);
    }

    public function login(array $p): void
    {
        $email = strtolower(input('email'));
        if (Auth::tooManyAttempts()) {
            View::render('admin/login', ['shop' => Shop::current(), 'error' => 'Previše pokušaja. Sačekajte 15 minuta.', 'email' => $email]);
            return;
        }
        if (!Auth::attempt($email, (string)($_POST['password'] ?? ''), Shop::id())) {
            View::render('admin/login', ['shop' => Shop::current(), 'error' => 'Pogrešan email ili lozinka.', 'email' => $email]);
            return;
        }
        redirect('/admin');
    }

    public function logout(array $p): void
    {
        Auth::logout();
        redirect('/admin/login');
    }

    public function sso(array $p): void
    {
        $userId = Auth::verifySso(input('t'), Shop::id());
        if (!$userId) {
            abort(403, 'Link za ulazak je istekao. Pokušajte ponovo iz dashboarda.');
        }
        Auth::login(Db::one('SELECT * FROM users WHERE id = ?', [$userId]));
        redirect('/admin');
    }

    public function account(array $p): void
    {
        $this->render('account', ['user' => $this->user], 'nalog');
    }

    public function updateAccount(array $p): void
    {
        $email = strtolower(input('email'));
        $name = input('name');
        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Email nije ispravan.');
            redirect('/admin/nalog');
        }
        $data = ['email' => $email, 'name' => $name];
        if ($new !== '') {
            if (!password_verify($current, $this->user['password_hash'])) {
                flash('error', 'Trenutna lozinka nije tačna.');
                redirect('/admin/nalog');
            }
            if (strlen($new) < 8) {
                flash('error', 'Nova lozinka mora imati najmanje 8 znakova.');
                redirect('/admin/nalog');
            }
            $data['password_hash'] = Auth::hash($new);
        }
        Db::update('users', $data, ['id' => $this->user['id']]);
        flash('success', 'Nalog je sačuvan.');
        redirect('/admin/nalog');
    }
}

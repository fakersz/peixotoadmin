<?php
/** Controller: autenticação */

require APP_PATH . '/models/User.php';

function auth_login(): void
{
    if (is_logged_in()) {
        redirect('/dashboard');
    }

    // Cria o admin inicial automaticamente se o banco estiver vazio.
    user_seed_admin();

    $error = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email    = post_str('email', '');
        $password = (string) ($_POST['password'] ?? '');

        $user = $email ? user_find_by_email($email) : null;

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION = ['user_id' => (int) $user['id']];
            redirect('/dashboard');
        } else {
            $error = 'E-mail ou senha inválidos.';
        }
    }

    render_bare('login', ['error' => $error]);
}

function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    redirect('/login');
}

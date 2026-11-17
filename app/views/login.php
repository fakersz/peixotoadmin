<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Entrar · <?= e(APP_NAME) ?></title>
<link rel="icon" type="image/png" href="<?= e(asset('/assets/img/logo.png')) ?>">
<link rel="stylesheet" href="<?= e(asset('/assets/css/app.css')) ?>">
</head>
<body class="login-page">
<div class="login-wrap">
    <div class="login-card">
        <div class="login-brand">
            <img src="<?= e(asset('/assets/img/logo.png')) ?>" alt="iFixAdmin" class="brand-logo brand-logo-lg">
            <h1>iFix<span>Admin</span></h1>
            <p>Assistência técnica · iFixAdmin</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="/login" autocomplete="on">
            <?= csrf_field() ?>
            <div class="field">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email">
            </div>
            <div class="field">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Entrar</button>
        </form>

        <p class="login-footer">Criado por Jean Peixoto</p>
    </div>
</div>
</body>
</html>

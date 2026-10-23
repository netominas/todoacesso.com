<?php /** @var ?string $error */ ?>
<div class="login-box">
  <h1>Ingresar</h1>
  <?php if ($error): ?><p class="alert alert--error" role="alert"><?= e($error) ?></p><?php endif; ?>
  <form method="post" action="/admin/login" class="form-grid" autocomplete="off">
    <div class="field"><label for="user">Usuario</label><input id="user" name="user" type="text" required autocomplete="username"></div>
    <div class="field"><label for="password">Contraseña</label><input id="password" name="password" type="password" required autocomplete="current-password"></div>
    <button class="btn btn--primary" type="submit">Entrar</button>
  </form>
</div>

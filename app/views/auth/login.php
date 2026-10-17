<!-- <img class="inline-img" src="<?= e(asset('images/logo2.png')) ?>" alt="" /> -->
<main class="auth-container">
  <section class="auth-box">
    <div class="brand-header">
      <p>ᜐᜒᜈᜓᜎᜒᜇ᜔</p>
    </div>

    <h1>Welcome Back!</h1>
    <p class="subtitle">Log in to your account.</p>

    <?php require BASE_PATH . '/app/views/partials/flash.php'; ?>
    <?php require BASE_PATH . '/app/views/partials/errors.php'; ?>

    <form id="loginForm" action="<?= e(url('auth/login')) ?>" method="post">
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="loginIdentifier">Email or Username</label>
        <input type="text" id="loginIdentifier" name="identifier" placeholder="Enter your email or username"
               value="<?= e($identifier) ?>" autocomplete="username" required />
      </div>

      <div class="form-group">
        <label for="loginPassword">Password</label>
        <input type="password" id="loginPassword" name="password" placeholder="Enter your password"
               autocomplete="current-password" required />
      </div>

      <button type="submit" class="primary-btn">Log In</button>
    </form>

    <p class="auth-footer">
      Don't have an account?
      <a href="<?= e(url('auth/signup')) ?>">Sign up here</a>
    </p>
  </section>
</main>
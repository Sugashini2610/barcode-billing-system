<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> — Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/assets/css/app.css">
</head>

<body>
    <div class="login-page">
        <div class="login-card">
            <div class="login-logo"><i class="bi bi-shop"></i></div>
            <h1 class="login-title"><?php echo COMPANY_NAME; ?></h1>
            <p class="login-sub">Billing & Inventory Management</p>

            <?php $err = Session::flash('error');
            if ($err): ?>
                <div class="alert alert-error mb-16">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <?php echo htmlspecialchars($err); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo BASE_URL; ?>/login">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Enter username" required
                        autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" name="password" id="pwdInput" class="form-control"
                            placeholder="Enter password" required>
                        <button type="button" class="btn btn-outline" onclick="togglePwd()" style="border-left:none">
                            <i class="bi bi-eye" id="pwdEye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-w-full" style="margin-top:8px">
                    <i class="bi bi-box-arrow-in-right"></i> Sign In
                </button>
            </form>

            <p style="text-align:center;margin-top:20px;font-size:0.75rem;color:#94a3b8">
                Default: admin / admin@123
            </p>
        </div>
    </div>
    <script>
        function togglePwd() {
            var i = document.getElementById('pwdInput');
            var e = document.getElementById('pwdEye');
            if (i.type === 'password') { i.type = 'text'; e.className = 'bi bi-eye-slash'; }
            else { i.type = 'password'; e.className = 'bi bi-eye'; }
        }
    </script>
</body>

</html>
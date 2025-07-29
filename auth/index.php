<?php
require_once __DIR__ . '/../config.php';

$title = "Login";
$sub_title = "Authentication";
$error = '';
$conn = db_connect();

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt'] = 0;
}
$throttle_time = 15 * 60; // 15 minutes
if ($_SESSION['login_attempts'] >= 5 && (time() - $_SESSION['last_attempt']) < $throttle_time) {
    $remaining = ceil(($throttle_time - (time() - $_SESSION['last_attempt'])) / 60);
    $error = "Too many login attempts. Please try again in {$remaining} minutes.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    if (!verify_token($_POST['token'])) {
        $error = "Invalid security token. Please refresh the page and try again.";
    } else {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = "Email and password are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } else {
            $stmt = $conn->prepare("SELECT id, password, role_id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password'])) {
                    session_regenerate_id(true);
                    
                    $_SESSION['id'] = $user['id'];
                    $_SESSION['role'] = $user['role_id'];
                    $_SESSION['last_login'] = time();
                    
                    unset($_SESSION['login_attempts']);
                    unset($_SESSION['last_attempt']);
                    
                    $_SESSION['token'] = bin2hex(random_bytes(32));
                    
                    redirect_based_on_role($user['role_id']);
                }
            }
            
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt'] = time();
            $error = "Invalid email or password. Please try again.";
        }
    }
}

ob_start();
?>

<div class="card bg-pattern">
    <div class="card-body p-4">
        <div class="text-center w-75 m-auto">
            <div class="auth-brand">
                <a href="javascript:void(0);" class="logo logo-dark text-center">
                    <span class="logo-lg">
                        <img src="<?= asset('images/logo-dark.png') ?>" alt="" height="150">
                    </span>
                </a>

                <a href="index.php" class="logo logo-light text-center">
                    <span class="logo-lg">
                        <img src="<?= asset('images/logo-light.png') ?>" alt="" height="150">
                    </span>
                </a>
            </div>
            <p class="text-muted mb-4 mt-3">Enter your email address and password to access dashboard panel.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= special_chars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= special_chars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="token" value="<?= special_chars($_SESSION['token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input class="form-control" type="email" id="email" name="email" required
                    placeholder="Enter your email" value="<?= isset($_POST['email']) ? special_chars($_POST['email'], ENT_QUOTES, 'UTF-8') : '' ?>">
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group input-group-merge">
                    <input type="password" id="password" name="password" class="form-control"
                        required placeholder="Enter your password" autocomplete="current-password">
                    <div class="input-group-text" data-password="false">
                        <span class="password-eye"></span>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
            </div>

            <div class="text-center d-grid">
                <button class="btn btn-primary" type="submit">Log In</button>
            </div>
        </form>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12 text-center">
        <p><a href="auth-recoverpw.php" class="text-white-50">Forgot your password?</a></p>
        <p class="text-white-50">Don't have an account?
            <a href="/auth/register" class="text-white fw-bold">Sign Up</a>
        </p>
    </div>
</div>

<?php
$content = ob_get_clean();
include layouts('auth.main');
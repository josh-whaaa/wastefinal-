<?php
// session_start();
require_once '../includes/conn.php'; // Database connection

$response = ["status" => "", "message" => ""];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $response["status"] = "error";
        $response["message"] = "Please enter your email.";
        echo json_encode($response);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM admin_table WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch();

    if (!$user) {
        $response["status"] = "error";
        $response["message"] = "Email not found.";
        echo json_encode($response);
        exit;
    }

    // Generate token
    $token = bin2hex(random_bytes(32));
    $stmt = $pdo->prepare("UPDATE admin_table SET reset_token = :token WHERE email = :email");
    $stmt->execute([':token' => $token, ':email' => $email]);

    // Reset Link (for actual use, send this via email)
    $reset_link = "http://yourwebsite.com/reset-password.php?token=$token";

    // Success Response
    $response["status"] = "success";
    $response["message"] = "Reset link sent! <br> <a href='$reset_link' target='_blank'>Click here</a>";
    echo json_encode($response);
    exit;
}
?>

<!-- Forgot Password Modal -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="forgotPasswordLabel">Reset Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="forgotPasswordForm" method="POST" action="forgot-password.php">
          <div class="mb-3">
            <label for="email" class="form-label">Enter your email</label>
            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($_SESSION['login_attempt_email'] ?? '') ?>" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
        </form>
      </div>
    </div>
  </div>
</div>

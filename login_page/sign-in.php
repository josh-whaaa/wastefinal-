<?php
session_start();
require_once '../includes/conn.php';

// Define Toast Messages
$messages = [
  "empty_fields" => "Please fill in all fields.",
  "invalid_user" => "No account found with this email.",
  "wrong_password" => "Incorrect password.",
  "success" => "Login successful! Redirecting...",
  "validation_pending" => "Registration submitted! Please check your email for verification.",
  "verified" => "Email verified successfully! You can now log in.",
  "invalid_verification" => "Invalid verification link.",
  "invalid_or_expired_token" => "Verification link is invalid or has expired.",
  "verification_failed" => "Email verification failed. Please try again.",
  "already_verified" => "This email has already been verified.",
  "token_expired" => "Verification link has expired. Please register again.",
  "invalid_token" => "Invalid verification token.",
];

$status = $_GET['status'] ?? null;
$verified = $_GET['verified'] ?? null;
$error = $_GET['error'] ?? null;
$message = $_GET['message'] ?? null;

// Determine which message to show
$displayStatus = $status;
if ($verified === 'success') {
    $displayStatus = 'verified';
} elseif ($error) {
    $displayStatus = $error;
} elseif ($message) {
    $displayStatus = $message;
}
?>

<?php if ($displayStatus && isset($messages[$displayStatus])): ?>
  <div class="container position-absolute top-0 start-50 translate-middle-x mt-4" style="z-index: 1050; max-width: 500px;">
    <?php if ($displayStatus === 'success'): ?>
      <div class="toast align-items-center text-white bg-success border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">
            <?= htmlspecialchars($messages[$displayStatus]) ?>
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
      <script>
        setTimeout(() => {
          window.location.href = "../dashboard_management/admin_dashboard.php";
        }, 3000);
      </script>
    <?php elseif ($displayStatus === 'verified'): ?>
      <div class="toast align-items-center text-white bg-success border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">
            <?= htmlspecialchars($messages[$displayStatus]) ?>
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    <?php elseif ($displayStatus === 'validation_pending'): ?>
      <div class="alert alert-info alert-dismissible text-white fade show mb-0" role="alert">
        <span class="text-sm">
          <?= htmlspecialchars($messages[$displayStatus]) ?>
        </span>
        <button type="button" class="btn-close text-lg py-3 opacity-10" data-bs-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    <?php else: ?>
      <div class="alert alert-danger alert-dismissible text-white fade show mb-0" role="alert">
        <span class="text-sm">
          <?= htmlspecialchars($messages[$displayStatus]) ?>
          <?php if ($displayStatus === 'invalid_user'): ?>
            <a href="javascript:;" class="alert-link text-white" data-bs-toggle="modal" data-bs-target="#signUpModal">Sign up</a> if you don't have an account.
          <?php endif; ?>
        </span>
        <button type="button" class="btn-close text-lg py-3 opacity-10" data-bs-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="icon" type="image/png" href="../assets/img/favicon.png">
  <title>
    CEMO - City Environment Management Office
  </title>
  <style>
    .background-overlay {
      position: absolute;
      top: 0;
      left: 0;
      height: 100%;
      width: 100%;
      background-color: rgba(97, 94, 94, 0.5); /* Darker gray overlay */
      z-index: 1;
    }
    
    /* Custom input styles with floating label animation */
    .custom-input-group {
      margin-bottom: 1.5rem;
      position: relative;
    }
    
    .custom-label {
      position: absolute;
      left: 1rem;
      top: 0.75rem;
      font-size: 0.875rem;
      font-weight: 400;
      color: #707d6cff; /* Default label color */
      background-color: #fff;
      padding: 0 0.25rem;
      transition: all 0.3s ease;
      pointer-events: none;
      transform-origin: left center;
    }
    
    .custom-input {
      width: 100%;
      padding: 0.75rem 1rem;
      border: 2px solid #d2d6da;
      border-radius: 0.375rem;
      font-size: 0.875rem;
      line-height: 1.5;
      /* background-color: #fff; */
      transition: all 0.3s ease;
      box-sizing: border-box;
    }
    
    .custom-input::placeholder {
      color: transparent;
    }
    
    .custom-input:focus {
      outline: none;
      border-color: #4c89d4ff; /* Your desired focus border color */
      box-shadow: 0 0 0 3px rgba(59, 79, 85, 0.1);
    }
    
    /* Floating label animation - when focused or has content */
    .custom-input:focus + .custom-label,
    .custom-input:not(:placeholder-shown) + .custom-label,
    .custom-input.has-content + .custom-label {
      top: -0.5rem;
      left: 0.75rem;
      font-size: 0.75rem;
      font-weight: 600;
      color: #344767b5; /* Your desired focused label color - easily changeable */
      transform: scale(1);
    }
    
    /* Label color when focused */
    .custom-input:focus + .custom-label {
      color: #4c89d4ff; /* Your desired focus label color */
    }
    
    .custom-input.is-invalid {
      border-color: #dc3545;
      box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
    }
    
    .custom-input.is-valid {
      border-color: #4CAF50;
      box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
    }
    
    /* Password toggle button styles */
    .password-toggle {
      position: absolute;
      top: 50%;
      right: 12px;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      font-size: 1.1rem;
      color: #6c757d;
      z-index: 10;
      padding: 4px;
    }
    
    .password-toggle:hover {
      color: #495057;
    }
    
    /* Error message styles */
    .error-message {
      color: #dc3545;
      font-size: 0.75rem;
      margin-top: 0.25rem;
      display: block;
    }
    
    .error-message.d-none {
      display: none !important;
    }
    
    /* White background for the sign-in card */
    .card.card-plain {
      background-color: #ffffff !important;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .card-header {
      background-color: transparent !important;
      border-bottom: none !important;
      padding: 2rem 2rem 1rem 2rem;
    }
    
    .card-body {
      padding: 1rem 2rem;
      background-color: transparent !important;
    }
    
    .card-footer {
      background-color: transparent !important;
      border-top: none !important;
      padding: 1rem 2rem 2rem 2rem;
    }
  </style>
  <link id="pagestyle" href="../assets/css/material-dashboard.css?v=3.2.0" rel="stylesheet" />
</head>

<body class="bg-gray-200">
  <div class="container position-sticky z-index-sticky top-0">
    <div class="row">
      <div class="col-12">
        <!-- Navbar -->
        <!-- End Navbar -->
      </div>
    </div>
  </div>
  <main class="main-content mt-0">
  <section>
  <div class="page-header min-vh-100 position-relative" 
      style="background-image: url('../assets/img/illustrations/bg.jpg'); 
            background-size: cover; 
            background-position: center; 
            background-repeat: no-repeat;">
    <div class="background-overlay"></div> <!-- Background Overlay -->

  <div class="container">
    <div class="row">
      <div class="page-header min-vh-100">
        <div class="container">
          <div class="row">
            
            <div class="ms-1 col-6 d-lg-flex d-none h-100 my-auto pe-0 position-absolute top-0 start-5 text-center justify-content-center flex-column position-relative">
              <!-- Box that contains both the Title and Background Image -->
              <div class="position-relative px-4 py-3 rounded d-flex flex-column align-items-center justify-content-center"
                  style="background: rgba(255, 255, 255, 0.51); color: white; width: 400px; max-width: 400px; text-align: center; 
                          border-radius: 10px; padding: 20px;">
          
                  <!-- Title -->
                  <h4 class="font-weight-bolder m-0">
                      City Environment Management Office
                  </h4>
          
                  <!-- Background Image Inside the Box -->
                  <div class="w-100 mt-3 " 
                      style="background-image: url('../assets/img/illustrations/illustration-signup.png'); 
                              background-size: contain; background-repeat: no-repeat; background-position: center; 
                              height: 200px;">
                  </div>
              </div>
          </div>

          <!-- Sign-in Form -->
          <div class="col-xl-4 col-lg-5 col-md-7 d-flex flex-column ms-auto me-auto ms-lg-auto me-lg-5">
            <div class="card card-plain">
              <div class="card-header">
                <h4 class="font-weight-bolder">Sign In</h4>
                <p class="mb-0">Enter your email and password to access your account</p>
              </div>
                <!-- Email -->
                <div class="card-body">
                  <form role="form" method="POST" action="sign-in-process.php">
                    
                    <!-- Custom Email Input -->
                    <div class="custom-input-group">
                      <input type="email" class="custom-input" name="email" id="email-input" placeholder="Enter your email" required autocomplete="username" value="<?= htmlspecialchars($_GET['email'] ?? '') ?>">
                      <label for="email-input" class="custom-label">Email Address</label>
                      <small id="emailError" class="error-message d-none">Email must end with @gmail.com</small>
                    </div>
                    
                    <!-- Custom Password Input -->
                    <div class="custom-input-group">
                      <div class="position-relative">
                        <input type="password" class="custom-input" name="password" id="password-input" placeholder="Enter your password" required autocomplete="current-password" style="padding-right: 45px;">
                        <label for="password-input" class="custom-label">Password</label>
                        <button type="button" id="togglePassword" class="password-toggle">
                          <span>👁️</span>
                        </button>
                      </div>
                    </div>

                <!-- Show "Reset Password?" Link If Login Fails -->
                <div class="card-footer text-center pt-0 px-lg-2 px-1">
                <p class="mt-2 text-center">
                  <?php if (isset($_SESSION['wrong_password'])): ?>Forgot your password?
                  <a href="" class="text-primary text-gradient font-weight-bold" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal" >Reset</a>
                <?php endif; ?>
              </p>
            </div>
              <div class="text-center">
                  <button type="submit" class="btn btn-lg bg-gradient-dark btn-lg w-100 mb-0">Sign In</button>
              </div>
              </form>
              <div class="card-footer text-center pt-0 px-lg-2 px-1 mt-2">
                <p class="mb-2 text-sm mx-auto">Don't have an account?
                <a href="" class="text-primary text-gradient font-weight-bold" data-bs-toggle="modal" data-bs-target="#signUpModal">Sign up</a>
                </p>
              </div>
              
              </div>
            </div>
          </div>
          </div>
        </div>
      </div>
    </div>
          </section>
  </main>
  <?php include 'sign-up.php'; ?>
  <?php include 'forgot-password.php'; ?>
  <?php include 'sign-in-process.php'; ?>
  <?php include 'sign-up-process.php'; ?>

  <!-- Custom JavaScript for form functionality -->
<script>
  document.addEventListener("DOMContentLoaded", function () {
    const passwordInput = document.getElementById("password-input");
    const emailInput = document.getElementById("email-input");
    const toggleBtn = document.getElementById("togglePassword");
    const emailError = document.getElementById("emailError");
    const form = document.querySelector("form");

    // Function to check if input has content and add/remove class
    function checkInputContent(input) {
      if (input.value.trim() !== '') {
        input.classList.add('has-content');
      } else {
        input.classList.remove('has-content');
      }
    }

    // Check initial state for email input (in case it has a value from $_GET)
    checkInputContent(emailInput);
    checkInputContent(passwordInput);

    // Add event listeners for input content detection
    emailInput.addEventListener('input', function() {
      checkInputContent(this);
      this.classList.remove("is-valid", "is-invalid");
      emailError.classList.add("d-none");
    });

    passwordInput.addEventListener('input', function() {
      checkInputContent(this);
      this.classList.remove("is-valid", "is-invalid");
    });

    // Toggle password visibility
    toggleBtn.addEventListener("click", function () {
      const type = passwordInput.type === "password" ? "text" : "password";
      passwordInput.type = type;
      const icon = this.querySelector('span');
      icon.textContent = type === "password" ? "👁️" : "👁️‍🗨️";
    });

    // Email domain validation
    form.addEventListener("submit", function (e) {
      const emailValue = emailInput.value.trim();

      if (!emailValue.endsWith("@gmail.com")) {
        e.preventDefault(); // Prevent form from submitting
        emailError.classList.remove("d-none");
        emailInput.classList.add("is-invalid");
      }
    });

    // Auto-hide alerts after 2 seconds
    setTimeout(() => {
      const alert = document.querySelector('.alert');
      if (alert) {
        alert.classList.remove('show');
        alert.classList.add('hide');
      }
    }, 2000);
  });
</script>

  <script src="../assets/js/core/popper.min.js"></script>
  <script src="../assets/js/core/bootstrap.min.js"></script>
  <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
  <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
  <script async defer src="https://buttons.github.io/buttons.js"></script>

</body>
</html>
<!-- filepath: c:\xampp\phpMyAdmin\CEMO_System\final\login_page\sign-up.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <!-- Bootstrap CSS -->
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> -->
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            height: 100vh;
            position: relative;
        }

        #id-preview-container {
            position: relative;
            width: 100%;
            max-width: 360px;
            margin: 10px auto;
            overflow: hidden;
        }

        .ocrloader p::before {
            content: '';
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #18c89b;
            position: relative;
            right: 4px;
        }

        .ocrloader p {
            color: #18c89b;
            position: absolute;
            bottom: -30px;
            left: 38%;
            font-size: 16px;
            font-weight: 600;
            animation: blinker 1.5s linear infinite;
            font-family: sans-serif;
            text-transform: uppercase;
        }

        .ocrloader {
            width: 100%;
            height: 200px;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            top: 0;
            backface-visibility: hidden;
        }

        .ocrloader span {
            position: absolute;
            left: 15%;
            top: 0;
            width: 70%;
            height: 5px;
            background-color: #18c89b;
            box-shadow: 0 0 10px 1px #18c89b,
                        0 0 1px 1px #18c89b;
            z-index: 1;
            transform: translateY(95px);
            animation: move 1.7s cubic-bezier(0.15, 0.54, 0.76, 0.74) infinite;
        }

        .ocrloader:before,
        .ocrloader:after,
        .ocrloader em:before,
        .ocrloader em:after {
            content: "";
            position: absolute;
            width: 45px;
            height: 46px;
            border-style: solid;
            border-width: 0;
            border-color: #18c89b;
        }

        .ocrloader:before {
            left: 0;
            top: 0;
            border-left-width: 5px;
            border-top-width: 5px;
            border-radius: 5px 0 0 0;
        }

        .ocrloader:after {
            right: 0;
            top: 0;
            border-right-width: 5px;
            border-top-width: 5px;
            border-radius: 0 5px 0 0;
        }

        .ocrloader em:before {
            left: 0;
            bottom: 0;
            border-left-width: 5px;
            border-bottom-width: 5px;
            border-radius: 0 0 0 5px;
        }

        .ocrloader em:after {
            right: 0;
            bottom: 0;
            border-right-width: 5px;
            border-bottom-width: 5px;
            border-radius: 0 0 5px 0;
        }

        @keyframes move {
            0%, 100% {
                transform: translateY(190px);
            }
            50% {
                transform: translateY(0);
            }
            75% {
                transform: translateY(160px);
            }
        }

        @keyframes blinker {  
            50% { opacity: 0; }
        }

        .email-error {
            position: absolute;
        }

        .input-group-outline .form-control {
            padding-right: 2.5rem;
        }

        .validation-icon {
            pointer-events: none;
            font-size: 1rem;
            background-color: transparent !important;
            border: 0 !important;
            padding: 0 !important;
            box-shadow: none !important;
            line-height: 1;
        }

        .modal-title {
            width: 100%;
            text-align: center;
        }

        .input-group input:invalid, .input-group select:invalid {
            border-color: rgb(74, 62, 64) !important;
        }

        .input-group input:valid, .input-group select:valid {
            border-color: #28a745 !important;
        }

        .input-group input, .input-group select {
            border-width: 2px;
            border-style: solid;
            transition: border-color 0.2s;
        }

        .input-group .form-label {
            font-weight: 500;
        }

        .input-group .form-control:focus {
            border-color: #007bff;
            box-shadow: none;
        }

        small.text-danger,
        small.text-muted {
            font-size: 0.85rem;
        }

        .input-group-outline {
            margin-bottom: 0.25rem;
        }

        .input-group + small {
            margin-left: 0.25rem;
            margin-top: 0.25rem;
        }

        /* Highlight mismatched fields in red */
        .mismatch {
            border-color: #dc3545 !important;
            background-color: #f8d7da !important;
        }

        /* Retake button style */
        .retake-btn {
            font-size: 0.875rem;
            margin-top: 8px;
            padding: 0.25rem 0.5rem;
        }

        /* Account Type Selection Buttons */
        .btn-group .btn-check:checked + .btn {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: #fff;
        }
        
        .btn-group .btn-outline-primary {
            border-color: #0d6efd;
            color: #0d6efd;
            transition: all 0.3s ease;
        }
        
        .btn-group .btn-outline-primary:hover {
            background-color: #0d6efd;
            color: #fff;
        }

        /* Responsive */
        @media (max-width: 576px) {
            .ocrloader {
                height: 180px;
            }
            .ocrloader p {
                font-size: 14px;
                bottom: -25px;
                left: 30%;
            }
            #id-preview {
                max-height: 180px;
            }
        }
    </style>
</head>
<body>

<div class="modal fade" id="signUpModal" tabindex="-1" aria-labelledby="signUpModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="text-center modal-title w-100" id="signUpModalLabel">Create an Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pb-0">
                <p class="text-center mb-3">Please fill in the form below to create an account.</p>
                <form action="sign-up-process.php" method="POST" id="signupForm" autocomplete="off">
                    <!-- Account Type Selection -->
                    <div class="text-center mb-3">
                        <label class="form-label fw-bold mb-2">Select Account Type:</label>
                        <div class="btn-group w-100" role="group" aria-label="Account type selection">
                            <input type="radio" class="btn-check" name="account_type" id="account_type_client" value="client" required>
                            <label class="btn btn-outline-primary" for="account_type_client">Client</label>
                            
                            <input type="radio" class="btn-check" name="account_type" id="account_type_driver" value="driver" required>
                            <label class="btn btn-outline-primary" for="account_type_driver">Driver</label>
                        </div>
                    </div>

                    <!-- First Name (Floating Style) -->
                    <div class="custom-input-group position-relative mb-3">
                        <input type="text" class="custom-input" id="signup-firstname" name="first_name" required placeholder="Enter your first name" autocomplete="off">
                        <label for="signup-firstname" class="custom-label">First Name</label>
                        <span class="input-group-text validation-icon position-absolute" id="firstname-icon" style="top: 50%; right: 10px; transform: translateY(-50%);"></span>
                    </div>
                    <!-- Last Name (Floating Style) -->
                    <div class="custom-input-group position-relative mb-3">
                        <input type="text" class="custom-input" id="signup-lastname" name="last_name" required placeholder="Enter your last name" autocomplete="off">
                        <label for="signup-lastname" class="custom-label">Last Name</label>
                        <span class="input-group-text validation-icon position-absolute" id="lastname-icon" style="top: 50%; right: 10px; transform: translateY(-50%);"></span>
                    </div>

                    <!-- Custom Email Input (Floating Style) -->
                    <div class="custom-input-group position-relative mb-3">
                        <input type="email" class="custom-input" id="signup-email" name="email" required placeholder="Enter your email" autocomplete="off">
                        <label for="signup-email" class="custom-label">Email Address</label>
                        <span class="input-group-text validation-icon position-absolute" id="email-icon" style="top: 35%; right: 10px; transform: translateY(-50%);"></span>
                        <small id="email-error" class="text-danger d-block mt-1 mb-1"></small>
                        <small id="email-format-hint" class="text-muted d-block mb-2" style="display: none;">Valid email formats accepted (Gmail, Yahoo, Outlook, iCloud, or custom domains).</small>
                    </div>

                    <!-- Phone Number (Philippines format) - Client Only -->
                    <div class="custom-input-group position-relative mb-3 client-only-field" style="display: none;">
                        <!-- Visible, formatted input -->
                        <input type="tel" class="custom-input" id="signup-contact" name="contact_display"
                                placeholder="+63 9XX XXX XXXX" autocomplete="off">
                        <label for="signup-contact" class="custom-label">Contact Number</label>
                        <span class="input-group-text validation-icon position-absolute" style="top: 45%; right: 10px; transform: translateY(-50%);"></span>
                        <small id="contact-error" class="text-danger d-block mt-1 mb-1"></small>
                        <!-- Hidden, normalized 11-digit local number (e.g., 09XXXXXXXXX) used for submit and checks -->
                        <input type="hidden" id="signup-contact-hidden" name="contact" value="">
                    </div>

                    <!-- Barangay Dropdown (Floating Style) - Client Only -->
                    <div class="custom-input-group position-relative mb-3 client-only-field" style="display: none;">
                        <select class="custom-input" id="signup-barangay" name="barangay">
                            <option value="" disabled selected>Select your barangay</option>
                            <?php
                            require_once '../includes/conn.php';
                            try {
                                $stmt = $pdo->query("SELECT barangay FROM barangays_table");
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<option value="' . htmlspecialchars($row['barangay']) . '">' . htmlspecialchars($row['barangay']) . '</option>';
                                }
                            } catch (PDOException $e) {
                                echo '<option value="" disabled>Error loading barangays</option>';
                            }
                            ?>
                        </select>
                        <label for="signup-barangay" class="custom-label">Barangay</label>
                    </div>

                    <small id="passwordHelp" class="text-muted mb-2 d-block">Must be at least 8 characters, include a number & symbol.</small>
                    <!-- Custom Password Input (Floating Style) -->
                    <div class="custom-input-group mb-3">
                        <div class="position-relative">
                            <input type="password" class="custom-input" id="signup-password" name="password" required placeholder="Enter your password" autocomplete="off" style="padding-right: 45px;">
                            <label for="signup-password" class="custom-label">Password</label>
                            <span class="input-group-text validation-icon position-absolute" style="top: 50%; right: 45px; transform: translateY(-50%);"></span>
                            <button type="button" tabindex="-1" class="password-toggle toggle-password" style="top: 50%; right: 10px; transform: translateY(-50%); cursor: pointer; position: absolute; background: none; border: none;">
                                <span>👁️</span>
                            </button>
                        </div>
                    </div>


                    <!-- ID Upload -->
                    <div class="mb-3">
                        <label for="id-upload" class="form-label fw-bold text-danger">📸 Upload Valid ID <span id="id-type-label">(Philippine)</span></label>
                        <input type="file" class="form-control form-control-sm" id="id-upload" accept="image/*" required>
                        <small class="form-text text-info" id="id-upload-hint">
                            ✨ <strong>Tip:</strong> Ensure the ID photo is clear to avoid verification errors!<br>
                            <span id="id-type-hint">We'll verify your identity using the ID.</span>
                        </small>

                        <!-- Preview Container with Scanner Overlay -->
                        <div class="position-relative mt-2" id="id-preview-container" style="display: none;">
                            <img id="id-preview" src="#" alt="ID Preview" class="w-100 rounded" style="max-height: 300px; object-fit: contain;">
                            <!-- OCR Scanner Animation Overlay -->
                            <div class="ocrloader" id="scanner-animation">
                                <span></span>
                                <p>Scanning</p>
                                <em></em>
                            </div>
                        </div>

                        <!-- Retake Button -->
                        <button type="button" id="retake-btn" class="btn btn-sm btn-outline-danger retake-btn" style="display: none;">📷 Retake ID</button>
                    </div>

                    <!-- OCR Status Message -->
                    <div id="ocr-status" class="text-muted small mb-2 text-center" style="display:none;"></div>

                    <!-- ID Verification Status Alert -->
                    <div id="id-verification-alert" class="alert alert-warning text-center" style="display: none; font-size: 0.85rem; padding: 0.75rem;">
                        <strong>⚠️ ID Verification Required</strong><br>
                        <small>Please upload and verify your ID to enable registration.</small>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-lg bg-gradient-dark w-100 mt-3 mb-0" id="signup-submit" disabled>Register</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/tesseract.js@v5.0.0/dist/tesseract.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Prevent interruption during ID scan
    let isScanning = false;
    // Helper to enable/disable all form fields and actions
    function setFormDisabled(disabled) {
        // Disable all inputs, selects, buttons except the retake button and file input
        const elements = form.querySelectorAll('input, select, button, textarea');
        elements.forEach(el => {
            // Don't disable the retake button or file input during scan
            if (el === idUpload || el === retakeBtn) return;
            el.disabled = disabled;
        });
        // Always keep the retake button enabled during scan
        if (retakeBtn) retakeBtn.disabled = false;
        // Always keep the file input disabled during scan
        if (idUpload) idUpload.disabled = disabled;
    }
    // Toggle password visibility (supports floating style)
    document.addEventListener("click", function(e) {
        if (e.target.closest('.toggle-password')) {
            const toggleBtn = e.target.closest('.toggle-password');
            // Find the input in the same .position-relative container
            const container = toggleBtn.closest('.position-relative');
            const passwordInput = container.querySelector('input[type="password"], input[type="text"]');
            if (!passwordInput) return;
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                toggleBtn.innerHTML = '<span>👁️‍🗨️</span>';
            } else {
                passwordInput.type = "password";
                toggleBtn.innerHTML = '<span>👁️</span>';
            }
        }
    });

    // Real-Time Form Validation
    const form = document.getElementById("signupForm");
    const submitButton = document.getElementById("signup-submit");
    const firstNameInput = document.getElementById("signup-firstname");
    const lastNameInput = document.getElementById("signup-lastname");
    const firstnameIcon = document.getElementById("firstname-icon");
    const lastnameIcon = document.getElementById("lastname-icon");
    const emailInput = document.getElementById("signup-email");
    const emailError = document.getElementById("email-error");
    const emailIcon = document.getElementById("email-icon");
    const contactInput = document.getElementById("signup-contact");
    const contactError = document.getElementById("contact-error");
    const contactIcon = contactInput.parentElement.querySelector(".validation-icon");
    const passwordInput = document.getElementById("signup-password");
    const passwordIcon = passwordInput.parentElement.querySelector(".validation-icon");
    const barangayInput = document.getElementById("signup-barangay");
    
    // Account Type Selection
    const accountTypeClient = document.getElementById('account_type_client');
    const accountTypeDriver = document.getElementById('account_type_driver');
    const clientOnlyFields = document.querySelectorAll('.client-only-field');
    
    // ID Upload & OCR Verification - Declare early for use in toggleAccountTypeFields
    const idUpload = document.getElementById('id-upload');
    const retakeBtn = document.getElementById('retake-btn');
    const container = document.getElementById('id-preview-container');
    const ocrStatus = document.getElementById('ocr-status');
    const idTypeLabel = document.getElementById('id-type-label');
    const idTypeHint = document.getElementById('id-type-hint');
    const idUploadHint = document.getElementById('id-upload-hint');

    let emailValid = false, contactValid = false, passwordValid = false, firstNameValid = false, lastNameValid = false, barangayValid = false;
    let ocrVerified = false;
    
    // Function to toggle fields based on account type
    function toggleAccountTypeFields() {
        const isDriver = accountTypeDriver.checked;
        const isClient = accountTypeClient.checked;
        
        if (isDriver) {
            // Show driver fields only
            clientOnlyFields.forEach(field => {
                field.style.display = 'none';
                // Remove required attribute from client-only fields
                const inputs = field.querySelectorAll('input, select');
                inputs.forEach(input => {
                    input.removeAttribute('required');
                    input.value = ''; // Clear values
                });
            });
            
            // Update ID verification message for driver
            if (idTypeLabel) idTypeLabel.textContent = '(Driver\'s License or National ID)';
            if (idTypeHint) idTypeHint.textContent = 'For drivers: Accepts Driver\'s License or Philippine National ID. We\'ll verify your identity using the ID.';
            
            // Reset contact and barangay validation
            contactValid = true; // Not required for driver
            barangayValid = true; // Not required for driver
            contactError.textContent = '';
            if (contactIcon) contactIcon.textContent = '';
            
        } else if (isClient) {
            // Show all fields including client-only
            clientOnlyFields.forEach(field => {
                field.style.display = 'block';
                // Add required attribute to client-only fields
                const inputs = field.querySelectorAll('input, select');
                inputs.forEach(input => {
                    input.setAttribute('required', 'required');
                });
            });
            
            // Update ID verification message for client
            if (idTypeLabel) idTypeLabel.textContent = '(Philippine)';
            if (idTypeHint) idTypeHint.textContent = 'We\'ll verify your identity using the ID.';
            
            // Re-validate contact and barangay - reset to invalid state first
            contactValid = false;
            barangayValid = false;
            contactError.textContent = '';
            if (contactIcon) contactIcon.textContent = '';
            
            // Validate if fields have values
            if (contactInput.value.trim()) {
                validateContact();
            }
            if (barangayInput.value) {
                validateBarangay();
            }
        }
        
        // Reset form state
        ocrVerified = false;
        if (idUpload) idUpload.value = '';
        if (container) container.style.display = 'none';
        if (retakeBtn) retakeBtn.style.display = 'none';
        if (ocrStatus) ocrStatus.style.display = 'none';
        firstNameInput.classList.remove("mismatch");
        lastNameInput.classList.remove("mismatch");
        
        updateSubmit();
    }
    
    // Event listeners for account type selection
    if (accountTypeClient) accountTypeClient.addEventListener('change', toggleAccountTypeFields);
    if (accountTypeDriver) accountTypeDriver.addEventListener('change', toggleAccountTypeFields);

    function validateFirstName() {
        if (firstNameInput.value.trim().length >= 2) {
            firstNameValid = true;
            firstnameIcon.textContent = "✅";
        } else {
            firstNameValid = false;
            firstnameIcon.textContent = "";
        }
        updateSubmit();
    }

    function validateLastName() {
        if (lastNameInput.value.trim().length >= 2) {
            lastNameValid = true;
            lastnameIcon.textContent = "✅";
        } else {
            lastNameValid = false;
            lastnameIcon.textContent = "";
        }
        updateSubmit();
    }

    function validateEmail() {
        const value = emailInput.value.trim().toLowerCase();
        const formatHint = document.getElementById("email-format-hint");
        
        // Standard email format regex (allows any valid email domain)
        const emailRegex = /^[a-zA-Z0-9][a-zA-Z0-9._+-]*@[a-zA-Z0-9][a-zA-Z0-9.-]*\.[a-zA-Z]{2,}$/;
        
        // Check if email is in valid format
        if (!emailRegex.test(value)) {
            emailError.textContent = "";
            emailIcon.textContent = "";
            formatHint.style.display = value.length > 0 ? "block" : "none";
            emailValid = false;
        } else {
            formatHint.style.display = "none";
            fetch("check-email.php?email=" + encodeURIComponent(value))
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        emailError.textContent = "Email is already registered!";
                        emailIcon.textContent = "❌";
                        emailValid = false;
                    } else {
                        emailError.textContent = "";
                        emailIcon.textContent = "✅";
                        emailValid = true;
                    }
                    updateSubmit();
                })
                .catch(() => {
                    emailError.textContent = "Could not check email.";
                    emailIcon.textContent = "❌";
                    emailValid = false;
                    updateSubmit();
                });
        }
        updateSubmit();
    }

    function validateContact() {
        // Skip validation if driver account type
        if (accountTypeDriver && accountTypeDriver.checked) {
            contactValid = true;
            return;
        }
        
        const display = contactInput.value;
        // Normalize to 11-digit local format (09XXXXXXXXX)
        let digits = display.replace(/[^0-9]/g, '');
        // Accept +63 or 63 prefix
        if (digits.startsWith('63')) {
            digits = '0' + digits.slice(2);
        }
        // Accept leading 9 (without 0) -> add 0
        if (digits.length === 10 && digits.startsWith('9')) {
            digits = '0' + digits;
        }
        // Update hidden normalized field
        const hidden = document.getElementById('signup-contact-hidden');
        hidden.value = digits.slice(0, 11);

        if (!/^0\d{10}$/.test(hidden.value)) {
            contactError.textContent = "Enter a valid PH number: +63 9XX XXX XXXX";
            contactIcon.textContent = "❌";
            contactValid = false;
            updateSubmit();
            return;
        }

        // Only check phone if client account type
        if (accountTypeClient && accountTypeClient.checked) {
            fetch("check-phone.php?contact=" + encodeURIComponent(hidden.value))
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        contactError.textContent = "Contact is already registered!";
                        contactIcon.textContent = "❌";
                        contactValid = false;
                    } else {
                        contactError.textContent = "";
                        contactIcon.textContent = "✅";
                        contactValid = true;
                    }
                    updateSubmit();
                })
                .catch(() => {
                    contactError.textContent = "Could not check contact.";
                    contactIcon.textContent = "❌";
                    contactValid = false;
                    updateSubmit();
                });
        } else {
            contactValid = true;
            updateSubmit();
        }
    }

    function validatePassword() {
        const value = passwordInput.value;
        if (value.length >= 8 && /[0-9]/.test(value) && /[\W_]/.test(value)) {
            passwordIcon.textContent = "✅";
            passwordValid = true;
        } else {
            passwordIcon.textContent = "❌";
            passwordValid = false;
        }
        updateSubmit();
    }

    function validateBarangay() {
        // Skip validation if driver account type
        if (accountTypeDriver && accountTypeDriver.checked) {
            barangayValid = true;
            updateSubmit();
            return;
        }
        
        barangayValid = barangayInput.value !== "";
        updateSubmit();
    }

    function updateSubmit() {
        // Check account type
        const isDriver = accountTypeDriver && accountTypeDriver.checked;
        const isClient = accountTypeClient && accountTypeClient.checked;
        
        // ID verification is MANDATORY for both client and driver
        // For driver: only require email, password, first name, last name, and OCR verification
        // For client: require all fields including contact and barangay
        let allFieldsValid;
        if (isDriver) {
            allFieldsValid = emailValid && passwordValid && firstNameValid && lastNameValid && ocrVerified;
        } else if (isClient) {
            allFieldsValid = emailValid && contactValid && passwordValid && firstNameValid && lastNameValid && barangayValid && ocrVerified;
        } else {
            // No account type selected yet
            allFieldsValid = false;
        }
        
        submitButton.disabled = !allFieldsValid;
        
        const verificationAlert = document.getElementById('id-verification-alert');
        
        // Show helpful message if all fields valid but ID not verified
        if (allFieldsValid && !ocrVerified) {
            submitButton.title = "Please upload and verify your ID to continue";
            if (verificationAlert) {
                verificationAlert.style.display = 'block';
            }
        } else {
            submitButton.title = "";
            if (verificationAlert) {
                verificationAlert.style.display = 'none';
            }
        }
    }
    
    // Initialize form state - hide client-only fields by default
    clientOnlyFields.forEach(field => {
        field.style.display = 'none';
    });
    
    // Initialize validation states
    contactValid = true; // Will be set to false when client is selected
    barangayValid = true; // Will be set to false when client is selected

    // Event Listeners
    firstNameInput.addEventListener("input", validateFirstName);
    lastNameInput.addEventListener("input", validateLastName);
    emailInput.addEventListener("input", validateEmail);
    contactInput.addEventListener("input", (e) => {
        // Apply visual PH formatting while typing: +63 9XX XXX XXXX
        let value = e.target.value.replace(/[^0-9+]/g, '');
        // Normalize leading +63 or 63
        if (value.startsWith('+63')) {
            value = '+63 ' + value.slice(3);
        } else if (value.startsWith('63')) {
            value = '+63 ' + value.slice(2);
        }
        // If starts with 0 or 9, transform to +63 9...
        if (!value.startsWith('+63')) {
            const digits = value.replace(/\D/g, '');
            if (digits.startsWith('0')) {
                value = '+63 ' + digits.slice(1);
            } else if (digits.startsWith('9')) {
                value = '+63 ' + digits;
            }
        }
        // Spacing: +63 9XX XXX XXXX
        const digitsOnly = value.replace(/\D/g, ''); // e.g., 639XXXXXXXXX or 9XXXXXXXXX
        let formatted = '+63 ';
        let local = digitsOnly.startsWith('63') ? digitsOnly.slice(2) : (digitsOnly.startsWith('0') ? digitsOnly.slice(1) : digitsOnly);
        local = local.slice(0, 10); // limit to 10 local digits after +63
        if (local.length > 0) {
            formatted += local.slice(0, 1);
        }
        if (local.length > 1) {
            formatted += local.slice(1, 3) ? local.slice(1, 3) : '';
        }
        if (local.length > 3) {
            formatted = '+63 ' + local.slice(0, 3) + ' ' + local.slice(3, 6);
        }
        if (local.length > 6) {
            formatted = '+63 ' + local.slice(0, 3) + ' ' + local.slice(3, 6) + ' ' + local.slice(6, 10);
        }
        e.target.value = formatted.trim();
        validateContact();
    });
    passwordInput.addEventListener("input", validatePassword);
    barangayInput.addEventListener("change", validateBarangay);

    validateBarangay();
    validatePassword();
    updateSubmit();


    let isSubmitting = false; // Flag to prevent double submission
    
    form.addEventListener("submit", function (e) {
        // If already confirmed and submitting, allow normal submission
        if (isSubmitting) {
            return true;
        }
        
        // Block submission if button is disabled
        if (submitButton.disabled) {
            e.preventDefault();
            return false;
        }
        
        // Double-check: MUST have verified ID to submit (for both client and driver)
        if (!ocrVerified) {
            e.preventDefault();
            const accountType = accountTypeDriver && accountTypeDriver.checked ? 'driver' : 'client';
            const idType = accountType === 'driver' ? 'Driver\'s License or National ID' : 'ID';
            
            Swal.fire({
                icon: 'error',
                title: 'ID Verification Required',
                html: `
                    <p>You must upload and verify your ${idType} before registering as a ${accountType}.</p>
                    <p><strong>Steps:</strong></p>
                    <ol class="text-start">
                        <li>Upload a clear photo of your ${idType}</li>
                        <li>Wait for the system to scan and verify it</li>
                        <li>Ensure your name matches the ${idType}</li>
                        <li>Then you can register</li>
                    </ol>
                    ${accountType === 'driver' ? '<p class="text-muted small mt-2"><strong>Note:</strong> Drivers can use either a Driver\'s License or Philippine National ID.</p>' : ''}
                `,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'I Understand'
            });
            return false;
        }
        
        // Prevent default form submission
        e.preventDefault();
        
        // Show email approval confirmation modal
        Swal.fire({
            icon: 'info',
            title: 'Email Account Approval',
            html: `
                <div class="text-start">
                    <ul class="text-start">
                        <li>📧 Make sure this email account <strong>${emailInput.value}</strong> is valid and not used by another account.</li>
                    </ul>
                    <p class="mt-3">Click the Register button to proceed with Registration.</p>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Register',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#0066cc',
            cancelButtonColor: '#6c757d',
            reverseButtons: true,
            allowOutsideClick: false,
            width: 480,
            customClass: {
                popup: 'shadow-lg rounded-4',
                container: 'swal2-container'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Set flag to prevent modal from showing again
                isSubmitting = true;
                
                // Show loading state
                Swal.fire({
                    title: '<h3 style="color:#0066cc; font-weight:600;">Registering Your Account...</h3>',
                    html: `
                        <div style="font-size:15px; color:#444; margin-top:8px;">
                            Please wait a moment while we process your registration.
                        </div>
                        <div style="margin-top:20px;">
                            <div class="spinner-border text-primary" role="status" style="width: 2.5rem; height: 2.5rem;">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    `,
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    background: '#ffffff',
                    width: 420,
                    customClass: {
                        popup: 'shadow-lg rounded-4'
                    }
                });
                
                // Submit the form after user confirms
                // Remove client-only fields if driver account type (so they're not sent to server)
                if (accountTypeDriver && accountTypeDriver.checked) {
                    if (contactInput) contactInput.removeAttribute('name');
                    const contactHidden = document.getElementById('signup-contact-hidden');
                    if (contactHidden) contactHidden.removeAttribute('name');
                    if (barangayInput) barangayInput.removeAttribute('name');
                }
                
                // Use a small delay to ensure loading modal is visible, then submit normally
                setTimeout(() => {
                    form.submit();
                }, 100);
            }
            // If cancelled, do nothing - user stays on form
        });
        
        return false;
    });

    // ID Upload & OCR Verification - Additional elements
    const preview = document.getElementById('id-preview');
    const scanner = document.getElementById('scanner-animation');

    // Retake ID
    retakeBtn.addEventListener('click', function () {
        if (isScanning) return; // Prevent retake during scan
        idUpload.value = "";
        container.style.display = 'none';
        retakeBtn.style.display = 'none';
        ocrStatus.style.display = 'none';
        submitButton.disabled = true;
        ocrVerified = false;
        firstNameInput.classList.remove("mismatch");
        lastNameInput.classList.remove("mismatch");
    });

    idUpload.addEventListener('change', function () {
        if (isScanning) return; // Prevent double scan or interruption
        const file = this.files[0];
        const fname = document.getElementById('signup-firstname').value.trim().toLowerCase();
        const lname = document.getElementById('signup-lastname').value.trim().toLowerCase();

        if (!file || !fname || !lname) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Input',
                text: 'Please fill out first and last name before uploading the ID.'
            });
            this.value = "";
            return;
        }

        // Validate name length requirements - must be at least 2 characters
        if (fname.length < 2 || lname.length < 2) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Name',
                text: 'First and last names must be at least 2 characters long for ID verification.'
            });
            this.value = "";
            return;
        }

        isScanning = true;
        setFormDisabled(true); // Disable all fields during scan
        const reader = new FileReader();
        reader.onload = function () {
            const img = new Image();
            img.onload = function () {
                preview.src = reader.result;
                container.style.display = 'block';
                retakeBtn.style.display = 'inline-block';
                ocrStatus.style.display = 'block';
                const accountType = accountTypeDriver && accountTypeDriver.checked ? 'driver' : 'client';
                const idType = accountType === 'driver' ? 'Driver\'s License/ID' : 'ID';
                ocrStatus.textContent = `Scanning ${idType}...`;
                scanner.style.display = 'block';

                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                canvas.width = img.width;
                canvas.height = img.height;
                ctx.drawImage(img, 0, 0);
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const qrCode = jsQR(imageData.data, canvas.width, canvas.height);

                if (qrCode) {
                    let qrData;
                    qrData = JSON.parse(qrCode.data);
                    

                    const qrFname  = (qrData.subject?.fName || "").trim().toLowerCase();
                    const qrLname  = (qrData.subject?.lName || "").trim().toLowerCase();

                     // Compare each field
                    const isFnameMatch = fname === qrFname;
                    const isLnameMatch = lname === qrLname;

                    if (isFnameMatch && isLnameMatch) {
                        scanner.style.display = 'none';
                        ocrVerified = true;
                        firstNameInput.classList.remove("mismatch");
                        lastNameInput.classList.remove("mismatch");
                        firstnameIcon.textContent = "✅";
                        lastnameIcon.textContent = "✅";
                        
                        const accountType = accountTypeDriver && accountTypeDriver.checked ? 'driver' : 'client';
                        const idType = accountType === 'driver' ? 'Driver\'s License/ID' : 'ID';
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'QR Name Match',
                            html: `${idType} QR code successfully verified.<br>Your identity has been confirmed.`,
                            confirmButtonColor: '#198754'
                        });
                    } else {
                        const mismatches = [];
                        if (!isFnameMatch) mismatches.push('First Name');
                        if (!isLnameMatch) mismatches.push('Last Name');

                        const shouldClear = !isFnameMatch || !isLnameMatch; // only clear if core names mismatch
                        if (shouldClear) {
                            document.getElementById('id-upload').value = "";
                            container.style.display = 'none';
                            retakeBtn.style.display = 'none';
                        }

                        const message = `❌ ${mismatches.join(", ")} not found on the ID.${shouldClear ? '<br>The uploaded image has been cleared.' : ''}`;
                        Swal.fire({
                            icon: 'error',
                            title: 'QR Name Mismatch',
                            html: message,
                            confirmButtonColor: '#dc3545'
                        });
                    }
                } else {
                    // Fallback to OCR with preprocessing and rotations
                    runTesseractOCR(file, fname, lname);
                }
            };
            img.src = reader.result;
        };
        reader.readAsDataURL(file);
    });

    function runTesseractOCR(file, fname, lname) {
        Swal.fire({
            title: 'Checking ID...',
            html: 'Extracting text from the ID image.<br><b>Please wait.</b>',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading()
        });

        Tesseract.recognize(file, 'eng', {
            logger: m => {
                if (m.status === "recognizing text") {
                    Swal.update({
                        html: `Extracting text... <b>${Math.round(m.progress * 100)}%</b>`
                    });
                }
            }
        }).then(({ data: { text } }) => {
            // Clean the extracted text
            const cleanText = text.replace(/\s+/g, ' ').trim();

            // ID number extraction
            const idNumberMatch = cleanText.match(/\b([A-Z0-9]{3,}-[A-Z0-9]{2,}-[A-Z0-9]{3,}(?:-[A-Z0-9]+)?)\b|\b\d{4}-\d{4}-\d{4}-\d{4}\b/);
            const extractedIdNumber = idNumberMatch ? idNumberMatch[0] : "";

            // ID type detection
            const idTypeMap = {
                "philippine national id": "Philippine National ID",
                "philsys": "Philippine National ID",
                "passport": "Passport",
                "driver": "Driver's License",
                "lto": "Driver's License",
                "umid": "UMID",
                "sss": "SSS ID",
                "prc": "PRC ID",
                "voter": "Voter's ID",
                "tin": "TIN ID",
                "philhealth": "PhilHealth ID"
            };

            let detectedType = "Unknown";
            for (const keyword in idTypeMap) {
                if (cleanText.includes(keyword)) {
                    detectedType = idTypeMap[keyword];
                    break;
                }
            }

            if (detectedType === "Unknown") {
                if (/^\d{4}-\d{4}-\d{4}-\d{4}$/.test(extractedIdNumber)) {
                    detectedType = "Philippine National ID";
                } else if (/^[A-Z]{1,3}-\d{2}-\d{6,7}$/.test(extractedIdNumber)) {
                    detectedType = "Driver's License";
                } else if (/^\d{2}-\d{9,10}$/.test(extractedIdNumber)) {
                    detectedType = "PhilHealth ID";
                } else if (/^\d{9}$/.test(extractedIdNumber)) {
                    detectedType = "TIN ID";
                } else if (/^\d{2}-\d{7,10}$/.test(extractedIdNumber)) {
                    detectedType = "SSS ID";
                }
            }


            // Smart name matching - handles short names while preventing partial matches
            const extractNameFromText = (name, text) => {
                if (!name || name.length < 2) return false;
                
                const inputName = name.toLowerCase().trim();
                const textLower = text.toLowerCase();
                
                // Split input name into words
                const inputWords = inputName.split(/\s+/).filter(word => word.length >= 2);
                if (inputWords.length === 0) return false;
                
                // Split text into words for matching
                const textWords = textLower.split(/\s+/);
                
                // For each input word, find exact matches in text words
                const matchedWords = [];
                for (const inputWord of inputWords) {
                    let found = false;
                    
                    // Try direct exact match first
                    for (const textWord of textWords) {
                        if (textWord === inputWord) {
                            matchedWords.push(inputWord);
                            found = true;
                            break;
                        }
                    }
                    
                    // If not found, try with OCR substitutions
                    if (!found) {
                        const ocrSubstitutions = {
                            '0': 'o', '1': 'l', '5': 's', '8': 'b', '6': 'g',
                            'o': '0', 'l': '1', 's': '5', 'b': '8', 'g': '6',
                            'i': '1', 'I': '1', 'O': '0'
                        };
                        
                        let modifiedWord = inputWord;
                        for (const [from, to] of Object.entries(ocrSubstitutions)) {
                            modifiedWord = modifiedWord.replace(new RegExp(from, 'g'), to);
                        }
                        
                        for (const textWord of textWords) {
                            if (textWord === modifiedWord) {
                                matchedWords.push(inputWord);
                                found = true;
                                break;
                            }
                        }
                    }
                    
                    // For short names (2-3 characters), also check if it's NOT a substring of a longer word
                    if (!found && inputWord.length <= 3) {
                        // Check if the short name appears as a complete word, not as part of a longer word
                        const wordBoundaryRegex = new RegExp(`\\b${inputWord.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\b`, 'i');
                        if (wordBoundaryRegex.test(textLower)) {
                            matchedWords.push(inputWord);
                            found = true;
                        }
                    }
                }
                
                // All input words must be found for a match
                const allWordsMatched = inputWords.length === matchedWords.length;
                
                
                return allWordsMatched;
            };


            const fnameMatch = extractNameFromText(fname, cleanText);
            const lnameMatch = extractNameFromText(lname, cleanText);


            scanner.style.display = 'none';

            if (fnameMatch && lnameMatch) {
                ocrVerified = true;
                firstNameInput.classList.remove("mismatch");
                lastNameInput.classList.remove("mismatch");
                firstnameIcon.textContent = "✅";
                lastnameIcon.textContent = "✅";
                
                const accountType = accountTypeDriver && accountTypeDriver.checked ? 'driver' : 'client';
                const idType = accountType === 'driver' ? 'Driver\'s License/ID' : 'ID';

                Swal.fire({
                    icon: 'success',
                    title: 'ID Verified Successfully!',
                    html: `
                        <div class="text-start">
                            <p><b>Detected ID Type:</b> ${detectedType}</p>
                            <p>✅ Your name matches the ${idType}!</p>
                            <p class="text-muted small">Identity verified. Please complete the remaining fields.</p>
                        </div>
                    `,
                    confirmButtonColor: '#198754',
                    width: '600px'
                });
            } else {
                const unmatched = [];
                if (!fnameMatch) unmatched.push("First Name");
                if (!lnameMatch) unmatched.push("Last Name");

                const shouldClear = !fnameMatch || !lnameMatch; // only clear if core names mismatch
                if (shouldClear) {
                    document.getElementById('id-upload').value = "";
                    container.style.display = 'none';
                    retakeBtn.style.display = 'none';
                }

                const accountType = accountTypeDriver && accountTypeDriver.checked ? 'driver' : 'client';
                const idType = accountType === 'driver' ? 'Driver\'s License/ID' : 'ID';
                const message = `❌ ${unmatched.join(", ")} not found on the ${idType}.${shouldClear ? '<br><br>The uploaded image has been cleared.' : ''}`;
                
                Swal.fire({
                    icon: 'error',
                    title: 'Name Mismatch',
                    html: `
                        <div><b>Detected ID:</b> ${detectedType}</div>
                        <div class="mt-2">
                            <p>${message}</p>
                            <p><strong>What you can do:</strong></p>
                            <ul class="text-start">
                                <li>Check your spelling in the form</li>
                                <li>Retake a clearer photo of your ${idType}</li>
                                <li>Ensure you're using your legal name as shown on the ${idType}</li>
                            </ul>
                        </div>
                    `,
                    confirmButtonColor: '#dc3545',
                    width: '700px'
                });
            }

            setFormDisabled(false); // Re-enable after scan
            isScanning = false;
            updateSubmit();
        }).catch(err => {
            console.error(err);
            document.getElementById('id-upload').value = "";
            Swal.fire({
                icon: 'error',
                title: 'OCR Error',
                text: 'There was an error reading the ID image.',
                confirmButtonColor: '#dc3545'
            });
            setFormDisabled(false); // Re-enable after scan error
            isScanning = false;
        });
    }
});
</script>

</body>
</html>
</html>

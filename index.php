<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Digital Judging System</title>
    <link rel="stylesheet" href="gate_style.css">
</head>
<body>
    <div class="parent landing-main">
        <h1 class="landing-title">Digital Judging System</h1>
        <div class="landing-btn-row">
            <button class="btn login-btn" type="button">LOGIN</button>
            <button class="btn signup-btn" type="button">SIGNUP</button>
        </div>
    </div>

    <!-- Login Modal -->
    <div id="loginModal" class="modal">
      <div class="modal-content">
        <span class="close" onclick="closeModal('loginModal')">&times;</span>
        <div id="login-error" style="color:#e53935;text-align:center;margin-bottom:1rem;"></div>
        <form id="loginForm" autocomplete="off">
          <h2 style="margin-bottom:1rem;text-align:center;">Login</h2>
          <label for="login-username">Username</label>
          <input type="text" id="login-username" name="username" required>
          <label for="login-password">Password</label>
          <input type="password" id="login-password" name="password" required>
          <button type="submit" class="btn" style="width:100%;margin-top:1rem;">Login</button>
          <div style="text-align:right;margin-top:0.5rem;">
            <a href="#" class="forgot-link" style="font-size:0.95rem;">Forgot password?</a>
          </div>
        </form>
      </div>
    </div>

    <!-- Signup Modal -->
    <div id="signupModal" class="modal">
      <div class="modal-content">
        <span class="close" onclick="closeModal('signupModal')">&times;</span>
        <div id="signup-error" style="color:#e53935;text-align:center;margin-bottom:1rem;"></div>
        <form id="signupForm" autocomplete="off">
          <h2 style="margin-bottom:1rem;text-align:center;">Sign Up</h2>
          <label for="signup-username">Username</label>
          <input type="text" id="signup-username" name="username" required autocomplete="username">
          <label for="signup-email">Email</label>
          <input type="email" id="signup-email" name="email" required autocomplete="email">
          <label for="signup-password">Password</label>
          <input type="password" id="signup-password" name="password" required autocomplete="new-password">
          <label for="signup-password2">Re-type Password</label>
          <input type="password" id="signup-password2" name="password2" required autocomplete="new-password">
          <button type="submit" class="btn" style="width:100%;margin-top:1rem;">Sign Up</button>
        </form>
      </div>
    </div>

    <!-- Success Popup -->
    <div id="successPopup" class="modal" style="background:rgba(0,0,0,0.3);">
      <div class="modal-content" style="max-width:320px;text-align:center;">
        <h3 style="color:#2e7d32;margin-bottom:1rem;">Account Created Successfully</h3>
        <button class="btn" onclick="closeModal('successPopup')">OK</button>
      </div>
    </div>

    <footer>
        <p>2025 Digital Judging System</p>
        <p>All rights reserved</p>
        <p>Developed by Hans Anuta and Kezia Carillo</p>
        <p>Version 1.0</p>
    </footer>

    <script src="gate_script.js"></script>
</body>
</html>
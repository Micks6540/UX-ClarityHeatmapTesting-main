<?php
session_start();

if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Librow</title>

<style>
    * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
    body {
    font-family: "Inter", Arial, sans-serif;
    background: #f4f6f6;
    color: #1a1a1a;
    overflow-x: hidden;
    min-height: 100vh; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    padding: 24px;
}
/* container for auth boxes */ .container { 
  width: 560px; background: #fff; 
  border-radius: 12px; padding: 20px; 
  box-shadow: 0 8px 20px rgba(15,20,30,0.07); 
  margin-bottom: 12px; 
  display: none; /* default hidden; we show #signin below */ 
}

  /* show sign-in by default */ 
  #signin { display: block; 
  } 

  h2 { font-size: 22px; 
    color: #214d25;
    margin-bottom: 14px; 
    text-align: left; 
  }
  
  .auth-form label { 
    display: block; 
    font-size: 13px; 
    margin: 10px 0 6px; 
    color: #444; 
    margin-bottom: 12px; 
  }

  .auth-form input[type="text"], 
  .auth-form input[type="password"], 
  .auth-form input[type="file"], 
  .auth-form input[type="search"] { 
    width: 100%; 
    padding: 10px 12px; 
    border-radius: 10px; 
    border: 1px solid #d6dbe0; 
    font-size: 14px; 
    background: #fff; 
  }

  .auth-form input[type="file"] { 
    padding: 8px 6px; } 

  .btn { 
    width: 100%; 
    padding: 12px; 
    border-radius: 10px; 
    border: none; 
    background: #214d25; 
    color: #fff; 
    font-size: 15px; 
    margin-top: 14px; 
    cursor: pointer; 
  }

  .link { 
    text-align: center; 
    margin-top: 12px; 
    font-size: 14px; 
  } 
  .link a { 
    color: #214d25; 
    font-weight: 600; 
    cursor: pointer; 
  }

  .small-link { font-size: 13px; margin-top: 8px; } 
  .small-link a { color: #214d25; text-decoration: none; } 
  .note { font-size: 12px; color: #6a717a; margin-top: 8px; }
</style>

</head>

<body>

<!-- SIGN IN PAGE -->
<div class="container" id="signin">
    <h2>Sign-in</h2>

    <!-- NOTE: action will point to your real handler later -->
    <form action="handle_login.php" method="POST" class="auth-form">
        <label>ID Number</label>
        <input type="text" name="id_number" placeholder="123456789" required />

        <label>Password</label>
        <input type="password" name="password" placeholder="Password" required />

        <p class="small-link"><a href="#">Forgot Password?</a></p>

        <button class="btn" type="submit">Sign in</button>
    </form>

    <div class="link">
        Don't have an account?
        <a onclick="showSignup()" role="button">Sign up</a>
    </div>
</div>

<!-- SIGN UP PAGE -->
<div class="container" id="signup" aria-hidden="true">
    <h2>Sign-up</h2>

    <form action="handle_signup.php" method="POST" enctype="multipart/form-data" class="auth-form">
        <label>Username</label>
        <input type="text" name="username" placeholder="Your name" required />

        <label>ID Number</label>
        <input type="text" name="id_number" placeholder="123456789" required />

        <label>Password</label>
        <input type="password" name="password" required />

        <label>Confirm password</label>
        <input type="password" name="confirm_password" required />

        <label>Upload ID picture</label>
        <input type="file" name="id_picture" accept="image/*" required />

        <p class="note">Please wait at least 24hrs to validate your account.</p>

        <button class="btn" type="submit">Sign up</button>
    </form>

    <div class="link">
        Already have an account?
        <a onclick="showSignin()" role="button">Sign in</a>
    </div>
</div>

<script src="script.js"></script>
</body>
</html>

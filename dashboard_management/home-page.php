<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Waste Management Home</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      height: 100vh;
      background: url('home_page_animation.php') no-repeat center center/cover;
      position: relative;
      overflow: hidden;
    }
    .background-overlay {
      position: absolute;
      top: 0;
      left: 0;
      height: 100%;
      width: 100%;
      background-color: rgba(169, 169, 169, 0.5); /* Darker gray overlay */
      z-index: 1;
    }
    .navbar {
      position: absolute;
      top: 0;
      right: 0;
      display: flex;
      justify-content: flex-end;
      gap: 30px;
      padding: 15px 30px;
      background-color: rgba(57, 51, 99, 0.9);
      width: 100%;
      box-sizing: border-box;
      z-index: 1000;
      height: 60px;
    }
    .navbar a {
      color: #ffffff;
      text-decoration: none;
      font-size: 16px;
      transition: color 0.3s;
      line-height: 30px;
    }
    .navbar a:hover {
      color: #aef4ae;
    }
    .top-header {
      position: absolute;
      top: 60px;
      width: 100%;
      background-color: rgba(0, 0, 0, 0.7);
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 15px 0;
      box-sizing: border-box;
      z-index: 900;
    }
    .logo-title {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }
    .logo-title img {
      height: 50px;
    }
    .logo-title h2 {
      margin: 0;
      font-size: 28px;
      font-weight: bold;
      color: white;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.7);
    }
    .system-title {
      margin-top: 8px;
    }
    .system-title p {
      margin: 0;
      font-size: 18px;
      color: white;
      text-shadow: 1px 1px 3px rgba(0,0,0,0.6);
    }
    .content {
      position: relative;
      z-index: 2;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100%;
    }
    a.button {
      margin-top: 350px;
      display: inline-block;
      padding: 15px 40px;
      font-size: 20px;
      background-color: rgb(28, 45, 203);
      color: white;
      text-decoration: none;
      border-radius: 30px;
      transition: background 0.3s, transform 0.3s;
    }
    a.button:hover {
      background-color: rgb(40, 126, 202);
      transform: scale(1.05);
    }
  </style>
</head>
<body>

  <div class="background-overlay"></div>

  <div class="navbar">
    <a href="#">Home</a>
    <a href="#">Contact</a>
    <a href="#">About Us</a>
    <a href="logout.php">Log Out</a>
  </div>

  <div class="top-header">
    <div class="logo-title">
      <img src="../assets/img/illustrations/illustration-signup.png" alt="Bago City Logo">
      <h2>BAGO CITY CEMO</h2>
    </div>
    <div class="system-title">
      <p>Bago City IoT Waste Management and Tracking System with Predictive Analytics</p>
    </div>
  </div>

  <div class="content">
    <a href="../login_page/sign-in.php" class="button">Get Started</a>
  </div>

</body>
</html>

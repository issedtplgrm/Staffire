<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../assets/css/login.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alata&family=Geist+Pixel&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
</head>

<body>

    <div class="grid" id="grid"></div>
    <main class="login-wrapper">
        <section class="left-panel">
            <div class="recent-header">
                <img src="../assets/img/recent.png" class="recent-icon" alt="">
                <h2>Recent Time-ins</h2>
            </div>

            <div class="time-card">

                <div class="employee-name">
                    Iced Pilgrim
                </div>

                <div class="time-column">
                    <span class="column-title">Time-in</span>
                    <span class="column-value">00:00:00</span>
                </div>

                <div class="time-column">
                    <span class="column-title">Status</span>
                    <span class="status">Present</span>
                </div>

            </div>
        </section>

        <form class="right-panel" action="../process/login-process.php" method="POST">
            <h1>Welcome to <span>Staffire!</span></h1>
            <h2>Sign in to continue</h2>
            <div class="field-group">
                <input type="text" id="email" placeholder=" " name="email" required>
                <label for="email">Email</label>
            </div>

            <div class="field-group">
                <input type="password" id="password" placeholder=" " name="password" required>
                <label for="password">Password</label>
            </div>

            <button type="submit" class="login-btn">
                Sign In
            </button>
            <h3>Don't have an acccount yet? <span>Contact your administrator</span></h3>
        </form>
    </main>

    <script src="../js/login.js"></script>
</body>

</html>
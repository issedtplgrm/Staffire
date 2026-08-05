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
    <main class="card">
        <form action="">
            <h1>Welcome to <span>Staffire!</span></h1>
            <h2>Sign in to continue</h2>
            <div class="field-group">
                <input type="text" id="email" placeholder=" " required>
                <label for="email">Email</label>
            </div>

            <div class="field-group">
                <input type="password" id="password" placeholder=" " required>
                <label for="password">Password</label>
            </div>
        </form>
    </main>

    <script src="../js/login.js"></script>
</body>

</html>
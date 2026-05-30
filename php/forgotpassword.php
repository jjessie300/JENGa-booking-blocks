<?php
// Nikola, Gabrielle
// Everything related to a forgotten password (reset, instruction pages, etc.)
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/sendEmail.php'; //File for sending out emails

// Run php code when form submitted with POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // User requested reset password link, first form submission where they enter their email
    if (isset($_POST["email"])){

        $email = $_POST['email'] ?? '';

        //Used for the link
        $reset_token = bin2hex(random_bytes(32));

        $statement = $conn->prepare('SELECT name FROM Users WHERE email = ?');
        $statement->bind_param('s', $email);
        $statement->execute();
        $result = $statement->get_result();

        $user = $result->fetch_assoc();

        $statement->close();

        if ($user){
            $update_statement = $conn-> prepare("UPDATE Users SET password_token = ? WHERE email = ?");
            $update_statement->bind_param("ss", $reset_token, $email);
            $update_statement->execute();
            $update_statement->close();

            $sentSuccess = sendPasswordResetEmail($email, $user["name"], $reset_token);
        }
        echo '<!DOCTYPE html>

            <html lang="en">

            <head>
                <meta charset="UTF-8" />
                <title>JENGa Booking Blocks</title>
                <!-- importing font -->
                <link
                href="https://fonts.googleapis.com/css2?family=Lora:wght@400;700&display=swap"
                rel="stylesheet"
                />
                <link rel="icon" type="image/png" href="../images/martlet.png" />
                <link rel="stylesheet" href="../css/logincreateStyle.css">
            </head>

                <body>
                    <div class="card-brick-wrapper">
                        <div class="blocks1">
                            <img src="../images/bricks.png" />
                            </div>
                            <div class="blocks2">
                            <img src="../images/bricks.png" />
                            </div>

                            <!-- white card -->
                            <div class="card">
                            <div class="brandheader">
                                <img src="../images/martlet.png" alt="mcgill martlet logo" />
                                <span class="brandname">JENGa Booking Blocks</span>
                            </div>
                            <p class="forgotpasstext"> Forgot your password? <br/>
                            If this account exists, an email has been sent on how to reset the password. </br>
                            </p>
                            <a href="login.php">Go back to login page </a>
                        </div>
                    </div>
                </body>
            </html>';
        exit();


    }

    // User submitted form with the new password they wish to use
    else if (isset($_POST["reset_password_button"]) && isset($_POST["token"])){

        $token = $_POST['token'];
        $new_password = $_POST['password'];

        $hash = password_hash($new_password, PASSWORD_DEFAULT);

        // Setting password token back to null so it cant be used again after this
        $update_statement = $conn->prepare("UPDATE Users SET password = ?, password_token = NULL WHERE password_token = ?");
        $update_statement->bind_param('ss', $hash, $token);
        $update_statement->execute();

        if ($update_statement->affected_rows > 0){
            // Go to html below
        }
        else {
            echo "Error updating password. This is awkward";
        }
        $update_statement->close();
        ?>
        <!doctype html>

        <html lang="en">
        <head>
            <meta charset="UTF-8" />
            <title>JENGa Booking Blocks</title>
            <!-- importing font -->
            <link
            href="https://fonts.googleapis.com/css2?family=Lora:wght@400;700&display=swap"
            rel="stylesheet"
            />
            <link rel="icon" type="image/png" href="../images/martlet.png" />
            <link rel="stylesheet" href="../css/accountverifiedStyle.css" />
        </head>

        <body>
            <div class="blocks1">
            <img src="../images/blocks.png" />
            </div>
            <div class="blocks2">
            <img src="../images/blocks.png" />
            </div>

            <!-- white card -->
            <div class="card">
            <div class="brandheader">
                <img src="../images/martlet.png" alt="mcgill martlet logo" />
                <span class="brandname">JENGa Booking Blocks</span>
            </div>

            <p class="AboutBlurb">
                Your password has been reset!<br /><br />
                You can close this tab <br />
                and return to the login page.
            </p>
            </div>
        </body>
        </html>

        <?php
        exit();

    }


}

// Should only be able to arrive here by clicking on the password reset link from email
else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['token'])) {

    //Grab the token from the URL
    $token = $_GET['token'];

    // Search for a user that has this specific token
    $statement = $conn->prepare("SELECT user_id FROM Users WHERE password_token = ? LIMIT 1");
    $statement->bind_param("s", $token);
    $statement->execute();
    $result = $statement->get_result();
    $user = $result->fetch_assoc();
    $statement->close();

    if ($user){
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8" />
            <title>Reset Password - JENGa Booking Blocks</title>
            <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;700&display=swap" rel="stylesheet" />
            <link rel="icon" type="image/png" href="../images/martlet.png" />
            <link rel="stylesheet" href="../css/logincreateStyle.css">
        </head>
        <body>
            <div class="card-brick-wrapper">
                <div class="blocks1">
                    <img src="../images/bricks.png" />
                </div>
                <div class="blocks2">
                    <img src="../images/bricks.png" />
                </div>

                <div class="card">
                    <div class="brandheader">
                        <img src="../images/martlet.png" alt="mcgill martlet logo" />
                        <span class="brandname">JENGa Booking Blocks</span>
                    </div>
                    
                    <p class="forgotpasstext"> 
                        Enter the new password you wish to set for your account. <br />
                        (Length must be between 8-64 characters) <br />
                    </p>
                    
                    <form method="POST" action="forgotpassword.php">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <label class="labels" for="password">New Password</label>
                        <input
                            class="input"
                            type="password"
                            id="password"
                            name="password"
                            required
                        />

                        <button type="submit" name="reset_password_button" id="reset_password_button" class="loginL"> 
                            Update Password 
                        </button>
                    </form>
                </div>
            </div>


        <script>
            const password = document.getElementById("password"); 
            const submit_btn = document.getElementById("reset_password_button");
            
            function validatePassword() {
                if (password.value.length < 8 || password.value.length > 64 || password.value.includes("  ")){
                submit_btn.disabled = true;
                submit_btn.style.opacity = "0.5"; // Show disabled
                submit_btn.style.cursor = "not-allowed";
                } else {
                // Valid, restore og properties
                submit_btn.disabled = false;
                submit_btn.style.opacity = "1";
                submit_btn.style.cursor = "pointer";
                }
            }
            password.addEventListener("input", validatePassword);

        </script>
        <?php
        exit();
    }
    else {
        echo "Invalid token";
        exit();
    }
    
}
?>



<!-- Not a POST request so we display the html instead -->
<!DOCTYPE html>

<html lang="en">

  <head>
    <meta charset="UTF-8" />
    <title>JENGa Booking Blocks</title>
    <!-- importing font -->
    <link
      href="https://fonts.googleapis.com/css2?family=Lora:wght@400;700&display=swap"
      rel="stylesheet"
    />
    <link rel="icon" type="image/png" href="../images/martlet.png" />
    <link rel="stylesheet" href="../css/logincreateStyle.css">
  </head>

    <body>
        <div class="card-brick-wrapper">
            <div class="blocks1">
                <img src="../images/bricks.png" />
                </div>
                <div class="blocks2">
                <img src="../images/bricks.png" />
                </div>

                <!-- white card -->
                <div class="card">
                <div class="brandheader">
                    <img src="../images/martlet.png" alt="mcgill martlet logo" />
                    <span class="brandname">JENGa Booking Blocks</span>
                </div>
                <p class="forgotpasstext"> Forgot your password? <br />
                Enter your email address and you'll be able to reset your password from there
                </p>
                
                <form action="forgotpassword.php" method="POST">

                    <label class="labels" ></label>
                    <input
                        class="input"
                        type="email"
                        id="emails"
                        name="email"
                        required
                    />

                    <button type="submit" class="loginL">Send Email</button>


                </form>
            </div>
        </div>

    </body>
</html>
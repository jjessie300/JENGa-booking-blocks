<?php
/*
Page where new users can create a new account
Gabrielle, Nikola (form processing + JS)
*/
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// Run php code when form submitted with POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    require_once __DIR__ . '/../PHPMailer/Exception.php';
    require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/../PHPMailer/SMTP.php';
    require_once __DIR__ . '/../db.php';
    require_once __DIR__ . '/sendEmail.php'; //File for sending out emails


    // Set to values or empty if not found
    $email = $_POST['email'] ?? '';
    $name = $_POST['name'] ?? '';
    $password = $_POST['password'] ?? '';

    $acc_type = "";

    // Email domain check
    // Profs
    if (str_ends_with($email, "@mcgill.ca")) {
        $acc_type = "owner";
    }
    // Students
    else if (str_ends_with($email, "@mail.mcgill.ca")) {
        $acc_type = "user";
    }
    // Check already exists in html, leaving it here nonetheless
    else {
        // Redirect if not mcgill email
        header("Location: createaccount.php");
        exit();
    }

    // Verify if user already signed up
    $statement = $conn->
        prepare("SELECT COUNT(*) AS num_rows FROM Users WHERE email = ?");
    $statement->bind_param("s", $email);

    $statement->execute();
    $result = $statement->get_result();
    $record = $result->fetch_assoc();

    // Email already exists
    if ($record["num_rows"] > 0){
        header("Location: createaccount.php?error=duplicate"); // Attach duplicate error when redirecting to createaccount.php
    exit();

    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(32));


    $statement = $conn->
    prepare("INSERT INTO Users (email, name, password, acc_type, verification_token, is_verified) 
    VALUES (?, ?, ?, ?, ?, 0)");

    // Create user with form data and added hash, account_type, and a "temp" verif token
    $statement->bind_param("sssss", $email, $name, $hash, $acc_type, $token);


    if ($statement->execute()) {

        // Call function from our sendEmail.php file
        $sentSuccess = sendVerificationEmail($email, $name, $token);

        if ($sentSuccess){
            // The email went through. Redirect to login.
            header("Location: login.php?notice=verifyinbox");
            exit();
        }

        else {
            echo "Hopefully you (we) never see this error message.";
            exit();
        }
    }
   
  
}


?>

<!-- Not a POST request so we display the html instead -->
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
    <link rel="stylesheet" href="../css/logincreateStyle.css" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        <form id="createaccountForm" action="createaccount.php" method="POST">
          <label class="labels" for="email">Name</label>
          <input
            class="input"
            id="name"
            name="name"
            placeholder="Enter Your Name"
          />

          <label class="labels" for="email">McGill Email</label>
          <input
            class="input"
            type="email"
            id="email"
            name="email"
            placeholder="joseph.vybihal@mail.mcgill.ca"
          />

          <label class="labels" for="password">Password</label>
          <input
            class="input"
            type="password"
            id="password"
            name="password"
            placeholder="Enter Your Password"
            required
          />

          <label class="labels" for="confirm_password">Re-Enter Password</label>
          <input
            class="input"
            type="password"
            id="confirm_password"
            name="password"
            placeholder="Re-Enter Your Password"
            required
          />

          <button class="createaccountC">Create Account</button>
          <p class="existinguser">Already Have An Account?</p>
          <a href="login.php" class="loginC">Login</a>
        </form>
      </div>
    </div>
    <!-- error popup for wrong email address domain-->
    <div id="EmailErrorPopup">
      <div class="popup-card">
        <h2>Uh Oh!</h2>
        <h2>McGill Email Required</h2>
        <p>
          JENGa Booking Blocks is only available to McGill students and staff.
          Please sign in with your <red>@mail.mcgill.ca</red> or
          <red>@mcgill.ca</red> email address.
        </p>

        <button type="button" class="PopupTryAgain" id="tryAgainEmailButton">
          Try a different email
        </button>
      </div>
    </div>

    <!-- error popup for account already signed up -->
    <div id="DuplicateErrorPopup">
      <div class="popup-card">
        <h2>Uh Oh!</h2>
        <h2>Email Already in Use</h2>
        <p>
          JENGa Booking Blocks has detected an account with that email address
          already in use. Please use the login page instead.
        </p>

        <button
          type="button"
          class="PopupTryAgain"
          onclick="window.location.href = 'login.php'"
        >
          Login
        </button>
      </div>
    </div>

    <script>
      const fullURL = window.location.href;
      const errorURLCheck = new URL(fullURL);

      // Account already exists
      if (errorURLCheck.searchParams.get("error") === "duplicate") {
        document.getElementById("DuplicateErrorPopup").style.display = "flex";
      }

      // Event listener to verify that passwords match
      const password = document.getElementById("password");
      const confirm_password = document.getElementById("confirm_password");
      const submit_btn = document.querySelector(".createaccountC");

      // Function checks for password validity
      // remark: as per NIST, users should be allowed to input spaces and special chars
      // however, maybe it shouldn't be great to have consecutive spaces, so we'll respect that
      // https://pages.nist.gov/800-63-4/sp800-63b/passwords/
      function validatePasswords() {
        if (
          password.value !== confirm_password.value ||
          password.value.length < 8 || password.value.length > 64 || password.value.includes("  ")
        ) {
          submit_btn.disabled = true;
          submit_btn.style.opacity = "0.5"; // Show disabled
          submit_btn.style.cursor = "not-allowed";
        } else {
          // Passwords match, restore og properties
          submit_btn.disabled = false;
          submit_btn.style.opacity = "1";
          submit_btn.style.cursor = "pointer";
        }
      }
      password.addEventListener("input", validatePasswords);
      confirm_password.addEventListener("input", validatePasswords);

      

      // Event listener to catch non McGill emails before sending form over to createaccount.php
      // Referenced source below on how to intercept an event (form submission)
      // https://www.xjavascript.com/blog/intercept-a-form-submit-in-javascript-and-prevent-normal-submission/
      const createaccountForm = document.getElementById("createaccountForm");
      const inputEmail = document.getElementById("email");
      const popupError = document.getElementById("EmailErrorPopup");

      function interceptEmail(event) {
        const email = inputEmail.value;

        const isValid =
          email.endsWith("@mail.mcgill.ca") || email.endsWith("@mcgill.ca");

        if (!isValid) {
          event.preventDefault();
          popupError.style.display = "flex";
        }
      }
      createaccountForm.addEventListener("submit", interceptEmail);

      // Event listener to dismiss email in use popup
      const tryAgainEmailButton = document.getElementById(
        "tryAgainEmailButton",
      );

      function clearTryAgain() {
        popupError.style.display = "none";
        inputEmail.value = "";
        inputEmail.focus(); // Set cursor to now emptied out input box
      }
      tryAgainEmailButton.addEventListener("click", clearTryAgain);
    </script>
  </body>
</html>




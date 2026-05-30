<?php
/*
login.php:
Where users can log into their account. accessible after homepage/index.php -->
Gabrielle, Nikola (Form processing + JS)*/

session_start();
require_once __DIR__ . '/../db.php';

// Run php code when form submitted with POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // If form empty, sets fields as empty strings
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';


    $statement = $conn->prepare('SELECT * FROM Users WHERE email = ?');
    // Binding email to user
    $statement->bind_param('s', $email);
    // Execute query
    $statement->execute();
    $result = $statement->get_result();
    // Create array user with associated data, if DNE, then user empty
    $user_data = $result ? $result->fetch_assoc() : null;
    $statement->close();

    $user = null;

    // Verify if user already signed up

    if (!$user_data) {
    // User's email does not exist, give popup service only for mcgill users.
            header("Location: login.php?error=noaccount");
            exit();
    }


    // Cannot find user (error message doesn't support this rn, TODO) or password incorrect
    if (!(password_verify($password, $user_data['password']))){
        header("Location: login.php?error=incorrectpassword");
        exit();
    }

    // User isn't verified yet
    // Instruct them to go to email
    else if ($user_data['is_verified'] === 0) {
    // User unverified, redirect with error param set.
            header("Location: login.php?error=unverified");
            exit();
    }

    // User is verified
    else {
        $user = $user_data; //match, set user to pulled data
        $_SESSION['user_id'] = (int) $user['user_id']; // Store user ID in server memory, used for staying logged in between pages
        $_SESSION['email'] = $user['email']; 
        $_SESSION['name'] = $user['name']; 
        $_SESSION['acc_type'] = $user['acc_type']; 
        // CHANGE REDIRECT BELOW: BE MINDFUL OF PATHS
        if (isset($_SESSION['oh_url'])) {
            $redirect = $_SESSION['oh_url'];  // Gets /pages/officehours.php?owner=ID
            unset($_SESSION['oh_url']);
            header("Location: " . $redirect);  // Sends user to owner oh booking page
            exit();
        } else {
            // Go to default dashboard, no saved redirect
            header("Location: ../pages/myappointments.php");
            exit();
        }
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
        
      <form action="login.php" method="POST">

      <label class="labels" for="email">McGill Email</label>
      <input
        class="input"
        type="email"
        id="emails"
        name="email"
        placeholder="joseph.vybihal@mail.mcgill.ca"
        required
      />

      <div class="label-row">
        <label class="labels" for="email">Password</label>
        <a href="forgotpassword.php" class="forgotpass">Forgot Password?</a>
      </div>
      <input
        class="input"
        type="password"
        id="password"
        name="password"
        placeholder="Enter Your Password"
        required
      />

      <button type="submit" class="loginL">Login</button>
      <p class="newuser">New to Booking Blocks?</p>
      <a href="createaccount.php" class="createaccountL">Create Account?</a>
      </form>
    </div>
  </div>
    <!-- Error popup when no account exists from login -->
    <div id="NoAccountErrorPopup">
      <div class="popup-card">
        <h2>Uh Oh!</h2>
        <h2>Account Not Found</h2>
        <p>
				JENGa Booking Blocks is only available to McGill students and staff.
				Please sign in or create an account with your <red>@mail.mcgill.ca</red> or <red>@mcgill.ca</red> email address.
			</p>

        <button type="button" class="PopupTryAgain" id="noAccountButton">
          Try a different email
        </button>
      </div>
    </div>


    <!-- Friendly reminder to check email inbox before login -->
    <div id="CheckInboxPopup">
      <div class="popup-card">
        <h2>Heads Up!</h2>
        <p>
          We've sent you an email with a link to verify your account. <br />
          Please do this before logging in.
        </p>

        <button
          type="button"
          class="PopupTryAgain"
          id="PopupReminder"
        >
          Got it
        </button>
      </div>
    </div>



    <!-- Popup for Unverified account trying to log in -->
    <div id="UnverifiedAccountPopup">
      <div class="popup-card">
        <h2>Uh Oh!</h2>
        <p>
          Your login attempt failed. <br />
          Please verify your account first using the email that was sent to your inbox.
        </p>

        <button
          type="button"
          class="PopupTryAgain"
          id="unverifiedAccountButton"
        >
          Got it
        </button>
      </div>
    </div>


    <!-- Popup for Incorrect Password entered -->
    <div id="IncorrectPasswordPopup">
      <div class="popup-card">
        <h2>Uh Oh!</h2>
        <p>
          Your password is incorrect. <br />
          Please try again.
        </p>

        <button
          type="button"
          class="PopupTryAgain"
          id="incorrectPasswordButton"
        >
          Got it
        </button>
      </div>
    </div>


    <script>
      const fullURL = window.location.href;
      const URLCheck = new URL(fullURL);

      if (URLCheck.searchParams.get("notice") === "verifyinbox") {
        document.getElementById("CheckInboxPopup").style.display = "flex";
      }

      else if (URLCheck.searchParams.get("error") === "noaccount") {
        document.getElementById("NoAccountErrorPopup").style.display = "flex";
      }


      else if (URLCheck.searchParams.get("error") === "unverified") {
        document.getElementById("UnverifiedAccountPopup").style.display = "flex";
      }

      else if (URLCheck.searchParams.get("error") === "incorrectpassword") {
        document.getElementById("IncorrectPasswordPopup").style.display = "flex";

      }

      const reminderButton = document.getElementById("PopupReminder");
      const verifyButton = document.getElementById("unverifiedAccountButton");

      function closePopupReminder(){
        document.getElementById("CheckInboxPopup").style.display = "none";
        window.history.replaceState(null, '', window.location.pathname); //(not storing anything new in history, not assigning title to state, URL without params)
      }

      function closeunverifiedAccountButton(){
        document.getElementById("UnverifiedAccountPopup").style.display = "none";
        window.history.replaceState(null, '', window.location.pathname); //(not storing anything new in history, not assigning title to state, URL without params)
      }

      // Event listener to dismiss incorrect password popup
      const inputPassword = document.getElementById("password");
      
      const incorrectPasswordButton = document.getElementById(
        "incorrectPasswordButton",
      );
      function closePopupIncorrectPassword() {
        document.getElementById("IncorrectPasswordPopup").style.display = "none";
        window.history.replaceState(null, '', window.location.pathname); //(not storing anything new in history, not assigning title to state, URL without params)
        inputPassword.value = "";
        inputPassword.focus(); // Set cursor to now emptied out input box
      }

      // Event listener to dismiss no account found popup
      const noAccountPopup = document.getElementById("NoAccountErrorPopup");
      const noAccountButton = document.getElementById("noAccountButton");

      function clearNoAccount() {
        noAccountPopup.style.display = "none";
        window.history.replaceState(null, "", window.location.pathname); //(not storing anything new in history, not assigning title to state, URL without params)
      }
      

      reminderButton.addEventListener("click", closePopupReminder);
      verifyButton.addEventListener("click", closeunverifiedAccountButton);
      incorrectPasswordButton.addEventListener("click", closePopupIncorrectPassword);
      noAccountButton.addEventListener("click", clearNoAccount);
    </script>


  </body>
</html>


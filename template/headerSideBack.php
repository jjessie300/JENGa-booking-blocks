<!-- headerSideBack.php
 header and sidenav bar template that's imported to each dashboard, book block, create page -->

<!-- Gabrielle  -->
 
<?php 
session_start(); 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$userEmail = $_SESSION['acc_type'] ?? '';
$isStudent = ($accType = 'student');
$isProf    = !$isStudent;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'JENGa Booking Blocks' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;700&display=swap" rel="stylesheet">
    <link href='https://fonts.googleapis.com/css?family=Arimo' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    

    <link rel="icon" type="image/png" href="../images/martlet.png">

    <!-- Bootstrap (only include on pages that need the datepicker) -->
    <?php if (!empty($useBootstrap)): ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.10.0/css/bootstrap-datepicker3.standalone.min.css" rel="stylesheet">
    
    <?php endif; ?>
    
    
    <!-- perpage stylesheet -->
    <link rel="stylesheet" href="../css/headerSideBackStyle.css">
    <?php if (!empty($pageStyle)): ?>
    <link rel="stylesheet" href="../css/<?= $pageStyle ?>">
    <?php endif; ?>

</head>

<body>
    <div class="topnav">
        <a href="../pages/myappointments.php" class="logo">
            <img src="../images/white-martlet.png" alt="Martlet Logo" class="topnavlogo">
            <div class="headertitle">JENGa Booking Blocks</div>
        </a>
        <div class="navright"><?= $pageTitle ?? '' ?></div>
        <a href="javascript:void(0);" class="topnav-icons" onclick="openMenu()" title="Menu"><i class="fa fa-bars"></i></a>
    </div>
    <nav class="sidenav" id="mySidenav">
        <h1>MENU</h1>
        <a href="myappointments.php"  class="<?= ($activePage==='appointments')  ? 'active' : '' ?>">My Appointments</a>
        <a href="requestblock.php"        class="<?= ($activePage==='requestblock' || $activePage==='officehours' || $activePage==='bookcalmethod' || $activePage==='groupmeetings') ? 'active' : '' ?>">Book Blocks</a>
        <?php if ($_SESSION['acc_type'] === 'owner'): ?>
            <a href="createofficehour.php"   class="<?= ($activePage==='createofficehour' || $activePage==='createavailable' || $activePage==='creategroupmeeting' || $activePage==='calendarmethod') ? 'active' : '' ?>">Create Blocks</a>
        <?php endif; ?> 
        <?php if ($_SESSION['acc_type'] === 'user'): ?>
            <a href="calendarmethod.php"   class="<?= ($activePage==='creategroupmeeting' || $activePage==='createavailable' || $activePage==='creategroupmeeting' || $activePage==='calendarmethod') ? 'active' : '' ?>">Create Blocks</a>
        <?php endif; ?> 
        
        <button class="logout" id="Logout" onclick="window.location.href='../php/logout.php'">Logout</button>
    </nav>

<!-- following code modified from w3 schools example https://www.w3schools.com/howto/tryit.asp?filename=tryhow_js_topnav -->
<script>
    function openMenu() {
        document.getElementById("mySidenav").classList.toggle("responsive");
    }
</script>

    <div class="main">
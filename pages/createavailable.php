<?php
/*
<!-- createavailable.php corresponds to the page on our website titled "Create Blocks", 
 under the Group Meeting -> Find Availabilities -> Create Heatmap tab
 this page covers the bonus feature titled Heatmap, 
 allowing an owner to create a heatmap with customized beginning/end dates -->

 <!-- Jessie, Emily -->

<!-- php error check making sure logged in to access -->

*/
session_start(); 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$pageStyle =  "bookingstyles.css";
$pageTitle = "Create Block";
$activePage = "createavailable";
$useBootstrap = true;

include '../template/headerSideBack.php';

?>

<!-- page stuff here -->
 <div class="card bookblock-card">
    <div class="booking-type-row">
        <div class="type-columns">
            <div class="type-col1">
                <h1>Booking Type</h1>
                <div class="columns">
                    <?php if ($_SESSION['acc_type'] === 'owner'): ?>
                        <a href="createofficehour.php" class="type-btn <?= ($activePage === 'createofficehour') ? 'active' : '' ?>">
                            Office Hours
                        </a>
                    <?php endif; ?> 
                <a href="createavailable.php" class="type-btn <?= ($activePage === 'createavailable' || $activePage === 'creategroupmeeting') ? 'active' : '' ?>">Group Meeting</a>
                </div>
            </div>
            <div class="type-col2">
                <h1>Plan or Create</h1>
                <div class="columns">
                    <a href="createavailable.php" class="type-btn <?= ($activePage === 'createavailable') ? 'active' : '' ?>">Find Availabilities</a>
                    <a href="creategroupmeeting.php" class="type-btn <?= ($activePage === 'creategroupmeeting') ? 'active' : '' ?>">Create Meeting</a>
                </div>
            </div>
        </div>
    </div>

    <hr class="solid">

    <form action="../php/create_invite.php" method="POST"> 
        <div class="columns">
            <div class="col1">
                <h1>Availabilities Method</h1>
                <div class="columns">
                    <a href="calendarmethod.php" class="type-btn <?= ($activePage === 'calendarmethod') ? 'active' : '' ?>">Create Slots</a>
                    <a href="createavailable.php" class="type-btn <?= ($activePage === 'createavailable') ? 'active' : '' ?>">Create Heatmap</a>
                </div>
                <div class="container">
                    <div class="well">
                        <h2>Start Date:</h2>
                        <div class="date-range">
                            <div class="checkin-picker box"></div>
                            <h2>End Date:</h2>
                            <div class="checkout-picker box"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col2">
                <h2>Group Name</h2>
                
                <input type="text" class="withinput" id="group-input" placeholder="Create a Group Name" name="group">

                <h2>Members</h2>

                <div class="members-input-container">
                    <div class="email-tags" id="email-tags-container">
                        <!-- Tags here -->
                    </div>
                    <input type="email" id="member-input" class="tag-email-input" placeholder="Type email and press Enter" autocomplete="off">
                </div>
                <input type="hidden" id="members-emails" name="emails"> 


                <h2>Confirmation</h2>
                <div class="box">
                    <div class="box-row">
                        <span class="conf-label">Type:</span>
                        <span class="conf-value">Group Meeting</span>
                    </div>
                    <div class="box-row">
                        <span class="conf-label">Group Name:</span>
                        <span class="conf-value" id="group-value">-</span>
                    </div>
                    <div class="box-row">
                        <span class="conf-label">Start Date:</span>
                        <span class="conf-value" id="display-checkin">-</span>
                    </div>
                    <div class="box-row">
                        <span class="conf-label">End Date:</span>
                        <span class="conf-value" id="display-checkout">-</span>
                    </div>
                </div>
                
                <input type="hidden" name="start_date" id="start-input">
                <input type="hidden" name="end_date" id="end-input">

                <button class="submit-btn">Create Group</button>

            </div>
        </div>
    </form>
</div>

<!-- Generic error popup -->
<div id="ErrorPopup">
  <div class="popup-card">
    <h2 id="popupTitle"></h2>
    <p id="popupMessage"></p>
    
    <button class="PopupTryAgain" id="closePopup">Got it</button>
  </div>
</div>


<?php include '../template/headerSideBack_end.php'; ?>

<script>
    // then page specific js goes here
    function updateGroupValue(value) {
        if (!value) return '-';
        return value;
    }

    // April 20, 2026 format for conf box 
    function formatDate(date) {
        return date.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
    }

    document.getElementById('group-input').addEventListener('change', function() {
        document.getElementById('group-value').textContent = updateGroupValue(this.value);
    });
</script>


<script>
    // heavily editted from bootstrap example with 2 date pickers https://jsfiddle.net/azaret/25bqa6ho/
    // Start date 
    $('.checkin-picker').datepicker({
        todayHighlight: true,
        autoclose: true,
        format: 'yyyy-mm-dd'
    }).on('changeDate', function(e) {
        checkin_date = e.date;
        // Update display
        $('#display-checkin').html(formatDate(e.date));
        $('#start-input').val(e.format('yyyy-mm-dd')); // for create_invite.php 
    });

    // End date 
    $('.checkout-picker').datepicker({
        todayHighlight: true,
        autoclose: true,
        format: 'yyyy-mm-dd'
    }).on('changeDate', function(e) {
        checkout_date = e.date;
        // Update display
        $('#display-checkout').html(formatDate(e.date));
        $('#end-input').val(e.format('yyyy-mm-dd')); // for create_invite.php 
    });
    // end of datepicker code from example
</script>

<script>
    function showErrorPopup(title, message) {
        document.getElementById('popupTitle').innerHTML = title;
        document.getElementById('popupMessage').innerHTML = message;
        
        document.getElementById('ErrorPopup').style.display = 'flex';
    }

    // Close popup when clicking btn 
    document.getElementById('closePopup')?.addEventListener('click', function() {
        document.getElementById('ErrorPopup').style.display = 'none';
    });
</script> 


<script>
    let emailTags = [];

    // Check if email exists in db 
    async function checkEmailExists(email) {
        try {
            const response = await fetch('../php/check_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email: email })
            });
            const result = await response.json();
            return result.exists;
        } catch (error) {
            console.error('Error checking email:', error);
            return false;
        }
    }

    // Handle tags 
    async function addEmailTag(email) {
        email = email.trim().toLowerCase();
        
        // Check for duplicates in current tags 
        if (emailTags.includes(email)) {
            showErrorPopup('Duplicate Email', 'This email has already been added.');
            return false;
        }
        
        // Check if input email exists in db 
        const emailExists = await checkEmailExists(email);
        if (!emailExists) {
            showErrorPopup('Invalid email','${email} is not a registered McGill student or professor.');
            return false;
        }

        emailTags.push(email);
        updateEmailTagDisplay();
        return true;
    }

    function removeEmailTag(index) {
        emailTags.splice(index, 1);
        updateEmailTagDisplay();
    }

    // Render email tags and update hidden form for backend 
    function updateEmailTagDisplay() {
        const container = document.getElementById('email-tags-container');
        
        container.innerHTML = '';
        
        // Create tag elements 
        emailTags.forEach((email, index) => {
            const tag = document.createElement('div');
            tag.className = 'email-tag';
            tag.innerHTML = `${email}<span class="remove-tag" onclick="removeEmailTag(${index})">x</span>`;
            container.appendChild(tag);
        });
        
        document.getElementById('members-emails').value = emailTags.join(','); // for create_invite.php 
    }

    // Handle tags 
    const emailInput = document.getElementById('member-input');
    emailInput.addEventListener('keydown', async (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const email = emailInput.value;
            if (email) {
                const cleanEmail = email.replace(/,+$/, '');
                if (await addEmailTag(cleanEmail)) {
                    emailInput.value = '';
                }
            }
        }
    });

    // Click on container to focus input
    document.querySelector('.members-input-container').addEventListener('click', () => {
        document.getElementById('member-input').focus();
    });
</script> 


<script> 
    // From create_invite.php redirect
    const url = new URL(window.location.href);
    const errorParam = url.searchParams.get('error');

    if (errorParam === 'empty') {
        showErrorPopup('Missing Fields', 'Please enter all required fields.');
    } else if (errorParam === 'date') {
        showErrorPopup('Invalid Dates', 'Please select valid future date.');
    } else if (errorParam === 'members') {
        showErrorPopup('Missing Members', 'Please enter at least one members.');
    } else if (errorParam === 'duplicate_group') {
        showErrorPopup('Duplicate Group Name', 'A group with this name already exists. Please choose a different group name.');
    }

    document.getElementById("closePopup").addEventListener("click", () => {
        document.getElementById("ErrorPopup").style.display = "none";
        window.history.replaceState(null, '', window.location.pathname);
    });
</script> 



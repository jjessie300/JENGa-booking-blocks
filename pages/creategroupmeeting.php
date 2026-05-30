<?php
/*
<!-- Jessie, Gabrielle -->

<!-- php error check making sure logged in to access -->
*/

session_start(); 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$pageStyle =  "bookingstyles.css";
$pageTitle = "Create Block";
$activePage = "creategroupmeeting";
$useBootstrap = true;

include '../template/headerSideBack.php';

?>

<!-- page stuff here -->
 <div class="card">
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
                    <a href="calendarmethod.php" class="type-btn <?= ($activePage === 'calendarmethod' || $activePage === 'createavailable' || $activePage === 'creategroupmeeting') ? 'active' : '' ?>">Group Meeting</a>
                </div>
            </div>
            <div class="type-col2">
                <h1>Plan or Create</h1>
                <div class="columns">
                    <a href="calendarmethod.php" class="type-btn <?= ($activePage === 'calendarmethod' || $activePage === 'createavailable') ? 'active' : '' ?>">Find Availabilities</a>
                    <a href="creategroupmeeting.php" class="type-btn <?= ($activePage === 'creategroupmeeting') ? 'active' : '' ?>">Create Meeting</a>
                </div>
            </div>
        </div>
    </div>

    <form action="../php/create_gm.php" method="POST">
        <div class="columns">
            <div class="col1">
                <h2>Group Name</h2>
                <input type="hidden" name="group_name" id="group-name">
                <input text="text" list="group-list" class="withinput" id="group-input" placeholder="Search by Group Name">
                <datalist id="group-list"></datalist>

                <h2>Pick a Date</h2>
                <!-- Bootstraped the calendar in NOT MY ORIGINAL CODE -->

                <input type="hidden" name="selected_date" id="selected_date">

                <div class="box">
                    <div id="inline-calendar"></div>
                </div>

                <h2>Add a Time</h2>
                <div class="box">
                    <span class="box-row">
                        <input type="time" name="start_time" class="timeinput" id="from-input" placeholder="From" step="1800">
                        <input type="time" name="end_time" class="timeinput" id="to-input" placeholder="To" step="1800">
                    </span>
                </div>
            </div>
            <div class="col2">
                <h2>Recurring</h2>
                <div class="box">
                    <div class="radio-option">
                        <label class="radio-row">
                            <input type="radio" name="recurring" value="One-time" checked>
                            <span class="label-text">One-time</span>
                        </label>
                    </div>
                    <div class="radio-option">
                        <label class="radio-row">
                            <input type="radio" name="recurring" value="Daily">
                            <span class="label-text">Daily</span>
                        </label>
                        <input type="number" name="frequency" class="frequency-dropdown" min="1" value="1">
                    </div>
                    <div class="radio-option">
                        <label class="radio-row">
                            <input type="radio" name="recurring" value="Weekly">
                            <span class="label-text">Weekly</span>
                        </label>
                        <input type="number" name="frequency" class="frequency-dropdown" min="1" value="1">

                    </div>
                </div>

                <h2>Location</h2>
                <textarea class="member-details" id="location-input" placeholder="Enter meeting location or Zoom link" name="location"></textarea>

                <h2>Confirmation</h2>
                <div class="box">
                    <div class="box-row">
                        <span class="conf-label">Type:</span>
                        <span class="conf-value">Group Meeting</span>
                    </div>
                    <div class="box-row">
                        <span class="conf-label">With:</span>
                        <span class="conf-value" id="group-value">-</span>
                    </div>
                    <div class="box-row">
                        <span class="conf-label">Date:</span>
                        <span class="conf-value" id="date-value">-</span>
                    </div>
                    <div class="box-row">
                        <span class="conf-label">From:</span>
                        <span class="conf-value" id="from-value">-</span>
                    </div>
                    <div class="box-row">
                        <span class="conf-label">To:</span>
                        <span class="conf-value" id="to-value">-</span>
                    </div>
                    <div class="box-row">
                        <span class="conf-label">Repeat:</span>
                        <span class="conf-value" id="recurring-value">Never</span>
                    </div>
                    <div class="box-row">
                        <span class="conf-label">Location:</span>
                        <span class="conf-value" id="location-value">-</span>
                    </div>
                </div>

                <input type="hidden" name="meeting_data" id="meeting_input">
                <button class="submit-btn">Create Group Meeting</button>
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

<div id="InvalidGroupPopup">
  <div class="popup-card">
    <h2>Uh Oh!</h2>
    <p>Please select an exisiting group. Or, create a group on the 'Find Availabilities' page.</p>
    <button class="PopupTryAgain" id="invalidGroupPopup">Got it</button>
  </div>
</div>

<?php include '../template/headerSideBack_end.php'; ?>

<script>
    // then page specific js goes here
    function updateGroupValue(value) {
        if (!value) return '-';
        return value;
    }
    function updateLocationValue(value) {
        if (!value) return 'None';
        return value;
    }
    function formatTime(value) {
        if (!value) return '-';
        const [h, m] = value.split(':');
        const hour = parseInt(h);
        const ampm = hour < 12 ? 'AM' : 'PM';
        const displayHour = hour % 12 || 12;
        return `${displayHour}:${m} ${ampm}`;
    }

    function updateRecurring(value) {
        const recurring = document.querySelector('input[name="recurring"]:checked').value;
        const isRecurring = recurring !== 'One-time';
        if (!isRecurring) {
            return 'Never';
        }
        return value;
    }

    document.getElementById('from-input').addEventListener('change', function() {
        document.getElementById('from-value').textContent = formatTime(this.value);
    });

    document.getElementById('to-input').addEventListener('change', function() {
        document.getElementById('to-value').textContent = formatTime(this.value);
    });

    document.getElementById('location-input').addEventListener('change', function() {
        document.getElementById('location-value').textContent = updateLocationValue(this.value);
    });

    document.querySelectorAll('input[name="recurring"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        document.getElementById('recurring-value').textContent = updateRecurring(this.value);
    });
});

</script>


<script>
  $(document).ready(function () {
    $('#inline-calendar').datepicker({
        todayHighlight: true,
        format: 'yyyy-mm-dd'
    }).on('changeDate', function (e) {
        $('#selected_date').val(e.format('yyyy-mm-dd'));

        // Update confirmation panel
        const d = e.date;
        const formatted = d.toLocaleDateString('en-US', {
            year: 'numeric', month: 'long', day: 'numeric'
        });
        document.getElementById('date-value').textContent = formatted;
    });
  });
</script>

<script>
    // Frequency dropdown for selected recurring option 
    document.querySelectorAll('input[name="recurring"]').forEach(radio => {
        radio.addEventListener('change', function(){
            document.querySelectorAll('.frequency-dropdown').forEach(drop =>{
                drop.classList.remove('visible');
            });

            const dropdown = this.closest('.radio-option').querySelector('.frequency-dropdown');

            if(dropdown){
                dropdown.classList.add('visible');
            }
        });
    });
</script>



<script> 
    let group_names = [];
    let selectedGroup = null;
    const groupInput = document.getElementById("group-input");
    const groupDatalist = document.getElementById("group-list");

    // Load group names your part of 
    fetch("../php/get_group_name.php")
        .then(res => {
            return res.json();
        })
        .then(data => {
            group_names = data;

            groupDatalist.innerHTML = data.map(g =>
                `<option value="${g.group_name}"></option>`
            ).join("");
        })
        .catch(err => {
            console.error("Fetch error:", err);
        });

    // Runs when user selects a group name 
    groupInput.addEventListener("change", function() {
        const selectedValue = this.value;
        
        // Get all options from datalist
        const options = Array.from(groupDatalist.options);
        const isExistingGroup = options.some(opt => opt.value === selectedValue);
        
        if (isExistingGroup) {
            document.getElementById("group-value").innerText = selectedValue;
            console.log("Selected group:", selectedValue);
            document.getElementById("group-name").value = selectedValue;
        } else if (selectedValue.trim() !== "") {
            // User typed a non-existing group name
            document.getElementById('InvalidGroupPopup').style.display = 'flex';
        } else {
            document.getElementById("group-value").innerText = "-";
            document.getElementById("group-name").value = ""; 
        }
    });
    
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
    // From create_gm.php redirect
    const url = new URL(window.location.href);
    const errorParam = url.searchParams.get('error');

    if (errorParam === 'empty') {
        showErrorPopup('Missing Fields', 'Please enter all required fields.');
    } else if (errorParam === 'date') {
        showErrorPopup('Invalid Dates', 'Please select valid future date.');
    } else if (errorParam === 'time') {
        showErrorPopup('Invalid Times', 'Please select valid start and end times.');
    } else if (errorParam === "no_slots") {
        showErrorPopup('No Slots Created', 'Failed to create slots.');
    } else if (errorParam === "notime"){
        showErrorPopup("No time selected", "Please select a date and time");
    }

    document.getElementById("closePopup").addEventListener("click", () => {
        document.getElementById("ErrorPopup").style.display = "none";
        window.history.replaceState(null, '', window.location.pathname);
    });

    document.getElementById("invalidGroupPopup").addEventListener("click", () => {
        document.getElementById("InvalidGroupPopup").style.display = "none";
    });
</script> 
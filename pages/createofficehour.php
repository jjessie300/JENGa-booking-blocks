<?php
/*
<!-- createofficehour.php: 
Corresponds to the page on our website titled "Create Blocks" -> Office hours
This page covers Type 3 where owner can create office hour slots to publish -->

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
$activePage = "createofficehour";
$useBootstrap = true;

include '../template/headerSideBack.php';

?>

<div class="card bookblock-card">
    <div class="columns">
        <h1>Booking Type</h1>
    </div>
    <div class="booking-type-row">
        <a href="createofficehour.php" class="type-btn <?= ($activePage === 'createofficehour') ? 'active' : '' ?>">Office Hours</a>
        <a href="calendarmethod.php" class="type-btn <?= ($activePage === 'calendarmethod') ? 'active' : '' ?>">Group Meeting</a>
        <button type="button" class="share-btn" id="url-btn">Share Your Hours</button>
        <input type="hidden" id="currentUserId" value="<?php echo $_SESSION['user_id']; ?>">
    </div>
    
    <form action="../php/create_oh.php" method="POST">
        <div class="columns">
            <div class="col1">
                
                <h2>Date</h2>
                <!-- Bootstraped the calendar in NOT MY ORIGINAL CODE -->

                <input type="hidden" name="selected_date" id="selected_date">

                <div class="box">
                    <div id="inline-calendar"></div>
                </div>
    
                <h2>Add a Time</h2>
                <div class="box">
                    <span class="box-row">
                        <input type="time" class="timeinput" id="from-input" placeholder="From" step="1800">
                        <input type="time" class="timeinput" id="to-input" placeholder="To" step="1800">
                    </span>
                </div>

                <h2>Location</h2>
                <textarea class="withinput" id="location-input" placeholder="Enter meeting location or Zoom link"></textarea>
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
                        <input type="number" class="frequency-dropdown" min="1" value="1">
                    </div>
                    <div class="radio-option">
                        <label class="radio-row">
                            <input type="radio" name="recurring" value="Weekly">
                            <span class="label-text">Weekly</span>
                        </label>
                        <input type="number" class="frequency-dropdown" min="1" value="1">

                    </div>
                    <div class="radio-option">
                        <label class="radio-row">
                            <input type="radio" name="recurring" value="Monthly">
                            <span class="label-text">Monthly</span>
                        </label>
                        <input type="number" class="frequency-dropdown" min="1" value="1">
                    </div>
                </div>

                <button type="button" class="addtime-btn" id="add-time-btn">+ Add Block</button>

                <div class="times-table-wrap" id="times-table-wrap" style="display:none">
                    <h2>Added Times:</h2>
                    <div class="box">
                    <table class="times-table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th colspan="2">Location</th>
                            </tr>
                        </thead>
                        <tbody id="times-container"></tbody>
                    </table>
                    </div>
                </div>

                <input type="hidden" name="slots_data" id="slots_data_input">
                <button class="submit-btn" id="publish-btn">Publish Booking</button>

            </div>
        </div>  
    </form>  
</div>

<!-- Slot conflict popup --> 
<div id="SkippedSlotsPopup">
  <div class="popup-card">
    <h2>Conflicting Slots</h2>
    <p>The following slots could not be created due to time conflicts with existing slots:</p>

    <div class="popup-scroll">
      <div id="skippedSlots"></div>
    </div>

    <button class="PopupTryAgain" id="closeSkippedSlots">Got it</button>
  </div>
</div>

<!-- Generic error popup -->
<div id="ErrorPopup">
  <div class="popup-card">
    <h2 id="popupTitle"></h2>
    <p id="popupMessage"></p>
    
    <button class="PopupTryAgain" id="closePopup">
      Got it
    </button>
  </div>
</div>

<!-- Shareable URL popup --> 
<div id="ShareLinkPopup">
    <div class="popup-card">
        <h2>Share Your Office Hours</h2>
        <p>Students can book office hours using this link:</p>
        <input type="text" id="shareLinkInput" readonly />
        <button type="button" class="PopupTryAgain" id="copyShareLinkBtn">Copy Link</button>
    </div>
</div>




<!-- keep this before scripts. this "closes" the template -->
<?php include '../template/headerSideBack_end.php'; ?>

<script>
    function formatTime(value) {
        if (!value) return '-';
        const [h, m] = value.split(':');
        const hour = parseInt(h);
        const ampm = hour < 12 ? 'AM' : 'PM';
        const displayHour = hour % 12 || 12;
        return `${displayHour}:${m} ${ampm}`;
    }

    let slots = []; 

    document.getElementById('add-time-btn').addEventListener('click', function(){
        const date = document.getElementById('selected_date').value;
        const from = document.getElementById('from-input').value;
        const to = document.getElementById('to-input').value;
        const location = document.getElementById('location-input').value;
        const recurring = document.querySelector('input[name="recurring"]:checked').value;
        const isRecurring = recurring !== 'One-time';

        // Validate slot before adding 
        if (!validateSlot(date, from, to, location)) {
            return; 
        }

        const d = new Date(date + 'T00:00:00');
        const formattedDate = d.toLocaleDateString('en-US', {month: 'short', day: 'numeric'});

        const container = document.getElementById('times-container');
        const row = document.createElement('tr');

        row.innerHTML = `<tr>
                            <td>${formattedDate} ${formatTime(from)} - ${formatTime(to)} ${isRecurring ? '<span class="recurring-r">R</span>' : ''}</td>
                            <td>${location || '-'}</td>
                            <td><button class="remove-btn" onClick="deleteRow(this)">&#128465</button></td>
                        </tr>`;

        container.appendChild(row);

        document.getElementById('times-table-wrap').style.display = 'block';

        let frequency = null;

        if (isRecurring) {
            const selectedRadio = document.querySelector('input[name="recurring"]:checked');
            const parentDiv = selectedRadio.closest('.radio-option');
            const frequencyInput = parentDiv.querySelector('.frequency-dropdown');
            if (frequencyInput) {
                frequency = parseInt(frequencyInput.value);
            }
        }

        const slot = {
            date: date,
            from: from,
            to: to,
            location: location,
            recurring: recurring,
            frequency: frequency
        };

        slots.push(slot); 
    });

    function validateSlot(date, from, to, location) {
        // Check if all fields are entered 
        if (!date || !from || !to || !location) {
            showErrorPopup('Missing Information', 'Please fill in all fields before adding a block.');
            return false;
        }

        // Check if start time is before end time 
        if (from > to) {
            showErrorPopup('Invalid Times', 'Please select valid start and end times.');
            return false;
        }

        // Check if selected date is a future date 
        const today = new Date().toISOString().split('T')[0]; // YYYY-MM-DD format 
        if (date < today) {
            showErrorPopup('Invalid Date', 'Please select a future date.')
            return false;
        }

        return true; 
    }

    function showErrorPopup(title, message) {
        document.getElementById('popupTitle').innerHTML = title;
        document.getElementById('popupMessage').innerHTML = message;
        
        document.getElementById('ErrorPopup').style.display = 'flex';
    }

    // Close popup when clicking btn 
    document.getElementById('closePopup')?.addEventListener('click', function() {
        document.getElementById('ErrorPopup').style.display = 'none';
    });

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

    // For create_oh.php 
     document.getElementById('publish-btn').addEventListener('click', function () {
        document.getElementById('slots_data_input').value = JSON.stringify(slots);
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
        });
    });
</script>


<script> 
    // From create_oh.php redirect
    const url = new URL(window.location.href);
    const skippedParam = url.searchParams.get("skipped");
    const errorParam = url.searchParams.get('error');

    if (skippedParam) {
        const skipped = JSON.parse(decodeURIComponent(skippedParam));

        let skipped_slots = "";

        skipped.forEach(s => {
            skipped_slots += `${s.date} ${formatTime(s.from)} - ${formatTime(s.to)}<br>`;
        });

        document.getElementById("skippedSlots").innerHTML = skipped_slots;
        document.getElementById("SkippedSlotsPopup").style.display = "flex";
    }

    // Handle no added slots error 
    if (errorParam === 'empty') {
        showErrorPopup('No Slots Added', 'Please add at least one office hour slot before publishing.');
    }

    // Close popup when clicking btn 
    document.getElementById("closeSkippedSlots").addEventListener("click", () => {
        document.getElementById("SkippedSlotsPopup").style.display = "none";
        
        // Remove URL params 
        window.history.replaceState(null, '', window.location.pathname);
    });

    document.getElementById("closePopup").addEventListener("click", () => {
        document.getElementById("ErrorPopup").style.display = "none";
        window.history.replaceState(null, '', window.location.pathname);
    });
</script> 

<script>  
    // Share url btn
    document.getElementById('url-btn')?.addEventListener('click', function() {
        const ownerId = document.getElementById('currentUserId')?.value;
        console.log("Owner ID:", ownerId);
        
        if (ownerId && ownerId !== '') {
            const shareableUrl = 'https://winter2026-comp307-group40.cs.mcgill.ca/Project/BookingBlocks/pages/officehours.php?owner=' + ownerId; 
            document.getElementById('shareLinkInput').value = shareableUrl;
            
            document.getElementById('ShareLinkPopup').style.display = 'flex';
        }
    });

    // Copy link btn
    document.getElementById('copyShareLinkBtn')?.addEventListener('click', function() {
        const linkInput = document.getElementById('shareLinkInput');
        linkInput.select();
        navigator.clipboard.writeText(linkInput.value).then(() => {
            linkInput.select();
            document.execCommand('copy');
            document.getElementById('ShareLinkPopup').style.display = 'none';
        });
    });

    
</script>

<script>
    // Source - https://stackoverflow.com/a/40014946
    // Posted by geekonaut, modified by community. See post 'Timeline' for change history
    // Retrieved 2026-04-27, License - CC BY-SA 3.0

    function deleteRow(elem) {
        var table = elem.parentNode.parentNode.parentNode;
        var rowCount = table.rows.length;

        // get the "<tr>" that is the parent of the clicked button
        var row = elem.parentNode.parentNode; 

        // Get index of row being deleted 
        var rowIndex = row.rowIndex - 1; 
        
        // Remove slot from slots array for backend 
        if (rowIndex >= 0 && rowIndex < slots.length) {
            slots.splice(rowIndex, 1);
        }

        row.parentNode.removeChild(row); // remove the row

        if (slots.length === 0) {
            document.getElementById('times-table-wrap').style.display = 'none';
        }
    }
</script>


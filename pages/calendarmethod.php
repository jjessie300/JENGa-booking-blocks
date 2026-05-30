<?php
/*
<!-- calendarmethod.php corresponds to the page on our website titled "Create Blocks", 
 under the Group Meeting -> Find Availabilities -> Create Slots tab
 this page covers Type 2 of the project description, 
 allowing an owner to create slots for group member to select -->

 <!-- Jessie, Emily, Gabrielle -->

<!-- php error check making sure logged in to access -->
*/
session_start(); 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$pageStyle =  "bookingstyles.css";
$pageTitle = "Create Block";
$activePage = "calendarmethod";
$useBootstrap = true;

include '../template/headerSideBack.php';

?>

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
                    <a href="createavailable.php" class="type-btn <?= ($activePage === 'createavailable' || $activePage === 'calendarmethod') ? 'active' : '' ?>">Group Meeting</a>
                    <input type="hidden" id="currentUserId" value="<?php echo $_SESSION['user_id']; ?>">
                </div>
            </div>
            <div class="type-col2">
                <h1>Plan or Create</h1>
                <div class="columns">
                    <a href="createavailable.php" class="type-btn <?= ($activePage === 'createavailable' || $activePage === 'calendarmethod') ? 'active' : '' ?>">Find Availabilities</a>
                    <a href="creategroupmeeting.php" class="type-btn <?= ($activePage === 'creategroupmeeting') ? 'active' : '' ?>">Create Meeting</a>
                </div>
            </div>
        </div>
    </div> 

    <hr class="solid">
    
    <form action="../php/create_groupslot.php" method="POST">
        <div class="columns">
            
            <div class="col1">
                <h1>Availabilities Method</h1>
                <div class="columns">
                    <a href="calendarmethod.php" class="type-btn <?= ($activePage === 'calendarmethod') ? 'active' : '' ?>">Create Slots</a>
                    <a href="createavailable.php" class="type-btn <?= ($activePage === 'createavailable') ? 'active' : '' ?>">Create Heatmap</a>
                </div>

                <h2>Date</h2>
                <!-- Bootstraped the calendar in NOT MY ORIGINAL CODE -->

                <!-- hidden input stores selected date -->
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
                <h2>Group Name</h2>
                <input type="hidden" name="group_name" id="group_name">
                <input type="text" list="group-list" class="withinput" id="group-input" placeholder="Create a Group Name" name="group">

                <h2>Members</h2>
                <div class="members-input-container">
                    <div class="email-tags" id="email-tags-container">
                        <!-- Tags here -->
                    </div>
                    <input type="email" id="member-input" class="tag-email-input" placeholder="Type email and press Enter" autocomplete="off">
                </div>
                <input type="hidden" id="members-emails" name="emails"> 
                
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
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody id="times-container"></tbody>
                    </table>
                    </div>
                </div>

                <input type="hidden" name="slots_data" id="slots_data_input">
                <button class="submit-btn" id="publish-btn">Create Group</button>

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

        row.innerHTML = `<td>${formattedDate} ${formatTime(from)} - ${formatTime(to)} ${isRecurring ? '<span class="recurring-r">R</span>' : ''}</td>
                            <td>${location || '-'}</td>`;

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
    // From create_groupslot.php redirect
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
        showErrorPopup('Missing Fields', 'Please enter all required fields.');
    } else if (errorParam === 'date') {
        showErrorPopup('Invalid Dates', 'Please select valid future date.');
    } else if (errorParam === 'members') {
        showErrorPopup('Missing Members', 'Please enter at least one members.');
    } else if (errorParam === 'duplicate_group') {
        showErrorPopup('Duplicate Group Name', 'A group with this name already exists. Please choose a different group name.');
    } else if (errorParam === 'group') {
        showErrorPopup('Missing Group Name', 'Please create a group name.');
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
    // Referenced https://www.codingnepalweb.com/tags-input-box-html-javascript/
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

    document.querySelector('.members-input-container').addEventListener('click', () => {
        document.getElementById('member-input').focus();
    });
</script> 

<!-- below is from bootstrap -->
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





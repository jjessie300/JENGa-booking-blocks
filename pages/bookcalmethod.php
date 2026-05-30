<?php
// Jessie, Emily, Gabrielle
// php error check making sure logged in to access
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$pageStyle =  "bookingstyles.css";
$pageTitle = "Book Block";
$activePage = "bookcalmethod";

include '../template/headerSideBack.php';

?>


<!-- page stuff here -->
<div class="card">
    <div class="booking-type-row">
        <div class="type-columns">
            <div class="HM-col1">
                <h1>Booking Type</h1>
                <div class="columns">
                    <a href="requestblock.php" class="type-btn <?= ($activePage === 'requestblock') ? 'active' : '' ?>">Request</a>
                    <a href="officehours.php" class="type-btn <?= ($activePage === 'officehours') ? 'active' : '' ?>">Office Hours</a>
                    <a href="bookcalmethod.php" class="type-btn <?= ($activePage === 'groupmeetings' || $activePage === 'bookcalmethod') ? 'active' : '' ?>">Group Meeting</a>
                </div>
            </div>
            <div class="HM-col2">
                <h1>Availability Type</h1>
                <div class="columns">
                    <a href="bookcalmethod.php" class="type-btn <?= ($activePage === 'bookcalmethod') ? 'active' : '' ?>">Create Slots</a>
                    <a href="groupmeetings.php" class="type-btn <?= ($activePage === 'groupmeetings') ? 'active' : '' ?>">Create Heatmap</a>
                </div>
            </div>
        </div>
    </div>

    <div class="heatmap-columns">
        <div class="HM-col1" id="HM-col1">
            <h2>Group Name</h2>
            <!-- https://www.w3schools.com/Tags/tag_datalist.asp --> 
            <input text="text" list="group-list" class="withinput" id="group-input" placeholder="Search by Group Name" autocomplete="off">
            <datalist id="group-list"></datalist>
    
            <h2>Pick a Block</h2>
            <div class="officehour-list">
                <div class="officehour-items" id="group-slots">
                    <!-- office hours will be dynamically loaded here -->
                    <p>Select a group to view slots</p>
                </div>
            </div>
        </div>
        <div class="HM-col2">
            <form action="../php/select_group_slot.php" method="POST" id="submitForm">
                <input type="hidden" name="slot_id" id="slot_id">
                <button type="button" class="avail-btn active" id="submitAvailBtn">Submit Availability</button>
            </form>
            <form action="../php/create_gm_direct.php" method="POST" id="createMeetingForm" style="display: none;">
                <input type="hidden" name="slot_id" id="meeting_id">
                <button type="button" class="avail-btn active" id="createMeeting">Create Meeting</button>
            </form> 

            <button type="button" class="avail-btn" id="viewAvailBtn">View Availabilities</button>
        </div>
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

<script>
    const groupInput = document.getElementById("group-input");
    const datalist = document.getElementById("group-list");
    const groupSlots = document.getElementById("group-slots");

    let groups = [];
    let selectedGroup = null;
    let selectedSlotIds = [];

    function formatTime(timeStr) {
        if (!timeStr) return '-';
        let [hours, minutes] = timeStr.split(":");
        hours = parseInt(hours);
        const ampm = hours >= 12 ? "PM" : "AM";
        hours = hours % 12 || 12;
        return `${hours}:${minutes} ${ampm}`;
    }

    function formatDate(dateStr) {
        const dateParts = dateStr.split('-');
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const date = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);
        const formattedDate = `${dayNames[date.getDay()]}, ${monthNames[date.getMonth()]} ${parseInt(dateParts[2])}`;
        
        return formattedDate;
    }

    function updateURL(group_name) {
        const url = new URL(window.location.href);
        if (group_name) {
            url.searchParams.set('group', encodeURIComponent(group_name));
        } else {
            url.searchParams.delete('group');
        }
        window.history.pushState({}, '', url);
    }

    function selectGroupFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        const groupNameFromUrl = urlParams.get('group');  
        
        if (groupNameFromUrl && groups.length > 0) {
            const matchedGroup = groups.find(g => g.group_name == groupNameFromUrl);
            if (matchedGroup) {
                groupInput.value = matchedGroup.group_name;
                const event = new Event('change');
                groupInput.dispatchEvent(event);
            }
        }
    }

    function renderSlots(slots) {
        groupSlots.innerHTML = "";
        selectedSlotIds = []; 

        if (slots.length === 0) {
            groupSlots.innerHTML = "<p>No group slots found</p>";
            return;
        }

        slots.forEach(slot => {
            const div = document.createElement("div");
            div.classList.add("officehour-item");
            div.dataset.gsId = slot.gs_id;
            
            div.innerHTML = `
                <span class="oh-time">
                    ${formatTime(slot.start_time)}
                </span>
                <span class="oh-date">
                    ${formatDate(slot.chosen_date)}
                </span>
                <span class="oh-count" style="display: none;">
                    ${slot.selected_count || 0}/${slot.total_invitees || 0}
                </span>
            `;
            
            div.addEventListener("click", function() {
                // Click to select and click again to deselect
                if (this.classList.contains('active')) {
                    this.classList.remove('active');
                    selectedSlotIds = selectedSlotIds.filter(id => id !== slot.gs_id);
                } else {
                    // If not selected, select it
                    this.classList.add('active');
                    selectedSlotIds.push(slot.gs_id);
                }
                
                document.getElementById("slot_id").value = selectedSlotIds.join(',');

                console.log("Selected slots:", selectedSlotIds);
            });
            
            groupSlots.appendChild(div);
        });
    }

    // Load groups from db 
    fetch("../php/get_group_name2.php")
        .then(res => {
            return res.json();
        })
        .then(data => {
            groups = data;

            datalist.innerHTML = data.map(g =>
                `<option value="${g.group_name}"></option>`
            ).join("");

            // Auto-select group based if URL has ?group=name
            selectGroupFromUrl(); 
        })
        .catch(err => {
            console.error("Fetch error:", err);
        });

    // Runs when user selects group
    groupInput.addEventListener("change", async function() {
        const selectedValue = this.value;
            
        const options = Array.from(datalist.options);
        selectedGroup = options.find(opt => opt.value === selectedValue);

        if (!selectedGroup) {
            groupSlots.innerHTML = "<p>Select a valid group</p>";
            selectedSlotIds = [];
            updateURL(null);
            await updateForm(null);
            return;
        }
        
        const groupName = selectedGroup.value; 
        updateURL(groupName); 
        await updateForm(groupName);

        const res = await fetch(`../php/get_group_slots.php?group_name=${encodeURIComponent(groupName)}`);
        const group_slots = await res.json();

        renderSlots(group_slots);
    });

    // INVITEE -> Submit availability 
    const hiddenSlotId = document.getElementById("slot_id");
    const submitForm = document.getElementById("submitForm");
    const submitAvailBtn = document.getElementById("submitAvailBtn");

    if (submitAvailBtn) {
        submitAvailBtn.addEventListener("click", function() {
            if (selectedSlotIds.length === 0) {
                window.location.href = 'bookcalmethod.php?error=missingfields';
                return;
            }
            hiddenSlotId.value = selectedSlotIds.join(',');
            submitForm.submit(); // to select_group_slot.php 
        });
    }

    // INVITER -> Create meeting 
    const hiddenMeetingId = document.getElementById("meeting_id");
    const createForm = document.getElementById("createMeetingForm");
    const createMeetingBtn = document.getElementById("createMeeting");
    
    if (createMeetingBtn) {
        createMeetingBtn.addEventListener("click", function() {
            if (selectedSlotIds.length === 0) {
                window.location.href = 'bookcalmethod.php?error=missingfields';
                return;
            }
            hiddenMeetingId.value = selectedSlotIds.join(',');
            createForm.submit(); // to create_gm_direct.php 
        });
    }


    // View availability count (num of invitees who select this group slot/total num of invitees)
    let showCounts = false;

    const viewAvailBtn = document.getElementById("viewAvailBtn");
    viewAvailBtn.addEventListener("click", async function() {
        if (!selectedGroup) {
            window.location.href = 'bookcalmethod.php?error=missingfields';
            return;
        }
        
        const groupName = selectedGroup.value;
        
        if (!showCounts) {
            const res = await fetch(`../php/get_group_slots.php?group_name=${encodeURIComponent(groupName)}`);
            const slots = await res.json();
            
            // Update count values
            document.querySelectorAll('.officehour-item').forEach((item, index) => {
                if (slots[index]) {
                    const countSpan = item.querySelector('.oh-count');
                    if (countSpan) {
                        countSpan.textContent = `${slots[index].selected_count || 0}/${slots[index].total_invitees || 0}`;
                        countSpan.style.display = 'inline';
                    }
                }
            });
            
            viewAvailBtn.textContent = 'Hide Availabilities';
            showCounts = true;
        } else {
            // Hide counts
            document.querySelectorAll('.officehour-item .oh-count').forEach(countSpan => {
                countSpan.style.display = 'none';
            });
            viewAvailBtn.textContent = 'View Availabilities';
            showCounts = false;
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

    const url = new URL(window.location.href);
    const errorParam = url.searchParams.get('error');

    if (errorParam === 'no_slot') {
        showErrorPopup('No Slots Added', 'No slots found.');
    } else if (errorParam === 'already_selected') {
        showErrorPopup('Duplicate Submission', 'You have already submitted this slot.');
    } else if (errorParam === 'db_failed') {
        showErrorPopup('Insert Failed', 'Database failure.');
    } else if (errorParam === 'owner') {
        showErrorPopup('Owner Submission', 'You are the owner of this group. Only invited students can submit availability.');
    } else if (errorParam === 'missingfields') {
        showErrorPopup('Uh Oh!', 'Please make sure all fields are filled out before submitting.');
    }

    document.getElementById("closePopup").addEventListener("click", () => {
        document.getElementById("ErrorPopup").style.display = "none";
        window.history.replaceState(null, '', window.location.pathname);
    });

</script> 

<script> 
    // Check if current user is the inviter of group meeting 
    async function checkInviter(groupName) {
        try {
            const response = await fetch(`../php/check_inviter.php?group_name=${encodeURIComponent(groupName)}`);
            const data = await response.json();
            return data.isInviter;
        } catch (err) {
            console.error("Error while checking inviter of group meeting:", err);
            return false;
        }
    }

    // Show/hide form (create meeting btn) 
    async function updateForm(groupName) {
        const submitForm = document.getElementById("submitForm");
        const createForm = document.getElementById("createMeetingForm");
        
        if (!groupName) {
            submitForm.style.display = "block";
            createForm.style.display = "none";
            return;
        }
        
        const isInviter = await checkInviter(groupName);
        
        if (isInviter) {
            // User is the inviter -> show create meeting and hide submit avail 
            submitForm.style.display = "none";
            createForm.style.display = "block";
        } else {
            // User is the invitee -> show submit avail and hide create meeting
            submitForm.style.display = "block";
            createForm.style.display = "none";
        }
    }
</script> 
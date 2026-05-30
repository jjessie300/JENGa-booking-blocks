<?php
/*
<!-- officehours.php: 
 corresponds to the page on our website titled "Book Blocks" -> office hours
This page covers Type 3 where users or owner can book the office hours of a given owner -->

<!-- Jessie, Gabrielle -->

<!-- php error check making sure logged in to access -->
*/
session_start(); 

if (!isset($_SESSION['user_id'])) {
    $_SESSION['oh_url'] = $_SERVER['REQUEST_URI']; // stores /pages/officehours.php?owner=ID
    header("Location: ../php/login.php"); 
    exit();
}

if (isset($_SESSION['oh_url'])) {
    unset($_SESSION['oh_url']);
}

$pageTitle = "Book Block";
$activePage = "officehours";

$useBootstrap = false; 
$pageStyle =  "bookingstyles.css";
include '../template/headerSideBack.php';

?>


<div class="card">
    <div class="columns">
        <h1>Booking Type</h1>
    </div>
    <div class="booking-type-row">
        <a href="requestblock.php" class="type-btn <?= ($activePage === 'requestblock') ? 'active' : '' ?>">Request</a>
        <a href="officehours.php" class="type-btn <?= ($activePage === 'officehours') ? 'active' : '' ?>">Office Hours</a>
        <a href="bookcalmethod.php" class="type-btn <?= ($activePage === 'groupmeetings' || $activePage === 'bookcalmethod') ? 'active' : '' ?>">Group Meeting</a>
    </div>

    <form action="../php/book_oh.php" method="POST">
        <div class="columns">
            <div class="col1">
                <h2>With</h2>
                <!-- https://www.w3schools.com/Tags/tag_datalist.asp --> 
                <input type="hidden" name="owner_id" id="owner_id_input">
                <input text="text" list="prof-list" class="withinput" id="prof-input" placeholder="Search by Name" autocomplete="off">
                <datalist id="prof-list"></datalist>
        
                <h2>Pick a Block</h2>
                    <div class="officehour-list">
                        <div class="officehour-items" id="officehour">
                            <!-- office hours will be dynamically loaded here -->
                            <p>Select a professor to view slots</p>
                        </div>
                    </div>

            </div>

            <div class="col2">
                <h2>Add Note (Optional)</h2>
                <textarea class="additionalnotes" placeholder="What would you like to discuss?" name="note"></textarea>

                <h2>Not seeing what you need? Contact them!</h2>
                <button type="button" class="submit-btn" id="email-btn">Email</button>

                <h2>Confirmation</h2>
                <div class="box">
                    <div class="box-row">
                        <span class="conf-label">Type:</span>
                        <span class="conf-value">Office Hour</span>
                    </div>
                    <div class="box-row">
                        <span class="conf-label">With:</span>
                        <span class="conf-value" id="prof-value">-</span>
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
                        <span class="conf-label">Location:</span>
                        <span class="conf-value" id="location-value">-</span>
                    </div>
                </div>

                <input type="hidden" name="slot_id" id="slot_id_input">

                <button type="submit" class="submit-btn">Submit Booking Block</button>

            </div>
        </div>
    </form>        
</div>

<div id="MissingFieldsPopup">
    <div class="popup-card">
        <h2>Uh Oh!</h2>
        <p>Please make sure all fields are filled out before submitting</p>
        <button type="button" class="PopupTryAgain" id="missingFields">Got it</button>
    </div>
</div>
<div id="AlreadyBookedPopup">
    <div class="popup-card">
        <h2>Uh Oh!</h2>
        <p>You have already booked this time slot</p>
        <button type="button" class="PopupTryAgain" id="alreadyBookedBtn">Got it</button>
    </div>
</div>
<div id="NoProfPopup">
    <div class="popup-card">
        <h2>Uh Oh!</h2>
        <p>Please select a professor before sending an email.</p>
        <button type="button" class="PopupTryAgain" id="noProfBtn">Got it</button>
    </div>
</div>



<?php include '../template/headerSideBack_end.php'; ?>

<script>
    const profInput = document.getElementById("prof-input");
    const datalist = document.getElementById("prof-list");
    const officeHourList = document.getElementById("officehour");

    let profs = [];
    let selectedProf = null;

    // Load profs from db 
    fetch("../php/get_professors.php")
        .then(res => {
            return res.json();
        })
        .then(data => {
            profs = data;

            datalist.innerHTML = data.map(p =>
                `<option value="${p.name} (${p.email})" data-id="${p.user_id}" data-name="${p.name}" data-email="${p.email}"></option>`
            ).join("");

            // Auto-select prof based if URL has ?owner=ID
            selectProfFromUrl(); 
        })
        .catch(err => {
            console.error("Fetch error:", err);
        });

    // Runs when user selects prof  
    profInput.addEventListener("change", async function () {
        const selectedValue = this.value;
            
        // Find matching option in datalist
        const options = Array.from(datalist.options);
        selectedProf = options.find(opt => opt.value === selectedValue);

        if (!selectedProf) {
            officeHourList.innerHTML = "<p>Select a valid professor</p>";

            selectedSlotId = null;

            // Reset conf block 
            document.getElementById("prof-value").innerText = "-";
            document.getElementById("date-value").innerText = "-";
            document.getElementById("from-value").innerText = "-";
            document.getElementById("to-value").innerText = "-";
            document.getElementById("location-value").innerText = "-";

            document.getElementById("slot_id_input").value = "";
            document.getElementById("owner_id_input").value = 0;

            // Remove owner param from URL 
            updateURL(null);

            return;
        }
        
        // Update conf block with prof's name 
        document.getElementById("prof-value").innerText = selectedProf.dataset.name; 
        document.getElementById("owner_id_input").value = selectedProf.dataset.id; // for book_oh.php 
        updateURL(selectedProf.dataset.id); 

        const res = await fetch(`../php/get_slots.php?prof_id=${selectedProf.dataset.id}`);
        const slots = await res.json();

        renderSlots(slots);
    });


    function renderSlots(slots) {
        officeHourList.innerHTML = "";

        // Prof has no office hours scheduled 
        if (slots.length === 0) {
            officeHourList.innerHTML = "<p>No office hours found</p>";
            return;
        }

        slots.forEach(slot => {
            const div = document.createElement("div");
            div.classList.add("officehour-item");
            
            div.innerHTML = `
                <span class="oh-time">
                    ${formatTime(slot.start_time)}
                </span>
                <span class="oh-date">
                    ${formatDate(slot.chosen_date)}
                </span>
            `;
            
            // Update conf block with date, time, location 
            div.addEventListener("click", function () {
                document.querySelectorAll('.officehour-item')
                    .forEach(oh_slot => oh_slot.classList.remove('active'));

                this.classList.add("active");

                document.getElementById("date-value").innerText = formatDate(slot.chosen_date);
                document.getElementById("from-value").innerText = formatTime(slot.start_time);
                document.getElementById("to-value").innerText = formatTime(slot.end_time);
                document.getElementById("location-value").innerText = slot.location;

                document.getElementById("slot_id_input").value = slot.slot_id; // for book_oh.php 
            });

            officeHourList.appendChild(div);
        });
    }

    // 10:00 AM time format for slot and conf blocks
    function formatTime(timeStr) {
    let [hours, minutes] = timeStr.split(":");
    hours = parseInt(hours);
    const ampm = hours >= 12 ? "PM" : "AM";
    hours = hours % 12 || 12;
    return `${hours}:${minutes} ${ampm}`;
    }

    function formatDate(dateStr) {
        // Input: "2026-06-02" -> Output: "Tue, Jun 02"
        
        const dateParts = dateStr.split('-');
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const date = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);
        const formattedDate = `${dayNames[date.getDay()]}, ${monthNames[date.getMonth()]} ${parseInt(dateParts[2])}`;
        
        return formattedDate;
    }

    document.getElementById("email-btn").addEventListener("click", function() {
        if (!selectedProf) {
            document.getElementById('NoProfPopup').style.display = 'flex';
            return;
        }
        // Opens/creates draft to prof 
        window.open(`mailto:${selectedProf.dataset.email}`, '_blank');

    });

</script>

<script> 
    // Updates browser URL based on selected prof 
    function updateURL(profId) {
        const url = new URL(window.location.href); 

        if (profId) {
            url.searchParams.set('owner', profId); // ?owner=ID
        } else {
            url.searchParams.delete('owner');
        }
        
        window.history.pushState({}, '', url);
    }

    // Read URL param and auto-select prof
    function selectProfFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        const prof_id = urlParams.get('owner');
        

        if (!prof_id) {
            console.log("No owner id");
            return;
        }   
        
        // Find professor by ID
        const prof = profs.find(p => p.user_id == prof_id);
        
        if (prof) {
            const optionValue = `${prof.name} (${prof.email})`;
            profInput.value = optionValue;
            
            // Load prof's slots 
            const changeEvent = new Event('change');
            profInput.dispatchEvent(changeEvent);
            
            console.log("Auto-selected prof:", prof.name);
        } else {
            console.log("Prof id not found:", prof_id);
        }
    }

    const urlp = new URLSearchParams(window.location.search);

    if(urlp.get('error') == 'missingfields'){
        document.getElementById('MissingFieldsPopup').style.display = 'flex';
    }
    else if(urlp.get('error') == 'alreadybooked'){
        document.getElementById('AlreadyBookedPopup').style.display = 'flex';
    }
    
    document.getElementById('missingFields').addEventListener('click', function(){
        document.getElementById('MissingFieldsPopup').style.display = 'none';
        window.history.replaceState(null, '', window.location.pathname);
    })
    document.getElementById('alreadyBookedBtn').addEventListener('click', function(){
        document.getElementById('AlreadyBookedPopup').style.display = 'none';
        window.history.replaceState(null, '', window.location.pathname);
    })

    document.getElementById('noProfBtn').addEventListener('click', function(){
        document.getElementById('NoProfPopup').style.display = 'none';
    });
</script> 
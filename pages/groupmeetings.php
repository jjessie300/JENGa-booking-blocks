<?php
 /*

 <!-- groupmeetings.php: 
Corresponds to the page on our website titled "Book Blocks" -> Group Meeting -> Create Heatmap
This page covers Type 2 inputting availability for group meeting (bonus) heatmap method -->

<!-- Emily, Nikola (~5%) -->

<!-- php error check making sure logged in to access -->

 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Allow script to grab the current user for fetchCurrentUser()
$current_user_id = $_SESSION['user_id'] ?? null;

$pageStyle =  "bookingstyles.css";
$pageTitle = "Book Block";
$activePage = "groupmeetings";

include '../template/headerSideBack.php';

?>

<!-- page stuff here -->
<div class="card bookblock-card">
    <div class="booking-type-row">
        <div class="type-columns">
            <div class="HM-col1">
                <h1>Booking Type</h1>
                <div class="columns">
                    <a href="requestblock.php" class="type-btn <?= ($activePage === 'requestblock') ? 'active' : '' ?>">Request</a>
                    <a href="officehours.php" class="type-btn <?= ($activePage === 'officehours') ? 'active' : '' ?>">Office Hours</a>
                    <a href="groupmeetings.php" class="type-btn <?= ($activePage === 'groupmeetings' || $activePage === 'bookcalmethod') ? 'active' : '' ?>">Group Meeting</a>
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
            <div class="box">
                Select a Group
            </div>
        </div>
        <div class="HM-col2">
            <form>
                <input type="text" class="group-input" id="group-input" placeholder="Enter Group Name &#9662">
                <button type="button" class="avail-btn active" id="inputMode">+ Add Availabilities</button>
                <button type="button" class="avail-btn" id="submitMode">Submit Availability</button>
                <button type="button" class="avail-btn" id="viewMode">View Availabilities</button>
                <div class="group-input">
                    <label for="best-times">
                        <input type="checkbox" id="best-times">
                        Only Show Best Times
                    </label>
                </div>
            </form>
            <div class="legend" id="legend"></div>
            <div id="summary"></div>
        </div>
    </div>
</div>

<div id="SuccessPopup">
    <div class="popup-card">
        <h2>Success!</h2>
        <p>Your availability has been successfully saved.</p>
        <button type="button" class="PopupTryAgain" id="successBtn">Got it</button>
    </div>
</div>

<div id="NoSlotsPopup">
    <div class="popup-card">
        <h2>Oh No!</h2>
        <p>Please enter a group name and select at least one time slot before submitting.</p>
        <button type="button" class="PopupTryAgain" id="noSlotsBtn">Got it</button>
    </div>
</div>

<?php include '../template/headerSideBack_end.php'; ?>

<script>
    // then page specific js goes here
    const SLOTS = 34;
    const START_HOUR = 6;
    const eventId = document.getElementById("group-input");

    let DAYS = [];
    let activeP = null;
	let availabilities = {};
	let isDragging = false;
	let dragValue = null;
	let viewMode = false;
    let bestTimes = false;


    async function fetchCurrentUser() {
        const user_id = "<?php echo $current_user_id; ?>";
        return user_id;
    }

  
    async function fetchDates() {

        const groupName = document.getElementById("group-input").value;

        // Nothing to display
        if (!groupName) return [];

        // encodeURIComponent safely handles groupName if user inputs special characters
        const res = await fetch(`../php/get_group_dates.php?group_name=${encodeURIComponent(groupName)}`);
        const rows = await res.json();

        if (rows.error) {
            console.error(rows.error);
            return [];
        }

        return rows.map(r => formatDate(r.date));
    }

   
    async function fetchAvailabilities() {
        const groupName = document.getElementById("group-input").value;

        // Nothing to be displayed on heatmap
        if (!groupName) return {};

        
        const res = await fetch(`../php/get_group_availability.php?group_name=${encodeURIComponent(groupName)}`);
        const rows = await res.json();
        return rows;
    }


    function formatDate(iso) {
        const d  = new Date(`${iso}T12:00:00Z`);
        const day = d.toLocaleDateString("en-GB", {weekday: "short", timeZone: "UTC"});
        const date = d.toLocaleDateString("en-GB", {month: "short", day: "numeric", timeZone: "UTC"});
        return {iso, label: `${day}|${date}`};
    }

    // return string of the slot time
    function timeLabel(slot) {
        const hour = START_HOUR + Math.floor(slot / 2);
        const minute = slot % 2 === 0 ? "00" : "30";
        const ampm = hour < 12 ? "am" : "pm";
        const h12 = hour > 12 ? hour - 12 : hour === 0 ? 12 : hour;
        // only return a label if the slot is on the hour
        return slot % 2 === 0 ? `${h12}:${minute} ${ampm}` : "";
    }

    function buildGrid() {
        document.getElementById("HM-col1").innerHTML = `
            <div class="grid-outer">
                <div class="time-col" id="timeCol"></div>
                <div class="grid-area">
					<div class="col-headers" id="colHeaders"></div>
					<div class="grid" id="grid"></div>
				</div>
            </div>`;
        
        const times = document.getElementById("timeCol");
        const dates = document.getElementById("colHeaders");
        const grid = document.getElementById("grid");

        // add all hours next to the grid
        for (let s = 0; s <= SLOTS; s++) {
            const label = document.createElement("div");
            label.className = "time-label";
            label.textContent = timeLabel(s);
            times.appendChild(label);
        }

        // create all columns
        DAYS.forEach((day, di) => {
            // create header row of dates
            const [weekday, date] = day.label.split("|");

            const header = document.createElement("div");
            header.className = "dates-header";
            header.innerHTML = `
                <div class="date">${date}</div>
                <div class="day">${weekday}</div>`;
            dates.appendChild(header);

            const col = document.createElement("div");
            col.className = "day-col";

            // add cells for each slot
            for (let s = 0; s < SLOTS; s++) {
                const cell = document.createElement("div");
                cell.className = "cell";

                cell.dataset.day = day.iso;
                cell.dataset.slot = s;
                cell.addEventListener("mousedown", onMouseDown);
                cell.addEventListener("mouseenter", onMouseEnter);
                col.appendChild(cell);
            }

            grid.appendChild(col);
        });

        document.addEventListener("mouseup", () => {
            isDragging = false;
            dragValue = null;
        });
        renderCells();
    }

    async function loadDates() {
        DAYS = [];

        DAYS = await fetchDates();
        buildGrid();
    }

    function cellKey(cell) {
        return `${cell.dataset.day}_${cell.dataset.slot}`;
    }

    function onMouseDown(e) {
        if (!activeP || viewMode) return;
        isDragging = true;
        const key = cellKey(e.target);
        dragValue = !(availabilities[activeP][key]);
        toggleCell(e.target);
    }

    // if a user is dragging before availability is entered, change availablity
    function onMouseEnter(e) {
        if (!isDragging || !activeP || viewMode) return;
        toggleCell(e.target);
    }

    function toggleCell(cell) {
        if (!activeP) return;

        const date = cell.dataset.day;
        const slot = parseInt(cell.dataset.slot, 10);
        const key = cellKey(cell);

        availabilities[activeP][key] = dragValue;
        renderCells();
    }

    function renderCells() {
        // get all members
        const allUsers = Object.keys(availabilities);

        document.querySelectorAll(".cell").forEach(cell => {
            const key = cellKey(cell);
            if (viewMode) {
                const avail = allUsers.filter(u => availabilities[u][key]).length;

                let bestAvail = 0
                let bestSlots = [];
                DAYS.forEach(day => {
                    for (let s = 0; s < SLOTS; s++) {
                        const key = `${day.iso}_${s}`;
                        const avail = allUsers.filter(u => availabilities[u][key]).length;

                        if (avail > bestAvail) {
                            bestAvail = avail;
                            bestSlots = [{day, s}];
                        } else if (avail === bestAvail && avail > 0) {
                            bestSlots.push({day, s});
                        }
                    }
                });

                if (bestTimes) {
                    if (avail === bestAvail) {
                        cell.style.background = "#ed1b2f";
                        cell.style.borderColor = "#cecdcd";
                    } else {
                        cell.style.background = "white";
                        cell.style.borderColor = "#cecdcd";
                    }
                } else {
                    const numAvail = avail / bestAvail;
                    cell.style.background = `rgba(237, 27, 47, ${numAvail})`;
                    cell.style.borderColor = "#cecdcd";
                }
            } else {
                const marked = availabilities[activeP] && availabilities[activeP][key];
                cell.style.background = marked ? "#ed1b2f" : "white";
                cell.style.borderColor = "#cecdcd";
            }
        });

        renderLengend();
        renderSummary();
    }

    function renderLengend() {
        const legend = document.getElementById("legend");
        const allUsers = Object.keys(availabilities);

        if (!viewMode || allUsers.length === 0) {
            legend.innerHTML = ""; 
            return;
        }

        legend.innerHTML = "";
        const low = document.createElement("span");
        low.textContent = "0 available"
        legend.appendChild(low);

        const grad = document.createElement("div");
        grad.setAttribute("id", "gradient");
        legend.appendChild(grad);

        let bestAvail = 0
        DAYS.forEach(day => {
            for (let s = 0; s < SLOTS; s++) {
                const key = `${day.iso}_${s}`;
                const avail = allUsers.filter(u => availabilities[u][key]).length;

                if (avail > bestAvail) {
                    bestAvail = avail;
                } 
            }
        });

        const high = document.createElement("span");
        high.textContent = `${bestAvail} available`;
        legend.appendChild(high);
    }

    function renderSummary() {
        const summary = document.getElementById("summary");
        const allUsers = Object.keys(availabilities);

        if (!viewMode || allUsers.length === 0) {
            summary.innerHTML = ""; 
            return;
        }

        let bestAvail = 0
        let bestSlots = [];
        DAYS.forEach(day => {
            for (let s = 0; s < SLOTS; s++) {
                const key = `${day.iso}_${s}`;
                const avail = allUsers.filter(u => availabilities[u][key]).length;

                if (avail > bestAvail) {
                    bestAvail = avail;
                    bestSlots = [{day, s}];
                } else if (avail === bestAvail && avail > 0) {
                    bestSlots.push({day, s});
                }
            }
        });

        if (bestAvail === 1) {
            summary.innerHTML = "No Overlapping Availabilities";
            return;
        }

        summary.innerHTML = `${bestAvail}/${allUsers.length} Users Available at Best Times`;
    }

    // Build grid only after group name was submitted
    document.getElementById("group-input").addEventListener("change", async (event) => {

    // Since we have it on change, we don't want to run code below if a user simply deletes the entry
    if (!event.target.value.trim()) {
        return;
    }

    showLoading();

    // Fetch dates and availabilities now that we have a group name
    const [dates, savedAvailability] = await Promise.all([
        fetchDates(),
        fetchAvailabilities()
    ]);

    DAYS = dates;
    availabilities = savedAvailability;
    
    // Ensure the active user has an object in the availabilities map
    if (!availabilities[activeP]){
        availabilities[activeP] = {};
    }
    
    buildGrid();
    });

    document.getElementById("submitMode").addEventListener("click", async () => {

        // Check that we actually have a user
        if (!activeP) {
            return;
        }

        if(!document.getElementById("group-input").value.trim()){
            document.getElementById('NoSlotsPopup').style.display = 'flex';
            return;
        }

        // Gather slots and mark them
        const user_availability = availabilities[activeP] || {}
        const selectedSlots = [];

        // Extract where true
        for (const [entry, isAvailable] of Object.entries(user_availability)){
            if (isAvailable){
                const [date, slot] = entry.split("_");
                selectedSlots.push({date: date, slot: parseInt(slot)});
            }
        }

        if(selectedSlots.length === 0){
            document.getElementById('NoSlotsPopup').style.display = 'flex';
            return;
        }

        // Send it over
        const res = await fetch("../php/user_submitted_availability.php", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({
                group_name: document.getElementById("group-input").value,
                user_id: activeP,
                slots: selectedSlots
            })

        });

        const result = await res.json();
        if (result.success) {
            document.getElementById('SuccessPopup').style.display='flex';
        }


    });

    document.getElementById('noSlotsBtn').addEventListener('click', function(){
        document.getElementById('NoSlotsPopup').style.display = 'none';
    });

    document.getElementById("inputMode").addEventListener("click", () => {
        viewMode = false;
        document.getElementById("inputMode").classList.add("active");
        document.getElementById("viewMode").classList.remove("active");
        renderCells();
    });

    document.getElementById("viewMode").addEventListener("click", () => {
        viewMode = true;
        document.getElementById("viewMode").classList.add("active");
        document.getElementById("inputMode").classList.remove("active");
        renderCells();
    });

    document.getElementById("best-times").addEventListener("click", () => {
        viewMode = true;
        document.getElementById("viewMode").classList.add("active");
        document.getElementById("inputMode").classList.remove("active");
        bestTimes = !bestTimes;
        renderCells();
    });

    document.getElementById('successBtn').addEventListener('click', function(){
        document.getElementById('SuccessPopup').style.display = 'none';
    })

    function showLoading() {
        document.getElementById("HM-col1").innerHTML = `
        <div class="loading-state">
            <div class="spinner"></div>
            Loading dates and availability…
        </div>`;
    }

    // initialization
    async function init() {
        // Fetch active userId; logic continues once a group name is entered
        activeP = await fetchCurrentUser();
    }

    init();

</script>
<?php
/*
<!-- requestblock.php: 
corresponds to the page on our website titled "Book Blocks" -> request
This page covers Type 1 where a user or owner can access to request a meeting with an owner -->

<!-- Jessie, Emily -->

<!-- php error check making sure logged in to access -->
*/
session_start(); 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$pageStyle =  "bookingstyles.css";
$pageTitle = "Book Block";
$activePage = "requestblock";

$useBootstrap = true;
include '../template/headerSideBack.php';

?>

<!-- page stuff here --> 


<div class="card bookblock-card">
    <div class="columns">
        <h1>Booking Type</h1>
    </div>
    <div class="booking-type-row">
        <a href="requestblock.php" class="type-btn <?= ($activePage === 'requestblock') ? 'active' : '' ?>">Request</a>
        <a href="officehours.php" class="type-btn <?= ($activePage === 'officehours') ? 'active' : '' ?>">Office Hours</a>
        <a href="bookcalmethod.php" class="type-btn <?= ($activePage === 'groupmeetings' || $activePage === 'bookcalmethod') ? 'active' : '' ?>">Group Meeting</a>
    </div>
    
    <form action="../php/make_request.php" method="POST">
        <div class="columns">
            <div class="col1">
                <h2>With</h2>
                <!-- https://www.w3schools.com/Tags/tag_datalist.asp --> 
                <input type="hidden" name="owner_id" id="owner_id">
                <input type="text" list="prof-list" class="withinput" id="prof-input" placeholder="Search by Name" autocomplete="off">
                <datalist id="prof-list"></datalist>

                <h2>Date</h2>
                <input type="hidden" name="selected_date" id="selected_date">

                <div class="box">
                    <div id="inline-calendar"></div>
                </div>

                <h2>Add a Time</h2>
                <div class="box">
                    <span class="box-row">
                        <input type="time" class="timeinput" id="from-input" placeholder="From" step="1800" name="start_time">
                        <input type="time" class="timeinput" id="to-input" placeholder="To" step="1800" name="end_time">
                    </span>
                </div>

            </div>
            <div class="col2">
                <h2>Add Note (Optional)</h2>
                <textarea class="additionalnotes" placeholder="What would you like to discuss?" name="note"></textarea>

                <h2>Confirmation</h2>
                <div class="box">
                    <div class="box-row">
                        <span class="conf-label">Type:</span>
                        <span class="conf-value">Request</span>
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
                </div>

                <button class="submit-btn">Submit Request</button>

            </div>
        </div>
    </form>   
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

<div id="InvalidProfPopup">
  <div class="popup-card">
    <h2>Uh Oh!</h2>
    <p>Please select a professor from the dropdiwn list.</p>
    <button type="button" class="PopupTryAgain" id="invalidProfBtn">Got it</button>
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

    document.getElementById('from-input').addEventListener('change', function() {
        document.getElementById('from-value').textContent = formatTime(this.value);
    });

    document.getElementById('to-input').addEventListener('change', function() {
        document.getElementById('to-value').textContent = formatTime(this.value);
    });


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

    // Prof dropdown 
    const datalist = document.getElementById("prof-list");
    let profs = [];

    // Load profs from db 
    fetch("../php/get_professors.php")
        .then(res => {
            return res.json();
        })
        .then(data => {
            profs = data; 

            datalist.innerHTML = data.map(p =>
                 `<option value="${p.name} (${p.email})" data-id="${p.user_id}" data-name="${p.name}"></option>`
            ).join("");
        })
        .catch(err => {
            console.error("Fetch error:", err);
        });
    
    // Handle prof selection
    document.getElementById('prof-input').addEventListener("change", function() {
        const selectedValue = this.value;
        
        // Find matching option in datalist
        const options = Array.from(datalist.options);
        const selectedProf = options.find(opt => opt.value === selectedValue);
        
        if (selectedProf) {
            const prof_id = selectedProf.dataset.id;
            const prof_name = selectedProf.dataset.name;
                        
            document.getElementById("owner_id").value = prof_id; // for make_request.php
            
            // Update prof in conf block 
            document.getElementById("prof-value").innerHTML = `${prof_name}`;
            
        } else {
            // User typed something not in dropdown
            if (selectedValue.trim() !== "") {
                document.getElementById('InvalidProfPopup').style.display = 'flex';

            }
            // Clear prof in conf block
            document.getElementById("prof-value").innerText = "-";
            document.getElementById("owner_id").value = 0;
        }
    });

    document.getElementById('closePopup').addEventListener('click', function() {
        document.getElementById('ErrorPopup').style.display = 'none';
    });

    document.getElementById('invalidProfBtn').addEventListener('click', function() {
        document.getElementById('InvalidProfPopup').style.display = 'none';
    });

    const errorParam = new URLSearchParams(window.location.search).get('error');
    if (errorParam) {
        document.getElementById('popupTitle').textContent = 'Error';
        document.getElementById('popupMessage').textContent = errorParam;
        document.getElementById('ErrorPopup').style.display = 'flex';
        window.history.replaceState(null, '', window.location.pathname);
    }


</script> 

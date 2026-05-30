<?php
/*
<!-- myappointments.php 
Corresponds to the page on our website titled "Dashboard".
This page covers the dashboard page with all of the users current list of requested slots 
or awaiting confirmation as well as list of confirmed appointments.
navigate to creating or booking blocks via the sidenav -->

<!-- Jessie (js), Emily -->

<!-- php error check making sure logged in to access -->
*/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$pageStyle =  "appointments.css";
$pageTitle = "My Appointments";
$activePage = "appointments";
$useBootstrap = false;
include '../template/headerSideBack.php';

?>



<!-- page stuff here -->
<div class="all-appointments">
    <div class="card myappointments-card">
        <h1>Requests</h1>
        <div class="request-list">
            <div class="appointment-items" id="requests">
                <!-- Requests will be dynamically loaded here, the appointment below is a placeholder -->
                <div class='appointment-item'>
                    <!-- Dynamic content loaded here --> 
                </div>
            </div>
        </div>
    </div>

    <div class="card myappointments-card">
        <h1>Confirmed</h1>
        <div class="app-btns">
            <div class="type-btns">
                <button class="type-btn active" onclick="filterConfirmed('all', this)">All</button>
                <button class="type-btn" onclick="filterConfirmed('office-hours', this)">Office Hours</button>
                <button class="type-btn" onclick="filterConfirmed('group-meeting', this)">Group Meeting</button>
                <button class="type-btn" onclick="filterConfirmed('request', this)">Request</button>
            </div>
            <button class="cal-btn" onclick="exportCalendar()">Export To Calendar</button>
        </div>
        

        <div class="confirmed-list">
            <div class="appointment-items" id="confirmed">
                <!-- Dynamic content loaded here --> 
            </div>
        </div>
    </div>
</div>

<div id="EditLocationPopup" style="display: none;">
    <div class="popup-card">
        <h2>Edit Location</h2>
        <p>Please enter the location for this appointment: </p>
        <textarea id="locationInput" class="location-input-field" placeholder="e.g., Room 123 MCENG or Zoom Link"></textarea>
        <button type="button" class="PopupTryAgain" id="editlocationbtn">Update</button>
    </div>
</div>


<?php include '../template/headerSideBack_end.php'; ?>

<script>
    // REQUEST ACTIONS 
    // Accept pending request 
    function acceptAppointment(id) { 
        // Get button that was clicked
        const button = event.target;
        const appointmentItem = button.closest('.appointment-item');

        const studentLink = appointmentItem.querySelector("a[href^='mailto:']");        
        const studentEmail = studentLink
            ? studentLink.getAttribute('href').replace('mailto:', '')
            : '';

        // get displayed values from card
        const date = appointmentItem.querySelector('.field:nth-child(2)')?.innerText || '';
        const startTime = appointmentItem.querySelector('.short-field:nth-child(3)')?.innerText || '';
        const endTime = appointmentItem.querySelector('.short-field:nth-child(4)')?.innerText || '';

        
        fetch('../php/accept_request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                request_id: id
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove specified request from DOM 
                if (appointmentItem) {
                    appointmentItem.remove();
                }
                
                // If no requests left display message 
                const requestsContainer = document.getElementById('requests');
                if (requestsContainer.children.length === 0) {
                    requestsContainer.innerHTML = '<div class="no-appointments">No pending requests found</div>';
                }

                refreshConfirmed(); 

                const subject = encodeURIComponent('Your meeting request has been accepted');
                const body = encodeURIComponent(
                    `Your meeting request has been accepted.\n` +
                    `Date: ${date}\n` +
                    `Time: ${startTime} - ${endTime}\n\n` +
                    `You can view this appointment in your dashboard.\n\n`
                );
                
                window.open(`mailto:${studentEmail}?subject=${subject}&body=${body}`, '_blank');
                
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }

    // Decline pending request 
    function declineAppointment(id) {
        const button = event.target;
        const appointmentItem = button.closest('.appointment-item');
        
        fetch('../php/decline_request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                request_id: id
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (appointmentItem) {
                    appointmentItem.remove();
                }
                
                const requestsContainer = document.getElementById('requests');
                if (requestsContainer.children.length === 0) {
                    requestsContainer.innerHTML = '<div class="no-appointments">No pending requests found</div>';
                }

                const studentLink = appointmentItem.querySelector("a[href^='mailto:']");
                const studentEmail = studentLink
                    ? studentLink.getAttribute('href').replace('mailto:', '')
                    : '';

                const date = appointmentItem.querySelector('.field:nth-child(2)')?.innerText || '';
                const startTime = appointmentItem.querySelector('.short-field:nth-child(3)')?.innerText || '';
                const endTime = appointmentItem.querySelector('.short-field:nth-child(4)')?.innerText || '';

                const subject = encodeURIComponent('Your meeting request was declined');

                const body = encodeURIComponent(
                    `Your meeting request could not be accepted.\n\n` +
                    `Date: ${date}\n` +
                    `Time: ${startTime} - ${endTime}\n\n` +
                    `Please submit another request if needed.`
                );

                window.open(`mailto:${studentEmail}?subject=${subject}&body=${body}`, '_blank');

            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }

    // NOTES DROPDOWN FUNCTIONS 
    // Used ClaudeAI to help get proper formatting for this dropdown
    function showMore(btn) {
        const dropdown = btn.nextElementSibling;
        const isOpen = dropdown.classList.contains("show");
        // Close all dropdowns
        document.querySelectorAll(".more-info").forEach(d => d.classList.remove("show"));
        if (!isOpen) { // If it wasn't open before, open it
            dropdown.classList.add("show");
        }
    }
    
    // Based on W3 Schools dropdown example for notes read more button https://www.w3schools.com/howto/tryit.asp?filename=tryhow_css_js_dropdown 
    // Close the dropdown if the user clicks outside of it
    window.onclick = function(event) {
        if (!event.target.matches('.dropbtn')) {
            var dropdowns = document.getElementsByClassName("more-info");
            var i;
            for (i = 0; i < dropdowns.length; i++) {
                var openDropdown = dropdowns[i];
                if (openDropdown.classList.contains('show')) {
                    openDropdown.classList.remove('show');
                }
            }
        }
    }
    // End of W3 Schools dropdown example
    
</script>

<script> 
    // PAGE INITIALIZATION AND LOADING 
    document.addEventListener("DOMContentLoaded", function() {
        loadRequests();
        loadConfirmed('all'); 

        document.getElementById('editlocationbtn').addEventListener("click", updateLocation); 
    });

    // Load pending requests 
    function loadRequests() {    
        fetch('../php/get_requests.php')
            .then(response => response.text())
            .then(data => {
                document.getElementById('requests').innerHTML = data;
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('requests').innerHTML = '<div class="error">Error loading requests</div>';
            });
    }

    // Load confirmed appointments, all by default 
    function loadConfirmed(type = 'all') {       
        fetch(`../php/get_confirmed.php?type=${type}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('confirmed').innerHTML = data;
            })
            .catch(error => {
                console.error('Error fetching confirmed:', error);
                document.getElementById('confirmed').innerHTML = '<div class="error">Error loading confirmed appointments</div>';
            });
    }

    // Update active filter button styling 
    function filterConfirmed(type, btn) {
        if (btn) {
            const btnContainer = btn.closest('.type-btns');
            if (btnContainer) {
                btnContainer.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            }
        }

        // Load filtered confirmed appts
        loadConfirmed(type);
    }

    // Refresh confirmed appointments based on current filter
    function refreshConfirmed() {
        const activeButton = document.querySelector('.type-btns .type-btn.active');
        let currentFilter = 'all';
        
        if (activeButton) {
            const onclickAttr = activeButton.getAttribute('onclick');
            if (onclickAttr && onclickAttr.includes("'office-hours'")) {
                currentFilter = 'office-hours';
            } else if (onclickAttr && onclickAttr.includes("'group-meeting'")) {
                currentFilter = 'group-meeting';
            } else if (onclickAttr && onclickAttr.includes("'request'")) {
                currentFilter = 'request';
            }
        }
        
        loadConfirmed(currentFilter);
    }
</script> 

<script>
    // APPT ACTIONS 
    let appointmentId = null;

    function editAppointment(id) {
        appointmentId = id;

        document.getElementById('EditLocationPopup').style.display = 'flex'; // Display popup 
        
        const locationInput = document.getElementById('locationInput');
        locationInput.value = '';
        locationInput.focus(); 
    }
    
    function updateLocation() {
        const newLocation = document.getElementById('locationInput').value.trim();

        document.getElementById('EditLocationPopup').style.display = 'none'; // Close popup 

        fetch('../php/update_appt.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                appointment_id: appointmentId,
                location: newLocation
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                refreshConfirmed(); 
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });

    }

    let slot_id = null; 
    // For profs 
    function cancelSlot(slotId) {
        const button = event.target;
        const appointmentItem = button.closest('.appointment-item');

        fetch('../php/cancel_slot.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                slot_id: slotId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (appointmentItem) {
                    appointmentItem.remove();
                }

                if (data.emails && data.emails.length > 0) {
                    const type = appointmentItem.querySelector('.field:nth-child(1)')?.innerText || '';
                    const date = appointmentItem.querySelector('.field:nth-child(2)')?.innerText || '';
                    const emailList = data.emails.join(',');
                    const subject = encodeURIComponent('Appointment cancelled');

                    const body = encodeURIComponent(
                        `Your appointment has been cancelled by the organizer.\n\n` +
                        `Type: ${type}\n` +
                        `Date: ${date}\n\n` +
                        `Please book another time if needed.`
                    );

                    window.open(`mailto:${emailList}?subject=${subject}&body=${body}`, '_blank');
                }

            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }

    // For students and profs 
    function cancelBooking(slotId) {
        const button = event.target;
        const appointmentItem = button.closest('.appointment-item');

        fetch('../php/cancel_booking.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                slot_id: slotId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (appointmentItem) {
                    appointmentItem.remove();
                }

                const profLink = appointmentItem.querySelector("a[href^='mailto:']");
                const profEmail = profLink
                    ? profLink.getAttribute('href').replace('mailto:', '')
                    : '';

                const studentName = "<?php echo $_SESSION['name']; ?>";
                const type = appointmentItem.querySelector('.field:nth-child(1)')?.innerText || '';
                const date = appointmentItem.querySelector('.field:nth-child(2)')?.innerText || '';
                const start = appointmentItem.querySelector('.short-field:nth-child(3)')?.innerText || '';
                const end = appointmentItem.querySelector('.short-field:nth-child(4)')?.innerText || '';
                const subject = encodeURIComponent('Booking cancelled');

                if (profEmail) {
                    const body = encodeURIComponent(
                        `${studentName} has cancelled their booking.\n\n` +
                        `Type: ${type}\n` +
                        `Date: ${date}\n` +
                        `Time: ${start} - ${end}\n\n`
                    );

                    window.open(`mailto:${profEmail}?subject=${subject}&body=${body}`,'_blank');
                }
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
</script> 

<script> 
    function submitAvail(method_type, group_name) {
    if (method_type == 'calendar') {
        window.location.href = `../pages/bookcalmethod.php?group=${encodeURIComponent(group_name)}`;
    } else {
        // Heatmap method
        window.location.href = `../pages/groupmeetings.php?group=${encodeURIComponent(group_name)}`;
    }
}
</script>
<script>
    function exportCalendar(){
        window.location.href = '../php/export_to_cal.php';
    }

</script>
    
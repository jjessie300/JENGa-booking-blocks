USE comp-307-db; 

CREATE TABLE Users (
 user_id INT AUTO_INCREMENT PRIMARY KEY,
 email VARCHAR(100) UNIQUE NOT NULL,
 name VARCHAR(50) NOT NULL, 
 password VARCHAR(255) NOT NULL,
 acc_type VARCHAR(20) NOT NULL -- owner/user 
);

CREATE TABLE Slots (
 slot_id INT AUTO_INCREMENT PRIMARY KEY,
 owner_id INT NOT NULL, -- person who created created slot (@mcgill user) 
 chosen_date DATE, 
 start_time TIME, 
 end_time TIME,
 status VARCHAR(20), -- public/cancelled 
 slot_type VARCHAR(20), -- group meeting/office hour/request 
 location VARCHAR(50), 
 FOREIGN KEY (owner_id) REFERENCES Users(user_id)
);

CREATE TABLE Bookings (
 user_id INT,
 slot_id INT,
 booking_date DATE, 
 note VARCHAR(255),  
 PRIMARY KEY (user_id, slot_id), 
 FOREIGN KEY (user_id) REFERENCES Users(user_id),
 FOREIGN KEY (slot_id) REFERENCES Slots(slot_id) ON DELETE CASCADE
);

CREATE TABLE Requests (
 request_id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT,
 owner_id INT,
 status VARCHAR(20), -- pending/accepted/declined
 chosen_date DATE, 
 requested_start TIME,
 requested_end TIME,
 note VARCHAR(255),
 FOREIGN KEY (user_id) REFERENCES Users(user_id), 
 FOREIGN KEY (owner_id) REFERENCES Users(user_id)
);


-- GROUP MEETING DB DESIGN 
CREATE TABLE GroupInvite (
group_name VARCHAR(100) PRIMARY KEY, 
inviter_id INT NOT NULL, 
start_date DATE, 
end_date DATE, 
status VARCHAR(20), -- pending/scheduled/cancelled 
type VARCHAR(20), -- calendar/heatmap
FOREIGN KEY (inviter_id) REFERENCES Users(user_id) 
); 

CREATE TABLE InviteRecipient (
invitee_id INT, 
group_name VARCHAR(100), 
invitee_availability LONGTEXT, 
PRIMARY KEY (invitee_id, group_name), 
FOREIGN KEY (invitee_id) REFERENCES Users(user_id), 
FOREIGN KEY (group_name) REFERENCES GroupInvite(group_name) ON DELETE CASCADE
); 

CREATE TABLE Availability (
availability_id INT PRIMARY KEY AUTO_INCREMENT, 
group_name VARCHAR(100), 
chosen_date DATE, 
start_time TIME, 
end_time TIME, 
FOREIGN KEY (group_name) REFERENCES GroupInvite(group_name) ON DELETE CASCADE
); 

CREATE TABLE GroupSlot (
gs_id INT PRIMARY KEY AUTO_INCREMENT,
chosen_date DATE, 
start_time TIME, 
end_time TIME, 
group_name VARCHAR(100),
location VARCHAR(50), 
FOREIGN KEY (group_name) REFERENCES GroupInvite(group_name) ON DELETE CASCADE
); 

CREATE TABLE SelectedGroupSlot (
user_id INT, 
gs_id INT, 
PRIMARY KEY (user_id, gs_id), 
FOREIGN KEY (user_id) REFERENCES Users(user_id), 
FOREIGN KEY (gs_id) REFERENCES GroupSlot(gs_id) ON DELETE CASCADE
); 

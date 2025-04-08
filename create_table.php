<?php
$servername = "localhost"; // MySQL server (usually localhost)
$username = "root";        // MySQL username (default is root)
$password = "";            // MySQL password (default is empty)
$dbname = "matrimosys";    // Database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// SQL query to create the religion table
$sql = "CREATE TABLE IF NOT EXISTS tbl_religion (
    religion_id INT AUTO_INCREMENT PRIMARY KEY,    -- Religion ID as primary key
    religion VARCHAR(50) NOT NULL         -- Name of the religion
)";

// Execute the query to create the religion table
if ($conn->query($sql) === TRUE) {
    echo "Table 'tbl_religion' created successfully!";
} else {
    echo "Error creating table: " . $conn->error;
}

// SQL query to create the education table
$sql = "CREATE TABLE IF NOT EXISTS tbl_education (
    education_id INT AUTO_INCREMENT PRIMARY KEY,    -- Education ID as primary key
    education VARCHAR(50) NOT NULL            -- Name of the education level
)";

// Execute the query to create the education table
if ($conn->query($sql) === TRUE) {
    echo "Table 'tbl_education' created successfully!";
} else {
    echo "Error creating table: " . $conn->error;
}

// SQL query to create the family table
$sql = "CREATE TABLE IF NOT EXISTS tbl_family (
    family_id INT AUTO_INCREMENT PRIMARY KEY,    -- Family ID as primary key                             
    father_name VARCHAR(100),                 -- Father's name
    father_job VARCHAR(100),                  -- Father's occupation
    mother_name VARCHAR(100),                 -- Mother's name
    mother_job VARCHAR(100),                  -- Mother's occupation
    family_name VARCHAR(100),                 -- Family name
    sibling_name VARCHAR(100)
)";

// Execute the query to create the family table
if ($conn->query($sql) === TRUE) {
    echo "Table 'tbl_family' created successfully!";
} else {
    echo "Error creating table: " . $conn->error;
}

// SQL query to create the caste table
$sql = "CREATE TABLE IF NOT EXISTS tbl_caste (
    caste_id INT AUTO_INCREMENT PRIMARY KEY,    -- Caste ID as primary key
    religion_id INT NOT NULL,                   -- Religion ID as foreign key
    caste VARCHAR(50) NOT NULL,                 -- Name of the caste
    FOREIGN KEY (religion_id) REFERENCES tbl_religion(religion_id)  -- Link to religion table
)";

// Execute the query to create the caste table
if ($conn->query($sql) === TRUE) {
    echo "Table 'tbl_caste' created successfully!";
} else {
    echo "Error creating table: " . $conn->error;
}

// SQL query to create the sub-education table
$sql = "CREATE TABLE IF NOT EXISTS tbl_subEducation (
    edusub_id INT AUTO_INCREMENT PRIMARY KEY,    -- Sub Education ID as primary key
    education_id INT NOT NULL,                   -- Education ID as foreign key
    eduSub VARCHAR(50) NOT NULL,                 -- Name of the sub-education
    FOREIGN KEY (education_id) REFERENCES tbl_education( education_id)  -- Link to education table
)";

// Execute the query to create the sub-education table
if ($conn->query($sql) === TRUE) {
    echo "Table 'tbl_subEducation' created successfully!";
} else {
    echo "Error creating table: " . $conn->error;
}

// SQL query to create the payment table
$sql = "CREATE TABLE IF NOT EXISTS tbl_payment (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,    -- Payment ID as primary key
    paymentType VARCHAR(50) NOT NULL,             -- Type of payment
    price DECIMAL(10,2) NOT NULL                  -- Price amount
)";

// Execute the query to create the payment table
if ($conn->query($sql) === TRUE) {
    echo "Table 'tbl_payment' created successfully!";
} else {
    echo "Error creating table: " . $conn->error;
}



// SQL query to create or update the table with the password column
$sql = "CREATE TABLE IF NOT EXISTS profiles (
    reg_id INT AUTO_INCREMENT PRIMARY KEY, -- Registration ID as primary key
    userid VARCHAR(50) UNIQUE NOT NULL,   -- Unique username for each profile
    username VARCHAR(100) NOT NULL,            -- Name of the person
    email VARCHAR(100) NOT NULL,           -- Email of the person
    phone VARCHAR(20) NOT NULL,            -- Phone number
    password VARCHAR(255) NOT NULL,        -- Encrypted password
    dob DATE NOT NULL,                     -- Date of Birth
    age INT,
    height VARCHAR(10),                    -- Height (e.g., in cm or meters)
    complexion VARCHAR(50),                -- Complexion (e.g., Fair, Dark, etc.)
    caste_id INT,
    edusub_id INT,
    family_id INT,
    payment_id INT DEFAULT 1,              -- Added default value of 1
    nativity VARCHAR(100),                 -- Nativity (e.g., City, Region)
    gender VARCHAR(10) DEFAULT NULL,       -- Gender with default value NULL
    about VARCHAR(200) DEFAULT NULL,       -- About section
    FOREIGN KEY (caste_id) REFERENCES tbl_caste(caste_id),
    FOREIGN KEY (edusub_id) REFERENCES tbl_subEducation (edusub_id),
    FOREIGN KEY (family_id) REFERENCES tbl_family(family_id),
    FOREIGN KEY (payment_id) REFERENCES tbl_payment(payment_id)  -- Added foreign key constraint
)";

// Execute the query to create or update the table
if ($conn->query($sql) === TRUE) {
    echo "Table 'profiles' created or updated successfully!";
} else {
    echo "Error creating or updating table: " . $conn->error;
}
// SQL query to create the login table
$sql = "CREATE TABLE IF NOT EXISTS tbl_login (
    id INT AUTO_INCREMENT PRIMARY KEY,    -- Login ID as primary key
    userid VARCHAR(50) NOT NULL UNIQUE,   -- Unique user identifier
    password VARCHAR(255) NOT NULL,       -- Hashed password
    reg_id INT NOT NULL,                 -- Registration ID as foreign key
    last_login TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,  -- Last login timestamp
    FOREIGN KEY (reg_id) REFERENCES profiles(reg_id) ON DELETE CASCADE
)";

// Execute the query to create the login table
if ($conn->query($sql) === TRUE) {
    echo "Table 'tbl_login' created successfully!";
} else {
    echo "Error creating table: " . $conn->error;
}

// SQL query to create the profile images table
$sql = "CREATE TABLE IF NOT EXISTS profile_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userid VARCHAR(255) NOT NULL,
    image VARCHAR(255) NOT NULL,
    FOREIGN KEY (userid) REFERENCES profiles(userid)
)";

// Execute the query to create the profile images table
if ($conn->query($sql) === TRUE) {
    echo "Table 'profile_images' created successfully!";
} else {
    echo "Error creating table: " . $conn->error;
}

// SQL query to create the preference table
$sql = "CREATE TABLE IF NOT EXISTS tbl_preference (
    pref_id INT AUTO_INCREMENT PRIMARY KEY,    -- Preference ID as primary key
    reg_id INT NOT NULL,                       -- Registration ID as foreign key
    gender VARCHAR(20) DEFAULT NULL,          -- Preferred gender
    min_age INT DEFAULT NULL,                  -- Minimum preferred age
    max_age INT DEFAULT NULL,                  -- Maximum preferred age
    religion VARCHAR(50) DEFAULT NULL,        -- Preferred religion
    caste_id INT DEFAULT NULL,                 -- Preferred caste (updated to caste_id)
    height INT DEFAULT NULL,                   -- Preferred height
    FOREIGN KEY (reg_id) REFERENCES profiles(reg_id) ON DELETE CASCADE,  -- Link to profiles table
    FOREIGN KEY (caste_id) REFERENCES tbl_caste(caste_id)
)";

// Execute the query to create the preference table
if ($conn->query($sql) === TRUE) {
    echo "Table 'tbl_preference' created successfully!";
} else {
    echo "Error creating table: " . $conn->error;
}

// SQL query to create the images table
$sql = "CREATE TABLE IF NOT EXISTS tbl_images (
    img_id INT AUTO_INCREMENT PRIMARY KEY,    -- Image ID as primary key
    userid VARCHAR(50) NOT NULL,              -- User ID as foreign key
    image VARCHAR(255) NOT NULL,               -- Image URL or path
    FOREIGN KEY (userid) REFERENCES profiles(userid)  -- Link to profiles table
)";

// Execute the query to create the images table
if ($conn->query($sql) === TRUE) {
    echo "Table 'tbl_images' created successfully!";
} else {
    echo "Error creating table: " . $conn->error;
}

// SQL query to create the request table
$sql = "CREATE TABLE IF NOT EXISTS tbl_request (
    request_id INT AUTO_INCREMENT PRIMARY KEY,    -- Request ID as primary key
    userid_sender VARCHAR(50) NOT NULL,          -- User ID of the sender
    userid_receiver VARCHAR(50) NOT NULL,        -- User ID of the receiver
    status INT DEFAULT 0,                         -- Added status column with default value 0
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Date when request was sent
    response_date TIMESTAMP NULL DEFAULT NULL,    -- Date when request was responded to
    FOREIGN KEY (userid_sender) REFERENCES profiles(userid),  -- Link to profiles table for sender
    FOREIGN KEY (userid_receiver) REFERENCES profiles(userid)  -- Link to profiles table for receiver
)";

// Execute the query to create the request table
if ($conn->query($sql) === TRUE) {
    echo "Table 'tbl_request' created successfully!";
} else {
    echo "Error creating table: " . $conn->error;
}

// SQL query to create the messages table
$sql = "CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id VARCHAR(255) NOT NULL,
    recipient_id VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    sent_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (sender_id) REFERENCES profiles(userid),
    FOREIGN KEY (recipient_id) REFERENCES profiles(userid)
)";

// Execute the query to create the messages table
if ($conn->query($sql) === TRUE) {
    echo "Table 'messages' created successfully!";
} else {
    echo "Error creating table: " . $conn->error;
}

// Close connection
$conn->close();
?>

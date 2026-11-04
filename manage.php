<?php
session_start();

/*
    TVETMARA Lumut Complaint Management System
    Admin Manage Complaints Page

    Database name: complaint_system
    Table name: complaints
*/

// Connect to MySQL
$db = null;
$databaseReady = false;

try {
    $db = new PDO(
        "mysql:host=localhost;dbname=complaint_system;charset=utf8mb4",
        "root",
        ""
    );

    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check whether the complaints table exists and can be read
    $db->query("SELECT complaint_id FROM complaints LIMIT 1");

    $databaseReady = true;

} catch (PDOException $e) {
    $databaseReady = false;
}

// Demo complaints used when the database is not ready
if (!isset($_SESSION["demo_complaints"])) {
    $_SESSION["demo_complaints"] = [
        [
            "complaint_id" => "CMP001",
            "student_name" => "Student A",
            "location" => "Dormitory",
            "problem" => "Broken ceiling fan",
            "description" => "The ceiling fan is not working.",
            "priority" => "High",
            "status" => "Pending",
            "photo" => "",
            "admin_response" => "",
            "created_at" => "2026-10-10"
        ],
        [
            "complaint_id" => "CMP002",
            "student_name" => "Student B",
            "location" => "Restrooms",
            "problem" => "Leaking tap",
            "description" => "Water keeps leaking from the tap.",
            "priority" => "Normal",
            "status" => "In Progress",
            "photo" => "",
            "admin_response" => "The maintenance team is checking the issue.",
            "created_at" => "2026-10-09"
        ],
        [
            "complaint_id" => "CMP003",
            "student_name" => "Student C",
            "location" => "Lecture Rooms",
            "problem" => "Broken chair",
            "description" => "One of the classroom chairs is damaged.",
            "priority" => "Low",
            "status" => "Resolved",
            "photo" => "",
            "admin_response" => "The chair has been repaired.",
            "created_at" => "2026-10-08"
        ],
        [
            "complaint_id" => "CMP004",
            "student_name" => "Student D",
            "location" => "Food Hall",
            "problem" => "Damaged table",
            "description" => "The table is damaged and needs repair.",
            "priority" => "Normal",
            "status" => "Pending",
            "photo" => "",
            "admin_response" => "",
            "created_at" => "2026-10-07"
        ],
        [
            "complaint_id" => "CMP005",
            "student_name" => "Student E",
            "location" => "Mosque",
            "problem" => "Faulty light",
            "description" => "The light is not working properly.",
            "priority" => "High",
            "status" => "Resolved",
            "photo" => "",
            "admin_response" => "The light has been replaced.",
            "created_at" => "2026-10-05"
        ]
    ];
}

// Use real database records when available
$complaints = [];

if ($databaseReady) {
    try {
        $statement = $db->query(
            "SELECT complaint_id, student_name, location, problem,
                    description, priority, status, photo,
                    admin_response, created_at
             FROM complaints
             ORDER BY created_at DESC"
        );

        $complaints = $statement->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $databaseReady = false;
    }
}

if (!$databaseReady) {
    $complaints = $_SESSION["demo_complaints"];
}

// Update complaint status and admin response
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id = $_POST["complaint_id"] ?? "";
    $newStatus = $_POST["status"] ?? "";
    $response = trim($_POST["admin_response"] ?? "");

    $allowedStatuses = ["Pending", "In Progress", "Resolved"];

    if (!in_array($newStatus, $allowedStatuses, true)) {
        $message = "Invalid complaint status.";

    } elseif ($databaseReady) {

        try {
            $update = $db->prepare(
                "UPDATE complaints
                 SET status = :status,
                     admin_response = :response
                 WHERE complaint_id = :id"
            );

            $update->execute([
                ":status" => $newStatus,
                ":response" => $response,
                ":id" => $id
            ]);

            $message = "Complaint updated successfully.";

        } catch (PDOException $e) {
            $message = "Unable to update the complaint. Check your database table.";
        }

    } else {

        // Save changes for this browser session in demo mode
        foreach ($_SESSION["demo_complaints"] as &$item) {
            if ($item["complaint_id"] === $id) {
                $item["status"] = $newStatus;
                $item["admin_response"] = $response;
                $message = "Demo complaint updated successfully.";
                break;
            }
        }
        unset($item);

        if ($message === "") {
            $message = "Complaint not found.";
        }
    }

    // Prevent the browser from submitting the same form again
    $_SESSION["admin_message"] = $message;

    header("Location: manage-complaints.php");
    exit;
}

if (isset($_SESSION["admin_message"])) {
    $message = $_SESSION["admin_message"];
    unset($_SESSION["admin_message"]);
}

// Search and status filter
$search = trim($_GET["search"] ?? "");
$filterStatus = $_GET["status"] ?? "All";

$filteredComplaints = [];

foreach ($complaints as $complaint) {
    $searchText = strtolower(
        ($complaint["complaint_id"] ?? "") . " " .
        ($complaint["student_name"] ?? "") . " " .
        ($complaint["location"] ?? "") . " " .
        ($complaint["problem"] ?? "")
    );

    $matchesSearch = strpos(
        $searchText,
        strtolower($search)
    ) !== false;

    $matchesStatus = (
        $filterStatus === "All" ||
        ($complaint["status"] ?? "") === $filterStatus
    );

    if ($matchesSearch && $matchesStatus) {
        $filteredComplaints[] = $complaint;
    }
}

// Calculate complaint totals
$totalCount = count($complaints);
$pendingCount = 0;
$progressCount = 0;
$resolvedCount = 0;

foreach ($complaints as $complaint) {
    if (($complaint["status"] ?? "") === "Pending") {
        $pendingCount++;
    } elseif (($complaint["status"] ?? "") === "In Progress") {
        $progressCount++;
    } elseif (($complaint["status"] ?? "") === "Resolved") {
        $resolvedCount++;
    }
}

// Safely display text in HTML
function e($value) {
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Complaints | TVETMARA Lumut</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #050b11;
            background-image:
                linear-gradient(#00d9f710 1px, transparent 1px),
                linear-gradient(90deg, #00d9f710 1px, transparent 1px);
            background-size: 32px 32px;
            color: #d9faff;
        }

        /* Header */
        header {
            background-color: #050b11;
            padding: 22px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            border-bottom: 1px solid #00d9f7;
            box-shadow: 0 5px 25px #00d9f710;
        }

        header h2 {
            color: #00eaff;
            text-shadow: 0 0 12px #00d9f755;
        }

        header p {
            margin-top: 5px;
            font-size: 13px;
            color: #a8c9d2;
        }

        .admin-label {
            background-color: #00d9f7;
            color: #031016;
            padding: 10px 16px;
            border-radius: 10px;
            font-weight: bold;
            box-shadow: 0 0 18px #00d9f755;
        }

        /* Navigation */
        nav {
            background-color: #08121b;
            padding: 14px 6%;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            border-bottom: 1px solid #123b49;
        }

        nav a {
            color: #d9faff;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            transition: 0.3s ease;
        }

        nav a:hover,
        nav a.active {
            color: #00eaff;
            text-shadow: 0 0 10px #00d9f777;
        }

        /* Main content */
        .container {
            width: 90%;
            max-width: 1400px;
            margin: 30px auto;
        }

        .welcome {
            margin-bottom: 25px;
        }

        .welcome h1 {
            color: #00eaff;
            margin-bottom: 8px;
            text-shadow: 0 0 14px #00d9f733;
        }

        .welcome p {
            color: #9bbbc6;
            line-height: 1.6;
        }

        /* Database notice */
        .database-notice {
            margin-top: 14px;
            padding: 12px 15px;
            border-radius: 8px;
            background-color: #302819;
            border: 1px solid #8e6d30;
            color: #ffd779;
            font-size: 13px;
            line-height: 1.6;
        }

        /* Message */
        .message {
            margin-bottom: 20px;
            padding: 14px 16px;
            background-color: #103b2b;
            border: 1px solid #24875c;
            color: #9bf0bd;
            border-radius: 8px;
            line-height: 1.5;
        }

        /* Summary cards */
        .summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 30px;
        }

        .card {
            background: linear-gradient(145deg, #0d1a25, #08111a);
            padding: 23px;
            border-radius: 14px;
            border: 1px solid #164352;
            box-shadow: 0 5px 20px #00000035;
            transition: 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            border-color: #00d9f7;
            box-shadow: 0 5px 24px #00d9f720;
        }

        .card p {
            color: #a7c3cd;
            font-size: 14px;
            margin-bottom: 12px;
        }

        .card h2 {
            color: #00eaff;
            font-size: 30px;
        }

        .card.total {
            border-top: 4px solid #00eaff;
        }

        .card.pending {
            border-top: 4px solid #ffc857;
        }

        .card.progress {
            border-top: 4px solid #65aaff;
        }

        .card.resolved {
            border-top: 4px solid #51d99a;
        }

        /* Complaint section */
        .table-section {
            background-color: #08121b;
            padding: 25px;
            border-radius: 14px;
            border: 1px solid #164352;
            box-shadow: 0 5px 25px #00000035;
        }

        .table-heading {
            margin-bottom: 20px;
        }

        .table-heading h2 {
            color: #00eaff;
            margin-bottom: 7px;
        }

        .table-heading p {
            color: #9bbbc6;
            font-size: 14px;
            line-height: 1.6;
        }

        /* Search and filter */
        .filters {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filters input,
        .filters select {
            padding: 12px;
            border: 1px solid #175466;
            border-radius: 8px;
            background-color: #071019;
            color: #e2fbff;
            font-size: 14px;
            outline: none;
            transition: 0.3s ease;
        }

        .filters input:focus,
        .filters select:focus {
            border-color: #00eaff;
            box-shadow: 0 0 10px #00d9f722;
        }

        .filters input::placeholder {
            color: #7697a3;
        }

        .filters input {
            flex: 1;
            min-width: 200px;
        }

        .filters select {
            min-width: 160px;
        }

        /* Table */
        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        th {
            background-color: #07313e;
            color: #00eaff;
            text-align: left;
            padding: 14px;
            font-size: 13px;
            border-bottom: 1px solid #08718a;
            white-space: nowrap;
        }

        td {
            padding: 14px;
            border-bottom: 1px solid #17313c;
            font-size: 13px;
            color: #d9faff;
            vertical-align: top;
        }

        tbody tr {
            transition: background-color 0.2s ease;
        }

        tbody tr:hover {
            background-color: #102631;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
        }

        .Pending {
            background-color: #44351a;
            color: #ffd36b;
        }

        .In-Progress {
            background-color: #102e4a;
            color: #91c7ff;
        }

        .Resolved {
            background-color: #103b2b;
            color: #81e9ad;
        }

        .priority {
            font-weight: bold;
        }

        .High {
            color: #ff727f;
        }

        .Normal {
            color: #ffc857;
        }

        .Low {
            color: #65dfa0;
        }

        /* Buttons */
        button {
            border: none;
            cursor: pointer;
            font-weight: bold;
            border-radius: 7px;
            transition: 0.3s ease;
        }

        .view-btn,
        .save-btn {
            background-color: #00d9f7;
            color: #031016;
            padding: 10px 14px;
            box-shadow: 0 0 10px #00d9f722;
        }

        .view-btn:hover,
        .save-btn:hover {
            background-color: #8af5ff;
            box-shadow: 0 0 18px #00d9f755;
            transform: translateY(-2px);
        }

        .status-select {
            padding: 8px;
            border: 1px solid #175466;
            border-radius: 6px;
            background-color: #071019;
            color: #d9faff;
            outline: none;
            width: 135px;
        }

        .status-select:focus {
            border-color: #00eaff;
        }

        .empty-message {
            text-align: center;
            padding: 25px;
            color: #9bbbc6;
        }

        /* Complaint details */
        .detail {
            margin-bottom: 14px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .detail strong {
            color: #00eaff;
        }

        .complaint-photo {
            display: block;
            max-width: 100%;
            width: 260px;
            max-height: 220px;
            object-fit: contain;
            margin-top: 10px;
            border-radius: 8px;
            border: 1px solid #175466;
        }

        /* Admin response form */
        .response-form {
            min-width: 230px;
            display: flex;
            flex-direction: column;
            gap: 9px;
        }

        .response-form textarea {
            width: 100%;
            min-height: 75px;
            resize: vertical;
            padding: 10px;
            border: 1px solid #175466;
            border-radius: 7px;
            background-color: #071019;
            color: #e2fbff;
            outline: none;
            font-size: 12px;
            line-height: 1.5;
        }

        .response-form textarea:focus {
            border-color: #00eaff;
        }

        .response-form textarea::placeholder {
            color: #7697a3;
        }

        /* Footer */
        footer {
            text-align: center;
            padding: 25px;
            color: #7697a3;
            font-size: 13px;
            border-top: 1px solid #123b49;
            margin-top: 30px;
        }

        @media (max-width: 1000px) {
            .summary {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .summary {
                grid-template-columns: 1fr;
            }

            .table-section {
                padding: 15px;
            }

            header {
                padding: 20px 5%;
            }

            nav {
                gap: 14px;
            }

            .container {
                width: 94%;
                margin: 22px auto;
            }

            .filters input,
            .filters select {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <!-- Header -->
    <header>
        <div>
            <h2>🏫 TVETMARA Lumut</h2>
            <p>Complaint Management System</p>
        </div>

        <div class="admin-label">Admin Dashboard</div>
    </header>

    <!-- Navigation -->
    <nav>
        <a href="home.html">Home</a>
        <a href="complaint.html">Make a Complaint</a>
        <a href="my-complaints.html">My Complaints</a>
        <a href="campus-gallery.html">Campus Gallery</a>
        <a href="manage-complaints.php" class="active">Manage Complaints</a>
        <a href="index.html">Logout</a>
    </nav>

    <main class="container">

        <!-- Welcome -->
        <section class="welcome">
            <h1>Welcome, Admin! ✨</h1>
            <p>
                Manage student complaints, review reported problems,
                and update their progress at TVETMARA Lumut.
            </p>

            <?php if (!$databaseReady): ?>
                <div class="database-notice">
                    <strong>Demo Mode:</strong>
                    The database is unavailable, so sample complaints are
                    being displayed. Changes will be saved only for this
                    browser session. Check your database name, table, and
                    column names to enable database mode.
                </div>
            <?php endif; ?>
        </section>

        <!-- Success or error message -->
        <?php if ($message !== ""): ?>
            <div class="message">
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <!-- Summary cards -->
        <section class="summary">

            <div class="card total">
                <p>Total Complaints</p>
                <h2><?= $totalCount ?></h2>
            </div>

            <div class="card pending">
                <p>Pending</p>
                <h2><?= $pendingCount ?></h2>
            </div>

            <div class="card progress">
                <p>In Progress</p>
                <h2><?= $progressCount ?></h2>
            </div>

            <div class="card resolved">
                <p>Resolved</p>
                <h2><?= $resolvedCount ?></h2>
            </div>

        </section>

        <!-- Complaint management -->
        <section class="table-section">

            <div class="table-heading">
                <h2>Manage Student Complaints</h2>
                <p>
                    Search complaints, view their details, update statuses,
                    and send responses to students.
                </p>
            </div>

            <!-- Search and filter -->
            <form class="filters" method="GET"
                  action="manage-complaints.php">

                <input
                    type="text"
                    name="search"
                    placeholder="Search ID, student, location or problem..."
                    value="<?= e($search) ?>"
                >

                <select name="status">
                    <option value="All"
                        <?= $filterStatus === "All" ? "selected" : "" ?>>
                        All Statuses
                    </option>

                    <option value="Pending"
                        <?= $filterStatus === "Pending" ? "selected" : "" ?>>
                        Pending
                    </option>

                    <option value="In Progress"
                        <?= $filterStatus === "In Progress" ? "selected" : "" ?>>
                        In Progress
                    </option>

                    <option value="Resolved"
                        <?= $filterStatus === "Resolved" ? "selected" : "" ?>>
                        Resolved
                    </option>
                </select>

                <button type="submit" class="view-btn">Search</button>

                <a href="manage-complaints.php"
                   class="view-btn"
                   style="text-decoration:none; display:inline-flex;
                          align-items:center;">
                    Reset
                </a>

            </form>

            <!-- Complaint table -->
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Complaint ID</th>
                            <th>Student Name</th>
                            <th>Location</th>
                            <th>Problem & Description</th>
                            <th>Photo</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Admin Response</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (count($filteredComplaints) > 0): ?>

                        <?php foreach ($filteredComplaints as $complaint): ?>

                            <tr>
                                <td>
                                    <?= e($complaint["complaint_id"] ?? "") ?>
                                    <br><br>
                                    <small>
                                        <?= e($complaint["created_at"] ?? "") ?>
                                    </small>
                                </td>

                                <td>
                                    <?= e($complaint["student_name"] ?? "") ?>
                                </td>

                                <td>
                                    <?= e($complaint["location"] ?? "") ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= e($complaint["problem"] ?? "") ?>
                                    </strong>

                                    <p style="margin-top:8px; color:#9bbbc6;
                                              line-height:1.5;">
                                        <?= e($complaint["description"] ?? "No description entered") ?>
                                    </p>
                                </td>

                                <td>
                                    <?php
                                    $photo = $complaint["photo"] ?? "";
                                    ?>

                                    <?php if ($photo !== ""): ?>
                                        <?php
                                        // Permit only local relative image paths
                                        $safePhoto = str_replace("\\", "/", $photo);
                                        $validPhoto =
                                            !preg_match('/^(?:[a-z]+:|\/\/|\/)/i', $safePhoto)
                                            && !preg_match('/(^|\/)\.\.(\/|$)/', $safePhoto)
                                            && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $safePhoto);
                                        ?>

                                        <?php if ($validPhoto): ?>
                                            <a href="<?= e($safePhoto) ?>"
                                               target="_blank"
                                               rel="noopener noreferrer">
                                                <img
                                                    src="<?= e($safePhoto) ?>"
                                                    alt="Complaint photo"
                                                    class="complaint-photo"
                                                    loading="lazy"
                                                >
                                            </a>
                                        <?php else: ?>
                                            No valid local photo path
                                        <?php endif; ?>

                                    <?php else: ?>
                                        No photo
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="priority <?= e($complaint["priority"] ?? "Normal") ?>">
                                        <?= e($complaint["priority"] ?? "Normal") ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="status
                                        <?= str_replace(" ", "-", e($complaint["status"] ?? "Pending")) ?>">
                                        <?= e($complaint["status"] ?? "Pending") ?>
                                    </span>
                                </td>

                                <td>
                                    <?= e($complaint["admin_response"] ?? "") !== ""
                                        ? nl2br(e($complaint["admin_response"]))
                                        : '<span style="color:#7697a3;">No response yet</span>' ?>
                                </td>

                                <td>
                                    <form method="POST"
                                          action="manage-complaints.php"
                                          class="response-form">

                                        <input
                                            type="hidden"
                                            name="complaint_id"
                                            value="<?= e($complaint["complaint_id"] ?? "") ?>"
                                        >

                                        <label for="status_<?= e($complaint["complaint_id"] ?? "") ?>"
                                               style="color:#9bbbc6; font-size:12px;">
                                            Update Status
                                        </label>

                                        <select
                                            class="status-select"
                                            name="status"
                                            id="status_<?= e($complaint["complaint_id"] ?? "") ?>"
                                            required
                                        >
                                            <option value="Pending"
                                                <?= ($complaint["status"] ?? "") === "Pending" ? "selected" : "" ?>>
                                                Pending
                                            </option>

                                            <option value="In Progress"
                                                <?= ($complaint["status"] ?? "") === "In Progress" ? "selected" : "" ?>>
                                                In Progress
                                            </option>

                                            <option value="Resolved"
                                                <?= ($complaint["status"] ?? "") === "Resolved" ? "selected" : "" ?>>
                                                Resolved
                                            </option>
                                        </select>

                                        <label for="response_<?= e($complaint["complaint_id"] ?? "") ?>"
                                               style="color:#9bbbc6; font-size:12px;">
                                            Admin Response
                                        </label>

                                        <textarea
                                            name="admin_response"
                                            id="response_<?= e($complaint["complaint_id"] ?? "") ?>"
                                            placeholder="Write a response to the student..."
                                        ><?= e($complaint["admin_response"] ?? "") ?></textarea>

                                        <button type="submit" class="save-btn">
                                            Save Changes
                                        </button>

                                    </form>
                                </td>

                                <td>
                                    <button
                                        type="button"
                                        class="view-btn"
                                        onclick="viewComplaint(this)"
                                        data-id="<?= e($complaint["complaint_id"] ?? "") ?>"
                                        data-student="<?= e($complaint["student_name"] ?? "") ?>"
                                        data-location="<?= e($complaint["location"] ?? "") ?>"
                                        data-problem="<?= e($complaint["problem"] ?? "") ?>"
                                        data-description="<?= e($complaint["description"] ?? "") ?>"
                                        data-priority="<?= e($complaint["priority"] ?? "") ?>"
                                        data-status="<?= e($complaint["status"] ?? "") ?>"
                                        data-response="<?= e($complaint["admin_response"] ?? "") ?>"
                                        data-date="<?= e($complaint["created_at"] ?? "") ?>"
                                    >
                                        View Details
                                    </button>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="9" class="empty-message">
                                No complaints found. Try another search
                                or select a different status.
                            </td>
                        </tr>

                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </section>
    </main>

    <!-- Complaint details popup -->
    <div class="modal" id="complaintModal"
         role="dialog" aria-modal="true"
         aria-labelledby="modalTitle">

        <div class="modal-content">
            <h2 id="modalTitle">Complaint Details</h2>

            <div id="complaintDetails"></div>

            <div style="display:flex; justify-content:flex-end; margin-top:20px;">
                <button type="button" class="close-btn"
                        onclick="closeModal()">
                    Close
                </button>
            </div>
        </div>
    </div>

    <footer>
        &copy; 2026 TVETMARA Lumut Complaint Management System
    </footer>

    <script>
        // Show complaint details
        function viewComplaint(button) {
            const data = button.dataset;
            const details = document.getElementById("complaintDetails");

            // Create text safely instead of inserting user data as HTML
            details.replaceChildren();

            function addDetail(label, value) {
                const paragraph = document.createElement("p");
                paragraph.className = "detail";

                const strong = document.createElement("strong");
                strong.textContent = label + ": ";

                paragraph.appendChild(strong);
                paragraph.appendChild(
                    document.createTextNode(value || "Not provided")
                );

                details.appendChild(paragraph);
            }

            addDetail("Complaint ID", data.id);
            addDetail("Student Name", data.student);
            addDetail("Location", data.location);
            addDetail("Problem", data.problem);
            addDetail("Description", data.description);
            addDetail("Priority", data.priority);
            addDetail("Status", data.status);
            addDetail("Date Submitted", data.date);
            addDetail("Admin Response", data.response);

            document.getElementById("complaintModal").style.display = "flex";
        }

        // Close the complaint details popup
        function closeModal() {
            document.getElementById("complaintModal").style.display = "none";
        }

        // Close popup if the user clicks outside it
        document.getElementById("complaintModal").addEventListener(
            "click",
            function(event) {
                if (event.target === this) {
                    closeModal();
                }
            }
        );

        // Close popup using the Escape key
        document.addEventListener("keydown", function(event) {
            if (event.key === "Escape") {
                closeModal();
            }
        });
    </script>

</body>
</html>
```
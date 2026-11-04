
<?php
// Connect to the database
$conn = new mysqli("localhost", "root", "", "complaint db");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Set character encoding
$conn->set_charset("utf8mb4");

// Get complaint records from database
$sql = "SELECT * FROM complaints";
$result = $conn->query($sql);

if (!$result) {
    die("Unable to retrieve complaints: " . $conn->error);
}

// Store complaint records
$complaints = [];

while ($row = $result->fetch_assoc()) {
    $complaints[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | TVETMARA Lumut</title>

    <style>
        :root {
            --black: #05090f;
            --navy: #081421;
            --cyan: #00e5ff;
            --cyan-soft: #b8f8ff;
            --panel: rgba(5, 12, 21, 0.90);
            --text: #eefcff;
            --muted: #a7c4d0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: var(--black);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Animated IT background */
        .background-grid {
            position: fixed;
            inset: 0;
            z-index: -3;
            pointer-events: none;
            background:
                linear-gradient(rgba(0, 229, 255, 0.055) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 229, 255, 0.055) 1px, transparent 1px),
                radial-gradient(ellipse at top, #102d43 0%, #07111e 48%, #03060b 100%);
            background-size: 45px 45px, 45px 45px, cover;
            animation: gridMove 18s linear infinite;
        }

        .background-glow {
            position: fixed;
            inset: -50%;
            z-index: -2;
            pointer-events: none;
            background:
                radial-gradient(circle at 25% 35%,
                    rgba(0, 229, 255, 0.15), transparent 25%),
                radial-gradient(circle at 75% 65%,
                    rgba(0, 110, 255, 0.12), transparent 28%);
            animation: glowMove 12s ease-in-out infinite alternate;
        }

        .light-beam {
            position: fixed;
            top: -30%;
            left: -40%;
            width: 35%;
            height: 160%;
            z-index: -1;
            pointer-events: none;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(0, 229, 255, 0.035),
                transparent
            );
            transform: rotate(25deg);
            animation: beamMove 14s linear infinite;
        }

        .floating-symbols {
            position: fixed;
            inset: 0;
            z-index: -1;
            overflow: hidden;
            pointer-events: none;
        }

        .symbol {
            position: absolute;
            color: rgba(0, 229, 255, 0.13);
            font-family: monospace;
            font-size: 25px;
            animation: floatUp 16s linear infinite;
        }

        .symbol:nth-child(1) {
            left: 8%;
            top: 90%;
        }

        .symbol:nth-child(2) {
            left: 25%;
            top: 100%;
            animation-delay: 4s;
        }

        .symbol:nth-child(3) {
            left: 53%;
            top: 95%;
            animation-delay: 2s;
        }

        .symbol:nth-child(4) {
            left: 76%;
            top: 100%;
            animation-delay: 7s;
        }

        .symbol:nth-child(5) {
            left: 90%;
            top: 95%;
            animation-delay: 10s;
        }

        @keyframes gridMove {
            from {
                background-position: 0 0, 0 0, center;
            }
            to {
                background-position: 45px 45px, 45px 45px, center;
            }
        }

        @keyframes glowMove {
            from {
                transform: translate(-3%, -2%) scale(1);
            }
            to {
                transform: translate(3%, 2%) scale(1.08);
            }
        }

        @keyframes beamMove {
            from {
                left: -40%;
            }
            to {
                left: 130%;
            }
        }

        @keyframes floatUp {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 0;
            }
            15% {
                opacity: 1;
            }
            85% {
                opacity: 0.8;
            }
            100% {
                transform: translateY(-120vh) rotate(180deg);
                opacity: 0;
            }
        }

        /* Header */
        header {
            position: relative;
            background: linear-gradient(
                120deg,
                rgba(5, 12, 21, 0.97),
                rgba(8, 30, 45, 0.96)
            );
            padding: 22px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            border-bottom: 1px solid rgba(0, 229, 255, 0.45);
            box-shadow: 0 5px 25px #00000050;
        }

        header h2 {
            color: var(--cyan);
            text-shadow: 0 0 14px rgba(0, 229, 255, 0.35);
        }

        header p {
            margin-top: 5px;
            font-size: 13px;
            color: var(--muted);
        }

        .admin-label {
            background: rgba(0, 229, 255, 0.09);
            color: var(--cyan);
            padding: 10px 16px;
            border-radius: 20px;
            font-weight: bold;
            border: 1px solid rgba(0, 229, 255, 0.55);
            box-shadow: 0 0 18px rgba(0, 229, 255, 0.08);
        }

        /* Navigation */
        nav {
            background: rgba(5, 12, 21, 0.94);
            padding: 14px 6%;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            border-bottom: 1px solid rgba(0, 229, 255, 0.20);
            backdrop-filter: blur(10px);
        }

        nav a {
            color: var(--muted);
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            transition: color 0.3s ease, text-shadow 0.3s ease;
        }

        nav a:hover {
            color: var(--cyan);
            text-shadow: 0 0 10px rgba(0, 229, 255, 0.65);
        }

        /* Main content */
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 30px auto;
        }

        .welcome {
            margin-bottom: 25px;
            animation: fadeIn 0.8s ease both;
        }

        .welcome h1 {
            color: var(--cyan);
            margin-bottom: 8px;
            text-shadow: 0 0 18px rgba(0, 229, 255, 0.22);
        }

        .welcome p {
            color: var(--muted);
            line-height: 1.7;
        }

        /* Summary cards */
        .summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 30px;
        }

        .card {
            position: relative;
            overflow: hidden;
            background: linear-gradient(
                145deg,
                rgba(12, 29, 43, 0.96),
                rgba(5, 12, 21, 0.96)
            );
            padding: 23px;
            border-radius: 14px;
            border: 1px solid rgba(0, 229, 255, 0.20);
            box-shadow: 0 5px 22px #00000035;
            transition: transform 0.25s ease,
                        border-color 0.25s ease,
                        box-shadow 0.25s ease;
        }

        .card:hover {
            transform: translateY(-4px);
            border-color: var(--cyan);
            box-shadow: 0 0 22px rgba(0, 229, 255, 0.10);
        }

        .card p {
            color: var(--muted);
            font-size: 14px;
            margin-bottom: 12px;
        }

        .card h2 {
            color: var(--cyan-soft);
            font-size: 30px;
        }

        .card.total {
            border-top: 4px solid var(--cyan);
        }

        .card.pending {
            border-top: 4px solid #ffcb70;
        }

        .card.progress {
            border-top: 4px solid #75bfff;
        }

        .card.resolved {
            border-top: 4px solid #6ce3a1;
        }

        /* Complaint table section */
        .table-section {
            background: var(--panel);
            padding: 25px;
            border-radius: 14px;
            border: 1px solid rgba(0, 229, 255, 0.23);
            box-shadow: 0 5px 25px #00000035;
            backdrop-filter: blur(10px);
        }

        .table-heading {
            margin-bottom: 20px;
        }

        .table-heading h2 {
            color: var(--cyan);
            margin-bottom: 7px;
        }

        .table-heading p {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.7;
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
            border: 1px solid rgba(0, 229, 255, 0.27);
            border-radius: 8px;
            background-color: #091724;
            color: var(--text);
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s ease,
                        box-shadow 0.3s ease;
        }

        .filters input:focus,
        .filters select:focus {
            border-color: var(--cyan);
            box-shadow: 0 0 10px rgba(0, 229, 255, 0.12);
        }

        .filters input::placeholder {
            color: #819eab;
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
            border-radius: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th {
            background: linear-gradient(135deg, #07516a, #087b92);
            color: #edfdff;
            text-align: left;
            padding: 14px;
            font-size: 13px;
            border-bottom: 1px solid var(--cyan);
        }

        td {
            padding: 14px;
            border-bottom: 1px solid rgba(0, 229, 255, 0.12);
            font-size: 13px;
            color: #d9edf4;
        }

        tbody tr {
            background: rgba(7, 19, 30, 0.62);
            transition: background-color 0.2s ease;
        }

        tbody tr:hover {
            background-color: rgba(0, 229, 255, 0.07);
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .Pending {
            background-color: #45351b;
            color: #ffd580;
        }

        .In-Progress {
            background-color: #15354b;
            color: #a9dfff;
        }

        .Resolved {
            background-color: #123b2b;
            color: #9de9b9;
        }

        .priority {
            font-weight: bold;
        }

        .High {
            color: #ff8585;
        }

        .Normal {
            color: #ffd580;
        }

        .Low {
            color: #85e6a9;
        }

        button {
            border: none;
            cursor: pointer;
            font-weight: bold;
            border-radius: 7px;
            transition: all 0.25s ease;
        }

        .view-btn {
            background: linear-gradient(135deg, #00e5ff, #009bb8);
            color: #04111b;
            padding: 9px 13px;
        }

        .view-btn:hover {
            background: linear-gradient(135deg, #b8f8ff, #00cce8);
            transform: translateY(-1px);
            box-shadow: 0 0 14px rgba(0, 229, 255, 0.22);
        }

        .status-select {
            padding: 7px;
            border: 1px solid rgba(0, 229, 255, 0.25);
            border-radius: 6px;
            background-color: #091724;
            color: var(--text);
            outline: none;
        }

        .status-select:focus {
            border-color: var(--cyan);
        }

        .empty-message {
            text-align: center;
            padding: 25px;
            color: var(--muted);
            display: none;
        }

        /* Complaint details popup */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background-color: rgba(0, 4, 10, 0.84);
            justify-content: center;
            align-items: center;
            padding: 20px;
            z-index: 10;
            backdrop-filter: blur(6px);
        }

        .modal-content {
            background: linear-gradient(145deg, #102436, #07111d);
            padding: 28px;
            border-radius: 15px;
            width: 100%;
            max-width: 500px;
            max-height: 85vh;
            overflow-y: auto;
            border: 1px solid rgba(0, 229, 255, 0.65);
            box-shadow: 0 0 35px rgba(0, 229, 255, 0.12);
            animation: fadeIn 0.25s ease both;
        }

        .modal-content h2 {
            color: var(--cyan);
            margin-bottom: 20px;
        }

        .detail {
            margin-bottom: 15px;
            line-height: 1.5;
            color: #d9edf4;
            overflow-wrap: anywhere;
        }

        .detail strong {
            color: var(--cyan-soft);
        }

        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .close-btn {
            background: linear-gradient(135deg, #00e5ff, #009bb8);
            color: #04111b;
            padding: 11px 20px;
        }

        .close-btn:hover {
            background: #b8f8ff;
            box-shadow: 0 0 14px rgba(0, 229, 255, 0.2);
        }

        footer {
            text-align: center;
            padding: 25px;
            color: var(--muted);
            font-size: 13px;
            border-top: 1px solid rgba(0, 229, 255, 0.18);
            margin-top: 30px;
            line-height: 1.8;
        }

        footer span {
            color: var(--cyan);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(14px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Mobile-friendly layout */
        @media (max-width: 850px) {
            .summary {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 500px) {
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
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* =====================================================
           NEW: Edit, Delete and Print styles (added only)
           ===================================================== */

        /* Edit button */
        .edit-btn {
            background: linear-gradient(135deg, #ffcb70, #e0a030);
            color: #1c1405;
            padding: 9px 13px;
        }

        .edit-btn:hover {
            background: linear-gradient(135deg, #ffe0a3, #ffc04d);
            transform: translateY(-1px);
            box-shadow: 0 0 14px rgba(255, 203, 112, 0.25);
        }

        /* Delete button */
        .delete-btn {
            background: linear-gradient(135deg, #ff6b6b, #c93a3a);
            color: #ffffff;
            padding: 9px 13px;
        }

        .delete-btn:hover {
            background: linear-gradient(135deg, #ff8a8a, #e04848);
            transform: translateY(-1px);
            box-shadow: 0 0 14px rgba(255, 107, 107, 0.28);
        }

        /* Print button */
        .print-btn {
            background: linear-gradient(135deg, #00e5ff, #009bb8);
            color: #04111b;
            padding: 12px 20px;
            font-size: 14px;
        }

        .print-btn:hover {
            background: linear-gradient(135deg, #b8f8ff, #00cce8);
            transform: translateY(-1px);
            box-shadow: 0 0 14px rgba(0, 229, 255, 0.22);
        }

        /* Edit popup form */
        .edit-id {
            color: var(--muted);
            font-size: 14px;
            margin-bottom: 18px;
        }

        .edit-id strong {
            color: var(--cyan-soft);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: bold;
            color: var(--cyan-soft);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(0, 229, 255, 0.27);
            border-radius: 8px;
            background-color: #091724;
            color: var(--text);
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s ease,
                        box-shadow 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--cyan);
            box-shadow: 0 0 10px rgba(0, 229, 255, 0.12);
        }

        .edit-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 22px;
        }

        .save-btn {
            background: linear-gradient(135deg, #00e5ff, #009bb8);
            color: #04111b;
            padding: 11px 20px;
        }

        .save-btn:hover {
            background: #b8f8ff;
            box-shadow: 0 0 14px rgba(0, 229, 255, 0.2);
        }

        .cancel-btn {
            background: transparent;
            color: var(--muted);
            border: 1px solid rgba(0, 229, 255, 0.35);
            padding: 11px 20px;
        }

        .cancel-btn:hover {
            color: var(--cyan);
            border-color: var(--cyan);
        }

        /* Text that only shows when printing */
        .print-only {
            display: none;
        }

        /* Print layout */
        @media print {
            @page {
                margin: 12mm;
            }

            .background-grid,
            .background-glow,
            .light-beam,
            .floating-symbols,
            .admin-label,
            nav,
            .filters,
            .modal,
            footer {
                display: none !important;
            }

            * {
                background: transparent !important;
                color: #000000 !important;
                box-shadow: none !important;
                text-shadow: none !important;
                backdrop-filter: none !important;
                animation: none !important;
            }

            body {
                background: #ffffff !important;
            }

            header {
                padding: 0 0 12px 0;
                border-bottom: 2px solid #000000 !important;
            }

            .container {
                width: 100%;
                max-width: 100%;
                margin: 15px 0;
            }

            .card,
            .table-section {
                border: 1px solid #999999 !important;
            }

            .summary {
                grid-template-columns: repeat(4, 1fr) !important;
            }

            .table-section {
                padding: 15px;
            }

            .table-wrapper {
                overflow: visible !important;
            }

            table {
                min-width: 0 !important;
            }

            th,
            td {
                border-bottom: 1px solid #999999 !important;
            }

            th {
                border-bottom: 2px solid #000000 !important;
            }

            /* Hide View, Edit and Delete columns when printing */
            th:nth-child(n+7),
            td:nth-child(n+7) {
                display: none !important;
            }

            .status-select {
                -webkit-appearance: none;
                appearance: none;
                border: none !important;
                padding: 0 !important;
            }

            tr {
                page-break-inside: avoid;
            }

            .print-only {
                display: block;
                margin-top: 6px;
                font-size: 12px;
            }
        }
    </style>
</head>

<body>

    <!-- Animated background -->
    <div class="background-grid"></div>
    <div class="background-glow"></div>
    <div class="light-beam"></div>

    <div class="floating-symbols" aria-hidden="true">
        <span class="symbol">&lt;/&gt;</span>
        <span class="symbol">{ }</span>
        <span class="symbol">01</span>
        <span class="symbol">&lt;?&gt;</span>
        <span class="symbol">#</span>
    </div>

    <!-- Header -->
    <header>
        <div>
            <h2>TVETMARA Lumut</h2>
            <p>Complaint Management System</p>
        </div>

        <div class="admin-label">Admin Dashboard</div>
    </header>

    <!-- Navigation menu -->
    <nav>
        <a href="home.html">Home</a>
        <a href="campus-gallery.html">Campus Gallery</a>
        <a href="complaint.html">Submit Complaint</a>
        <a href="my-complaints.html">My Complaints</a>
        <a href="admin-dashboard.php">Dashboard</a>
        <a href="index.html">Log Out</a>
    </nav>

    <main class="container">

        <!-- Welcome message -->
        <section class="welcome">
            <h1>Welcome, Admin! 🌟</h1>
            <p>Manage and monitor student complaints at TVETMARA Lumut.</p>
        </section>

        <!-- Summary cards -->
        <section class="summary">

            <div class="card total">
                <p>Total Complaints</p>
                <h2 id="totalCount">5</h2>
            </div>

            <div class="card pending">
                <p>Pending</p>
                <h2 id="pendingCount">2</h2>
            </div>

            <div class="card progress">
                <p>In Progress</p>
                <h2 id="progressCount">1</h2>
            </div>

            <div class="card resolved">
                <p>Resolved</p>
                <h2 id="resolvedCount">2</h2>
            </div>

        </section>

        <!-- Complaint management -->
        <section class="table-section">

            <div class="table-heading">
                <h2>Manage Student Complaints</h2>
                <p>Search complaints, view details, and update their status.</p>
                <!-- NEW: only visible when printing -->
                <p class="print-only">Printed on: <span id="printDate"></span></p>
            </div>

            <!-- Search and filter -->
            <div class="filters">
                <input
                    type="text"
                    id="searchInput"
                    placeholder="Search ID, student, location or problem..."
                    oninput="filterComplaints()"
                >

                <select id="statusFilter" onchange="filterComplaints()">
                    <option value="All">All Statuses</option>
                    <option value="Pending">Pending</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Resolved">Resolved</option>
                </select>

                <!-- NEW: Print button -->
                <button class="print-btn" onclick="printComplaints()">🖨️ Print</button>
            </div>

            <!-- Complaint table -->
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Complaint ID</th>
                            <th>Student Name</th>
                            <th>Location</th>
                            <th>Problem</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Action</th>
                            <!-- NEW columns -->
                            <th>Edit</th>
                            <th>Delete</th>
                        </tr>
                    </thead>

                    
<tbody id="complaintTable">

<?php foreach ($complaints as $row): ?>

    <tr data-status="<?php echo htmlspecialchars($row['status']); ?>">

        <td><?php echo htmlspecialchars($row['complaint_id']); ?></td>

        <td><?php echo htmlspecialchars($row['student_name']); ?></td>

        <td><?php echo htmlspecialchars($row['location']); ?></td>

        <td><?php echo htmlspecialchars($row['problem']); ?></td>

        <td>
            <span class="priority <?php echo htmlspecialchars($row['priority']); ?>">
                <?php echo htmlspecialchars($row['priority']); ?>
            </span>
        </td>

        <td>
            <select class="status-select" onchange="updateStatus(this)">
                <option <?php echo $row['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                <option <?php echo $row['status'] == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                <option <?php echo $row['status'] == 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
            </select>
        </td>

        <td><button class="view-btn" onclick="viewComplaint(this)">View</button></td>

        <td><button class="edit-btn" onclick="editComplaint(this)">Edit</button></td>

        <td><button class="delete-btn" onclick="deleteComplaint(this)">Delete</button></td>

    </tr>

<?php endforeach; ?>

</tbody>

                </table>
            </div>

            <p id="emptyMessage" class="empty-message">
                No complaints found.
            </p>

        </section>
    </main>

    <!-- Complaint details popup -->
    <div class="modal" id="complaintModal">
        <div class="modal-content">
            <h2>Complaint Details</h2>

            <div id="complaintDetails"></div>

            <div class="modal-buttons">
                <button class="close-btn" onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- NEW: Edit complaint popup -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <h2>Edit Complaint</h2>

            <p class="edit-id"><strong>Complaint ID:</strong> <span id="editId"></span></p>

            <form id="editForm" onsubmit="saveEdit(event)">

                <div class="form-group">
                    <label for="editName">Student Name</label>
                    <input type="text" id="editName" required>
                </div>

                <div class="form-group">
                    <label for="editLocation">Location</label>
                    <input type="text" id="editLocation" required>
                </div>

                <div class="form-group">
                    <label for="editProblem">Problem</label>
                    <input type="text" id="editProblem" required>
                </div>

                <div class="form-group">
                    <label for="editPriority">Priority</label>
                    <select id="editPriority">
                        <option value="High">High</option>
                        <option value="Normal">Normal</option>
                        <option value="Low">Low</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="editStatus">Status</label>
                    <select id="editStatus">
                        <option value="Pending">Pending</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Resolved">Resolved</option>
                    </select>
                </div>

                <div class="edit-buttons">
                    <button type="button" class="cancel-btn" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="save-btn">Save Changes</button>
                </div>

            </form>
        </div>
    </div>

    <footer>
        &copy; 2026 <span>TVETMARA Lumut</span> Complaint Management System
    </footer>

    <script>
        // Update the dashboard summary numbers
        function updateSummary() {
            const rows = document.querySelectorAll("#complaintTable tr");

            let pending = 0;
            let progress = 0;
            let resolved = 0;

            rows.forEach(function(row) {
                const status = row.dataset.status;

                if (status === "Pending") {
                    pending++;
                } else if (status === "In Progress") {
                    progress++;
                } else if (status === "Resolved") {
                    resolved++;
                }
            });

            document.getElementById("totalCount").textContent = rows.length;
            document.getElementById("pendingCount").textContent = pending;
            document.getElementById("progressCount").textContent = progress;
            document.getElementById("resolvedCount").textContent = resolved;
        }

        // Update a complaint status
        function updateStatus(select) {
            const row = select.closest("tr");

            row.dataset.status = select.value;

            updateSummary();
            filterComplaints();
        }

        // Search and filter complaints
        function filterComplaints() {
            const searchText = document.getElementById("searchInput")
                .value.toLowerCase();

            const selectedStatus = document.getElementById("statusFilter").value;
            const rows = document.querySelectorAll("#complaintTable tr");

            let visibleCount = 0;

            rows.forEach(function(row) {
                const rowText = row.textContent.toLowerCase();
                const rowStatus = row.dataset.status;

                const matchesSearch = rowText.includes(searchText);
                const matchesStatus =
                    selectedStatus === "All" || rowStatus === selectedStatus;

                if (matchesSearch && matchesStatus) {
                    row.style.display = "";
                    visibleCount++;
                } else {
                    row.style.display = "none";
                }
            });

            document.getElementById("emptyMessage").style.display =
                visibleCount === 0 ? "block" : "none";
        }

        // Show complaint details
        function viewComplaint(button) {
            const row = button.closest("tr");
            const cells = row.querySelectorAll("td");

            const details = `
                <p class="detail"><strong>Complaint ID:</strong> ${cells[0].textContent}</p>
                <p class="detail"><strong>Student Name:</strong> ${cells[1].textContent}</p>
                <p class="detail"><strong>Location:</strong> ${cells[2].textContent}</p>
                <p class="detail"><strong>Problem:</strong> ${cells[3].textContent}</p>
                <p class="detail"><strong>Priority:</strong> ${cells[4].textContent}</p>
                <p class="detail"><strong>Status:</strong> ${row.dataset.status}</p>
                <p class="detail"><strong>Description:</strong> Sample description. Actual details will appear when connected to the database.</p>
            `;

            document.getElementById("complaintDetails").innerHTML = details;
            document.getElementById("complaintModal").style.display = "flex";
        }

        // Close the complaint details popup
        function closeModal() {
            document.getElementById("complaintModal").style.display = "none";
        }

        // Close popup if the user clicks outside it
        document.getElementById("complaintModal").addEventListener("click", function(event) {
            if (event.target === this) {
                closeModal();
            }
        });

        // Show correct totals when the page loads
        updateSummary();

        /* =====================================================
           NEW: Edit, Delete and Print functions (added only)
           ===================================================== */

        // The row currently being edited
        let currentEditRow = null;

        // Open the edit popup and fill it with the row's current data
        function editComplaint(button) {
            currentEditRow = button.closest("tr");
            const cells = currentEditRow.querySelectorAll("td");

            document.getElementById("editId").textContent = cells[0].textContent.trim();
            document.getElementById("editName").value = cells[1].textContent.trim();
            document.getElementById("editLocation").value = cells[2].textContent.trim();
            document.getElementById("editProblem").value = cells[3].textContent.trim();
            document.getElementById("editPriority").value = cells[4].textContent.trim();
            document.getElementById("editStatus").value = currentEditRow.dataset.status;

            document.getElementById("editModal").style.display = "flex";
        }

        // Save the edited data back into the table row
        function saveEdit(event) {
            event.preventDefault();

            if (!currentEditRow) {
                return;
            }

            const cells = currentEditRow.querySelectorAll("td");

            const newName = document.getElementById("editName").value.trim();
            const newLocation = document.getElementById("editLocation").value.trim();
            const newProblem = document.getElementById("editProblem").value.trim();
            const newPriority = document.getElementById("editPriority").value;
            const newStatus = document.getElementById("editStatus").value;

            // Text columns
            cells[1].textContent = newName;
            cells[2].textContent = newLocation;
            cells[3].textContent = newProblem;

            // Priority (text and colour class)
            const prioritySpan = cells[4].querySelector(".priority");
            prioritySpan.textContent = newPriority;
            prioritySpan.className = "priority " + newPriority;

            // Status (dropdown in the table and the row data)
            cells[5].querySelector(".status-select").value = newStatus;
            currentEditRow.dataset.status = newStatus;

            updateSummary();
            filterComplaints();
            closeEditModal();
        }

        // Close the edit popup
        function closeEditModal() {
            document.getElementById("editModal").style.display = "none";
            currentEditRow = null;
        }

        // Close edit popup if the user clicks outside it
        document.getElementById("editModal").addEventListener("click", function(event) {
            if (event.target === this) {
                closeEditModal();
            }
        });

        // Delete a complaint (asks for confirmation first)
        function deleteComplaint(button) {
            const row = button.closest("tr");
            const complaintId = row.querySelectorAll("td")[0].textContent.trim();

            const confirmed = confirm(
                "Are you sure you want to delete complaint " + complaintId + "?"
            );

            if (confirmed) {
                row.remove();
                updateSummary();
                filterComplaints();
            }
        }

        // Print the complaint list
        function printComplaints() {
            window.print();
        }

        // Put the current date and time on the printed page
        // (also works when pressing Ctrl + P)
        window.addEventListener("beforeprint", function() {
            document.getElementById("printDate").textContent =
                new Date().toLocaleString();
        });
    </script>

</body>
</html>
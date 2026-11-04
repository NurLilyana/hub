


<?php
$host = 'localhost';
$dbname = 'complaint db';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database connection failed. Please check your database settings.");
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $student_name = trim($_POST['student_name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $problem = trim($_POST['problem'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? 'Normal';

    // Students cannot set the complaint status.
    $status = 'Pending';

    $photo_path = '';
    $upload_error = '';

    if (!empty($_FILES['photo']['name'])) {
        $upload_dir = 'uploads/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_ext = strtolower(
            pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION)
        );

        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

        if (
            !in_array($file_ext, $allowed_exts, true) ||
            $_FILES['photo']['error'] !== UPLOAD_ERR_OK
        ) {
            $upload_error = 'Please upload a valid JPG, PNG or GIF image.';
        } elseif ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
            $upload_error = 'The photo must be 5 MB or smaller.';
        } else {
            $photo_path = $upload_dir
                . uniqid('complaint_', true)
                . '.'
                . $file_ext;

            if (!move_uploaded_file(
                $_FILES['photo']['tmp_name'],
                $photo_path
            )) {
                $upload_error = 'The photo could not be uploaded.';
                $photo_path = '';
            }
        }
    }

    if ($upload_error !== '') {
        $message = '<div class="message error">'
            . htmlspecialchars($upload_error)
            . '</div>';
    } elseif (
        $student_name === '' ||
        $location === '' ||
        $problem === ''
    ) {
        $message = '<div class="message error">'
            . 'Please fill in all required fields.'
            . '</div>';
    } else {
        $allowed_priorities = ['Low', 'Normal', 'High', 'Urgent'];

        if (!in_array($priority, $allowed_priorities, true)) {
            $priority = 'Normal';
        }

        $stmt = $conn->prepare(
            "INSERT INTO complaints
            (student_name, location, problem, description,
             priority, status, photo)
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        if ($stmt) {
            $stmt->bind_param(
                "sssssss",
                $student_name,
                $location,
                $problem,
                $description,
                $priority,
                $status,
                $photo_path
            );

            if ($stmt->execute()) {
                $message = '<div class="message success">'
                    . '✓ Complaint submitted successfully!'
                    . '</div>';
            } else {
                $message = '<div class="message error">'
                    . 'Unable to submit the complaint. Please try again.'
                    . '</div>';
            }

            $stmt->close();
        } else {
            $message = '<div class="message error">'
                . 'Unable to prepare the complaint form.'
                . '</div>';
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Submit Complaint | TVETMARA Lumut</title>

<style>
:root {
    --black: #05090f;
    --panel: #0b1521;
    --cyan: #00e5ff;
    --soft-cyan: #b8f8ff;
    --text: #ecfbff;
    --muted: #8ca8b8;
}

/* RESET */
* {
    margin: 0 !important;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, Helvetica, sans-serif;
}

html, body {
    min-height: 100%;
    width: 100%;
}

body {
    background: var(--black) !important;
    color: var(--text) !important;
    min-height: 100vh;
    padding: 35px 18px;
    overflow-x: hidden;
    position: relative;
    isolation: isolate;
}

/* ANIMATED GRID */
body::before {
    content: "";
    position: fixed;
    inset: -40% -20%;
    z-index: -2;
    pointer-events: none;
    background-image:
        linear-gradient(rgba(0,229,255,.10) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,229,255,.10) 1px, transparent 1px);
    background-size: 52px 52px;
    transform: perspective(500px) rotateX(55deg);
    animation: gridMove 16s linear infinite;
}

@keyframes gridMove {
    from { background-position: 0 0; }
    to { background-position: 0 208px; }
}

/* CYAN AMBIENT LIGHT */
body::after {
    content: "";
    position: fixed;
    inset: 0;
    z-index: -2;
    pointer-events: none;
    background:
        radial-gradient(ellipse at 10% 15%, rgba(0,229,255,.18), transparent 42%),
        radial-gradient(ellipse at 90% 85%, rgba(0,100,255,.15), transparent 40%);
    animation: glowMove 8s ease-in-out infinite alternate;
}

@keyframes glowMove {
    from { opacity: .55; }
    to { opacity: 1; }
}

/* FLOATING CODE SYMBOLS */
.code-symbols {
    position: fixed;
    inset: 0;
    z-index: -1;
    overflow: hidden;
    pointer-events: none;
}

.code-symbols span {
    position: absolute;
    bottom: -60px;
    color: var(--cyan);
    font-family: Consolas, monospace;
    font-weight: bold;
    font-size: 22px;
    text-shadow: 0 0 12px var(--cyan);
    opacity: 0;
    animation: floatCode 14s linear infinite;
}

.code-symbols span:nth-child(1) { left: 8%; animation-delay: 0s; }
.code-symbols span:nth-child(2) { left: 24%; animation-delay: 3s; }
.code-symbols span:nth-child(3) { left: 42%; animation-delay: 1s; }
.code-symbols span:nth-child(4) { left: 60%; animation-delay: 5s; }
.code-symbols span:nth-child(5) { left: 78%; animation-delay: 2s; }
.code-symbols span:nth-child(6) { left: 92%; animation-delay: 4s; }

@keyframes floatCode {
    0% { transform: translateY(0); opacity: 0; }
    12% { opacity: .8; }
    80% { opacity: .45; }
    100% { transform: translateY(-110vh) rotate(25deg); opacity: 0; }
}

/* PAGE HEADER */
.page-header {
    max-width: 700px;
    margin: 0 auto 25px !important;
    text-align: center;
    position: relative;
    z-index: 1;
}

.tag {
    display: inline-block;
    color: var(--cyan) !important;
    border: 1px solid rgba(0,229,255,.4);
    background: rgba(0,229,255,.07) !important;
    padding: 9px 16px;
    border-radius: 30px;
    font-size: 11px;
    font-weight: bold;
    letter-spacing: 2px;
    margin-bottom: 20px !important;
}

.page-header h1 {
    color: #ffffff !important;
    font-size: clamp(30px, 5vw, 44px);
    line-height: 1.2;
    margin-bottom: 12px !important;
}

.page-header h1 span {
    color: var(--cyan) !important;
}

.page-header p {
    color: var(--muted) !important;
    font-size: 14px;
    line-height: 1.7;
}

/* DARK FORM PANEL */
.form-container {
    display: block;
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 650px;
    margin: 0 auto !important;
    padding: 30px !important;
    background: #0b1521 !important;
    background-color: #0b1521 !important;
    color: var(--text) !important;
    border: 1px solid rgba(0,229,255,.5) !important;
    border-radius: 18px !important;
    box-shadow:
        0 0 18px rgba(0,229,255,.12),
        0 0 45px rgba(0,229,255,.06) !important;
}

/* FORM TITLE */
.form-title {
    color: var(--cyan) !important;
    font-size: 23px;
    margin-bottom: 8px !important;
}

.form-description {
    color: var(--muted) !important;
    font-size: 13px;
    line-height: 1.6;
    margin-bottom: 25px !important;
}

.section-title {
    color: var(--soft-cyan) !important;
    font-size: 11px;
    font-weight: bold;
    letter-spacing: 2px;
    padding-bottom: 14px;
    border-bottom: 1px solid rgba(0,229,255,.2);
    margin-bottom: 18px !important;
}

/* LABELS */
.form-container label {
    display: block;
    color: #e8faff !important;
    font-size: 13px;
    font-weight: bold;
    margin: 17px 0 8px !important;
}

/* INPUTS */
.form-container input,
.form-container select,
.form-container textarea {
    display: block;
    width: 100%;
    padding: 13px 14px !important;
    margin: 0 !important;
    border: 1px solid rgba(0,229,255,.3) !important;
    border-radius: 8px !important;
    background: #050b13 !important;
    background-color: #050b13 !important;
    color: #ecfbff !important;
    font-size: 14px;
    outline: none;
    box-shadow: none !important;
    transition: .25s ease;
}

.form-container input::placeholder,
.form-container textarea::placeholder {
    color: #7893a3 !important;
}

.form-container input:focus,
.form-container select:focus,
.form-container textarea:focus {
    border-color: var(--cyan) !important;
    box-shadow: 0 0 12px rgba(0,229,255,.15) !important;
}

.form-container select option {
    background: #050b13 !important;
    color: #ecfbff !important;
}

.form-container textarea {
    min-height: 110px;
    resize: vertical;
    line-height: 1.6;
}

/* PHOTO INPUT */
.form-container input[type="file"] {
    padding: 10px !important;
    color: var(--muted) !important;
    cursor: pointer;
}

.form-container input[type="file"]::file-selector-button {
    color: var(--cyan) !important;
    background: #0c202c !important;
    border: 1px solid var(--cyan) !important;
    border-radius: 5px;
    padding: 8px 10px;
    margin-right: 10px;
    cursor: pointer;
}

.hint {
    color: var(--muted) !important;
    font-size: 11px;
    margin-top: 8px !important;
    line-height: 1.6;
}

/* BUTTON */
.submit-button {
    display: block;
    width: 100%;
    margin: 25px 0 0 !important;
    padding: 15px !important;
    background: var(--cyan) !important;
    color: #041016 !important;
    border: 1px solid var(--cyan) !important;
    border-radius: 8px !important;
    font-size: 14px;
    font-weight: bold;
    letter-spacing: 1px;
    cursor: pointer;
    transition: .25s ease;
}

.submit-button:hover {
    background: transparent !important;
    color: var(--cyan) !important;
    box-shadow: 0 0 22px rgba(0,229,255,.2) !important;
    transform: translateY(-2px);
}

/* FEEDBACK MESSAGES */
.message {
    padding: 14px;
    border-radius: 8px;
    margin-bottom: 20px !important;
    font-size: 13px;
    line-height: 1.6;
}

.success {
    color: #8bffc1 !important;
    background: #0b241e !important;
    border: 1px solid #1d9b70 !important;
}

.error {
    color: #ffaaaa !important;
    background: #2a1119 !important;
    border: 1px solid #a63c54 !important;
}

/* FOOTER */
.footer {
    text-align: center;
    color: var(--muted) !important;
    font-size: 11px;
    line-height: 1.8;
    margin: 22px auto 0 !important;
    position: relative;
    z-index: 1;
}

.footer span {
    color: var(--cyan) !important;
}

@media (max-width: 600px) {
    body {
        padding: 25px 13px;
    }

    .form-container {
        padding: 22px 17px !important;
    }

    .page-header h1 {
        font-size: 30px;
    }
}

@media (prefers-reduced-motion: reduce) {
    body::before,
    body::after,
    .code-symbols span {
        animation: none;
    }
}
</style>
</head>

<body>

<div class="code-symbols" aria-hidden="true">
    <span>&lt;/&gt;</span>
    <span>01</span>
    <span>{ }</span>
    <span>010</span>
    <span>[ ]</span>
    <span>&lt;code&gt;</span>
</div>

<header class="page-header">
    <div class="tag">TVETMARA LUMUT // IT SYSTEM</div>

    <h1>Submit Your <span>Complaint</span></h1>

    <p>
        Report campus facility problems and help us create
        a safer, better learning environment.
    </p>
</header>

<main class="form-container">

    <h2 class="form-title">Complaint Report</h2>

    <p class="form-description">
        Enter your complaint details below.
        Fields marked as required must be completed.
    </p>

    <div class="section-title">01 // COMPLAINT DETAILS</div>

    <?php echo $message; ?>

    <form method="post" action="" enctype="multipart/form-data">

        <label for="student_name">Student Name</label>
        <input
            type="text"
            id="student_name"
            name="student_name"
            placeholder="Enter your full name"
            required
        >

        <label for="location">Location</label>
        <input
            type="text"
            id="location"
            name="location"
            placeholder="e.g. Dormitory, Lecture Room"
            required
        >

        <label for="problem">Problem / Issue</label>
        <input
            type="text"
            id="problem"
            name="problem"
            placeholder="e.g. Broken fan, Damaged light"
            required
        >

        <label for="description">Description</label>
        <textarea
            id="description"
            name="description"
            rows="4"
            placeholder="Explain the problem in more detail..."
        ></textarea>

        <label for="priority">Priority Level</label>
        <select id="priority" name="priority">
            <option value="Low">Low</option>
            <option value="Normal" selected>Normal</option>
            <option value="High">High</option>
            <option value="Urgent">Urgent</option>
        </select>

        <label for="photo">Upload Photo (Optional)</label>
        <input
            type="file"
            id="photo"
            name="photo"
            accept=".jpg,.jpeg,.png,.gif"
        >

        <p class="hint">
            Accepted formats: JPG, JPEG, PNG and GIF. Maximum size: 5 MB.
        </p>

        <button type="submit" class="submit-button">
            SUBMIT COMPLAINT &rarr;
        </button>

    </form>
</main>

<footer class="footer">
    <p><span>TVETMARA LUMUT</span> | Complaint Management System</p>
    <p>© 2026 Campus IT System</p>
</footer>

</body>
</html>

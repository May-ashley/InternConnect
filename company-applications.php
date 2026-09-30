<?php

session_start();

require_once "db.php";

/* ==================================================
   COMPANY ACCESS PROTECTION
================================================== */

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (
    !isset($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'company'
) {
    header("Location: login.php");
    exit();
}


/* ==================================================
   DATABASE
================================================== */

$conn = connect();

if (!$conn) {
    die("Database connection failed.");
}


/* ==================================================
   HELPER FUNCTIONS
================================================== */

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}


function externalUrl($url)
{
    $url = trim((string)$url);

    if ($url === '') {
        return '';
    }

    if (!preg_match('/^https?:\/\//i', $url)) {
        $url = 'https://' . $url;
    }

    return $url;
}

/* ==================================================
   GET COMPANY INFORMATION
================================================== */

$user_id = (int)$_SESSION['user_id'];

$company_id = 0;
$company_name = 'Company';
$company_logo = '';
$company_email = '';

$stmt = mysqli_prepare($conn, "
    SELECT 
        c.id,
        c.company_name,
        c.logo,
        u.email AS company_email
    FROM companies c
    JOIN users u 
        ON c.user_id = u.id
    WHERE c.user_id = ?
    LIMIT 1
");

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if ($company = mysqli_fetch_assoc($result)) {

    $company_id = (int)$company['id'];

    $company_name = $company['company_name']
        ?: 'Company';

    $company_logo = $company['logo'] ?? '';

    $company_email = $company['company_email'] ?? '';
}

mysqli_stmt_close($stmt);

/* ==================================================
   CSRF TOKEN
================================================== */

if (!isset($_SESSION['company_applications_csrf'])) {
    $_SESSION['company_applications_csrf'] =
        bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['company_applications_csrf'];


/* ==================================================
   STATUS FILTER
================================================== */

$allowed_statuses = [
    'all',
    'pending',
    'accepted',
    'rejected'
];

$status_filter = $_GET['status'] ?? 'all';

if (!in_array($status_filter, $allowed_statuses, true)) {
    $status_filter = 'all';
}


/* ==================================================
   UPDATE APPLICATION STATUS
================================================== */

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $posted_token = $_POST['csrf_token'] ?? '';

    if (
        !hash_equals(
            $_SESSION['company_applications_csrf'],
            $posted_token
        )
    ) {

        $error_message = "Security verification failed. Please try again.";

    } else {

        $action = $_POST['action'] ?? '';

        if ($action === 'update_status') {

            $application_id = (int) ($_POST['application_id'] ?? 0);
            $new_status = $_POST['status'] ?? '';

            $allowed_update_statuses = [
                'pending',
                'accepted',
                'rejected'
            ];

            if (
                $application_id <= 0 ||
                !in_array($new_status, $allowed_update_statuses, true)
            ) {

                $error_message = "Invalid application status.";

            } else {

                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE applications a
                     INNER JOIN internships i
                        ON a.internship_id = i.id
                     SET a.status = ?
                     WHERE a.id = ?
                     AND i.company_id = ?"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "sii",
                    $new_status,
                    $application_id,
                    $company_id
                );

                if (mysqli_stmt_execute($stmt)) {

                    mysqli_stmt_close($stmt);

                    $redirect_params = [
                        'updated' => '1'
                    ];

                    if ($status_filter !== 'all') {
                        $redirect_params['status'] = $status_filter;
                    }

                    header(
                        "Location: company-applications.php?" .
                        http_build_query($redirect_params)
                    );

                    exit();

                } else {

                    $error_message =
                        "Unable to update the application status.";

                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
}


/* ==================================================
   SUCCESS MESSAGE
================================================== */

$success_message = '';

if (isset($_GET['updated']) && $_GET['updated'] === '1') {
    $success_message =
        "Application status updated successfully.";
}


/* ==================================================
   APPLICATION STATISTICS
================================================== */

$total_applications = 0;
$pending_applications = 0;
$accepted_applications = 0;
$rejected_applications = 0;

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        COUNT(*) AS total,
        COALESCE(SUM(a.status = 'pending'), 0) AS pending,
        COALESCE(SUM(a.status = 'accepted'), 0) AS accepted,
        COALESCE(SUM(a.status = 'rejected'), 0) AS rejected
     FROM applications a
     INNER JOIN internships i
        ON a.internship_id = i.id
     WHERE i.company_id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $company_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if ($stats = mysqli_fetch_assoc($result)) {

    $total_applications =
        (int) $stats['total'];

    $pending_applications =
        (int) $stats['pending'];

    $accepted_applications =
        (int) $stats['accepted'];

    $rejected_applications =
        (int) $stats['rejected'];
}

mysqli_stmt_close($stmt);


/* ==================================================
   APPLICATIONS
================================================== */

$applications = [];

$sql = "
    SELECT
        a.id AS application_id,
        a.cover_letter,
        a.resume,
        a.status,
        a.applied_at,

        i.id AS internship_id,
        i.title AS internship_title,
        i.category,
        i.location AS internship_location,
        i.type AS internship_type,

        u.id AS student_id,
        u.name AS student_name,
        u.email AS student_email,

        sp.phone,
        sp.university,
        sp.major,
        sp.year_of_study,
        sp.portfolio_url,
        sp.linkedin_url

    FROM applications a

    INNER JOIN internships i
        ON a.internship_id = i.id

    INNER JOIN users u
        ON a.student_id = u.id

    LEFT JOIN students_profile sp
        ON sp.user_id = u.id

    WHERE i.company_id = ?
";

if ($status_filter !== 'all') {
    $sql .= " AND a.status = ?";
}

$sql .= " ORDER BY a.applied_at DESC";


$stmt = mysqli_prepare($conn, $sql);

if ($status_filter === 'all') {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $company_id
    );

} else {

    mysqli_stmt_bind_param(
        $stmt,
        "is",
        $company_id,
        $status_filter
    );
}

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $applications[] = $row;
}

mysqli_stmt_close($stmt);


/* ==================================================
   COMPANY INITIAL
================================================== */

$company_initial = strtoupper(
    substr(
        trim($company_name),
        0,
        1
    )
);

if ($company_initial === '') {
    $company_initial = 'C';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Applications | InternConnect
    </title>

    <!-- Google Font -->
    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <!-- Existing company dashboard theme -->
    <link
        rel="stylesheet"
        href="company-dashboard.css"
    >

    <!-- Applications page styles -->
    <link
        rel="stylesheet"
        href="company-applications.css"
    >

</head>


<body>

<div class="dashboard-container">


    <!-- ==================================================
         SIDEBAR
    ================================================== -->

    <aside class="sidebar">


        <div class="sidebar-logo">

            <h2>
                Intern<span>Connect</span>
            </h2>

            <p>
                Company Portal
            </p>

        </div>


        <nav class="sidebar-nav">

            <a href="company-dashboard.php">

                <i class="fa-solid fa-chart-line"></i>

                <span>
                    Dashboard
                </span>

            </a>


            <a href="company-internships.php">

                <i class="fa-solid fa-briefcase"></i>

                <span>
                    My Internships
                </span>

            </a>


            <a
                href="company-applications.php"
                class="active"
            >

                <i class="fa-solid fa-file-lines"></i>

                <span>
                    Applications
                </span>

            </a>


            <a href="company-accepted.php">

                <i class="fa-solid fa-user-check"></i>

                <span>
                    Accepted Interns
                </span>

            </a>


            <a href="company-profile.php">

                <i class="fa-solid fa-building"></i>

                <span>
                    Company Profile
                </span>

            </a>

        </nav>


        <div class="sidebar-bottom">

            <a
                href="index.php"
                class="back-to-site"
            >

                <i class="fa-solid fa-arrow-left"></i>

                <span>
                    Back to Site
                </span>

            </a>


            <a
                href="logout.php"
                class="logout"
            >

                <i class="fa-solid fa-right-from-bracket"></i>

                <span>
                    Logout
                </span>

            </a>

        </div>

    </aside>


    <!-- ==================================================
         MAIN
    ================================================== -->

    <main class="dashboard-main applications-main">


        <!-- ==================================================
             HEADER
        ================================================== -->

        <header class="dashboard-header applications-header">


            <div>

                <div class="dashboard-label">
                    RECRUITMENT
                </div>

                <h1>
                    Student Applications
                </h1>

                <p class="dashboard-subtitle">
                    Review applications from students who are
                    interested in joining your company.
                </p>

            </div>

            <div class="company-info">
                <div class="company-avatar">
                    <?php if (!empty($company_logo)): ?>
                        <img src="<?= e($company_logo) ?>" alt="<?= e($company_name) ?>">
                    <?php else: ?>
                        <?= e(strtoupper(substr($company_name, 0, 1))) ?>
                    <?php endif; ?>
                </div>

                <div class="company-info-text">
                    <strong><?= e($company_name) ?></strong>
                    <span><?= e($company_email) ?></span>
                </div>
            </div>


        </header>


        <!-- ==================================================
             ALERTS
        ================================================== -->

        <?php if ($success_message): ?>

            <div class="page-alert success-alert">

                <i class="fa-solid fa-circle-check"></i>

                <span>
                    <?= e($success_message) ?>
                </span>

            </div>

        <?php endif; ?>


        <?php if ($error_message): ?>

            <div class="page-alert error-alert">

                <i class="fa-solid fa-circle-exclamation"></i>

                <span>
                    <?= e($error_message) ?>
                </span>

            </div>

        <?php endif; ?>


        <!-- ==================================================
             STATISTICS
        ================================================== -->

        <section class="application-stats">


            <div class="application-stat-card">

                <div class="application-stat-icon">

                    <i class="fa-solid fa-file-lines"></i>

                </div>

                <div>

                    <p>
                        Total Applications
                    </p>

                    <h2>
                        <?= $total_applications ?>
                    </h2>

                </div>

            </div>


            <div class="application-stat-card">

                <div class="application-stat-icon pending-icon">

                    <i class="fa-solid fa-clock"></i>

                </div>

                <div>

                    <p>
                        Pending
                    </p>

                    <h2>
                        <?= $pending_applications ?>
                    </h2>

                </div>

            </div>


            <div class="application-stat-card">

                <div class="application-stat-icon accepted-icon">

                    <i class="fa-solid fa-circle-check"></i>

                </div>

                <div>

                    <p>
                        Accepted
                    </p>

                    <h2>
                        <?= $accepted_applications ?>
                    </h2>

                </div>

            </div>


            <div class="application-stat-card">

                <div class="application-stat-icon rejected-icon">

                    <i class="fa-solid fa-circle-xmark"></i>

                </div>

                <div>

                    <p>
                        Rejected
                    </p>

                    <h2>
                        <?= $rejected_applications ?>
                    </h2>

                </div>

            </div>


        </section>


        <!-- ==================================================
             FILTER
        ================================================== -->

        <section class="applications-toolbar">


            <div class="toolbar-title">

                <span>
                    APPLICATIONS
                </span>

                <h2>
                    Review Students
                </h2>

            </div>


            <div class="application-filters">


                <a
                    href="company-applications.php?status=all"
                    class="<?= $status_filter === 'all' ? 'active' : '' ?>"
                >

                    <i class="fa-solid fa-layer-group"></i>

                    All

                    <span>
                        <?= $total_applications ?>
                    </span>

                </a>


                <a
                    href="company-applications.php?status=pending"
                    class="<?= $status_filter === 'pending' ? 'active' : '' ?>"
                >

                    <i class="fa-solid fa-clock"></i>

                    Pending

                    <span>
                        <?= $pending_applications ?>
                    </span>

                </a>


                <a
                    href="company-applications.php?status=accepted"
                    class="<?= $status_filter === 'accepted' ? 'active' : '' ?>"
                >

                    <i class="fa-solid fa-check"></i>

                    Accepted

                    <span>
                        <?= $accepted_applications ?>
                    </span>

                </a>


                <a
                    href="company-applications.php?status=rejected"
                    class="<?= $status_filter === 'rejected' ? 'active' : '' ?>"
                >

                    <i class="fa-solid fa-xmark"></i>

                    Rejected

                    <span>
                        <?= $rejected_applications ?>
                    </span>

                </a>


            </div>


        </section>


        <!-- ==================================================
             APPLICATION LIST
        ================================================== -->

        <section class="applications-container">


            <?php if (empty($applications)): ?>


                <div class="applications-empty">

                    <div class="empty-icon">

                        <i class="fa-solid fa-file-circle-xmark"></i>

                    </div>

                    <h3>
                        No Applications Found
                    </h3>

                    <p>

                        <?php if ($status_filter === 'all'): ?>

                            You haven't received any student
                            applications yet.

                        <?php else: ?>

                            There are no
                            <?= e($status_filter) ?>
                            applications at the moment.

                        <?php endif; ?>

                    </p>

                    <a
                        href="company-internships.php"
                        class="primary-button"
                    >

                        <i class="fa-solid fa-briefcase"></i>

                        View My Internships

                    </a>

                </div>


            <?php else: ?>


                <div class="application-results-header">

                    <div>

                        <span class="results-label">
                            APPLICATION RESULTS
                        </span>

                        <h2>
                            <?= count($applications) ?>
                            <?= count($applications) === 1 ? 'Application' : 'Applications' ?>
                        </h2>

                    </div>

                </div>


                <div class="application-list">


                    <?php foreach ($applications as $application): ?>

                        <?php

                        $student_name =
                            trim($application['student_name'] ?? '');

                        if ($student_name === '') {
                            $student_name = 'Student';
                        }

                        $student_initial =
                            strtoupper(
                                substr(
                                    $student_name,
                                    0,
                                    1
                                )
                            );

                        if ($student_initial === '') {
                            $student_initial = 'S';
                        }

                        $current_status =
                            $application['status'] ?? 'pending';

                        $portfolio =
                            externalUrl(
                                $application['portfolio_url'] ?? ''
                            );

                        $linkedin =
                            externalUrl(
                                $application['linkedin_url'] ?? ''
                            );

                        $applied_date =
                            !empty($application['applied_at'])
                                ? date(
                                    'M d, Y',
                                    strtotime(
                                        $application['applied_at']
                                    )
                                )
                                : 'Unknown date';

                        ?>


                        <article class="application-card">


                            <!-- APPLICATION TOP -->

                            <div class="application-card-top">


                                <div class="student-profile">


                                    <div class="student-avatar">

                                        <?= e($student_initial) ?>

                                    </div>


                                    <div class="student-heading">

                                        <h3>
                                            <?= e($student_name) ?>
                                        </h3>

                                        <p>
                                            <?= e(
                                                $application['student_email']
                                                ?? 'No email provided'
                                            ) ?>
                                        </p>

                                    </div>


                                </div>


                                <div class="application-status-area">

                                    <span
                                        class="status-badge status-<?= e($current_status) ?>"
                                    >

                                        <?php if ($current_status === 'pending'): ?>

                                            <i class="fa-solid fa-clock"></i>
                                            Pending

                                        <?php elseif ($current_status === 'accepted'): ?>

                                            <i class="fa-solid fa-check"></i>
                                            Accepted

                                        <?php else: ?>

                                            <i class="fa-solid fa-xmark"></i>
                                            Rejected

                                        <?php endif; ?>

                                    </span>

                                </div>


                            </div>


                            <!-- INTERNSHIP -->

                            <div class="application-internship">


                                <div class="internship-mini-icon">

                                    <i class="fa-solid fa-briefcase"></i>

                                </div>


                                <div>

                                    <span>
                                        APPLIED FOR
                                    </span>

                                    <h4>
                                        <?= e(
                                            $application['internship_title']
                                            ?? 'Internship'
                                        ) ?>
                                    </h4>

                                    <div class="internship-details">

                                        <?php if (!empty($application['category'])): ?>

                                            <span>

                                                <i class="fa-solid fa-tag"></i>

                                                <?= e(
                                                    $application['category']
                                                ) ?>

                                            </span>

                                        <?php endif; ?>


                                        <?php if (!empty($application['internship_location'])): ?>

                                            <span>

                                                <i class="fa-solid fa-location-dot"></i>

                                                <?= e(
                                                    $application['internship_location']
                                                ) ?>

                                            </span>

                                        <?php endif; ?>


                                        <?php if (!empty($application['internship_type'])): ?>

                                            <span>

                                                <i class="fa-solid fa-clock"></i>

                                                <?= e(
                                                    $application['internship_type']
                                                ) ?>

                                            </span>

                                        <?php endif; ?>

                                    </div>

                                </div>


                            </div>


                            <!-- STUDENT INFORMATION -->

                            <div class="student-information">


                                <div class="section-heading">

                                    <i class="fa-solid fa-user-graduate"></i>

                                    <span>
                                        Student Information
                                    </span>

                                </div>


                                <div class="student-info-grid">


                                    <div class="student-info-item">

                                        <div class="info-icon">

                                            <i class="fa-solid fa-building-columns"></i>

                                        </div>

                                        <div>

                                            <span>
                                                University
                                            </span>

                                            <strong>

                                                <?= e(
                                                    $application['university']
                                                    ?: 'Not provided'
                                                ) ?>

                                            </strong>

                                        </div>

                                    </div>


                                    <div class="student-info-item">

                                        <div class="info-icon">

                                            <i class="fa-solid fa-book"></i>

                                        </div>

                                        <div>

                                            <span>
                                                Major
                                            </span>

                                            <strong>

                                                <?= e(
                                                    $application['major']
                                                    ?: 'Not provided'
                                                ) ?>

                                            </strong>

                                        </div>

                                    </div>


                                    <div class="student-info-item">

                                        <div class="info-icon">

                                            <i class="fa-solid fa-graduation-cap"></i>

                                        </div>

                                        <div>

                                            <span>
                                                Year of Study
                                            </span>

                                            <strong>

                                                <?= e(
                                                    $application['year_of_study']
                                                    ?: 'Not provided'
                                                ) ?>

                                            </strong>

                                        </div>

                                    </div>


                                    <div class="student-info-item">

                                        <div class="info-icon">

                                            <i class="fa-solid fa-phone"></i>

                                        </div>

                                        <div>

                                            <span>
                                                Phone
                                            </span>

                                            <strong>

                                                <?= e(
                                                    $application['phone']
                                                    ?: 'Not provided'
                                                ) ?>

                                            </strong>

                                        </div>

                                    </div>


                                </div>


                            </div>


                            <!-- COVER LETTER -->

                            <?php if (!empty(trim($application['cover_letter'] ?? ''))): ?>

                                <div class="cover-letter-section">


                                    <div class="section-heading">

                                        <i class="fa-solid fa-quote-left"></i>

                                        <span>
                                            Cover Letter
                                        </span>

                                    </div>


                                    <div class="cover-letter">

                                        <?= nl2br(
                                            e(
                                                $application['cover_letter']
                                            )
                                        ) ?>

                                    </div>


                                </div>

                            <?php endif; ?>


                            <!-- APPLICATION FOOTER -->

                            <div class="application-footer">


                                <div class="application-date">

                                    <i class="fa-regular fa-calendar"></i>

                                    <span>
                                        Applied <?= e($applied_date) ?>
                                    </span>

                                </div>


                                <div class="application-actions">


                                    <?php if (!empty($application['resume'])): ?>

                                        <a
                                            href="<?= e($application['resume']) ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="application-action resume-action"
                                        >

                                            <i class="fa-solid fa-file-pdf"></i>

                                            Resume

                                        </a>

                                    <?php endif; ?>


                                    <?php if ($portfolio): ?>

                                        <a
                                            href="<?= e($portfolio) ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="application-action"
                                        >

                                            <i class="fa-solid fa-globe"></i>

                                            Portfolio

                                        </a>

                                    <?php endif; ?>


                                    <?php if ($linkedin): ?>

                                        <a
                                            href="<?= e($linkedin) ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="application-action"
                                        >

                                            <i class="fa-brands fa-linkedin-in"></i>

                                            LinkedIn

                                        </a>

                                    <?php endif; ?>


                                </div>


                            </div>


                            <!-- STATUS UPDATE -->

                                <div class="status-update-area">

                                    <div class="status-update-label">

                                        <i class="fa-solid fa-sliders"></i>

                                        Update Application Status

                                    </div>


                                    <div class="status-buttons">

                                        <!-- PENDING -->

                                        <form method="POST">

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($csrf_token) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="update_status"
                                            >

                                            <input
                                                type="hidden"
                                                name="application_id"
                                                value="<?= (int) $application['application_id'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="status"
                                                value="pending"
                                            >

                                            <button
                                                type="submit"
                                                class="status-button pending-button <?= $current_status === 'pending' ? 'selected' : '' ?>"
                                            >
                                                <i class="fa-solid fa-clock"></i>
                                                Pending
                                            </button>

                                        </form>


                                        <!-- ACCEPT -->

                                        <form method="POST">

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($csrf_token) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="update_status"
                                            >

                                            <input
                                                type="hidden"
                                                name="application_id"
                                                value="<?= (int) $application['application_id'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="status"
                                                value="accepted"
                                            >

                                            <button
                                                type="submit"
                                                class="status-button accept-button <?= $current_status === 'accepted' ? 'selected' : '' ?>"
                                            >
                                                <i class="fa-solid fa-check"></i>
                                                Accept
                                            </button>

                                        </form>


                                        <!-- REJECT -->

                                        <form method="POST">

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($csrf_token) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="update_status"
                                            >

                                            <input
                                                type="hidden"
                                                name="application_id"
                                                value="<?= (int) $application['application_id'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="status"
                                                value="rejected"
                                            >

                                            <button
                                                type="submit"
                                                class="status-button reject-button <?= $current_status === 'rejected' ? 'selected' : '' ?>"
                                            >
                                                <i class="fa-solid fa-xmark"></i>
                                                Reject
                                            </button>

                                        </form>

                                    </div>

                                </div>


                        </article>


                    <?php endforeach; ?>


                </div>


            <?php endif; ?>


        </section>


    </main>

</div>


</body>

</html>
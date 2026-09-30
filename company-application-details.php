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
   HELPER
================================================== */

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


function externalUrl($url)
{
    $url = trim((string) $url);

    if ($url === '') {
        return '';
    }

    if (!preg_match('/^https?:\/\//i', $url)) {
        $url = 'https://' . $url;
    }

    return $url;
}


/* ==================================================
   GET INTERNSHIP ID
================================================== */

$internship_id = isset($_GET['internship_id'])
    ? (int) $_GET['internship_id']
    : 0;


if ($internship_id <= 0) {
    header("Location: company-internships.php");
    exit();
}


/* ==================================================
   GET COMPANY
================================================== */

$user_id = (int) $_SESSION['user_id'];

$company_id = 0;
$company_name = 'Company';
$company_logo = '';
$company_industry = '';
$company_location = '';

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id,
        company_name,
        logo,
        industry,
        location
     FROM companies
     WHERE user_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if ($company = mysqli_fetch_assoc($result)) {

    $company_id = (int) $company['id'];

    $company_name =
        $company['company_name'] ?: 'Company';

    $company_logo =
        $company['logo'] ?? '';

    $company_industry =
        $company['industry'] ?? '';

    $company_location =
        $company['location'] ?? '';
}

mysqli_stmt_close($stmt);


if ($company_id <= 0) {
    die("Company profile not found.");
}


/* ==================================================
   GET INTERNSHIP
   IMPORTANT:
   Only allow internships belonging to this company.
================================================== */

$internship = null;

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id,
        title,
        category,
        location,
        type,
        description,
        skills,
        status
     FROM internships
     WHERE id = ?
     AND company_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $internship_id,
    $company_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$internship = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$internship) {
    header("Location: company-internships.php");
    exit();
}


/* ==================================================
   CSRF TOKEN
================================================== */

if (!isset($_SESSION['company_application_details_csrf'])) {
    $_SESSION['company_application_details_csrf'] =
        bin2hex(random_bytes(32));
}

$csrf_token =
    $_SESSION['company_application_details_csrf'];


/* ==================================================
   UPDATE APPLICATION STATUS
================================================== */

$error_message = '';

$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $posted_token =
        $_POST['csrf_token'] ?? '';

    if (
        !hash_equals(
            $_SESSION['company_application_details_csrf'],
            $posted_token
        )
    ) {

        $error_message =
            "Security verification failed. Please try again.";

    } else {

        $action =
            $_POST['action'] ?? '';

        if ($action === 'update_status') {

            $application_id =
                (int) ($_POST['application_id'] ?? 0);

            $new_status =
                $_POST['status'] ?? '';

            $allowed_statuses = [
                'pending',
                'accepted',
                'rejected'
            ];


            if (
                $application_id <= 0 ||
                !in_array(
                    $new_status,
                    $allowed_statuses,
                    true
                )
            ) {

                $error_message =
                    "Invalid application status.";

            } else {

                /*
                 * Make sure the application belongs to:
                 *
                 * 1. This internship
                 * 2. This company
                 */

                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE applications a
                     INNER JOIN internships i
                        ON a.internship_id = i.id
                     SET a.status = ?
                     WHERE a.id = ?
                     AND a.internship_id = ?
                     AND i.company_id = ?"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "siii",
                    $new_status,
                    $application_id,
                    $internship_id,
                    $company_id
                );


                if (mysqli_stmt_execute($stmt)) {

                    mysqli_stmt_close($stmt);

                    header(
                        "Location: company-application-details.php?" .
                        http_build_query([
                            'internship_id' => $internship_id,
                            'updated' => '1'
                        ])
                    );

                    exit();

                } else {

                    $error_message =
                        "Unable to update application status.";

                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
}


/* ==================================================
   SUCCESS MESSAGE
================================================== */

if (
    isset($_GET['updated']) &&
    $_GET['updated'] === '1'
) {

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
        COALESCE(
            SUM(status = 'pending'),
            0
        ) AS pending,
        COALESCE(
            SUM(status = 'accepted'),
            0
        ) AS accepted,
        COALESCE(
            SUM(status = 'rejected'),
            0
        ) AS rejected
     FROM applications
     WHERE internship_id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $internship_id
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
   GET APPLICATIONS
================================================== */

$applications = [];

$sql = "
    SELECT

        a.id AS application_id,
        a.cover_letter,
        a.resume,
        a.status,
        a.applied_at,

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

    INNER JOIN users u
        ON a.student_id = u.id

    LEFT JOIN students_profile sp
        ON sp.user_id = u.id

    WHERE a.internship_id = ?

    ORDER BY a.applied_at DESC
";


$stmt = mysqli_prepare(
    $conn,
    $sql
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $internship_id
);

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
        Applicants - <?= e($internship['title']) ?> | InternConnect
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


    <!-- Shared company dashboard theme -->

    <link
        rel="stylesheet"
        href="company-dashboard.css"
    >


    <!-- This page -->

    <link
        rel="stylesheet"
        href="company-application-details.css"
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


            <a
                href="company-internships.php"
                class="active"
            >

                <i class="fa-solid fa-briefcase"></i>

                <span>
                    My Internships
                </span>

            </a>


            <a href="company-applications.php">

                 <i class="fa-regular fa-file-lines"></i>

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

                 <i class="fa-regular fa-building"></i>

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

    <main class="dashboard-main application-details-main">


        <!-- ==================================================
             BACK
        ================================================== -->

        <a
            href="company-internships.php"
            class="back-link"
        >

            <i class="fa-solid fa-arrow-left"></i>

            Back to My Internships

        </a>


        <!-- ==================================================
             HEADER
        ================================================== -->

        <header class="dashboard-header details-header">


            <div class="header-content">


                <div class="dashboard-label">
                    INTERNSHIP APPLICANTS
                </div>


                <h1>
                    <?= e($internship['title']) ?>
                </h1>


                <p class="dashboard-subtitle">
                    Review and manage students who applied
                    for this internship.
                </p>


                <!-- Internship details -->

                <div class="internship-header-meta">


                    <?php if (!empty($internship['category'])): ?>

                        <span>

                            <i class="fa-solid fa-tag"></i>

                            <?= e($internship['category']) ?>

                        </span>

                    <?php endif; ?>


                    <?php if (!empty($internship['location'])): ?>

                        <span>

                            <i class="fa-solid fa-location-dot"></i>

                            <?= e($internship['location']) ?>

                        </span>

                    <?php endif; ?>


                    <?php if (!empty($internship['type'])): ?>

                        <span>

                            <i class="fa-solid fa-clock"></i>

                            <?= e($internship['type']) ?>

                        </span>

                    <?php endif; ?>


                    <span>

                        <i class="fa-solid fa-users"></i>

                        <?= $total_applications ?>

                        <?= $total_applications === 1
                            ? 'Applicant'
                            : 'Applicants'
                        ?>

                    </span>


                </div>


            </div>



        </header>


        <!-- ==================================================
             ALERTS
        ================================================== -->

        <?php if ($success_message): ?>

            <div class="page-alert success-alert">

                <i class="fa-solid fa-circle-check"></i>

                <?= e($success_message) ?>

            </div>

        <?php endif; ?>


        <?php if ($error_message): ?>

            <div class="page-alert error-alert">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?= e($error_message) ?>

            </div>

        <?php endif; ?>


        <!-- ==================================================
             INTERNSHIP SUMMARY
        ================================================== -->

        <section class="internship-summary">


            <div class="summary-content">


                <div class="summary-icon">

                    <i class="fa-solid fa-briefcase"></i>

                </div>


                <div class="summary-text">

                    <span>
                        APPLICATIONS FOR
                    </span>

                    <h2>
                        <?= e($internship['title']) ?>
                    </h2>


                    <?php if (!empty($internship['description'])): ?>

                        <p>
                            <?= e(
                                $internship['description']
                            ) ?>
                        </p>

                    <?php endif; ?>


                </div>


            </div>


            <div class="summary-status">

                <?php
                $internship_status =
                    strtolower(
                        $internship['status'] ?? ''
                    );
                ?>


                <span
                    class="status-badge status-<?= e($internship_status) ?>"
                >

                    <?php if ($internship_status === 'active'): ?>

                        <i class="fa-solid fa-circle-check"></i>
                        Active

                    <?php elseif ($internship_status === 'pending'): ?>

                        <i class="fa-solid fa-clock"></i>
                        Pending

                    <?php elseif ($internship_status === 'closed'): ?>

                        <i class="fa-solid fa-lock"></i>
                        Closed

                    <?php else: ?>

                        <?= e(
                            ucfirst(
                                $internship_status ?: 'Unknown'
                            )
                        ) ?>

                    <?php endif; ?>

                </span>

            </div>


        </section>


        <!-- ==================================================
             STATISTICS
        ================================================== -->

        <section class="application-stats">


            <div class="application-stat-card">

                <div class="application-stat-icon">

                    <i class="fa-solid fa-users"></i>

                </div>

                <div>

                    <p>
                        Total Applicants
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
             APPLICANTS
        ================================================== -->

        <section class="applicants-section">


            <div class="section-top">


                <div>

                    <span class="section-label">
                        STUDENT APPLICATIONS
                    </span>

                    <h2>
                        Applicants
                    </h2>

                </div>


                <a
                    href="company-applications.php"
                    class="secondary-button top-button"
                >

                    <i class="fa-solid fa-file-lines"></i>

                    View All Applications

                </a>


            </div>


            <?php if (empty($applications)): ?>


                <div class="applicants-empty">


                    <div class="empty-icon">

                        <i class="fa-solid fa-user-group"></i>

                    </div>


                    <h3>
                        No Applications Yet
                    </h3>


                    <p>
                        No students have applied for this
                        internship yet. Applications will
                        appear here when students apply.
                    </p>


                    <a
                        href="company-internships.php"
                        class="primary-button"
                    >

                        <i class="fa-solid fa-arrow-left"></i>

                        Back to My Internships

                    </a>


                </div>


            <?php else: ?>


                <div class="applicants-list">


                    <?php foreach ($applications as $application): ?>


                        <?php

                        $student_name =
                            trim(
                                $application['student_name']
                                ?? ''
                            );

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
                            $application['status']
                            ?? 'pending';


                        $portfolio =
                            externalUrl(
                                $application['portfolio_url']
                                ?? ''
                            );


                        $linkedin =
                            externalUrl(
                                $application['linkedin_url']
                                ?? ''
                            );


                        $applied_date =
                            !empty(
                                $application['applied_at']
                            )
                                ? date(
                                    'M d, Y',
                                    strtotime(
                                        $application['applied_at']
                                    )
                                )
                                : 'Unknown date';

                        ?>


                        <article class="applicant-card">


                            <!-- ==================================
                                 TOP
                            ================================== -->

                            <div class="applicant-top">


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


                            <!-- ==================================
                                 STUDENT INFORMATION
                            ================================== -->

                            <div class="student-information">


                                <div class="section-heading">

                                    <i class="fa-solid fa-user-graduate"></i>

                                    Student Information

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


                            <!-- ==================================
                                 COVER LETTER
                            ================================== -->

                            <?php if (
                                !empty(
                                    trim(
                                        $application['cover_letter']
                                        ?? ''
                                    )
                                )
                            ): ?>


                                <div class="cover-letter-section">


                                    <div class="section-heading">

                                        <i class="fa-solid fa-quote-left"></i>

                                        Cover Letter

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


                            <!-- ==================================
                                 FOOTER
                            ================================== -->

                            <div class="applicant-footer">


                                <div class="application-date">

                                    <i class="fa-regular fa-calendar"></i>

                                    Applied
                                    <?= e($applied_date) ?>

                                </div>


                                <div class="application-actions">


                                    <?php if (
                                        !empty(
                                            $application['resume']
                                        )
                                    ): ?>

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


                            <!-- ==================================
                                 STATUS BUTTONS
                            ================================== -->

                            <div class="status-update-area">


                                <div class="status-update-label">

                                    <i class="fa-solid fa-sliders"></i>

                                    Update Application Status

                                </div>


                                <div class="status-buttons">


                                    <!-- Pending -->

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


                                    <!-- Accept -->

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


                                    <!-- Reject -->

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
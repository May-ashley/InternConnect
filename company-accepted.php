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
   DATABASE CONNECTION
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
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
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
   COMPANY NOT FOUND
================================================== */

if ($company_id <= 0) {

    mysqli_close($conn);

    die("Company profile not found.");
}


/* ==================================================
   GET ACCEPTED APPLICATION STATISTICS
================================================== */

$accepted_count = 0;
$accepted_internships = 0;

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        COUNT(*) AS accepted_count,
        COUNT(DISTINCT applications.internship_id) AS accepted_internships
     FROM applications
     INNER JOIN internships
        ON applications.internship_id = internships.id
     WHERE internships.company_id = ?
       AND applications.status = 'accepted'"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $company_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if ($stats = mysqli_fetch_assoc($result)) {

    $accepted_count =
        (int)$stats['accepted_count'];

    $accepted_internships =
        (int)$stats['accepted_internships'];
}

mysqli_stmt_close($stmt);


/* ==================================================
   GET ACCEPTED APPLICATIONS
================================================== */

$sql = "
    SELECT

        /* Application */
        applications.id AS application_id,
        applications.cover_letter,
        applications.resume,
        applications.status,
        applications.applied_at,

        /* Internship */
        internships.id AS internship_id,
        internships.title AS internship_title,
        internships.category,
        internships.location,
        internships.type,

        /* Student user */
        users.id AS student_id,
        users.name AS student_name,
        users.email AS student_email,

        /* Student profile */
        students_profile.phone,
        students_profile.university,
        students_profile.major,
        students_profile.year_of_study,
        students_profile.portfolio_url,
        students_profile.linkedin_url

    FROM applications

    INNER JOIN internships
        ON applications.internship_id = internships.id

    INNER JOIN users
        ON applications.student_id = users.id

    LEFT JOIN students_profile
        ON students_profile.user_id = users.id

    WHERE internships.company_id = ?
      AND applications.status = 'accepted'

    ORDER BY applications.applied_at DESC
";

$stmt = mysqli_prepare(
    $conn,
    $sql
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $company_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$accepted_applications = [];

while ($row = mysqli_fetch_assoc($result)) {

    $accepted_applications[] = $row;
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


/* ==================================================
   CLOSE DATABASE
================================================== */

mysqli_close($conn);

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
        Accepted Interns | InternConnect
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


    <!-- Page CSS -->

    <link
        rel="stylesheet"
        href="company-accepted.css"
    >

</head>


<body>


<div class="dashboard-container">


    <!-- ==================================================
         SIDEBAR
    ================================================== -->

    <aside class="sidebar">


        <!-- LOGO -->

        <div class="sidebar-logo">

            <h2>
                Intern<span>Connect</span>
            </h2>

            <p>
                Company Portal
            </p>

        </div>


        <!-- NAVIGATION -->

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


            <a href="company-applications.php">

                <i class="fa-solid fa-file-lines"></i>

                <span>
                    Applications
                </span>

            </a>


            <a
                href="company-accepted.php"
                class="active"
                aria-current="page"
            >

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


        <!-- SIDEBAR BOTTOM -->

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
         MAIN CONTENT
    ================================================== -->

    <main class="dashboard-main">


        <!-- ==================================================
             HEADER
        ================================================== -->

        <header class="dashboard-header">


            <div class="header-content">


                <div class="dashboard-label">

                    <i class="fa-solid fa-circle-check"></i>

                    RECRUITMENT

                </div>


                <h1>
                    Accepted
                    <span>Interns</span>
                </h1>


                <p class="dashboard-subtitle">

                    View students who have been accepted
                    for your internship opportunities.

                </p>


            </div>


            <!-- COMPANY INFO -->
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
             STATISTICS
        ================================================== -->

        <section class="stats-grid">


            <!-- TOTAL ACCEPTED -->

            <div class="stat-card">


                <div class="stat-icon accepted-icon">

                    <i class="fa-solid fa-user-check"></i>

                </div>


                <div>

                    <p>
                        Accepted Interns
                    </p>

                    <h2>
                        <?= $accepted_count ?>
                    </h2>

                </div>


            </div>


            <!-- INTERNSHIPS -->

            <div class="stat-card">


                <div class="stat-icon internship-icon">

                    <i class="fa-solid fa-briefcase"></i>

                </div>


                <div>

                    <p>
                        Internships
                    </p>

                    <h2>
                        <?= $accepted_internships ?>
                    </h2>

                </div>


            </div>


            <!-- STATUS -->

            <div class="stat-card">


                <div class="stat-icon status-icon">

                    <i class="fa-solid fa-circle-check"></i>

                </div>


                <div>

                    <p>
                        Status
                    </p>

                    <h2 class="small-stat-text">
                        Active
                    </h2>

                </div>


            </div>


            <!-- CONNECTION -->

            <div class="stat-card">


                <div class="stat-icon connection-icon">

                    <i class="fa-solid fa-users"></i>

                </div>


                <div>

                    <p>
                        Your Team
                    </p>

                    <h2 class="small-stat-text">
                        <?= $accepted_count ?>
                    </h2>

                </div>


            </div>


        </section>


        <!-- ==================================================
             PAGE CARD
        ================================================== -->

        <section class="accepted-section">


            <div class="section-header">


                <div>

                    <div class="section-label">
                        ACCEPTED APPLICATIONS
                    </div>

                    <h2>
                        Your Accepted Interns
                    </h2>

                    <p>
                        Students who successfully received
                        an internship offer from your company.
                    </p>

                </div>


                <div class="accepted-count">

                    <i class="fa-solid fa-user-check"></i>

                    <?= $accepted_count ?>
                    Accepted

                </div>


            </div>


            <!-- ==================================================
                 ACCEPTED LIST
            ================================================== -->

            <?php if (count($accepted_applications) > 0): ?>


                <div class="accepted-list">


                    <?php foreach ($accepted_applications as $application): ?>


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


                        $portfolio_url =
                            externalUrl(
                                $application['portfolio_url']
                                ?? ''
                            );


                        $linkedin_url =
                            externalUrl(
                                $application['linkedin_url']
                                ?? ''
                            );

                        ?>


                        <!-- ==================================================
                             ACCEPTED CARD
                        ================================================== -->

                        <article class="accepted-card">


                            <!-- TOP -->

                            <div class="accepted-top">


                                <div class="student-section">


                                    <div class="student-avatar">

                                        <?= e($student_initial) ?>

                                    </div>


                                    <div class="student-heading">


                                        <h3>
                                            <?= e($student_name) ?>
                                        </h3>


                                        <p>
                                            <i class="fa-solid fa-envelope"></i>

                                            <?= e(
                                                $application['student_email']
                                                ?? 'No email'
                                            ) ?>
                                        </p>


                                    </div>


                                </div>


                                <div class="accepted-badge">

                                    <i class="fa-solid fa-check"></i>

                                    Accepted

                                </div>


                            </div>


                            <!-- INTERNSHIP -->

                            <div class="internship-box">


                                <div class="internship-box-icon">

                                    <i class="fa-solid fa-briefcase"></i>

                                </div>


                                <div class="internship-box-content">


                                    <span>
                                        INTERNSHIP
                                    </span>


                                    <h4>
                                        <?= e(
                                            $application['internship_title']
                                        ) ?>
                                    </h4>


                                    <div class="internship-meta">


                                        <?php if (!empty($application['category'])): ?>

                                            <span>

                                                <i class="fa-solid fa-layer-group"></i>

                                                <?= e(
                                                    $application['category']
                                                ) ?>

                                            </span>

                                        <?php endif; ?>


                                        <?php if (!empty($application['location'])): ?>

                                            <span>

                                                <i class="fa-solid fa-location-dot"></i>

                                                <?= e(
                                                    $application['location']
                                                ) ?>

                                            </span>

                                        <?php endif; ?>


                                        <?php if (!empty($application['type'])): ?>

                                            <span>

                                                <i class="fa-solid fa-clock"></i>

                                                <?= e(
                                                    $application['type']
                                                ) ?>

                                            </span>

                                        <?php endif; ?>


                                    </div>


                                </div>


                            </div>


                            <!-- STUDENT INFORMATION -->

                            <div class="student-info-grid">


                                <!-- UNIVERSITY -->

                                <div class="info-item">


                                    <div class="info-icon">

                                        <i class="fa-solid fa-graduation-cap"></i>

                                    </div>


                                    <div>

                                        <span>
                                            University
                                        </span>

                                        <strong>

                                            <?= !empty(
                                                $application['university']
                                            )
                                                ? e(
                                                    $application['university']
                                                )
                                                : 'Not provided'
                                            ?>

                                        </strong>

                                    </div>


                                </div>


                                <!-- MAJOR -->

                                <div class="info-item">


                                    <div class="info-icon">

                                        <i class="fa-solid fa-book-open"></i>

                                    </div>


                                    <div>

                                        <span>
                                            Major
                                        </span>

                                        <strong>

                                            <?= !empty(
                                                $application['major']
                                            )
                                                ? e(
                                                    $application['major']
                                                )
                                                : 'Not provided'
                                            ?>

                                        </strong>

                                    </div>


                                </div>


                                <!-- YEAR -->

                                <div class="info-item">


                                    <div class="info-icon">

                                        <i class="fa-solid fa-calendar-days"></i>

                                    </div>


                                    <div>

                                        <span>
                                            Year of Study
                                        </span>

                                        <strong>

                                            <?= !empty(
                                                $application['year_of_study']
                                            )
                                                ? e(
                                                    $application['year_of_study']
                                                )
                                                : 'Not provided'
                                            ?>

                                        </strong>

                                    </div>


                                </div>


                                <!-- PHONE -->

                                <div class="info-item">


                                    <div class="info-icon">

                                        <i class="fa-solid fa-phone"></i>

                                    </div>


                                    <div>

                                        <span>
                                            Phone
                                        </span>

                                        <strong>

                                            <?= !empty(
                                                $application['phone']
                                            )
                                                ? e(
                                                    $application['phone']
                                                )
                                                : 'Not provided'
                                            ?>

                                        </strong>

                                    </div>


                                </div>


                            </div>


                            <!-- FOOTER -->

                            <div class="accepted-footer">


                                <div class="accepted-date">

                                    <i class="fa-regular fa-calendar"></i>

                                    Accepted application

                                    <strong>

                                        <?= !empty(
                                            $application['applied_at']
                                        )
                                            ? date(
                                                'M d, Y',
                                                strtotime(
                                                    $application['applied_at']
                                                )
                                            )
                                            : 'Unknown'
                                        ?>

                                    </strong>

                                </div>


                                <div class="accepted-actions">


                                    <!-- RESUME -->

                                    <?php if (!empty($application['resume'])): ?>

                                        <a
                                            href="<?= e($application['resume']) ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="action-button resume-button"
                                        >

                                            <i class="fa-solid fa-file-pdf"></i>

                                            Resume

                                        </a>

                                    <?php endif; ?>


                                    <!-- PORTFOLIO -->

                                    <?php if ($portfolio_url): ?>

                                        <a
                                            href="<?= e($portfolio_url) ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="action-button portfolio-button"
                                        >

                                            <i class="fa-solid fa-globe"></i>

                                            Portfolio

                                        </a>

                                    <?php endif; ?>


                                    <!-- LINKEDIN -->

                                    <?php if ($linkedin_url): ?>

                                        <a
                                            href="<?= e($linkedin_url) ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="action-button linkedin-button"
                                        >

                                            <i class="fa-brands fa-linkedin-in"></i>

                                            LinkedIn

                                        </a>

                                    <?php endif; ?>


                                    <!-- DETAILS -->

                                  <a
                                    href="company-application-details.php?internship_id=<?= (int)$application['internship_id'] ?>"
                                    class="action-button details-button"
                                >
                                    <i class="fa-solid fa-eye"></i>
                                    View Details
                                </a>


                                </div>


                            </div>


                        </article>


                    <?php endforeach; ?>


                </div>


            <?php else: ?>


                <!-- ==================================================
                     EMPTY STATE
                ================================================== -->

                <div class="empty-state">


                    <div class="empty-icon">

                        <i class="fa-solid fa-user-check"></i>

                    </div>


                    <h3>
                        No Accepted Interns Yet
                    </h3>


                    <p>

                        You don't have any accepted applicants
                        at the moment. Accepted students will
                        appear here once you approve their
                        applications.

                    </p>


                    <a
                        href="company-applications.php"
                        class="primary-button"
                    >

                        <i class="fa-solid fa-file-lines"></i>

                        View Applications

                    </a>


                </div>


            <?php endif; ?>


        </section>


    </main>


</div>


</body>

</html>
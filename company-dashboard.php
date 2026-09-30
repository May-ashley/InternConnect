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
   COMPANY INFORMATION
================================================== */

$user_id = (int) $_SESSION['user_id'];

$company_name_session = $_SESSION['user_name'] ?? 'Company';
$company_email = $_SESSION['user_email'] ?? '';


/* ==================================================
   DATABASE CONNECTION
================================================== */

$conn = connect();


/* ==================================================
   COMPANY PROFILE
================================================== */

$company_id = 0;
$company_name = $company_name_session;
$company_logo = '';
$company_location = '';
$company_industry = '';

$sql = "
    SELECT
        id,
        company_name,
        description,
        industry,
        location,
        website,
        logo
    FROM companies
    WHERE user_id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $user_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {

        $company_id = (int) $row['id'];

        $company_name =
            $row['company_name'] ?: $company_name_session;

        $company_logo =
            $row['logo'] ?? '';

        $company_location =
            $row['location'] ?? '';

        $company_industry =
            $row['industry'] ?? '';
    }

    mysqli_stmt_close($stmt);
}


/* ==================================================
   DEFAULT STATISTICS
================================================== */

$total_internships = 0;
$active_internships = 0;
$pending_internships = 0;
$closed_internships = 0;
$total_applicants = 0;


/* ==================================================
   INTERNSHIP STATISTICS
================================================== */

if ($company_id > 0) {

    $sql = "
        SELECT
            COUNT(*) AS total,
            SUM(CASE
                WHEN status = 'active'
                THEN 1 ELSE 0
            END) AS active,
            SUM(CASE
                WHEN status = 'pending'
                THEN 1 ELSE 0
            END) AS pending,
            SUM(CASE
                WHEN status = 'closed'
                THEN 1 ELSE 0
            END) AS closed
        FROM internships
        WHERE company_id = ?
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $company_id
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {

            $total_internships =
                (int) ($row['total'] ?? 0);

            $active_internships =
                (int) ($row['active'] ?? 0);

            $pending_internships =
                (int) ($row['pending'] ?? 0);

            $closed_internships =
                (int) ($row['closed'] ?? 0);
        }

        mysqli_stmt_close($stmt);
    }


    /* ==================================================
       TOTAL APPLICANTS
    ================================================== */

    $sql = "
        SELECT COUNT(*) AS total
        FROM applications a
        INNER JOIN internships i
            ON a.internship_id = i.id
        WHERE i.company_id = ?
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $company_id
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {

            $total_applicants =
                (int) ($row['total'] ?? 0);
        }

        mysqli_stmt_close($stmt);
    }
}


/* ==================================================
   RECENT INTERNSHIPS
================================================== */

$recent_internships = [];

if ($company_id > 0) {

    $sql = "
        SELECT
            id,
            title,
            category,
            location,
            type,
            application_deadline,
            status,
            created_at,

            (
                SELECT COUNT(*)
                FROM applications a
                WHERE a.internship_id = internships.id
            ) AS applicant_count

        FROM internships

        WHERE company_id = ?

        ORDER BY created_at DESC

        LIMIT 5
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $company_id
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {

            $recent_internships[] = $row;
        }

        mysqli_stmt_close($stmt);
    }
}


/* ==================================================
   RECENT APPLICATIONS
================================================== */

$recent_applications = [];

if ($company_id > 0) {

    $sql = "
        SELECT
            a.id,
            a.status,
            a.applied_at,

            i.id AS internship_id,
            i.title,

            s.user_id AS student_user_id,

            u.name AS student_name,
            u.email AS student_email

        FROM applications a

        INNER JOIN internships i
            ON a.internship_id = i.id

        INNER JOIN students_profile s
            ON a.student_id = s.user_id

        INNER JOIN users u
            ON s.user_id = u.id

        WHERE i.company_id = ?

        ORDER BY a.applied_at DESC

        LIMIT 5
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $company_id
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {

            $recent_applications[] = $row;
        }

        mysqli_stmt_close($stmt);
    }
}


/* ==================================================
   COMPANY INITIAL
================================================== */

$company_initial = strtoupper(
    substr(
        $company_name,
        0,
        1
    )
);

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
        Company Dashboard | InternConnect
    </title>


    <!-- Google Font -->

    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- Company Dashboard CSS -->

    <link
        rel="stylesheet"
        href="company-dashboard.css"
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


            <a
                href="company-dashboard.php"
                class="active"
            >

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


        <!-- BOTTOM -->

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

    <main class="dashboard-main">


        <!-- ==================================================
             HEADER
        ================================================== -->

        <header class="dashboard-header">


            <div>

                <p class="dashboard-label">
                    Company Dashboard
                </p>

                <h1>

                    Welcome back,
                    <?php
                    echo htmlspecialchars($company_name);
                    ?>

                </h1>

                <p class="dashboard-subtitle">

                    Manage your internship opportunities,
                    review applicants, and build your team.

                </p>

            </div>


            <div class="company-info">


                <div class="company-avatar">

                    <?php if (!empty($company_logo)): ?>

                        <img
                            src="<?php
                            echo htmlspecialchars($company_logo);
                            ?>"
                            alt="Company Logo"
                        >

                    <?php else: ?>

                        <?php
                        echo htmlspecialchars($company_initial);
                        ?>

                    <?php endif; ?>

                </div>


                <div>

                    <strong>

                        <?php
                        echo htmlspecialchars($company_name);
                        ?>

                    </strong>

                    <span>

                        <?php
                        echo htmlspecialchars($company_email);
                        ?>

                    </span>

                </div>


            </div>


        </header>



        <!-- ==================================================
             STATISTICS
        ================================================== -->

        <section class="stats-grid">


            <!-- TOTAL INTERNSHIPS -->

            <div class="stat-card">

                <div class="stat-icon">

                    <i class="fa-solid fa-briefcase"></i>

                </div>

                <div>

                    <p>
                        Total Internships
                    </p>

                    <h2>
                        <?php
                        echo $total_internships;
                        ?>
                    </h2>

                </div>

            </div>


            <!-- ACTIVE -->

            <div class="stat-card">

                <div class="stat-icon">

                    <i class="fa-solid fa-circle-check"></i>

                </div>

                <div>

                    <p>
                        Active
                    </p>

                    <h2>
                        <?php
                        echo $active_internships;
                        ?>
                    </h2>

                </div>

            </div>


            <!-- PENDING -->

            <div class="stat-card">

                <div class="stat-icon">

                    <i class="fa-regular fa-clock"></i>

                </div>

                <div>

                    <p>
                        Pending
                    </p>

                    <h2>
                        <?php
                        echo $pending_internships;
                        ?>
                    </h2>

                </div>

            </div>


            <!-- APPLICANTS -->

            <div class="stat-card">

                <div class="stat-icon">

                    <i class="fa-solid fa-users"></i>

                </div>

                <div>

                    <p>
                        Applicants
                    </p>

                    <h2>
                        <?php
                        echo $total_applicants;
                        ?>
                    </h2>

                </div>

            </div>


        </section>



        <!-- ==================================================
             MAIN GRID
        ================================================== -->

        <section class="dashboard-grid">


            <!-- ==================================================
                 INTERNSHIPS
            ================================================== -->

            <div class="dashboard-card internships-card">


                <div class="card-header">

                    <div>

                        <p class="card-label">
                            Recruitment
                        </p>

                        <h2>
                            Recent Internships
                        </h2>

                    </div>


                    <a href="company-internships.php">
                        View All
                    </a>

                </div>


                <div class="internship-list">


                    <?php if (!empty($recent_internships)): ?>


                        <?php foreach (
                            $recent_internships
                            as $internship
                        ): ?>


                            <article class="internship-item">


                                <div class="internship-icon">

                                    <i class="fa-solid fa-briefcase"></i>

                                </div>


                                <div class="internship-info">


                                    <h3>

                                        <?php
                                        echo htmlspecialchars(
                                            $internship['title']
                                        );
                                        ?>

                                    </h3>


                                    <p>

                                        <?php
                                        echo htmlspecialchars(
                                            $internship['category']
                                        );
                                        ?>

                                    </p>


                                    <div class="internship-meta">

                                        <span>

                                            <i class="fa-solid fa-location-dot"></i>

                                            <?php
                                            echo htmlspecialchars(
                                                $internship['location']
                                            );
                                            ?>

                                        </span>


                                        <span>

                                            <i class="fa-solid fa-users"></i>

                                            <?php
                                            echo (int)
                                                $internship[
                                                    'applicant_count'
                                                ];
                                            ?>

                                            Applicants

                                        </span>

                                    </div>


                                </div>


                                <div class="internship-right">


                                    <?php

                                    $status =
                                        strtolower(
                                            $internship['status']
                                        );

                                    ?>


                                    <span
                                        class="status-badge status-<?php
                                        echo htmlspecialchars($status);
                                        ?>"
                                    >

                                        <?php
                                        echo ucfirst(
                                            htmlspecialchars($status)
                                        );
                                        ?>

                                    </span>


                                    <a
                                        href="company-internship-edit.php?id=<?php
                                        echo (int)
                                            $internship['id'];
                                        ?>"
                                        class="view-button"
                                    >

                                        Manage

                                    </a>


                                </div>


                            </article>


                        <?php endforeach; ?>


                    <?php else: ?>


                        <div class="empty-state">


                            <div class="empty-icon">

                                <i class="fa-solid fa-briefcase"></i>

                            </div>


                            <h3>
                                No internships yet
                            </h3>


                            <p>
                                Create your first internship
                                opportunity to start recruiting
                                students.
                            </p>


                            <a
                                href="company-internship-create.php"
                                class="primary-button"
                            >

                                <i class="fa-solid fa-plus"></i>

                                Create Internship

                            </a>


                        </div>


                    <?php endif; ?>


                </div>


            </div>



            <!-- ==================================================
                 COMPANY PROFILE
            ================================================== -->

            <div class="dashboard-card profile-card">


                <div class="card-header">

                    <div>

                        <p class="card-label">
                            Your Business
                        </p>

                        <h2>
                            Company Profile
                        </h2>

                    </div>

                </div>


                <div class="profile-content">


                    <div class="large-company-avatar">


                        <?php if (!empty($company_logo)): ?>

                            <img
                                src="<?php
                                echo htmlspecialchars($company_logo);
                                ?>"
                                alt="Company Logo"
                            >

                        <?php else: ?>

                            <?php
                            echo htmlspecialchars($company_initial);
                            ?>

                        <?php endif; ?>


                    </div>


                    <h3>

                        <?php
                        echo htmlspecialchars($company_name);
                        ?>

                    </h3>


                    <?php if (!empty($company_industry)): ?>

                        <p class="profile-industry">

                            <i class="fa-solid fa-industry"></i>

                            <?php
                            echo htmlspecialchars(
                                $company_industry
                            );
                            ?>

                        </p>

                    <?php endif; ?>


                    <?php if (!empty($company_location)): ?>

                        <p class="profile-location">

                            <i class="fa-solid fa-location-dot"></i>

                            <?php
                            echo htmlspecialchars(
                                $company_location
                            );
                            ?>

                        </p>

                    <?php endif; ?>


                    <div class="profile-stats">


                        <div>

                            <strong>
                                <?php
                                echo $total_internships;
                                ?>
                            </strong>

                            <span>
                                Internships
                            </span>

                        </div>


                        <div>

                            <strong>
                                <?php
                                echo $total_applicants;
                                ?>
                            </strong>

                            <span>
                                Applicants
                            </span>

                        </div>


                    </div>


                    <a
                        href="company-profile.php"
                        class="secondary-button"
                    >

                        <i class="fa-regular fa-pen-to-square"></i>

                        Edit Company Profile

                    </a>


                </div>


            </div>


        </section>



        <!-- ==================================================
             RECENT APPLICATIONS
        ================================================== -->

        <section class="dashboard-card applications-card">


            <div class="card-header">


                <div>

                    <p class="card-label">
                        Applicant Management
                    </p>

                    <h2>
                        Recent Applications
                    </h2>

                </div>


                <a href="company-applications.php">
                    View All
                </a>


            </div>


            <?php if (!empty($recent_applications)): ?>


                <div class="applications-list">


                    <?php foreach (
                        $recent_applications
                        as $application
                    ): ?>


                        <div class="application-item">


                            <div class="student-avatar-small">

                                <?php

                                echo strtoupper(
                                    substr(
                                        $application['student_name'],
                                        0,
                                        1
                                    )
                                );

                                ?>

                            </div>


                            <div class="application-info">


                                <h3>

                                    <?php
                                    echo htmlspecialchars(
                                        $application[
                                            'student_name'
                                        ]
                                    );
                                    ?>

                                </h3>


                                <p>

                                    <?php
                                    echo htmlspecialchars(
                                        $application[
                                            'title'
                                        ]
                                    );
                                    ?>

                                </p>


                                <small>

                                    Applied

                                    <?php
                                    echo date(
                                        'M d, Y',
                                        strtotime(
                                            $application[
                                                'applied_at'
                                            ]
                                        )
                                    );
                                    ?>

                                </small>


                            </div>


                            <div class="application-status">


                                <?php

                                $application_status =
                                    strtolower(
                                        $application['status']
                                    );

                                ?>


                                <span
                                    class="status-badge status-<?php
                                    echo htmlspecialchars(
                                        $application_status
                                    );
                                    ?>"
                                >

                                    <?php
                                    echo ucfirst(
                                        htmlspecialchars(
                                            $application_status
                                        )
                                    );
                                    ?>

                                </span>


                            </div>


                            <a
                                href="company-application-details.php?id=<?php
                                echo (int)
                                    $application['id'];
                                ?>"
                                class="small-view-button"
                            >

                                View

                            </a>


                        </div>


                    <?php endforeach; ?>


                </div>


            <?php else: ?>


                <div class="empty-applications">


                    <div class="empty-icon">

                        <i class="fa-solid fa-users"></i>

                    </div>


                    <h3>
                        No applications yet
                    </h3>


                    <p>
                        Student applications will appear
                        here when students apply to your
                        internships.
                    </p>


                    <a
                        href="company-internships.php"
                        class="secondary-button"
                    >

                        View My Internships

                    </a>


                </div>


            <?php endif; ?>


        </section>


    </main>


</div>


</body>

</html>
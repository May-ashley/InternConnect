<?php

session_start();

require_once "db.php";

/* ==================================================
   STUDENT ACCESS PROTECTION
================================================== */

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit();
}


/* ==================================================
   STUDENT INFORMATION
================================================== */

$student_id = (int) $_SESSION['user_id'];

$student_name = $_SESSION['user_name'] ?? 'Student';
$student_email = $_SESSION['user_email'] ?? '';


/* ==================================================
   DATABASE CONNECTION
================================================== */

$conn = connect();


/* ==================================================
   DEFAULT STATISTICS
================================================== */

$total_applications = 0;
$pending_applications = 0;
$accepted_applications = 0;
$rejected_applications = 0;


/* ==================================================
   APPLICATION STATISTICS
================================================== */

$sql = "
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) AS accepted,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected
    FROM applications
    WHERE student_id = ?
";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $student_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {

        $total_applications =
            (int) ($row['total'] ?? 0);

        $pending_applications =
            (int) ($row['pending'] ?? 0);

        $accepted_applications =
            (int) ($row['accepted'] ?? 0);

        $rejected_applications =
            (int) ($row['rejected'] ?? 0);
    }

    mysqli_stmt_close($stmt);
}


/* ==================================================
   RECOMMENDED INTERNSHIPS
================================================== */

$recommended_internships = [];

$sql = "
    SELECT
        internships.id,
        internships.title,
        internships.category,
        internships.location,
        internships.type,
        internships.description,
        internships.application_deadline,
        companies.company_name,
        companies.logo

    FROM internships

    INNER JOIN companies
        ON internships.company_id = companies.id

    WHERE internships.status = 'active'

    ORDER BY internships.created_at DESC

    LIMIT 4
";

$result = mysqli_query($conn, $sql);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $recommended_internships[] = $row;
    }
}


/* ==================================================
   RECENT APPLICATIONS
================================================== */

$recent_applications = [];

$sql = "
    SELECT
        applications.id,
        applications.status,
        applications.applied_at,

        internships.id AS internship_id,
        internships.title,
        internships.category,
        internships.location,
        internships.type,

        companies.company_name,
        companies.logo

    FROM applications

    INNER JOIN internships
        ON applications.internship_id = internships.id

    INNER JOIN companies
        ON internships.company_id = companies.id

    WHERE applications.student_id = ?

    ORDER BY applications.applied_at DESC

    LIMIT 3
";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $student_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {

        $recent_applications[] = $row;
    }

    mysqli_stmt_close($stmt);
}


/* ==================================================
   PROFILE COMPLETION
================================================== */

$profile_completion = 0;

$profile_sql = "
    SELECT
        phone,
        university,
        major,
        year_of_study,
        portfolio_url,
        linkedin_url
    FROM students_profile
    WHERE user_id = ?
    LIMIT 1
";

$profile_stmt = mysqli_prepare($conn, $profile_sql);

if ($profile_stmt) {

    mysqli_stmt_bind_param(
        $profile_stmt,
        "i",
        $student_id
    );

    mysqli_stmt_execute($profile_stmt);

    $profile_result = mysqli_stmt_get_result($profile_stmt);

    if ($profile_row = mysqli_fetch_assoc($profile_result)) {

        $profile_fields = [
            $profile_row['phone'] ?? '',
            $profile_row['university'] ?? '',
            $profile_row['major'] ?? '',
            $profile_row['year_of_study'] ?? '',
            $profile_row['portfolio_url'] ?? '',
            $profile_row['linkedin_url'] ?? ''
        ];

        $completed_fields = 0;

        foreach ($profile_fields as $field) {
            if (!empty(trim($field))) {
                $completed_fields++;
            }
        }

        $profile_completion = (int) round(
            ($completed_fields / count($profile_fields)) * 100
        );
    }

    mysqli_stmt_close($profile_stmt);
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
        Student Dashboard | InternConnect
    </title>

    
    <!-- ==================================================
        Main CSS
    ================================================== -->
    <link
        rel="stylesheet"
        href="student-dashboard.css"
    >

    <!-- ==================================================
        For Side Bar Icons
    ================================================== -->

    <link
        rel="stylesheet"
        href="student-internships.css"
    >


    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
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
                Student Portal
            </p>

        </div>


        <nav class="sidebar-nav">

            <a
                href="student-dashboard.php"
                class="active"
            >

                <i class="fa-solid fa-chart-line"></i>

                <span>
                    Dashboard
                </span>

            </a>


            <a href="student-internships.php">

                <i class="fa-solid fa-briefcase"></i>

                <span>
                    Browse Internships
                </span>

            </a>


            <a href="student-applications.php">

                <i class="fa-regular fa-file-lines"></i>

                <span>
                    My Applications
                </span>

            </a>


            <a href="student-profile.php">

                <i class="fa-regular fa-user"></i>

                <span>
                    My Profile
                </span>

            </a>

        </nav>


        <div class="sidebar-bottom">


            <a href="index.php" class="back-to-site">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to Site</span>
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


            <div>

                <p class="dashboard-label">
                    Student Dashboard
                </p>

                <h1>
                    Welcome back,
                    <?php
                    echo htmlspecialchars($student_name);
                    ?>
                </h1>

                <p class="dashboard-subtitle">

                    Find internship opportunities,
                    manage your applications,
                    and build your career.

                </p>

            </div>


            <div class="student-info">


                <div class="student-avatar">

                    <?php

                    echo strtoupper(
                        substr(
                            $student_name,
                            0,
                            1
                        )
                    );

                    ?>

                </div>


                <div>

                    <strong>

                        <?php
                        echo htmlspecialchars(
                            $student_name
                        );
                        ?>

                    </strong>

                    <span>

                        <?php
                        echo htmlspecialchars(
                            $student_email
                        );
                        ?>

                    </span>

                </div>


            </div>


        </header>



        <!-- ==================================================
             APPLICATION STATISTICS
        ================================================== -->

        <section class="stats-grid">


            <div class="stat-card">

                <div class="stat-icon">

                    <i class="fa-regular fa-file-lines"></i>

                </div>

                <div>

                    <p>
                        Applications
                    </p>

                    <h2>
                        <?php
                        echo $total_applications;
                        ?>
                    </h2>

                </div>

            </div>



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
                        echo $pending_applications;
                        ?>
                    </h2>

                </div>

            </div>



            <div class="stat-card">

                <div class="stat-icon">

                    <i class="fa-solid fa-check"></i>

                </div>

                <div>

                    <p>
                        Accepted
                    </p>

                    <h2>
                        <?php
                        echo $accepted_applications;
                        ?>
                    </h2>

                </div>

            </div>



            <div class="stat-card">

                <div class="stat-icon">

                    <i class="fa-solid fa-xmark"></i>

                </div>

                <div>

                    <p>
                        Rejected
                    </p>

                    <h2>
                        <?php
                        echo $rejected_applications;
                        ?>
                    </h2>

                </div>

            </div>


        </section>



        <!-- ==================================================
             MAIN DASHBOARD GRID
        ================================================== -->

        <section class="dashboard-grid">


            <!-- ==================================================
                 RECOMMENDED INTERNSHIPS
            ================================================== -->

            <div class="dashboard-card internships-card">


                <div class="card-header">

                    <div>

                        <p class="card-label">
                            Opportunities
                        </p>

                        <h2>
                            Latest Internships
                        </h2>

                    </div>


                    <a
                        href="student-internships.php"
                    >
                        View All
                    </a>

                </div>



                <div class="internship-list">


                    <?php if (!empty($recommended_internships)): ?>


                        <?php foreach (
                            $recommended_internships
                            as $internship
                        ): ?>


                            <article class="internship-item">


                                <!-- COMPANY LOGO -->

                                <div class="internship-logo">

                                    <?php if (
                                        !empty(
                                            $internship['logo']
                                        )
                                    ): ?>

                                        <img
                                            src="<?php
                                            echo htmlspecialchars(
                                                $internship['logo']
                                            );
                                            ?>"
                                            alt="<?php
                                            echo htmlspecialchars(
                                                $internship['company_name']
                                            );
                                            ?>"
                                        >

                                    <?php else: ?>

                                        <span>

                                            <?php

                                            echo strtoupper(
                                                substr(
                                                    $internship[
                                                        'company_name'
                                                    ],
                                                    0,
                                                    1
                                                )
                                            );

                                            ?>

                                        </span>

                                    <?php endif; ?>

                                </div>



                                <!-- INTERNSHIP INFORMATION -->

                                <div class="internship-info">


                                    <h3>

                                        <?php
                                        echo htmlspecialchars(
                                            $internship['title']
                                        );
                                        ?>

                                    </h3>


                                    <p class="company-name">

                                        <?php
                                        echo htmlspecialchars(
                                            $internship[
                                                'company_name'
                                            ]
                                        );
                                        ?>

                                    </p>


                                    <div class="internship-meta">


                                        <span>

                                            <i class="fa-solid fa-location-dot"></i>

                                            <?php
                                            echo htmlspecialchars(
                                                $internship[
                                                    'location'
                                                ]
                                            );
                                            ?>

                                        </span>


                                        <span>

                                            <i class="fa-solid fa-briefcase"></i>

                                            <?php
                                            echo htmlspecialchars(
                                                $internship[
                                                    'type'
                                                ]
                                            );
                                            ?>

                                        </span>


                                        <span>

                                            <i class="fa-solid fa-tag"></i>

                                            <?php
                                            echo htmlspecialchars(
                                                $internship[
                                                    'category'
                                                ]
                                            );
                                            ?>

                                        </span>


                                    </div>


                                </div>



                                <!-- ACTION -->

                                <div class="internship-action">

                                    <a
                                        href="student-internship-details.php?id=<?php
                                        echo $internship['id'];
                                        ?>"
                                        class="view-button"
                                    >
                                        View
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
                                No internships available
                            </h3>

                            <p>
                                New internship opportunities
                                will appear here when they
                                become available.
                            </p>

                            <a
                                href="student-internships.php"
                                class="primary-button"
                            >
                                Browse Internships
                            </a>

                        </div>


                    <?php endif; ?>


                </div>


            </div>



            <!-- ==================================================
                 PROFILE CARD
            ================================================== -->

            <div class="dashboard-card profile-card">


                <div class="card-header">

                    <div>

                        <p class="card-label">
                            Your Account
                        </p>

                        <h2>
                            Profile
                        </h2>

                    </div>

                </div>



                <div class="profile-content">


                    <div class="large-avatar">

                        <?php

                        echo strtoupper(
                            substr(
                                $student_name,
                                0,
                                1
                            )
                        );

                        ?>

                    </div>


                    <h3>

                        <?php
                        echo htmlspecialchars(
                            $student_name
                        );
                        ?>

                    </h3>


                    <p>

                        <?php
                        echo htmlspecialchars(
                            $student_email
                        );
                        ?>

                    </p>



                    <div class="profile-progress">


                        <div class="progress-header">

                            <span>
                                Profile Completion
                            </span>

                            <strong>
                                <?php
                                echo $profile_completion;
                                ?>%
                            </strong>

                        </div>


                        <div class="progress-bar">

                            <div
                                class="progress-fill"
                                style="width: <?php
                                echo $profile_completion;
                                ?>%;"
                            ></div>

                        </div>


                    </div>



                    <a
                        href="student-profile.php"
                        class="secondary-button"
                    >
                        Complete Profile
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
                        Application Tracking
                    </p>

                    <h2>
                        Recent Applications
                    </h2>

                </div>


                <a href="student-applications.php">
                    View All
                </a>


            </div>



            <?php if (
                !empty($recent_applications)
            ): ?>


                <div class="recent-applications-list">


                    <?php foreach (
                        $recent_applications
                        as $application
                    ): ?>


                        <div class="recent-application">


                            <div class="recent-logo">

                                <?php if (
                                    !empty(
                                        $application['logo']
                                    )
                                ): ?>

                                    <img
                                        src="<?php
                                        echo htmlspecialchars(
                                            $application['logo']
                                        );
                                        ?>"
                                        alt=""
                                    >

                                <?php else: ?>

                                    <span>

                                        <?php

                                        echo strtoupper(
                                            substr(
                                                $application[
                                                    'company_name'
                                                ],
                                                0,
                                                1
                                            )
                                        );

                                        ?>

                                    </span>

                                <?php endif; ?>

                            </div>



                            <div class="recent-info">

                                <h3>

                                    <?php
                                    echo htmlspecialchars(
                                        $application['title']
                                    );
                                    ?>

                                </h3>

                                <p>

                                    <?php
                                    echo htmlspecialchars(
                                        $application[
                                            'company_name'
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



                            <div class="recent-status">

                                <?php

                                $status =
                                    strtolower(
                                        $application['status']
                                    );

                                ?>

                                <span
                                    class="status-badge status-<?php
                                    echo htmlspecialchars(
                                        $status
                                    );
                                    ?>"
                                >

                                    <?php
                                    echo ucfirst(
                                        htmlspecialchars(
                                            $status
                                        )
                                    );
                                    ?>

                                </span>

                            </div>



                            <a
                                href="student-internship-details.php?id=<?php
                                echo $application[
                                    'internship_id'
                                ];
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

                        <i class="fa-solid fa-file-circle-question"></i>

                    </div>


                    <h3>
                        No applications yet
                    </h3>


                    <p>
                        Once you apply for an internship,
                        your application status will appear here.
                    </p>


                    <a
                        href="student-internships.php"
                        class="primary-button"
                    >
                        Find an Internship
                    </a>


                </div>


            <?php endif; ?>


        </section>


    </main>


</div>


</body>

</html>
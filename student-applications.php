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
   DATABASE CONNECTION
================================================== */

$conn = connect();

$student_id = (int) $_SESSION['user_id'];


/* ==================================================
   GET STUDENT APPLICATIONS
================================================== */

$sql = "
    SELECT
        applications.id AS application_id,
        applications.status,
        applications.applied_at,

        internships.id AS internship_id,
        internships.title,
        internships.category,
        internships.location,
        internships.type,
        internships.description,
        internships.application_deadline,

        companies.company_name,
        companies.industry,
        companies.logo

    FROM applications

    INNER JOIN internships
        ON applications.internship_id = internships.id

    INNER JOIN companies
        ON internships.company_id = companies.id

    WHERE applications.student_id = ?

    ORDER BY applications.applied_at DESC
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Unable to load applications.");
}

mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


/* ==================================================
   APPLICATION STATISTICS
================================================== */

$total_applications = 0;
$pending_applications = 0;
$accepted_applications = 0;
$rejected_applications = 0;


/* ==================================================
   SUCCESS MESSAGE
================================================== */

$application_success = isset($_GET['applied']) &&
                       $_GET['applied'] === 'success';

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
        My Applications | InternConnect
    </title>

    <link
        rel="stylesheet"
        href="student-applications.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

</head>


<body>


<!-- ==================================================
     BACKGROUND
================================================== -->

<div class="background-glow glow-one"></div>
<div class="background-glow glow-two"></div>


<div class="student-layout">


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

            <a href="student-dashboard.php">

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


            <a
                href="student-applications.php"
                class="active"
            >

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

    <main class="applications-main">


        <!-- ==================================================
             PAGE HEADER
        ================================================== -->

        <header class="page-header">

            <div>

                <span class="page-label">
                    STUDENT PORTAL
                </span>

                <h1>
                    My <span>Applications</span>
                </h1>

                <p>
                    Track your internship applications and
                    review your application history.
                </p>

            </div>


            <a
                href="student-internships.php"
                class="browse-button"
            >

                <i class="fa-solid fa-briefcase"></i>

                Browse Internships

            </a>

        </header>



        <!-- ==================================================
             SUCCESS MESSAGE
        ================================================== -->

        <?php if ($application_success): ?>

            <div class="success-message">

                <div class="success-icon">

                    <i class="fa-solid fa-check"></i>

                </div>

                <div>

                    <strong>
                        Application submitted successfully
                    </strong>

                    <p>
                        Your application has been recorded and
                        is now waiting for review.
                    </p>

                </div>

            </div>

        <?php endif; ?>



        <!-- ==================================================
             APPLICATION STATISTICS
        ================================================== -->

        <?php

        /*
         * We need to read the application result once.
         * Store the rows temporarily so we can display
         * statistics and applications without running
         * another database query.
         */

        $applications = [];

        while ($row = mysqli_fetch_assoc($result)) {

            $applications[] = $row;

            $total_applications++;

            if ($row['status'] === 'pending') {
                $pending_applications++;
            }

            elseif ($row['status'] === 'accepted') {
                $accepted_applications++;
            }

            elseif ($row['status'] === 'rejected') {
                $rejected_applications++;
            }
        }

        mysqli_stmt_close($stmt);

        ?>


        <section class="stats-grid">


            <!-- TOTAL -->

            <div class="stat-card">

                <div class="stat-icon">

                    <i class="fa-regular fa-file-lines"></i>

                </div>

                <div>

                    <span>
                        TOTAL APPLICATIONS
                    </span>

                    <strong>
                        <?php echo $total_applications; ?>
                    </strong>

                </div>

            </div>


            <!-- PENDING -->

            <div class="stat-card">

                <div class="stat-icon pending-icon">

                    <i class="fa-regular fa-clock"></i>

                </div>

                <div>

                    <span>
                        PENDING
                    </span>

                    <strong>
                        <?php echo $pending_applications; ?>
                    </strong>

                </div>

            </div>


            <!-- ACCEPTED -->

            <div class="stat-card">

                <div class="stat-icon accepted-icon">

                    <i class="fa-solid fa-check"></i>

                </div>

                <div>

                    <span>
                        ACCEPTED
                    </span>

                    <strong>
                        <?php echo $accepted_applications; ?>
                    </strong>

                </div>

            </div>


            <!-- REJECTED -->

            <div class="stat-card">

                <div class="stat-icon rejected-icon">

                    <i class="fa-solid fa-xmark"></i>

                </div>

                <div>

                    <span>
                        REJECTED
                    </span>

                    <strong>
                        <?php echo $rejected_applications; ?>
                    </strong>

                </div>

            </div>

        </section>



        <!-- ==================================================
             APPLICATION SECTION
        ================================================== -->

        <section class="applications-section">




            <?php if (empty($applications)): ?>


                <!-- ==================================================
                     EMPTY STATE
                ================================================== -->

                <div class="empty-state">

                    <div class="empty-icon">

                        <i class="fa-regular fa-file-lines"></i>

                    </div>

                    <h3>
                        No applications yet
                    </h3>

                    <p>
                        You haven't applied for any internships.
                        Start exploring opportunities that match
                        your skills and interests.
                    </p>

                    <a
                        href="student-internships.php"
                        class="primary-button"
                    >

                        Browse Internships

                        <i class="fa-solid fa-arrow-right"></i>

                    </a>

                </div>


            <?php else: ?>


                <!-- ==================================================
                     APPLICATION LIST
                ================================================== -->

                <div class="application-list">


                    <?php foreach ($applications as $application): ?>


                        <?php

                        $status = strtolower(
                            $application['status']
                        );

                        $status_label = ucfirst($status);


                        /* Company logo fallback */

                        $company_name =
                            $application['company_name'];

                        $company_initial =
                            strtoupper(
                                substr(
                                    $company_name,
                                    0,
                                    1
                                )
                            );

                        ?>


                        <article class="application-card">


                            <!-- ==================================================
                                 COMPANY LOGO
                            ================================================== -->

                            <div class="application-logo">

                                <?php if (!empty($application['logo'])): ?>

                                    <img
                                        src="<?php
                                            echo htmlspecialchars(
                                                $application['logo']
                                            );
                                        ?>"
                                        alt="<?php
                                            echo htmlspecialchars(
                                                $company_name
                                            );
                                        ?>"
                                    >

                                <?php else: ?>

                                    <span>
                                        <?php
                                        echo htmlspecialchars(
                                            $company_initial
                                        );
                                        ?>
                                    </span>

                                <?php endif; ?>

                            </div>



                            <!-- ==================================================
                                 APPLICATION INFORMATION
                            ================================================== -->

                            <div class="application-info">

                                <span class="application-category">

                                    <?php
                                    echo htmlspecialchars(
                                        $application['category']
                                    );
                                    ?>

                                </span>


                                <h3>

                                    <?php
                                    echo htmlspecialchars(
                                        $application['title']
                                    );
                                    ?>

                                </h3>


                                <p class="company-name">

                                    <i class="fa-regular fa-building"></i>

                                    <?php
                                    echo htmlspecialchars(
                                        $company_name
                                    );
                                    ?>

                                </p>


                                <div class="application-meta">

                                    <span>

                                        <i class="fa-solid fa-location-dot"></i>

                                        <?php
                                        echo htmlspecialchars(
                                            $application['location']
                                        );
                                        ?>

                                    </span>


                                    <span>

                                        <i class="fa-solid fa-briefcase"></i>

                                        <?php
                                        echo htmlspecialchars(
                                            $application['type']
                                        );
                                        ?>

                                    </span>


                                    <span>

                                        <i class="fa-regular fa-calendar"></i>

                                        Applied
                                        <?php
                                        echo htmlspecialchars(
                                            date(
                                                "M d, Y",
                                                strtotime(
                                                    $application['applied_at']
                                                )
                                            )
                                        );
                                        ?>

                                    </span>

                                </div>

                            </div>



                            <!-- ==================================================
                                 STATUS
                            ================================================== -->

                            <div class="application-status">

                                <span class="status-label">
                                    STATUS
                                </span>


                                <?php if ($status === 'pending'): ?>

                                    <span class="status-badge pending">

                                        <i class="fa-regular fa-clock"></i>

                                        Pending

                                    </span>

                                <?php elseif ($status === 'accepted'): ?>

                                    <span class="status-badge accepted">

                                        <i class="fa-solid fa-circle-check"></i>

                                        Accepted

                                    </span>

                                <?php elseif ($status === 'rejected'): ?>

                                    <span class="status-badge rejected">

                                        <i class="fa-solid fa-circle-xmark"></i>

                                        Rejected

                                    </span>

                                <?php else: ?>

                                    <span class="status-badge">

                                        <?php
                                        echo htmlspecialchars(
                                            $status_label
                                        );
                                        ?>

                                    </span>

                                <?php endif; ?>

                            </div>



                            <!-- ==================================================
                                 VIEW INTERNSHIP
                            ================================================== -->

                            <a
                                href="student-internship-details.php?id=<?php
                                    echo (int) $application['internship_id'];
                                ?>"
                                class="view-link"
                            >

                                View

                                <i class="fa-solid fa-arrow-right"></i>

                            </a>


                        </article>


                    <?php endforeach; ?>


                </div>


            <?php endif; ?>


        </section>



        <!-- ==================================================
             STATUS INFORMATION
        ================================================== -->

        <section class="status-guide">


            <div class="guide-heading">

                <div class="guide-icon">

                    <i class="fa-solid fa-route"></i>

                </div>

                <div>

                    <span>
                        APPLICATION TRACKING
                    </span>

                    <h2>
                        What happens after you apply?
                    </h2>

                </div>

            </div>


            <div class="status-flow">


                <div class="flow-step">

                    <div class="flow-number">
                        1
                    </div>

                    <div>

                        <strong>
                            Applied
                        </strong>

                        <p>
                            Your application is submitted
                            through InternConnect.
                        </p>

                    </div>

                </div>


                <div class="flow-line"></div>


                <div class="flow-step">

                    <div class="flow-number">
                        2
                    </div>

                    <div>

                        <strong>
                            Pending
                        </strong>

                        <p>
                            Your application is waiting
                            for review.
                        </p>

                    </div>

                </div>


                <div class="flow-line"></div>


                <div class="flow-step">

                    <div class="flow-number">
                        3
                    </div>

                    <div>

                        <strong>
                            Accepted / Rejected
                        </strong>

                        <p>
                            The application status is updated
                            after the review process.
                        </p>

                    </div>

                </div>


            </div>

        </section>


    </main>

</div>

</body>

</html>
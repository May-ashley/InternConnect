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
   GET INTERNSHIP ID
================================================== */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: student-internships.php");
    exit();
}

$internship_id = (int) $_GET['id'];


/* ==================================================
   GET INTERNSHIP DETAILS
================================================== */

$sql = "
    SELECT
        internships.id,
        internships.title,
        internships.category,
        internships.location,
        internships.type,
        internships.description,
        internships.requirements,
        internships.application_deadline,
        internships.created_at,

        companies.id AS company_id,
        companies.company_name,
        companies.description AS company_description,
        companies.industry,
        companies.location AS company_location,
        companies.website,
        companies.logo

    FROM internships

    INNER JOIN companies
        ON internships.company_id = companies.id

    WHERE internships.id = ?
    AND internships.status = 'active'

    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database error.");
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $internship_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$internship = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* ==================================================
   INTERNSHIP NOT FOUND
================================================== */

if (!$internship) {
    header("Location: student-internships.php");
    exit();
}


/* ==================================================
   CHECK APPLICATION STATUS
================================================== */

$application_status = null;

$sql = "
    SELECT status
    FROM applications
    WHERE student_id = ?
    AND internship_id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $student_id,
        $internship_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $application = mysqli_fetch_assoc($result);

    if ($application) {
        $application_status = $application['status'];
    }

    mysqli_stmt_close($stmt);
}


/* ==================================================
   DEADLINE FORMATTING
================================================== */

$deadline_text = "Not specified";

if (!empty($internship['application_deadline'])) {

    $deadline_timestamp = strtotime(
        $internship['application_deadline']
    );

    if ($deadline_timestamp !== false) {

        $deadline_text = date(
            "M d, Y",
            $deadline_timestamp
        );
    }
}


/* ==================================================
   CHECK DEADLINE
================================================== */

$deadline_passed = false;

if (!empty($internship['application_deadline'])) {

    $deadline_timestamp = strtotime(
        $internship['application_deadline']
    );

    if (
        $deadline_timestamp !== false &&
        $deadline_timestamp < time()
    ) {
        $deadline_passed = true;
    }
}


/* ==================================================
   REQUIREMENTS
================================================== */

$requirements = "";

if (isset($internship['requirements'])) {
    $requirements = trim($internship['requirements']);
}


/* ==================================================
   WEBSITE
================================================== */

$company_website = trim(
    $internship['website'] ?? ''
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
        <?php
        echo htmlspecialchars(
            $internship['title']
        );
        ?>
        | InternConnect
    </title>

    <link
        rel="stylesheet"
        href="student-internship-details.css"
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

<div class="background-orb orb-one"></div>
<div class="background-orb orb-two"></div>


<!-- ==================================================
     PAGE
================================================== -->

<div class="student-details-page">


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


            <a
                href="student-internships.php"
                class="active"
            >

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

    <main class="details-main">


    
        <!-- ==================================================
             TOP BUTTON
        ================================================== -->

        <div class="top-bar">

            <a
                href="student-internships.php"
                class="back-internships"
            >

                <i class="fa-solid fa-arrow-left"></i>

                Back to Internships

            </a>

        </div>





        <!-- ==================================================
             INTERNSHIP HERO
        ================================================== -->

        <section class="internship-hero">


            <div class="hero-company-logo">

                <?php if (!empty($internship['logo'])): ?>

                    <img
                        src="<?php echo htmlspecialchars($internship['logo']); ?>"
                        alt="<?php echo htmlspecialchars($internship['company_name']); ?>"
                    >

                <?php else: ?>

                    <span>
                        <?php
                        echo strtoupper(
                            substr(
                                $internship['company_name'],
                                0,
                                1
                            )
                        );
                        ?>
                    </span>

                <?php endif; ?>

            </div>


            <div class="hero-content">

                <span class="opportunity-label">

                    <i class="fa-solid fa-circle-check"></i>

                    Internship Opportunity

                </span>


                <h1>

                    <?php
                    echo htmlspecialchars(
                        $internship['title']
                    );
                    ?>

                </h1>


                <p class="hero-company">

                    <?php
                    echo htmlspecialchars(
                        $internship['company_name']
                    );
                    ?>

                </p>


                <div class="hero-meta">

                    <span>

                        <i class="fa-solid fa-location-dot"></i>

                        <?php
                        echo htmlspecialchars(
                            $internship['location']
                        );
                        ?>

                    </span>


                    <span>

                        <i class="fa-solid fa-briefcase"></i>

                        <?php
                        echo htmlspecialchars(
                            $internship['type']
                        );
                        ?>

                    </span>


                    <span>

                        <i class="fa-solid fa-layer-group"></i>

                        <?php
                        echo htmlspecialchars(
                            $internship['category']
                        );
                        ?>

                    </span>

                </div>

            </div>

        </section>


        <!-- ==================================================
             IMPORTANT INFORMATION
        ================================================== -->

        <section class="info-grid">


            <div class="info-card">

                <div class="info-icon">
                    <i class="fa-solid fa-location-dot"></i>
                </div>

                <div>

                    <span>
                        Location
                    </span>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $internship['location']
                        );
                        ?>
                    </strong>

                </div>

            </div>


            <div class="info-card">

                <div class="info-icon">
                    <i class="fa-solid fa-clock"></i>
                </div>

                <div>

                    <span>
                        Internship Type
                    </span>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $internship['type']
                        );
                        ?>
                    </strong>

                </div>

            </div>


            <div class="info-card">

                <div class="info-icon">
                    <i class="fa-solid fa-tag"></i>
                </div>

                <div>

                    <span>
                        Category
                    </span>

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $internship['category']
                        );
                        ?>
                    </strong>

                </div>

            </div>


            <div class="info-card">

                <div class="info-icon deadline-icon">
                    <i class="fa-regular fa-calendar"></i>
                </div>

                <div>

                    <span>
                        Application Deadline
                    </span>

                    <strong
                        class="<?php echo $deadline_passed ? 'deadline-passed' : ''; ?>"
                    >

                        <?php
                        echo htmlspecialchars(
                            $deadline_text
                        );
                        ?>

                    </strong>

                </div>

            </div>

        </section>


        <!-- ==================================================
             MAIN DETAILS GRID
        ================================================== -->

        <section class="content-grid">


            <!-- ==================================================
                 LEFT CONTENT
            ================================================== -->

            <div class="details-content">


                <!-- DESCRIPTION -->

                <article class="content-card">

                    <div class="card-heading">

                        <div>

                            <span class="card-label">
                                Opportunity Overview
                            </span>

                            <h2>
                                About This Internship
                            </h2>

                        </div>

                        <div class="heading-icon">
                            <i class="fa-solid fa-file-lines"></i>
                        </div>

                    </div>


                    <div class="description-text">

                        <?php

                        if (
                            !empty(
                                trim(
                                    $internship['description']
                                )
                            )
                        ) {

                            echo nl2br(
                                htmlspecialchars(
                                    $internship['description']
                                )
                            );

                        } else {

                            echo "No internship description has been provided yet.";

                        }

                        ?>

                    </div>

                </article>


                <!-- REQUIREMENTS -->

                <article class="content-card">

                    <div class="card-heading">

                        <div>

                            <span class="card-label">
                                What You'll Need
                            </span>

                            <h2>
                                Requirements
                            </h2>

                        </div>

                        <div class="heading-icon">
                            <i class="fa-solid fa-list-check"></i>
                        </div>

                    </div>


                    <?php if (!empty($requirements)): ?>

                        <div class="requirements-text">

                            <?php

                            $requirement_lines =
                                preg_split(
                                    "/\r\n|\r|\n/",
                                    $requirements
                                );

                            foreach (
                                $requirement_lines
                                as $requirement
                            ):

                                $requirement =
                                    trim($requirement);

                                if (
                                    $requirement === ''
                                ) {
                                    continue;
                                }

                            ?>

                                <div class="requirement-item">

                                    <span class="requirement-check">

                                        <i class="fa-solid fa-check"></i>

                                    </span>

                                    <p>
                                        <?php
                                        echo htmlspecialchars(
                                            ltrim(
                                                $requirement,
                                                "•- "
                                            )
                                        );
                                        ?>
                                    </p>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php else: ?>

                        <div class="requirements-empty">

                            <i class="fa-regular fa-circle-info"></i>

                            <p>
                                No specific requirements have been provided for this internship.
                            </p>

                        </div>

                    <?php endif; ?>

                </article>


                <!-- COMPANY INFORMATION -->

                <article class="content-card company-card">

                    <div class="card-heading">

                        <div>

                            <span class="card-label">
                                About the Employer
                            </span>

                            <h2>
                                Company Information
                            </h2>

                        </div>

                        <div class="heading-icon">
                            <i class="fa-solid fa-building"></i>
                        </div>

                    </div>


                    <div class="company-profile">


                        <div class="company-profile-logo">

                            <?php if (!empty($internship['logo'])): ?>

                                <img
                                    src="<?php echo htmlspecialchars($internship['logo']); ?>"
                                    alt="<?php echo htmlspecialchars($internship['company_name']); ?>"
                                >

                            <?php else: ?>

                                <span>

                                    <?php
                                    echo strtoupper(
                                        substr(
                                            $internship['company_name'],
                                            0,
                                            1
                                        )
                                    );
                                    ?>

                                </span>

                            <?php endif; ?>

                        </div>


                        <div class="company-profile-info">

                            <h3>

                                <?php
                                echo htmlspecialchars(
                                    $internship['company_name']
                                );
                                ?>

                            </h3>


                            <?php if (!empty($internship['industry'])): ?>

                                <p class="company-industry">

                                    <i class="fa-solid fa-building-circle-check"></i>

                                    <?php
                                    echo htmlspecialchars(
                                        $internship['industry']
                                    );
                                    ?>

                                </p>

                            <?php endif; ?>


                            <?php if (!empty($internship['company_location'])): ?>

                                <p class="company-location">

                                    <i class="fa-solid fa-location-dot"></i>

                                    <?php
                                    echo htmlspecialchars(
                                        $internship['company_location']
                                    );
                                    ?>

                                </p>

                            <?php endif; ?>

                        </div>

                    </div>


                    <?php if (!empty($internship['company_description'])): ?>

                        <div class="company-description">

                            <?php

                            echo nl2br(
                                htmlspecialchars(
                                    $internship['company_description']
                                )
                            );

                            ?>

                        </div>

                    <?php endif; ?>


                    <?php if (!empty($company_website)): ?>

                        <a
                            href="<?php echo htmlspecialchars($company_website); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="company-website"
                        >

                            <i class="fa-solid fa-arrow-up-right-from-square"></i>

                            Visit Company Website

                        </a>

                    <?php endif; ?>

                </article>

            </div>


            <!-- ==================================================
                 RIGHT APPLICATION PANEL
            ================================================== -->

            <aside class="application-panel">


                <div class="application-card">


                    <span class="card-label">
                        Application
                    </span>


                    <h2>
                        Ready to apply?
                    </h2>


                    <p class="application-description">

                        Submit your application directly through InternConnect and track your application status from your student portal.

                    </p>


                    <!-- APPLICATION STATUS -->

                    <?php if ($application_status !== null): ?>

                        <div class="already-applied">

                            <div class="status-icon">

                                <?php if ($application_status === 'accepted'): ?>

                                    <i class="fa-solid fa-check"></i>

                                <?php elseif ($application_status === 'rejected'): ?>

                                    <i class="fa-solid fa-xmark"></i>

                                <?php else: ?>

                                    <i class="fa-solid fa-clock"></i>

                                <?php endif; ?>

                            </div>


                            <div>

                                <span>
                                    Application Status
                                </span>

                                <strong>

                                    <?php
                                    echo ucfirst(
                                        htmlspecialchars(
                                            $application_status
                                        )
                                    );
                                    ?>

                                </strong>

                            </div>

                        </div>


                        <a
                            href="student-applications.php"
                            class="application-status-button"
                        >

                            View My Applications

                            <i class="fa-solid fa-arrow-right"></i>

                        </a>


                    <?php elseif ($deadline_passed): ?>

                        <div class="deadline-warning">

                            <i class="fa-solid fa-calendar-xmark"></i>

                            <div>

                                <strong>
                                    Application Closed
                                </strong>

                                <p>
                                    The application deadline has passed.
                                </p>

                            </div>

                        </div>


                    <?php else: ?>

                        <a
                            href="student-apply.php?id=<?php echo $internship_id; ?>"
                            class="apply-button"
                        >

                            <span>
                                Apply Now
                            </span>

                            <i class="fa-solid fa-arrow-right"></i>

                        </a>


                        <p class="application-note">

                            <i class="fa-solid fa-circle-info"></i>

                            Make sure your profile and CV are complete before applying.

                        </p>

                    <?php endif; ?>


                </div>


                <!-- QUICK INFORMATION -->

                <div class="quick-card">

                    <div class="quick-heading">

                        <i class="fa-solid fa-circle-info"></i>

                        <h3>
                            Internship Summary
                        </h3>

                    </div>


                    <div class="quick-list">


                        <div class="quick-item">

                            <span>
                                Position
                            </span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $internship['title']
                                );
                                ?>
                            </strong>

                        </div>


                        <div class="quick-item">

                            <span>
                                Company
                            </span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $internship['company_name']
                                );
                                ?>
                            </strong>

                        </div>


                        <div class="quick-item">

                            <span>
                                Category
                            </span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $internship['category']
                                );
                                ?>
                            </strong>

                        </div>


                        <div class="quick-item">

                            <span>
                                Location
                            </span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $internship['location']
                                );
                                ?>
                            </strong>

                        </div>


                        <div class="quick-item">

                            <span>
                                Deadline
                            </span>

                            <strong
                                class="<?php echo $deadline_passed ? 'deadline-passed' : ''; ?>"
                            >
                                <?php
                                echo htmlspecialchars(
                                    $deadline_text
                                );
                                ?>
                            </strong>

                        </div>

                    </div>

                </div>


            </aside>

        </section>



    </main>

</div>


</body>
</html>
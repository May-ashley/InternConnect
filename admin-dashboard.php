<?php

session_start();

require_once "db.php";

$conn = connect();


// ==================================================
// ADMIN ACCESS PROTECTION
// ==================================================

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (
    !isset($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'admin'
) {
    header("Location: login.php");
    exit();
}


// ==================================================
// ADMIN INFORMATION
// ==================================================

$admin_name = $_SESSION['user_name'] ?? 'Admin';


// ==================================================
// DASHBOARD STATISTICS
// ==================================================

// Total Students
$sql = "SELECT COUNT(*) AS total FROM users WHERE role = 'student'";
$result = mysqli_query($conn, $sql);
$total_students = (int) mysqli_fetch_assoc($result)['total'];


// Total Companies
$sql = "SELECT COUNT(*) AS total FROM users WHERE role = 'company'";
$result = mysqli_query($conn, $sql);
$total_companies = (int) mysqli_fetch_assoc($result)['total'];


// Total Internships
$sql = "SELECT COUNT(*) AS total FROM internships";
$result = mysqli_query($conn, $sql);
$total_internships = (int) mysqli_fetch_assoc($result)['total'];


// Pending Internships
$sql = "
    SELECT COUNT(*) AS total
    FROM internships
    WHERE status = 'pending'
";
$result = mysqli_query($conn, $sql);
$pending_internships = (int) mysqli_fetch_assoc($result)['total'];


// Total Applications
$sql = "SELECT COUNT(*) AS total FROM applications";
$result = mysqli_query($conn, $sql);
$total_applications = (int) mysqli_fetch_assoc($result)['total'];


// Accepted Applications
$sql = "
    SELECT COUNT(*) AS total
    FROM applications
    WHERE status = 'accepted'
";
$result = mysqli_query($conn, $sql);
$accepted_applications = (int) mysqli_fetch_assoc($result)['total'];


// ==================================================
// RECENT PENDING INTERNSHIPS
// ==================================================

$sql = "
    SELECT
        internships.id,
        internships.title,
        internships.category,
        internships.location,
        internships.type,
        internships.created_at,
        companies.company_name
    FROM internships
    INNER JOIN companies
        ON internships.company_id = companies.id
    WHERE internships.status = 'pending'
    ORDER BY internships.created_at DESC
    LIMIT 5
";

$pending_result = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Dashboard | InternConnect</title>

    <!-- Google Font -->
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    >

    <!-- Admin Dashboard CSS -->
    <link
        rel="stylesheet"
        href="admin-dashboard.css"
    >

</head>


<body>

    <!-- Background -->
    <div class="bg-circle one"></div>
    <div class="bg-circle two"></div>


    <!-- ==================================================
         SIDEBAR
    ================================================== -->

    <aside class="sidebar">

        <!-- Logo -->

        <div class="sidebar-logo">

            <h2>
                Intern<span>Connect</span>
            </h2>

            <div class="admin-badge">

                <i class="fa-solid fa-shield-halved"></i>

                Administrator

            </div>

        </div>


        <!-- Navigation -->

        <nav class="sidebar-nav">

            <a
                href="admin-dashboard.php"
                class="active"
            >

                <i class="fa-solid fa-chart-pie"></i>

                <span>Dashboard</span>

            </a>


            <a href="admin-internships.php">

                <i class="fa-solid fa-briefcase"></i>

                <span>Internships</span>

                <?php if ($pending_internships > 0): ?>

                    <span class="nav-count">
                        <?php echo $pending_internships; ?>
                    </span>

                <?php endif; ?>

            </a>


            <a href="admin-companies.php">

                <i class="fa-solid fa-building"></i>

                <span>Companies</span>

            </a>


            <a href="admin-students.php">

                <i class="fa-solid fa-user-graduate"></i>

                <span>Students</span>

            </a>


            

        </nav>


        <!-- Bottom -->

        <div class="sidebar-bottom">

            <a
                href="index.php"
                class="home-link"
            >

                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to Website</span>

            </a>


            <a
                href="logout.php"
                class="logout-link"
            >

                <i class="fa-solid fa-right-from-bracket"></i>

                <span>Logout</span>

            </a>

        </div>

    </aside>


    <!-- ==================================================
         MAIN CONTENT
    ================================================== -->

    <main class="main">


        <!-- ==================================================
             HEADER
        ================================================== -->

        <header class="top-header">

            <div class="welcome-text">

                <span class="page-label">
                    Admin Panel
                </span>

                <h1>
                    Welcome back,
                    <span>
                        <?php echo htmlspecialchars($admin_name); ?>
                    </span>
                </h1>

                <p>
                    Here's what's happening on InternConnect today.
                </p>

            </div>


            <div class="admin-profile">

                <div class="admin-avatar">

                    <i class="fa-solid fa-shield-halved"></i>

                </div>

                <div class="admin-profile-info">

                    <strong>
                        <?php echo htmlspecialchars($admin_name); ?>
                    </strong>

                    <span>
                        Administrator
                    </span>

                </div>

            </div>

        </header>


        <!-- ==================================================
             STATISTICS
        ================================================== -->

        <section class="stats-grid">


            <!-- Students -->

            <div class="stat-card">

                <div class="stat-top">

                    <div>

                        <span class="stat-label">
                            Total Students
                        </span>

                        <div class="stat-number">
                            <?php echo $total_students; ?>
                        </div>

                    </div>

                    <div class="stat-icon">

                        <i class="fa-solid fa-user-graduate"></i>

                    </div>

                </div>

                <p class="stat-description">
                    Registered student accounts
                </p>

            </div>


            <!-- Companies -->

            <div class="stat-card">

                <div class="stat-top">

                    <div>

                        <span class="stat-label">
                            Total Companies
                        </span>

                        <div class="stat-number">
                            <?php echo $total_companies; ?>
                        </div>

                    </div>

                    <div class="stat-icon">

                        <i class="fa-solid fa-building"></i>

                    </div>

                </div>

                <p class="stat-description">
                    Registered company accounts
                </p>

            </div>


            <!-- Internships -->

            <div class="stat-card">

                <div class="stat-top">

                    <div>

                        <span class="stat-label">
                            Total Internships
                        </span>

                        <div class="stat-number">
                            <?php echo $total_internships; ?>
                        </div>

                    </div>

                    <div class="stat-icon">

                        <i class="fa-solid fa-briefcase"></i>

                    </div>

                </div>

                <p class="stat-description">
                    All internship posts
                </p>

            </div>


            <!-- Pending -->

            <div class="stat-card pending-card">

                <div class="stat-top">

                    <div>

                        <span class="stat-label">
                            Pending Internships
                        </span>

                        <div class="stat-number">
                            <?php echo $pending_internships; ?>
                        </div>

                    </div>

                    <div class="stat-icon">

                        <i class="fa-solid fa-clock"></i>

                    </div>

                </div>

                <p class="stat-description">
                    Waiting for approval
                </p>

            </div>


            <!-- Applications -->

            <div class="stat-card">

                <div class="stat-top">

                    <div>

                        <span class="stat-label">
                            Total Applications
                        </span>

                        <div class="stat-number">
                            <?php echo $total_applications; ?>
                        </div>

                    </div>

                    <div class="stat-icon">

                        <i class="fa-solid fa-file-lines"></i>

                    </div>

                </div>

                <p class="stat-description">
                    Student applications
                </p>

            </div>


            <!-- Accepted -->

            <div class="stat-card">

                <div class="stat-top">

                    <div>

                        <span class="stat-label">
                            Accepted Applications
                        </span>

                        <div class="stat-number">
                            <?php echo $accepted_applications; ?>
                        </div>

                    </div>

                    <div class="stat-icon">

                        <i class="fa-solid fa-circle-check"></i>

                    </div>

                </div>

                <p class="stat-description">
                    Successfully accepted
                </p>

            </div>


        </section>


        <!-- ==================================================
             LOWER CONTENT
        ================================================== -->

        <section class="content-grid">


            <!-- ==================================================
                 PENDING INTERNSHIPS
            ================================================== -->

            <div class="dashboard-card">

                <div class="card-header">

                    <div>

                        <h2>
                            Pending Internships
                        </h2>

                        <p>
                            Internship posts waiting for approval
                        </p>

                    </div>


                    <a
                        href="admin-internships.php"
                        class="view-all"
                    >
                        View All
                    </a>

                </div>


                <div class="pending-list">

                    <?php if (
                        $pending_result &&
                        mysqli_num_rows($pending_result) > 0
                    ): ?>


                        <?php while (
                            $internship = mysqli_fetch_assoc($pending_result)
                        ): ?>

                            <div class="internship-item">

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

                                        <span>
                                            <?php
                                            echo htmlspecialchars(
                                                $internship['company_name']
                                            );
                                            ?>
                                        </span>

                                        <span class="dot">
                                            •
                                        </span>

                                        <span>
                                            <?php
                                            echo htmlspecialchars(
                                                $internship['location']
                                            );
                                            ?>
                                        </span>

                                    </p>

                                </div>


                                <span class="pending-status">
                                    Pending
                                </span>

                            </div>

                        <?php endwhile; ?>


                    <?php else: ?>


                        <div class="empty-state">

                            <div class="empty-icon">

                                <i class="fa-solid fa-circle-check"></i>

                            </div>

                            <h3>
                                All caught up!
                            </h3>

                            <p>
                                There are no internships waiting for approval.
                            </p>

                        </div>


                    <?php endif; ?>

                </div>

            </div>


            <!-- ==================================================
                 QUICK ACTIONS
            ================================================== -->

            <div class="dashboard-card quick-card">

                <div class="card-header">

                    <div>

                        <h2>
                            Quick Actions
                        </h2>

                        <p>
                            Manage your platform
                        </p>

                    </div>

                </div>


                <div class="quick-actions">


                    <a
                        href="admin-internships.php"
                        class="quick-action"
                    >

                        <div class="quick-icon">

                            <i class="fa-solid fa-check"></i>

                        </div>

                        <div>

                            <strong>
                                Review Internships
                            </strong>

                            <span>
                                Approve pending posts
                            </span>

                        </div>

                        <i class="fa-solid fa-chevron-right arrow"></i>

                    </a>


                    <a
                        href="admin-companies.php"
                        class="quick-action"
                    >

                        <div class="quick-icon">

                            <i class="fa-solid fa-building"></i>

                        </div>

                        <div>

                            <strong>
                                Manage Companies
                            </strong>

                            <span>
                                View registered companies
                            </span>

                        </div>

                        <i class="fa-solid fa-chevron-right arrow"></i>

                    </a>


                    <a
                        href="admin-students.php"
                        class="quick-action"
                    >

                        <div class="quick-icon">

                            <i class="fa-solid fa-user-graduate"></i>

                        </div>

                        <div>

                            <strong>
                                Manage Students
                            </strong>

                            <span>
                                View student accounts
                            </span>

                        </div>

                        <i class="fa-solid fa-chevron-right arrow"></i>

                    </a>


                    <a
                        href="admin-applications.php"
                        class="quick-action"
                    >

                        <div class="quick-icon">

                            <i class="fa-solid fa-file-lines"></i>

                        </div>

                        <div>

                            <strong>
                                View Applications
                            </strong>

                            <span>
                                Monitor applications
                            </span>

                        </div>

                        <i class="fa-solid fa-chevron-right arrow"></i>

                    </a>


                </div>

            </div>


        </section>


    </main>


</body>

</html>
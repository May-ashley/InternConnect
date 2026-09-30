<?php

session_start();

require_once "db.php";

$conn = connect();

/* ==================================================
   ADMIN ACCESS PROTECTION
================================================== */

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


/* ==================================================
   GET INTERNSHIP ID
================================================== */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin-internships.php");
    exit();
}

$internship_id = (int) $_GET['id'];


/* ==================================================
   HANDLE ADMIN ACTIONS
================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    /* ---------- APPROVE ---------- */

    if ($action === 'approve') {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE internships
             SET status = 'active'
             WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt, "i", $internship_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header("Location: admin-internship-details.php?id=" . $internship_id . "&success=approved");
        exit();
    }


    /* ---------- CLOSE ---------- */

    if ($action === 'close') {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE internships
             SET status = 'closed'
             WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt, "i", $internship_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header("Location: admin-internship-details.php?id=" . $internship_id . "&success=closed");
        exit();
    }


    /* ---------- REOPEN ---------- */

    if ($action === 'reopen') {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE internships
             SET status = 'active'
             WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt, "i", $internship_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header("Location: admin-internship-details.php?id=" . $internship_id . "&success=reopened");
        exit();
    }


    /* ---------- DELETE ---------- */

    if ($action === 'delete') {

        $stmt = mysqli_prepare(
            $conn,
            "DELETE FROM internships
             WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt, "i", $internship_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header("Location: admin-internships.php?success=deleted");
        exit();
    }
}


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
        internships.skills,
        internships.application_deadline,
        internships.status,
        internships.created_at,

        companies.id AS company_id,
        companies.company_name,
        companies.description AS company_description,
        companies.industry,
        companies.location AS company_location,
        companies.website,
        companies.logo,

        users.name AS company_user_name,
        users.email AS company_email

    FROM internships

    INNER JOIN companies
        ON internships.company_id = companies.id

    LEFT JOIN users
        ON companies.user_id = users.id

    WHERE internships.id = ?

    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $internship_id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$internship = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* ==================================================
   IF NOT FOUND
================================================== */

if (!$internship) {
    header("Location: admin-internships.php");
    exit();
}


/* ==================================================
   ADMIN NAME
================================================== */

$admin_name = $_SESSION['user_name'] ?? 'Admin';


/* ==================================================
   STATUS
================================================== */

$status = strtolower($internship['status']);

$status_class = 'status-pending';

if ($status === 'active') {
    $status_class = 'status-active';
} elseif ($status === 'closed') {
    $status_class = 'status-closed';
}


/* ==================================================
   SUCCESS MESSAGE
================================================== */

$success_message = '';

if (isset($_GET['success'])) {

    if ($_GET['success'] === 'approved') {
        $success_message = 'Internship approved successfully.';
    }

    if ($_GET['success'] === 'closed') {
        $success_message = 'Internship closed successfully.';
    }

    if ($_GET['success'] === 'reopened') {
        $success_message = 'Internship reopened successfully.';
    }
}


/* ==================================================
   FORMAT DEADLINE
================================================== */

$deadline = 'Not specified';

if (!empty($internship['application_deadline'])) {
    $deadline = date(
        'F d, Y',
        strtotime($internship['application_deadline'])
    );
}


/* ==================================================
   FORMAT CREATED DATE
================================================== */

$created_date = 'Unknown';

if (!empty($internship['created_at'])) {
    $created_date = date(
        'F d, Y',
        strtotime($internship['created_at'])
    );
}


/* ==================================================
   SKILLS
================================================== */

$skills = [];

if (!empty($internship['skills'])) {

    $skills = array_filter(
        array_map(
            'trim',
            explode(',', $internship['skills'])
        )
    );
}


/* ==================================================
   REQUIREMENTS
================================================== */

$requirements = [];

if (!empty($internship['requirements'])) {

    $requirements = preg_split(
        '/\r\n|\r|\n/',
        $internship['requirements']
    );

    $requirements = array_filter(
        array_map('trim', $requirements)
    );
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
        Internship Details | Admin | InternConnect
    </title>

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

    <!-- Page CSS -->

    <link
        rel="stylesheet"
        href="admin-internship-details.css"
    >

</head>

<body>


<!-- ==================================================
     SIDEBAR
================================================== -->

<aside class="sidebar">

    <div class="sidebar-logo">

        <h2>
            Intern<span>Connect</span>
        </h2>

        <div class="admin-badge">

            <i class="fa-solid fa-shield-halved"></i>

            Administrator

        </div>

    </div>


    <nav class="sidebar-nav">

        <a href="admin-dashboard.php">

            <i class="fa-solid fa-chart-pie"></i>

            <span>Dashboard</span>

        </a>


        <a
            href="admin-internships.php"
            class="active"
        >

            <i class="fa-solid fa-briefcase"></i>

            <span>Internships</span>

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
     MAIN
================================================== -->

<main class="main">

    <!-- ==================================================
         BACK BUTTON
    ================================================== -->

    <a
        href="admin-internships.php"
        class="back-button"
    >

        <i class="fa-solid fa-arrow-left"></i>

        Back to Internships

    </a>



    <!-- ==================================================
         HEADER
    ================================================== -->

    <header class="top-header">

        <div class="welcome-text">

            <div class="page-label">
                INTERNSHIP MANAGEMENT
            </div>

            <h1>
                Internship <span>Details</span>
            </h1>

            <p>
                Review and manage this internship opportunity.
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
         SUCCESS MESSAGE
    ================================================== -->

    <?php if (!empty($success_message)): ?>

        <div class="success-message">

            <i class="fa-solid fa-circle-check"></i>

            <span>
                <?php echo htmlspecialchars($success_message); ?>
            </span>

        </div>

    <?php endif; ?>


    <!-- ==================================================
         INTERNSHIP HERO
    ================================================== -->

    <section class="internship-hero">

        <div class="hero-main">

            <div class="internship-icon-large">

                <i class="fa-solid fa-briefcase"></i>

            </div>


            <div class="hero-content">

                <div class="category-label">

                    <?php
                    echo htmlspecialchars(
                        $internship['category']
                    );
                    ?>

                </div>

                <h2>

                    <?php
                    echo htmlspecialchars(
                        $internship['title']
                    );
                    ?>

                </h2>

                <p class="company-name">

                    <i class="fa-solid fa-building"></i>

                    <?php
                    echo htmlspecialchars(
                        $internship['company_name']
                    );
                    ?>

                </p>

            </div>

        </div>


        <div class="hero-status">

            <span class="status-label">
                STATUS
            </span>

            <span class="status-badge <?php echo $status_class; ?>">

                <?php if ($status === 'active'): ?>

                    <i class="fa-solid fa-circle-check"></i>

                <?php elseif ($status === 'closed'): ?>

                    <i class="fa-solid fa-lock"></i>

                <?php else: ?>

                    <i class="fa-solid fa-clock"></i>

                <?php endif; ?>

                <?php echo ucfirst($status); ?>

            </span>

        </div>

    </section>


    <!-- ==================================================
         ACTION BAR
    ================================================== -->

    <section class="action-card">

        <div class="action-info">

            <div class="action-icon">

                <i class="fa-solid fa-sliders"></i>

            </div>

            <div>

                <h3>
                    Manage Internship
                </h3>

                <p>
                    Change the status or remove this internship.
                </p>

            </div>

        </div>


        <div class="action-buttons">


            <?php if ($status === 'pending'): ?>

                <form
                    method="POST"
                    class="action-form"
                    onsubmit="return confirm('Approve this internship?');"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="approve"
                    >

                    <button
                        type="submit"
                        class="btn btn-approve"
                    >

                        <i class="fa-solid fa-check"></i>

                        Approve

                    </button>

                </form>


                <form
                    method="POST"
                    class="action-form"
                    onsubmit="return confirm('Delete this internship? This action cannot be undone.');"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="delete"
                    >

                    <button
                        type="submit"
                        class="btn btn-delete"
                    >

                        <i class="fa-solid fa-trash"></i>

                        Delete

                    </button>

                </form>


            <?php elseif ($status === 'active'): ?>

                <form
                    method="POST"
                    class="action-form"
                    onsubmit="return confirm('Close this internship?');"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="close"
                    >

                    <button
                        type="submit"
                        class="btn btn-close"
                    >

                        <i class="fa-solid fa-lock"></i>

                        Close Internship

                    </button>

                </form>


                <form
                    method="POST"
                    class="action-form"
                    onsubmit="return confirm('Delete this internship? This action cannot be undone.');"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="delete"
                    >

                    <button
                        type="submit"
                        class="btn btn-delete"
                    >

                        <i class="fa-solid fa-trash"></i>

                        Delete

                    </button>

                </form>


            <?php elseif ($status === 'closed'): ?>

                <form
                    method="POST"
                    class="action-form"
                    onsubmit="return confirm('Reopen this internship?');"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="reopen"
                    >

                    <button
                        type="submit"
                        class="btn btn-reopen"
                    >

                        <i class="fa-solid fa-rotate-right"></i>

                        Reopen

                    </button>

                </form>


                <form
                    method="POST"
                    class="action-form"
                    onsubmit="return confirm('Delete this internship? This action cannot be undone.');"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="delete"
                    >

                    <button
                        type="submit"
                        class="btn btn-delete"
                    >

                        <i class="fa-solid fa-trash"></i>

                        Delete

                    </button>

                </form>

            <?php endif; ?>


        </div>

    </section>


    <!-- ==================================================
         QUICK INFORMATION
    ================================================== -->

    <section class="info-grid">


        <div class="info-box">

            <div class="info-box-icon">

                <i class="fa-solid fa-location-dot"></i>

            </div>

            <div>

                <span>Location</span>

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $internship['location']
                    );
                    ?>
                </strong>

            </div>

        </div>


        <div class="info-box">

            <div class="info-box-icon">

                <i class="fa-solid fa-clock"></i>

            </div>

            <div>

                <span>Internship Type</span>

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $internship['type']
                    );
                    ?>
                </strong>

            </div>

        </div>


        <div class="info-box">

            <div class="info-box-icon">

                <i class="fa-solid fa-calendar-days"></i>

            </div>

            <div>

                <span>Application Deadline</span>

                <strong>
                    <?php echo htmlspecialchars($deadline); ?>
                </strong>

            </div>

        </div>


        <div class="info-box">

            <div class="info-box-icon">

                <i class="fa-solid fa-calendar-plus"></i>

            </div>

            <div>

                <span>Posted On</span>

                <strong>
                    <?php echo htmlspecialchars($created_date); ?>
                </strong>

            </div>

        </div>


    </section>


    <!-- ==================================================
         CONTENT GRID
    ================================================== -->

    <section class="content-grid">


        <!-- ==================================================
             LEFT CONTENT
        ================================================== -->

        <div class="left-content">


            <!-- DESCRIPTION -->

            <div class="detail-card">

                <div class="card-header">

                    <div>

                        <span class="card-label">
                            ABOUT THE OPPORTUNITY
                        </span>

                        <h3>
                            Internship Description
                        </h3>

                    </div>

                    <div class="card-header-icon">

                        <i class="fa-solid fa-align-left"></i>

                    </div>

                </div>


                <div class="description-content">

                    <?php if (!empty($internship['description'])): ?>

                        <?php
                        $description_paragraphs = preg_split(
                            '/\r\n|\r|\n/',
                            $internship['description']
                        );

                        foreach ($description_paragraphs as $paragraph):
                            if (trim($paragraph) !== ''):
                        ?>

                            <p>
                                <?php
                                echo nl2br(
                                    htmlspecialchars(
                                        trim($paragraph)
                                    )
                                );
                                ?>
                            </p>

                        <?php
                            endif;
                        endforeach;
                        ?>

                    <?php else: ?>

                        <div class="empty-detail">

                            <i class="fa-regular fa-file-lines"></i>

                            <span>
                                No description provided.
                            </span>

                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <!-- REQUIREMENTS -->

            <div class="detail-card">

                <div class="card-header">

                    <div>

                        <span class="card-label">
                            CANDIDATE REQUIREMENTS
                        </span>

                        <h3>
                            Requirements
                        </h3>

                    </div>

                    <div class="card-header-icon">

                        <i class="fa-solid fa-list-check"></i>

                    </div>

                </div>


                <?php if (!empty($requirements)): ?>

                    <ul class="requirements-list">

                        <?php foreach ($requirements as $requirement): ?>

                            <li>

                                <span class="check-icon">

                                    <i class="fa-solid fa-check"></i>

                                </span>

                                <span>
                                    <?php
                                    echo htmlspecialchars(
                                        $requirement
                                    );
                                    ?>
                                </span>

                            </li>

                        <?php endforeach; ?>

                    </ul>

                <?php else: ?>

                    <div class="empty-detail">

                        <i class="fa-solid fa-list-check"></i>

                        <span>
                            No specific requirements provided.
                        </span>

                    </div>

                <?php endif; ?>

            </div>


            <!-- SKILLS -->

            <div class="detail-card">

                <div class="card-header">

                    <div>

                        <span class="card-label">
                            REQUIRED SKILLS
                        </span>

                        <h3>
                            Skills
                        </h3>

                    </div>

                    <div class="card-header-icon">

                        <i class="fa-solid fa-code"></i>

                    </div>

                </div>


                <?php if (!empty($skills)): ?>

                    <div class="skills-list">

                        <?php foreach ($skills as $skill): ?>

                            <span class="skill-tag">

                                <i class="fa-solid fa-circle"></i>

                                <?php
                                echo htmlspecialchars($skill);
                                ?>

                            </span>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="empty-detail">

                        <i class="fa-solid fa-code"></i>

                        <span>
                            No skills specified.
                        </span>

                    </div>

                <?php endif; ?>

            </div>


        </div>


        <!-- ==================================================
             RIGHT CONTENT
        ================================================== -->

        <div class="right-content">


            <!-- COMPANY CARD -->

            <div class="detail-card company-card">

                <div class="card-header">

                    <div>

                        <span class="card-label">
                            POSTED BY
                        </span>

                        <h3>
                            Company
                        </h3>

                    </div>

                    <div class="card-header-icon">

                        <i class="fa-solid fa-building"></i>

                    </div>

                </div>


                <div class="company-profile">


                    <div class="company-logo">

                        <?php if (!empty($internship['logo'])): ?>

                            <img
                                src="<?php echo htmlspecialchars($internship['logo']); ?>"
                                alt="Company Logo"
                            >

                        <?php else: ?>

                            <i class="fa-solid fa-building"></i>

                        <?php endif; ?>

                    </div>


                    <div class="company-details">

                        <h4>
                            <?php
                            echo htmlspecialchars(
                                $internship['company_name']
                            );
                            ?>
                        </h4>

                        <?php if (!empty($internship['industry'])): ?>

                            <span>

                                <i class="fa-solid fa-layer-group"></i>

                                <?php
                                echo htmlspecialchars(
                                    $internship['industry']
                                );
                                ?>

                            </span>

                        <?php endif; ?>

                    </div>

                </div>


                <div class="company-information">


                    <?php if (!empty($internship['company_location'])): ?>

                        <div class="company-info-row">

                            <i class="fa-solid fa-location-dot"></i>

                            <div>

                                <span>Location</span>

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $internship['company_location']
                                    );
                                    ?>
                                </strong>

                            </div>

                        </div>

                    <?php endif; ?>


                    <?php if (!empty($internship['company_email'])): ?>

                        <div class="company-info-row">

                            <i class="fa-solid fa-envelope"></i>

                            <div>

                                <span>Email</span>

                                <strong class="break-text">
                                    <?php
                                    echo htmlspecialchars(
                                        $internship['company_email']
                                    );
                                    ?>
                                </strong>

                            </div>

                        </div>

                    <?php endif; ?>


                    <?php if (!empty($internship['website'])): ?>

                        <div class="company-info-row">

                            <i class="fa-solid fa-globe"></i>

                            <div>

                                <span>Website</span>

                                <a
                                    href="<?php echo htmlspecialchars($internship['website']); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    Visit Website
                                </a>

                            </div>

                        </div>

                    <?php endif; ?>


                </div>

            </div>


            <!-- ACCOUNT CARD -->

            <div class="detail-card account-card">

                <div class="card-header">

                    <div>

                        <span class="card-label">
                            COMPANY ACCOUNT
                        </span>

                        <h3>
                            Account Information
                        </h3>

                    </div>

                    <div class="card-header-icon">

                        <i class="fa-solid fa-user"></i>

                    </div>

                </div>


                <div class="account-row">

                    <div class="account-icon">

                        <i class="fa-solid fa-user"></i>

                    </div>

                    <div>

                        <span>Account Name</span>

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $internship['company_user_name']
                                ?: 'Not available'
                            );
                            ?>
                        </strong>

                    </div>

                </div>


                <div class="account-row">

                    <div class="account-icon">

                        <i class="fa-solid fa-envelope"></i>

                    </div>

                    <div>

                        <span>Email Address</span>

                        <strong class="break-text">

                            <?php
                            echo htmlspecialchars(
                                $internship['company_email']
                                ?: 'Not available'
                            );
                            ?>

                        </strong>

                    </div>

                </div>

            </div>


            <!-- ADMIN NOTE -->

            <div class="admin-note">

                <div class="admin-note-icon">

                    <i class="fa-solid fa-shield-halved"></i>

                </div>

                <div>

                    <h4>
                        Administrator Control
                    </h4>

                    <p>
                        Only approved internships with an
                        <strong>Active</strong> status are visible
                        to students on the public website.
                    </p>

                </div>

            </div>


        </div>


    </section>


</main>


</body>

</html>
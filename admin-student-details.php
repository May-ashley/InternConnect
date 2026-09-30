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

$admin_name = $_SESSION['user_name'] ?? 'Admin';


/* ==================================================
   GET STUDENT ID
================================================== */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin-students.php");
    exit();
}

$student_id = (int) $_GET['id'];


/* ==================================================
   GET STUDENT INFORMATION
================================================== */

$sql = "
    SELECT
        u.id,
        u.name,
        u.email,
        u.created_at,

        sp.phone,
        sp.university,
        sp.major,
        sp.year_of_study,
        sp.portfolio_url,
        sp.linkedin_url

    FROM users u

    LEFT JOIN students_profile sp
        ON u.id = sp.user_id

    WHERE u.id = ?
    AND u.role = 'student'

    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database error.");
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $student_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$student = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* ==================================================
   STUDENT NOT FOUND
================================================== */

if (!$student) {
    header("Location: admin-students.php");
    exit();
}


/* ==================================================
   APPLICATION STATISTICS
================================================== */

$total_applications = 0;
$pending_applications = 0;
$accepted_applications = 0;
$rejected_applications = 0;


/* Total */

$stmt = mysqli_prepare(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM applications
    WHERE student_id = ?
    "
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $student_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$row = mysqli_fetch_assoc($result);

$total_applications = (int) $row['total'];

mysqli_stmt_close($stmt);


/* Pending */

$stmt = mysqli_prepare(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM applications
    WHERE student_id = ?
    AND status = 'pending'
    "
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $student_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$row = mysqli_fetch_assoc($result);

$pending_applications = (int) $row['total'];

mysqli_stmt_close($stmt);


/* Accepted */

$stmt = mysqli_prepare(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM applications
    WHERE student_id = ?
    AND status = 'accepted'
    "
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $student_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$row = mysqli_fetch_assoc($result);

$accepted_applications = (int) $row['total'];

mysqli_stmt_close($stmt);


/* Rejected */

$stmt = mysqli_prepare(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM applications
    WHERE student_id = ?
    AND status = 'rejected'
    "
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $student_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$row = mysqli_fetch_assoc($result);

$rejected_applications = (int) $row['total'];

mysqli_stmt_close($stmt);


/* ==================================================
   GET STUDENT APPLICATIONS
================================================== */

$applications_sql = "
    SELECT
        a.id,
        a.cover_letter,
        a.resume,
        a.status,
        a.applied_at,

        i.id AS internship_id,
        i.title AS internship_title,
        i.category,
        i.location,
        i.type,

        c.company_name

    FROM applications a

    INNER JOIN internships i
        ON a.internship_id = i.id

    INNER JOIN companies c
        ON i.company_id = c.id

    WHERE a.student_id = ?

    ORDER BY a.applied_at DESC
";

$stmt = mysqli_prepare(
    $conn,
    $applications_sql
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $student_id
);

mysqli_stmt_execute($stmt);

$applications_result = mysqli_stmt_get_result($stmt);

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
        Student Details | Admin | InternConnect
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


    <!-- CSS -->

    <link
        rel="stylesheet"
        href="admin-student-details.css"
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


        <a href="admin-internships.php">

            <i class="fa-solid fa-briefcase"></i>

            <span>Internships</span>

        </a>


        <a href="admin-companies.php">

            <i class="fa-solid fa-building"></i>

            <span>Companies</span>

        </a>


        <a
            href="admin-students.php"
            class="active"
        >

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
         TOP HEADER
    ================================================== -->

    <header class="top-header">


        <div class="header-left">


            <a
                href="admin-students.php"
                class="back-button"
            >

                <i class="fa-solid fa-arrow-left"></i>

                Back to Students

            </a>


            <div class="page-label">
                STUDENT PROFILE
            </div>


            <h1>
                Student <span>Details</span>
            </h1>


            <p>
                View the student's account, profile information,
                and internship application activity.
            </p>


        </div>


        <div class="admin-profile">


            <div class="admin-avatar">

                <i class="fa-solid fa-shield-halved"></i>

            </div>


            <div class="admin-profile-info">

                <strong>
                    <?php
                    echo htmlspecialchars($admin_name);
                    ?>
                </strong>


                <span>
                    Administrator
                </span>


            </div>


        </div>


    </header>


    <!-- ==================================================
         STUDENT HERO
    ================================================== -->

    <section class="student-hero">


        <div class="hero-avatar">

            <?php

            $student_name =
                trim($student['name'] ?? '');

            if (!empty($student_name)) {

                echo strtoupper(
                    substr(
                        $student_name,
                        0,
                        1
                    )
                );

            } else {

                echo 'S';

            }

            ?>

        </div>


        <div class="hero-info">


            <div class="hero-label">

                <i class="fa-solid fa-user-graduate"></i>

                STUDENT ACCOUNT

            </div>


            <h2>
                <?php
                echo htmlspecialchars(
                    $student['name']
                );
                ?>
            </h2>


            <div class="hero-email">

                <i class="fa-solid fa-envelope"></i>

                <?php
                echo htmlspecialchars(
                    $student['email']
                );
                ?>

            </div>


            <div class="joined-date">

                <i class="fa-regular fa-calendar"></i>

                Joined

                <?php

                echo date(
                    "M d, Y",
                    strtotime(
                        $student['created_at']
                    )
                );

                ?>

            </div>


        </div>


        <div class="profile-status">

            <?php if (
                !empty($student['university']) ||
                !empty($student['major']) ||
                !empty($student['phone'])
            ): ?>

                <span class="complete-status">

                    <i class="fa-solid fa-circle-check"></i>

                    Profile Available

                </span>

            <?php else: ?>

                <span class="incomplete-status">

                    <i class="fa-solid fa-circle"></i>

                    Profile Incomplete

                </span>

            <?php endif; ?>

        </div>


    </section>


    <!-- ==================================================
         APPLICATION STATS
    ================================================== -->

    <section class="stats-grid">


        <div class="stat-card">


            <div class="stat-top">

                <div>

                    <div class="stat-label">
                        Applications
                    </div>

                    <div class="stat-number">
                        <?php
                        echo $total_applications;
                        ?>
                    </div>

                </div>


                <div class="stat-icon applications-icon">

                    <i class="fa-solid fa-file-lines"></i>

                </div>

            </div>


            <div class="stat-description">
                Total submitted applications
            </div>


        </div>


        <div class="stat-card">


            <div class="stat-top">

                <div>

                    <div class="stat-label">
                        Pending
                    </div>

                    <div class="stat-number">
                        <?php
                        echo $pending_applications;
                        ?>
                    </div>

                </div>


                <div class="stat-icon pending-icon">

                    <i class="fa-solid fa-clock"></i>

                </div>

            </div>


            <div class="stat-description">
                Applications awaiting review
            </div>


        </div>


        <div class="stat-card">


            <div class="stat-top">

                <div>

                    <div class="stat-label">
                        Accepted
                    </div>

                    <div class="stat-number">
                        <?php
                        echo $accepted_applications;
                        ?>
                    </div>

                </div>


                <div class="stat-icon accepted-icon">

                    <i class="fa-solid fa-circle-check"></i>

                </div>

            </div>


            <div class="stat-description">
                Successful applications
            </div>


        </div>


        <div class="stat-card">


            <div class="stat-top">

                <div>

                    <div class="stat-label">
                        Rejected
                    </div>

                    <div class="stat-number">
                        <?php
                        echo $rejected_applications;
                        ?>
                    </div>

                </div>


                <div class="stat-icon rejected-icon">

                    <i class="fa-solid fa-circle-xmark"></i>

                </div>

            </div>


            <div class="stat-description">
                Applications not accepted
            </div>


        </div>


    </section>


    <!-- ==================================================
         PROFILE + ACCOUNT
    ================================================== -->

    <section class="content-grid">


        <!-- PROFILE INFORMATION -->

        <div class="dashboard-card">


            <div class="card-header">


                <div>

                    <span class="card-label">
                        PERSONAL INFORMATION
                    </span>

                    <h2>
                        Student Profile
                    </h2>

                </div>


                <div class="card-icon">

                    <i class="fa-solid fa-user-graduate"></i>

                </div>


            </div>


            <div class="profile-grid">


                <div class="info-box">

                    <div class="info-icon">

                        <i class="fa-solid fa-user"></i>

                    </div>

                    <div>

                        <span>
                            Full Name
                        </span>

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $student['name']
                            );
                            ?>
                        </strong>

                    </div>

                </div>


                <div class="info-box">

                    <div class="info-icon">

                        <i class="fa-solid fa-phone"></i>

                    </div>

                    <div>

                        <span>
                            Phone
                        </span>

                        <strong>

                            <?php

                            echo !empty(
                                $student['phone']
                            )
                                ? htmlspecialchars(
                                    $student['phone']
                                )
                                : 'Not provided';

                            ?>

                        </strong>

                    </div>

                </div>


                <div class="info-box">

                    <div class="info-icon">

                        <i class="fa-solid fa-school"></i>

                    </div>

                    <div>

                        <span>
                            University
                        </span>

                        <strong>

                            <?php

                            echo !empty(
                                $student['university']
                            )
                                ? htmlspecialchars(
                                    $student['university']
                                )
                                : 'Not provided';

                            ?>

                        </strong>

                    </div>

                </div>


                <div class="info-box">

                    <div class="info-icon">

                        <i class="fa-solid fa-book-open"></i>

                    </div>

                    <div>

                        <span>
                            Major
                        </span>

                        <strong>

                            <?php

                            echo !empty(
                                $student['major']
                            )
                                ? htmlspecialchars(
                                    $student['major']
                                )
                                : 'Not provided';

                            ?>

                        </strong>

                    </div>

                </div>


                <div class="info-box">

                    <div class="info-icon">

                        <i class="fa-solid fa-graduation-cap"></i>

                    </div>

                    <div>

                        <span>
                            Year of Study
                        </span>

                        <strong>

                            <?php

                            echo !empty(
                                $student['year_of_study']
                            )
                                ? htmlspecialchars(
                                    $student['year_of_study']
                                )
                                : 'Not provided';

                            ?>

                        </strong>

                    </div>

                </div>


            </div>


            <!-- LINKS -->

            <div class="profile-links">


                <div class="link-row">

                    <div class="link-icon">

                        <i class="fa-solid fa-globe"></i>

                    </div>


                    <div class="link-content">

                        <span>
                            Portfolio
                        </span>


                        <?php if (
                            !empty(
                                $student['portfolio_url']
                            )
                        ): ?>

                            <?php if (
                                filter_var(
                                    $student['portfolio_url'],
                                    FILTER_VALIDATE_URL
                                )
                            ): ?>

                                <a
                                    href="<?php
                                    echo htmlspecialchars(
                                        $student[
                                            'portfolio_url'
                                        ]
                                    );
                                    ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >

                                    View Portfolio

                                    <i
                                        class="fa-solid fa-arrow-up-right-from-square"
                                    ></i>

                                </a>

                            <?php else: ?>

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $student[
                                            'portfolio_url'
                                        ]
                                    );
                                    ?>
                                </strong>

                            <?php endif; ?>

                        <?php else: ?>

                            <strong class="not-provided">
                                Not provided
                            </strong>

                        <?php endif; ?>

                    </div>

                </div>


                <div class="link-row">

                    <div class="link-icon linkedin-icon">

                        <i class="fa-brands fa-linkedin-in"></i>

                    </div>


                    <div class="link-content">

                        <span>
                            LinkedIn
                        </span>


                        <?php if (
                            !empty(
                                $student['linkedin_url']
                            )
                        ): ?>

                            <?php if (
                                filter_var(
                                    $student['linkedin_url'],
                                    FILTER_VALIDATE_URL
                                )
                            ): ?>

                                <a
                                    href="<?php
                                    echo htmlspecialchars(
                                        $student[
                                            'linkedin_url'
                                        ]
                                    );
                                    ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >

                                    View LinkedIn

                                    <i
                                        class="fa-solid fa-arrow-up-right-from-square"
                                    ></i>

                                </a>

                            <?php else: ?>

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $student[
                                            'linkedin_url'
                                        ]
                                    );
                                    ?>
                                </strong>

                            <?php endif; ?>

                        <?php else: ?>

                            <strong class="not-provided">
                                Not provided
                            </strong>

                        <?php endif; ?>

                    </div>

                </div>


            </div>


        </div>


        <!-- ACCOUNT DETAILS -->

        <div class="dashboard-card account-card">


            <div class="card-header">


                <div>

                    <span class="card-label">
                        ACCOUNT
                    </span>

                    <h2>
                        Account Details
                    </h2>

                </div>


                <div class="card-icon">

                    <i class="fa-solid fa-id-card"></i>

                </div>


            </div>


            <div class="account-profile">


                <div class="account-avatar">

                    <?php

                    $initial =
                        !empty($student['name'])
                            ? strtoupper(
                                substr(
                                    trim(
                                        $student['name']
                                    ),
                                    0,
                                    1
                                )
                            )
                            : 'S';

                    echo htmlspecialchars(
                        $initial
                    );

                    ?>

                </div>


                <h3>

                    <?php
                    echo htmlspecialchars(
                        $student['name']
                    );
                    ?>

                </h3>


                <span>

                    <i class="fa-solid fa-user-graduate"></i>

                    Student Account

                </span>


            </div>


            <div class="account-details-list">


                <div class="account-detail">

                    <div class="detail-icon">

                        <i class="fa-solid fa-envelope"></i>

                    </div>


                    <div>

                        <span>
                            Email
                        </span>

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $student['email']
                            );
                            ?>
                        </strong>

                    </div>

                </div>


                <div class="account-detail">

                    <div class="detail-icon">

                        <i class="fa-regular fa-calendar"></i>

                    </div>


                    <div>

                        <span>
                            Account Created
                        </span>

                        <strong>

                            <?php

                            echo date(
                                "M d, Y",
                                strtotime(
                                    $student['created_at']
                                )
                            );

                            ?>

                        </strong>

                    </div>

                </div>


            </div>


        </div>


    </section>


    <!-- ==================================================
         APPLICATIONS
    ================================================== -->

    <section class="dashboard-card applications-card">


        <div class="card-header">


            <div>

                <span class="card-label">
                    APPLICATION HISTORY
                </span>

                <h2>
                    Internship Applications
                </h2>

            </div>


            <div class="application-total">

                <i class="fa-solid fa-file-lines"></i>

                <?php
                echo $total_applications;
                ?>

                Application<?php
                echo $total_applications !== 1
                    ? 's'
                    : '';
                ?>

            </div>


        </div>


        <div class="applications-list">


            <?php if (
                $applications_result &&
                mysqli_num_rows(
                    $applications_result
                ) > 0
            ): ?>


                <?php while (
                    $application =
                    mysqli_fetch_assoc(
                        $applications_result
                    )
                ): ?>


                    <div class="application-item">


                        <div class="application-main">


                            <div class="application-icon-box">

                                <i
                                    class="fa-solid fa-briefcase"
                                ></i>

                            </div>


                            <div class="application-info">


                                <h3>

                                    <?php
                                    echo htmlspecialchars(
                                        $application[
                                            'internship_title'
                                        ]
                                    );
                                    ?>

                                </h3>


                                <p>

                                    <i
                                        class="fa-solid fa-building"
                                    ></i>

                                    <?php
                                    echo htmlspecialchars(
                                        $application[
                                            'company_name'
                                        ]
                                    );
                                    ?>

                                </p>


                                <div class="application-meta">


                                    <span>

                                        <i
                                            class="fa-solid fa-location-dot"
                                        ></i>

                                        <?php
                                        echo htmlspecialchars(
                                            $application[
                                                'location'
                                            ]
                                        );
                                        ?>

                                    </span>


                                    <span>

                                        <i
                                            class="fa-solid fa-layer-group"
                                        ></i>

                                        <?php
                                        echo htmlspecialchars(
                                            $application[
                                                'category'
                                            ]
                                        );
                                        ?>

                                    </span>


                                    <span>

                                        <i
                                            class="fa-solid fa-calendar"
                                        ></i>

                                        <?php

                                        echo date(
                                            "M d, Y",
                                            strtotime(
                                                $application[
                                                    'applied_at'
                                                ]
                                            )
                                        );

                                        ?>

                                    </span>


                                </div>


                            </div>


                        </div>


                        <div class="application-right">


                            <?php

                            $status =
                                strtolower(
                                    $application[
                                        'status'
                                    ]
                                );

                            ?>


                            <span
                                class="status-badge
                                <?php
                                echo htmlspecialchars(
                                    $status
                                );
                                ?>"
                            >

                                <?php if (
                                    $status === 'accepted'
                                ): ?>

                                    <i
                                        class="fa-solid fa-circle-check"
                                    ></i>

                                <?php elseif (
                                    $status === 'rejected'
                                ): ?>

                                    <i
                                        class="fa-solid fa-circle-xmark"
                                    ></i>

                                <?php else: ?>

                                    <i
                                        class="fa-solid fa-clock"
                                    ></i>

                                <?php endif; ?>


                                <?php
                                echo ucfirst(
                                    $status
                                );
                                ?>

                            </span>


                            <a
                                href="admin-internship-details.php?id=<?php
                                echo (int)
                                    $application[
                                        'internship_id'
                                    ];
                                ?>"
                                class="internship-link"
                            >

                                View Internship

                                <i
                                    class="fa-solid fa-arrow-right"
                                ></i>

                            </a>


                        </div>


                    </div>


                <?php endwhile; ?>


            <?php else: ?>


                <div class="empty-state">


                    <div class="empty-icon">

                        <i
                            class="fa-solid fa-file-circle-xmark"
                        ></i>

                    </div>


                    <h3>
                        No Applications Yet
                    </h3>


                    <p>
                        This student has not submitted
                        any internship applications.
                    </p>


                </div>


            <?php endif; ?>


        </div>


    </section>


</main>


</body>

</html>

<?php

mysqli_stmt_close($stmt);

?>
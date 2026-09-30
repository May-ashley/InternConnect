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
   STUDENT STATISTICS
================================================== */

/* Total students */

$total_students = 0;

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'student'"
);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $total_students = (int) $row['total'];
}


/* Students with profiles */

$profiled_students = 0;

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users u
     INNER JOIN students_profile sp
        ON u.id = sp.user_id
     WHERE u.role = 'student'"
);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $profiled_students = (int) $row['total'];
}


/* Students with applications */

$students_with_applications = 0;

$result = mysqli_query(
    $conn,
    "SELECT COUNT(DISTINCT student_id) AS total
     FROM applications"
);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $students_with_applications = (int) $row['total'];
}


/* ==================================================
   GET STUDENTS
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
        sp.linkedin_url,

        COUNT(DISTINCT a.id) AS application_count

    FROM users u

    LEFT JOIN students_profile sp
        ON u.id = sp.user_id

    LEFT JOIN applications a
        ON u.id = a.student_id

    WHERE u.role = 'student'

    GROUP BY
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

    ORDER BY u.created_at DESC
";

$students_result = mysqli_query($conn, $sql);

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
        Students | Admin | InternConnect
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
        href="admin-students.css"
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
         HEADER
    ================================================== -->

    <header class="top-header">


        <div class="welcome-text">


            <div class="page-label">
                STUDENT MANAGEMENT
            </div>


            <h1>
                Manage <span>Students</span>
            </h1>


            <p>
                View registered students, their profiles,
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
         STATISTICS
    ================================================== -->

    <section class="stats-grid">


        <!-- TOTAL STUDENTS -->

        <div class="stat-card">


            <div class="stat-top">


                <div>

                    <div class="stat-label">
                        Total Students
                    </div>


                    <div class="stat-number">
                        <?php
                        echo $total_students;
                        ?>
                    </div>

                </div>


                <div class="stat-icon student-icon">

                    <i class="fa-solid fa-user-graduate"></i>

                </div>


            </div>


            <div class="stat-description">
                Registered student accounts
            </div>


        </div>


        <!-- PROFILED STUDENTS -->

        <div class="stat-card">


            <div class="stat-top">


                <div>

                    <div class="stat-label">
                        Completed Profiles
                    </div>


                    <div class="stat-number">
                        <?php
                        echo $profiled_students;
                        ?>
                    </div>

                </div>


                <div class="stat-icon profile-icon">

                    <i class="fa-solid fa-id-card"></i>

                </div>


            </div>


            <div class="stat-description">
                Students with profile information
            </div>


        </div>


        <!-- STUDENTS WITH APPLICATIONS -->

        <div class="stat-card">


            <div class="stat-top">


                <div>

                    <div class="stat-label">
                        Active Applicants
                    </div>


                    <div class="stat-number">
                        <?php
                        echo $students_with_applications;
                        ?>
                    </div>

                </div>


                <div class="stat-icon application-icon">

                    <i class="fa-solid fa-file-lines"></i>

                </div>


            </div>


            <div class="stat-description">
                Students who submitted applications
            </div>


        </div>


    </section>


    <!-- ==================================================
         STUDENTS CARD
    ================================================== -->

    <section class="students-card">


        <div class="card-header">


            <div>


                <span class="card-label">
                    REGISTERED STUDENTS
                </span>


                <h2>
                    All Students
                </h2>


            </div>


            <div class="student-total">

                <i class="fa-solid fa-user-graduate"></i>

                <?php
                echo $total_students;
                ?>

                Student<?php
                echo $total_students !== 1
                    ? 's'
                    : '';
                ?>

            </div>


        </div>


        <!-- ==================================================
             STUDENT LIST
        ================================================== -->

        <div class="students-list">


            <?php if (
                $students_result &&
                mysqli_num_rows($students_result) > 0
            ): ?>


                <?php while (
                    $student =
                    mysqli_fetch_assoc($students_result)
                ): ?>


                    <div class="student-item">


                        <!-- STUDENT AVATAR -->

                        <div class="student-avatar">

                            <span>

                                <?php

                                $student_name =
                                    trim(
                                        $student['name'] ?? ''
                                    );

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

                            </span>

                        </div>


                        <!-- STUDENT INFORMATION -->

                        <div class="student-info">


                            <div class="student-title-row">


                                <h3>

                                    <?php
                                    echo htmlspecialchars(
                                        $student['name']
                                    );
                                    ?>

                                </h3>


                                <?php if (
                                    !empty(
                                        $student['university']
                                    )
                                ): ?>

                                    <span class="profile-badge">

                                        <i
                                            class="fa-solid fa-circle-check"
                                        ></i>

                                        Profiled

                                    </span>

                                <?php else: ?>

                                    <span class="incomplete-badge">

                                        <i
                                            class="fa-solid fa-circle"
                                        ></i>

                                        Profile Incomplete

                                    </span>

                                <?php endif; ?>


                            </div>


                            <div class="student-email">

                                <i
                                    class="fa-solid fa-envelope"
                                ></i>

                                <?php
                                echo htmlspecialchars(
                                    $student['email']
                                );
                                ?>

                            </div>


                            <div class="student-meta">


                                <?php if (
                                    !empty(
                                        $student['university']
                                    )
                                ): ?>

                                    <span>

                                        <i
                                            class="fa-solid fa-school"
                                        ></i>

                                        <?php
                                        echo htmlspecialchars(
                                            $student['university']
                                        );
                                        ?>

                                    </span>

                                <?php endif; ?>


                                <?php if (
                                    !empty(
                                        $student['major']
                                    )
                                ): ?>

                                    <span>

                                        <i
                                            class="fa-solid fa-book-open"
                                        ></i>

                                        <?php
                                        echo htmlspecialchars(
                                            $student['major']
                                        );
                                        ?>

                                    </span>

                                <?php endif; ?>


                                <?php if (
                                    !empty(
                                        $student['year_of_study']
                                    )
                                ): ?>

                                    <span>

                                        <i
                                            class="fa-solid fa-graduation-cap"
                                        ></i>

                                        <?php
                                        echo htmlspecialchars(
                                            $student['year_of_study']
                                        );
                                        ?>

                                    </span>

                                <?php endif; ?>


                            </div>


                        </div>


                        <!-- APPLICATION COUNT -->

                        <div class="application-box">


                            <div class="application-icon">

                                <i
                                    class="fa-solid fa-file-lines"
                                ></i>

                            </div>


                            <div>


                                <strong>
                                    <?php
                                    echo (int)
                                        $student[
                                            'application_count'
                                        ];
                                    ?>
                                </strong>


                                <span>
                                    Application<?php
                                    echo (
                                        (int)
                                        $student[
                                            'application_count'
                                        ] !== 1
                                    )
                                        ? 's'
                                        : '';
                                    ?>
                                </span>


                            </div>


                        </div>


                        <!-- VIEW BUTTON -->

                        <a
                            href="admin-student-details.php?id=<?php
                            echo (int) $student['id'];
                            ?>"
                            class="view-button"
                        >

                            <span>
                                View Details
                            </span>


                            <i
                                class="fa-solid fa-arrow-right"
                            ></i>


                        </a>


                    </div>


                <?php endwhile; ?>


            <?php else: ?>


                <div class="empty-state">


                    <div class="empty-icon">

                        <i
                            class="fa-solid fa-user-graduate"
                        ></i>

                    </div>


                    <h3>
                        No Students Found
                    </h3>


                    <p>
                        There are currently no registered
                        student accounts.
                    </p>


                </div>


            <?php endif; ?>


        </div>


    </section>


</main>


</body>

</html>
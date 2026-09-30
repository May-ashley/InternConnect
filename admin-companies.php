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
   DELETE COMPANY
================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'delete_company') {

        $company_id = isset($_POST['company_id'])
            ? (int) $_POST['company_id']
            : 0;

        if ($company_id > 0) {

            /*
             * Find the company user account first.
             */
            $stmt = mysqli_prepare(
                $conn,
                "SELECT user_id
                 FROM companies
                 WHERE id = ?
                 LIMIT 1"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $company_id
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            $company = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);


            if ($company) {

                $company_user_id = (int) $company['user_id'];


                /*
                 * Delete the company account.
                 *
                 * Because your database uses ON DELETE CASCADE,
                 * this will also remove:
                 * - company profile
                 * - internships belonging to company
                 * - applications belonging to those internships
                 */
                $stmt = mysqli_prepare(
                    $conn,
                    "DELETE FROM users
                     WHERE id = ?
                     AND role = 'company'"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "i",
                    $company_user_id
                );

                mysqli_stmt_execute($stmt);

                mysqli_stmt_close($stmt);
            }
        }

        header("Location: admin-companies.php?success=deleted");
        exit();
    }
}


/* ==================================================
   SUCCESS MESSAGE
================================================== */

$success_message = '';

if (
    isset($_GET['success']) &&
    $_GET['success'] === 'deleted'
) {
    $success_message = 'Company account deleted successfully.';
}


/* ==================================================
   COMPANY STATISTICS
================================================== */

$total_companies = 0;
$total_internships = 0;
$active_internships = 0;


/* Total Companies */

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM companies"
);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $total_companies = (int) $row['total'];
}


/* Total Internships */

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM internships"
);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $total_internships = (int) $row['total'];
}


/* Active Internships */

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM internships
     WHERE status = 'active'"
);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $active_internships = (int) $row['total'];
}


/* ==================================================
   GET COMPANIES
================================================== */

$sql = "
    SELECT
        companies.id,
        companies.company_name,
        companies.description,
        companies.industry,
        companies.location,
        companies.website,
        companies.logo,

        users.name AS account_name,
        users.email AS account_email,

        COUNT(internships.id) AS internship_count,

        SUM(
            CASE
                WHEN internships.status = 'active'
                THEN 1
                ELSE 0
            END
        ) AS active_internship_count

    FROM companies

    INNER JOIN users
        ON companies.user_id = users.id

    LEFT JOIN internships
        ON companies.id = internships.company_id

    GROUP BY
        companies.id,
        companies.company_name,
        companies.description,
        companies.industry,
        companies.location,
        companies.website,
        companies.logo,
        users.name,
        users.email

    ORDER BY companies.company_name ASC
";

$result = mysqli_query($conn, $sql);

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
        Companies | Admin | InternConnect
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
        href="admin-companies.css"
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


        <a
            href="admin-companies.php"
            class="active"
        >

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
         HEADER
    ================================================== -->

    <header class="top-header">


        <div class="welcome-text">

            <div class="page-label">
                COMPANY MANAGEMENT
            </div>

            <h1>
                Manage <span>Companies</span>
            </h1>

            <p>
                Review company accounts and manage organizations
                registered on InternConnect.
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
         SUCCESS MESSAGE
    ================================================== -->

    <?php if (!empty($success_message)): ?>

        <div class="success-message">

            <i class="fa-solid fa-circle-check"></i>

            <span>
                <?php
                echo htmlspecialchars($success_message);
                ?>
            </span>

        </div>

    <?php endif; ?>


    <!-- ==================================================
         STATISTICS
    ================================================== -->

    <section class="stats-grid">


        <!-- TOTAL COMPANIES -->

        <div class="stat-card">

            <div class="stat-top">

                <div>

                    <div class="stat-label">
                        Total Companies
                    </div>

                    <div class="stat-number">
                        <?php
                        echo $total_companies;
                        ?>
                    </div>

                </div>


                <div class="stat-icon company-icon">

                    <i class="fa-solid fa-building"></i>

                </div>

            </div>


            <div class="stat-description">
                Registered company accounts
            </div>

        </div>


        <!-- TOTAL INTERNSHIPS -->

        <div class="stat-card">

            <div class="stat-top">

                <div>

                    <div class="stat-label">
                        Total Internships
                    </div>

                    <div class="stat-number">
                        <?php
                        echo $total_internships;
                        ?>
                    </div>

                </div>


                <div class="stat-icon internship-icon">

                    <i class="fa-solid fa-briefcase"></i>

                </div>

            </div>


            <div class="stat-description">
                Opportunities posted by companies
            </div>

        </div>


        <!-- ACTIVE INTERNSHIPS -->

        <div class="stat-card">

            <div class="stat-top">

                <div>

                    <div class="stat-label">
                        Active Internships
                    </div>

                    <div class="stat-number">
                        <?php
                        echo $active_internships;
                        ?>
                    </div>

                </div>


                <div class="stat-icon active-icon">

                    <i class="fa-solid fa-circle-check"></i>

                </div>

            </div>


            <div class="stat-description">
                Currently visible to students
            </div>

        </div>


    </section>


    <!-- ==================================================
         PAGE CARD
    ================================================== -->

    <section class="companies-card">


        <div class="card-header">


            <div>

                <span class="card-label">
                    REGISTERED ORGANIZATIONS
                </span>

                <h2>
                    All Companies
                </h2>

            </div>


            <div class="company-total">

                <i class="fa-solid fa-building"></i>

                <?php echo $total_companies; ?>
                Companies

            </div>


        </div>


        <!-- ==================================================
             COMPANY LIST
        ================================================== -->

        <div class="companies-list">


            <?php if ($result && mysqli_num_rows($result) > 0): ?>


                <?php while ($company = mysqli_fetch_assoc($result)): ?>


                    <div class="company-item">


                        <!-- COMPANY LOGO -->

                        <div class="company-logo">


                            <?php if (!empty($company['logo'])): ?>

                                <img
                                    src="<?php
                                    echo htmlspecialchars(
                                        $company['logo']
                                    );
                                    ?>"
                                    alt="Company Logo"
                                >

                            <?php else: ?>

                                <i class="fa-solid fa-building"></i>

                            <?php endif; ?>


                        </div>


                        <!-- COMPANY INFORMATION -->

                        <div class="company-info">


                            <div class="company-title-row">


                                <h3>

                                    <?php
                                    echo htmlspecialchars(
                                        $company['company_name']
                                    );
                                    ?>

                                </h3>


                                <?php if (
                                    (int) $company['active_internship_count'] > 0
                                ): ?>

                                    <span class="active-company">

                                        <i class="fa-solid fa-circle"></i>

                                        Active

                                    </span>

                                <?php else: ?>

                                    <span class="inactive-company">

                                        <i class="fa-solid fa-circle"></i>

                                        No Active Posts

                                    </span>

                                <?php endif; ?>


                            </div>


                            <div class="company-meta">


                                <?php if (!empty($company['industry'])): ?>

                                    <span>

                                        <i class="fa-solid fa-layer-group"></i>

                                        <?php
                                        echo htmlspecialchars(
                                            $company['industry']
                                        );
                                        ?>

                                    </span>

                                <?php endif; ?>


                                <?php if (!empty($company['location'])): ?>

                                    <span>

                                        <i class="fa-solid fa-location-dot"></i>

                                        <?php
                                        echo htmlspecialchars(
                                            $company['location']
                                        );
                                        ?>

                                    </span>

                                <?php endif; ?>


                                <span>

                                    <i class="fa-solid fa-briefcase"></i>

                                    <?php
                                    echo (int)
                                        $company['internship_count'];
                                    ?>

                                    Internship<?php
                                    echo (
                                        (int)
                                        $company['internship_count'] !== 1
                                        ? 's'
                                        : ''
                                    );
                                    ?>

                                </span>


                            </div>


                            <div class="company-email">

                                <i class="fa-solid fa-envelope"></i>

                                <?php
                                echo htmlspecialchars(
                                    $company['account_email']
                                );
                                ?>

                            </div>


                        </div>


                        <!-- ACTIONS -->

                        <div class="company-actions">


                            <a
                                href="admin-company-details.php?id=<?php
                                echo (int) $company['id'];
                                ?>"
                                class="view-button"
                            >

                                <i class="fa-solid fa-eye"></i>

                                <span>View Details</span>

                            </a>


                            <form
                                method="POST"
                                class="delete-form"
                                onsubmit="return confirm(
                                    'Are you sure you want to delete this company account?\\n\\nThis will also delete all internships posted by this company and related applications. This action cannot be undone.'
                                );"
                            >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="delete_company"
                                >

                                <input
                                    type="hidden"
                                    name="company_id"
                                    value="<?php
                                    echo (int) $company['id'];
                                    ?>"
                                >


                                <button
                                    type="submit"
                                    class="delete-button"
                                    title="Delete Company"
                                >

                                    <i class="fa-solid fa-trash"></i>

                                    <span>Delete</span>

                                </button>

                            </form>


                        </div>


                    </div>


                <?php endwhile; ?>


            <?php else: ?>


                <div class="empty-state">

                    <div class="empty-icon">

                        <i class="fa-solid fa-building"></i>

                    </div>

                    <h3>
                        No Companies Found
                    </h3>

                    <p>
                        There are currently no registered companies
                        on InternConnect.
                    </p>

                </div>


            <?php endif; ?>


        </div>


    </section>


</main>


</body>

</html>
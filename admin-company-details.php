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
   GET COMPANY ID
================================================== */

$company_id = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($company_id <= 0) {
    header("Location: admin-companies.php");
    exit();
}


/* ==================================================
   DELETE COMPANY
================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'delete_company') {

        $delete_company_id = isset($_POST['company_id'])
            ? (int) $_POST['company_id']
            : 0;

        if ($delete_company_id > 0) {

            /*
             * Get the user account connected to this company.
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
                $delete_company_id
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            $company_user = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);


            if ($company_user) {

                $company_user_id =
                    (int) $company_user['user_id'];


                /*
                 * Delete the company account.
                 *
                 * ON DELETE CASCADE will remove:
                 * - company profile
                 * - internships
                 * - related applications
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
   GET COMPANY INFORMATION
================================================== */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        companies.id,
        companies.user_id,
        companies.company_name,
        companies.description,
        companies.industry,
        companies.location,
        companies.website,
        companies.logo,

        users.name AS account_name,
        users.email AS account_email,
        users.created_at AS account_created_at

     FROM companies

     INNER JOIN users
        ON companies.user_id = users.id

     WHERE companies.id = ?

     AND users.role = 'company'

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


if (!$company) {
    header("Location: admin-companies.php");
    exit();
}


/* ==================================================
   COMPANY INTERNSHIP STATISTICS
================================================== */

$total_internships = 0;
$active_internships = 0;
$pending_internships = 0;
$closed_internships = 0;


$stmt = mysqli_prepare(
    $conn,
    "SELECT
        COUNT(*) AS total,
        SUM(status = 'active') AS active_count,
        SUM(status = 'pending') AS pending_count,
        SUM(status = 'closed') AS closed_count

     FROM internships

     WHERE company_id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $company_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$stats = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if ($stats) {

    $total_internships =
        (int) ($stats['total'] ?? 0);

    $active_internships =
        (int) ($stats['active_count'] ?? 0);

    $pending_internships =
        (int) ($stats['pending_count'] ?? 0);

    $closed_internships =
        (int) ($stats['closed_count'] ?? 0);
}


/* ==================================================
   GET COMPANY INTERNSHIPS
================================================== */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id,
        title,
        category,
        location,
        type,
        description,
        status,
        application_deadline,
        created_at

     FROM internships

     WHERE company_id = ?

     ORDER BY created_at DESC"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $company_id
);

mysqli_stmt_execute($stmt);

$internships_result =
    mysqli_stmt_get_result($stmt);

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
            $company['company_name']
        );
        ?>
        | Company Details | InternConnect
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
        href="admin-company-details.css"
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
         BACK BUTTON
    ================================================== -->

    <a
        href="admin-companies.php"
        class="back-button"
    >

        <i class="fa-solid fa-arrow-left"></i>

        Back to Companies

    </a>

    <!-- ==================================================
         HEADER
    ================================================== -->

    <header class="top-header">


        <div class="welcome-text">

            <div class="page-label">
                COMPANY MANAGEMENT
            </div>


            <h1>
                Company <span>Details</span>
            </h1>


            <p>
                View company information, account details,
                and internship opportunities.
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
         COMPANY HERO
    ================================================== -->

    <section class="company-hero">


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


        <div class="company-hero-info">


            <span class="hero-label">

                <i class="fa-solid fa-building"></i>

                COMPANY PROFILE

            </span>


            <h2>
                <?php
                echo htmlspecialchars(
                    $company['company_name']
                );
                ?>
            </h2>


            <?php if (!empty($company['industry'])): ?>

                <div class="hero-industry">

                    <i class="fa-solid fa-layer-group"></i>

                    <?php
                    echo htmlspecialchars(
                        $company['industry']
                    );
                    ?>

                </div>

            <?php endif; ?>


            <div class="hero-meta">


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

                    <?php echo $total_internships; ?>

                    Internship<?php
                    echo $total_internships !== 1
                        ? 's'
                        : '';
                    ?>

                </span>


            </div>


        </div>


        <form
            method="POST"
            class="hero-delete-form"
            onsubmit="return confirm(
                'Are you sure you want to delete this company account?\\n\\nThis will also delete all internships posted by this company and related applications.\\n\\nThis action cannot be undone.'
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
                echo $company_id;
                ?>"
            >


            <button
                type="submit"
                class="delete-button"
            >

                <i class="fa-solid fa-trash"></i>

                Delete Company

            </button>

        </form>


    </section>


    <!-- ==================================================
         STATISTICS
================================================== -->

    <section class="stats-grid">


        <!-- TOTAL -->

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


                <div class="stat-icon company-icon">

                    <i class="fa-solid fa-briefcase"></i>

                </div>

            </div>


            <div class="stat-description">
                Opportunities posted by this company
            </div>


        </div>


        <!-- ACTIVE -->

        <div class="stat-card">


            <div class="stat-top">

                <div>

                    <div class="stat-label">
                        Active
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


        <!-- PENDING -->

        <div class="stat-card">


            <div class="stat-top">

                <div>

                    <div class="stat-label">
                        Pending
                    </div>

                    <div class="stat-number">
                        <?php
                        echo $pending_internships;
                        ?>
                    </div>

                </div>


                <div class="stat-icon pending-icon">

                    <i class="fa-solid fa-clock"></i>

                </div>

            </div>


            <div class="stat-description">
                Waiting for admin approval
            </div>


        </div>


    </section>


    <!-- ==================================================
         MAIN CONTENT GRID
================================================== -->

    <div class="content-grid">


        <!-- ==================================================
             COMPANY INFORMATION
        ================================================== -->

        <section class="dashboard-card company-information">


            <div class="card-header">


                <div>

                    <span class="card-label">
                        ORGANIZATION
                    </span>

                    <h2>
                        Company Information
                    </h2>

                </div>


            </div>


            <?php if (!empty($company['description'])): ?>

                <div class="description-box">

                    <h3>

                        <i class="fa-solid fa-align-left"></i>

                        About the Company

                    </h3>


                    <p>
                        <?php
                        echo nl2br(
                            htmlspecialchars(
                                $company['description']
                            )
                        );
                        ?>
                    </p>

                </div>

            <?php endif; ?>


            <div class="information-grid">


                <div class="info-item">

                    <div class="info-icon">

                        <i class="fa-solid fa-building"></i>

                    </div>


                    <div>

                        <span>
                            Company Name
                        </span>

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $company['company_name']
                            );
                            ?>
                        </strong>

                    </div>

                </div>


                <div class="info-item">

                    <div class="info-icon">

                        <i class="fa-solid fa-layer-group"></i>

                    </div>


                    <div>

                        <span>
                            Industry
                        </span>

                        <strong>

                            <?php
                            echo !empty($company['industry'])
                                ? htmlspecialchars(
                                    $company['industry']
                                )
                                : 'Not provided';
                            ?>

                        </strong>

                    </div>

                </div>


                <div class="info-item">

                    <div class="info-icon">

                        <i class="fa-solid fa-location-dot"></i>

                    </div>


                    <div>

                        <span>
                            Location
                        </span>

                        <strong>

                            <?php
                            echo !empty($company['location'])
                                ? htmlspecialchars(
                                    $company['location']
                                )
                                : 'Not provided';
                            ?>

                        </strong>

                    </div>

                </div>


                <div class="info-item">

                    <div class="info-icon">

                        <i class="fa-solid fa-globe"></i>

                    </div>


                    <div>

                        <span>
                            Website
                        </span>


                        <?php

                        $website =
                            trim(
                                $company['website'] ?? ''
                            );

                        if (
                            !empty($website) &&
                            filter_var(
                                $website,
                                FILTER_VALIDATE_URL
                            )
                        ):

                        ?>

                            <a
                                href="<?php
                                echo htmlspecialchars(
                                    $website
                                );
                                ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $website
                                );
                                ?>

                                <i
                                    class="fa-solid fa-arrow-up-right-from-square"
                                ></i>

                            </a>

                        <?php else: ?>

                            <strong>
                                <?php
                                echo !empty($website)
                                    ? htmlspecialchars(
                                        $website
                                    )
                                    : 'Not provided';
                                ?>
                            </strong>

                        <?php endif; ?>


                    </div>

                </div>


            </div>


        </section>


        <!-- ==================================================
             ACCOUNT INFORMATION
        ================================================== -->

        <section class="dashboard-card account-card">


            <div class="card-header">

                <div>

                    <span class="card-label">
                        ACCOUNT
                    </span>

                    <h2>
                        Account Details
                    </h2>

                </div>

            </div>


           <div class="account-avatar">

    <?php if (!empty($company['logo'])): ?>

        <img
            src="<?php echo htmlspecialchars($company['logo']); ?>"
            alt="<?php echo htmlspecialchars($company['company_name']); ?> Logo"
        >

    <?php else: ?>

        <i class="fa-solid fa-building"></i>

    <?php endif; ?>

</div>


            <div class="account-name">

                <?php
                echo htmlspecialchars(
                    $company['account_name']
                );
                ?>

            </div>


            <div class="account-role">

                <i class="fa-solid fa-building"></i>

                Company Account

            </div>


            <div class="account-details">


                <div class="account-detail">

                    <i class="fa-solid fa-envelope"></i>

                    <div>

                        <span>
                            Email
                        </span>

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $company['account_email']
                            );
                            ?>
                        </strong>

                    </div>

                </div>


                <div class="account-detail">

                    <i class="fa-solid fa-calendar"></i>

                    <div>

                        <span>
                            Account Created
                        </span>

                        <strong>

                            <?php

                            if (
                                !empty(
                                    $company['account_created_at']
                                )
                            ) {

                                echo date(
                                    'M d, Y',
                                    strtotime(
                                        $company[
                                            'account_created_at'
                                        ]
                                    )
                                );

                            } else {

                                echo 'Not available';

                            }

                            ?>

                        </strong>

                    </div>

                </div>


            </div>


        </section>


    </div>


    <!-- ==================================================
         INTERNSHIPS
    ================================================== -->

    <section class="dashboard-card internships-card">


        <div class="card-header">


            <div>

                <span class="card-label">
                    COMPANY OPPORTUNITIES
                </span>

                <h2>
                    Internships Posted
                </h2>

            </div>


            <div class="internship-total">

                <i class="fa-solid fa-briefcase"></i>

                <?php echo $total_internships; ?>

            </div>


        </div>


        <div class="internship-list">


            <?php if (
                $internships_result &&
                mysqli_num_rows($internships_result) > 0
            ): ?>


                <?php while (
                    $internship =
                    mysqli_fetch_assoc(
                        $internships_result
                    )
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


                            <div class="internship-meta">


                                <?php if (
                                    !empty(
                                        $internship['category']
                                    )
                                ): ?>

                                    <span>

                                        <i
                                            class="fa-solid fa-layer-group"
                                        ></i>

                                        <?php
                                        echo htmlspecialchars(
                                            $internship['category']
                                        );
                                        ?>

                                    </span>

                                <?php endif; ?>


                                <span>

                                    <i
                                        class="fa-solid fa-location-dot"
                                    ></i>

                                    <?php
                                    echo htmlspecialchars(
                                        $internship['location']
                                    );
                                    ?>

                                </span>


                                <span>

                                    <i
                                        class="fa-solid fa-clock"
                                    ></i>

                                    <?php
                                    echo htmlspecialchars(
                                        $internship['type']
                                    );
                                    ?>

                                </span>


                                <span>

                                    <i
                                        class="fa-solid fa-calendar"
                                    ></i>

                                    <?php
                                    echo date(
                                        'M d, Y',
                                        strtotime(
                                            $internship['created_at']
                                        )
                                    );
                                    ?>

                                </span>


                            </div>


                        </div>


                        <div class="internship-status">


                            <?php

                            $status =
                                strtolower(
                                    $internship['status']
                                );

                            ?>


                            <?php if ($status === 'active'): ?>

                                <span class="status active">

                                    <i
                                        class="fa-solid fa-circle"
                                    ></i>

                                    Active

                                </span>

                            <?php elseif (
                                $status === 'pending'
                            ): ?>

                                <span class="status pending">

                                    <i
                                        class="fa-solid fa-circle"
                                    ></i>

                                    Pending

                                </span>

                            <?php elseif (
                                $status === 'closed'
                            ): ?>

                                <span class="status closed">

                                    <i
                                        class="fa-solid fa-circle"
                                    ></i>

                                    Closed

                                </span>

                            <?php endif; ?>


                        </div>


                        <a
                            href="admin-internship-details.php?id=<?php
                            echo (int)
                                $internship['id'];
                            ?>"
                            class="view-internship"
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

                        <i class="fa-solid fa-briefcase"></i>

                    </div>


                    <h3>
                        No Internships Yet
                    </h3>


                    <p>
                        This company has not posted any
                        internship opportunities.
                    </p>


                </div>


            <?php endif; ?>


        </div>


    </section>


</main>


</body>

</html>
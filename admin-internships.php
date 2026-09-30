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
// HANDLE ACTIONS
// ==================================================

$message = "";
$message_type = "";


// ---------- APPROVE INTERNSHIP ----------

if (isset($_GET['approve'])) {

    $internship_id = (int) $_GET['approve'];

    if ($internship_id > 0) {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE internships
             SET status = 'active'
             WHERE id = ?
             AND status = 'pending'"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $internship_id
        );

        if (mysqli_stmt_execute($stmt)) {

            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $message = "Internship approved successfully.";
                $message_type = "success";
            } else {
                $message = "Internship could not be approved.";
                $message_type = "error";
            }

        } else {

            $message = "Something went wrong.";
            $message_type = "error";
        }

        mysqli_stmt_close($stmt);
    }
}


// ---------- CLOSE INTERNSHIP ----------

if (isset($_GET['close'])) {

    $internship_id = (int) $_GET['close'];

    if ($internship_id > 0) {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE internships
             SET status = 'closed'
             WHERE id = ?
             AND status = 'active'"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $internship_id
        );

        if (mysqli_stmt_execute($stmt)) {

            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $message = "Internship closed successfully.";
                $message_type = "success";
            } else {
                $message = "Internship could not be closed.";
                $message_type = "error";
            }

        } else {

            $message = "Something went wrong.";
            $message_type = "error";
        }

        mysqli_stmt_close($stmt);
    }
}


// ---------- REOPEN INTERNSHIP ----------

if (isset($_GET['reopen'])) {

    $internship_id = (int) $_GET['reopen'];

    if ($internship_id > 0) {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE internships
             SET status = 'active'
             WHERE id = ?
             AND status = 'closed'"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $internship_id
        );

        if (mysqli_stmt_execute($stmt)) {

            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $message = "Internship reopened successfully.";
                $message_type = "success";
            } else {
                $message = "Internship could not be reopened.";
                $message_type = "error";
            }

        } else {

            $message = "Something went wrong.";
            $message_type = "error";
        }

        mysqli_stmt_close($stmt);
    }
}


// ---------- DELETE INTERNSHIP ----------

if (isset($_GET['delete'])) {

    $internship_id = (int) $_GET['delete'];

    if ($internship_id > 0) {

        $stmt = mysqli_prepare(
            $conn,
            "DELETE FROM internships
             WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $internship_id
        );

        if (mysqli_stmt_execute($stmt)) {

            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $message = "Internship deleted successfully.";
                $message_type = "success";
            } else {
                $message = "Internship not found.";
                $message_type = "error";
            }

        } else {

            $message = "Unable to delete internship.";
            $message_type = "error";
        }

        mysqli_stmt_close($stmt);
    }
}


// ==================================================
// SEARCH
// ==================================================

$search = "";

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}


// ==================================================
// STATUS FILTER
// ==================================================

$status_filter = "";

if (isset($_GET['status'])) {
    $status_filter = trim($_GET['status']);
}


// ==================================================
// BUILD QUERY
// ==================================================

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
        companies.company_name
    FROM internships
    INNER JOIN companies
        ON internships.company_id = companies.id
    WHERE 1=1
";


$params = [];
$types = "";


// ---------- SEARCH ----------

if ($search !== "") {

    $sql .= "
        AND (
            internships.title LIKE ?
            OR internships.category LIKE ?
            OR internships.location LIKE ?
            OR internships.skills LIKE ?
            OR companies.company_name LIKE ?
        )
    ";

    $search_value = "%" . $search . "%";

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;

    $types .= "sssss";
}


// ---------- STATUS FILTER ----------

if (
    $status_filter === "pending" ||
    $status_filter === "active" ||
    $status_filter === "closed"
) {

    $sql .= " AND internships.status = ?";

    $params[] = $status_filter;

    $types .= "s";
}


$sql .= "
    ORDER BY
        CASE
            WHEN internships.status = 'pending' THEN 1
            WHEN internships.status = 'active' THEN 2
            WHEN internships.status = 'closed' THEN 3
        END,
        internships.created_at DESC
";


// ==================================================
// PREPARE QUERY
// ==================================================

$stmt = mysqli_prepare($conn, $sql);


if (!empty($params)) {

    mysqli_stmt_bind_param(
        $stmt,
        $types,
        ...$params
    );
}


mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


// ==================================================
// COUNTS
// ==================================================

$count_sql = "
    SELECT
        COUNT(*) AS total,
        SUM(status = 'pending') AS pending,
        SUM(status = 'active') AS active,
        SUM(status = 'closed') AS closed
    FROM internships
";

$count_result = mysqli_query($conn, $count_sql);

$count_data = mysqli_fetch_assoc($count_result);

$total_internships = (int) ($count_data['total'] ?? 0);
$pending_internships = (int) ($count_data['pending'] ?? 0);
$active_internships = (int) ($count_data['active'] ?? 0);
$closed_internships = (int) ($count_data['closed'] ?? 0);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Manage Internships | InternConnect</title>


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


    <!-- Admin Internships CSS -->

    <link
        rel="stylesheet"
        href="admin-internships.css"
    >

</head>


<body>


<!-- ==================================================
     BACKGROUND
================================================== -->

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


    <!-- Sidebar Bottom -->

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

            <span class="page-label">
                Internship Management
            </span>

            <h1>
                Manage
                <span>Internships</span>
            </h1>

            <p>
                Review, approve and manage internship opportunities.
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
         MESSAGE
    ================================================== -->

    <?php if ($message !== ""): ?>

        <div class="message <?php echo $message_type; ?>">

            <?php if ($message_type === "success"): ?>

                <i class="fa-solid fa-circle-check"></i>

            <?php else: ?>

                <i class="fa-solid fa-circle-exclamation"></i>

            <?php endif; ?>

            <span>
                <?php echo htmlspecialchars($message); ?>
            </span>

        </div>

    <?php endif; ?>


    <!-- ==================================================
         STATISTICS
    ================================================== -->

    <section class="stats-grid">


        <div class="stat-card">

            <div class="stat-content">

                <span>
                    Total Internships
                </span>

                <strong>
                    <?php echo $total_internships; ?>
                </strong>

            </div>

            <div class="stat-icon">

                <i class="fa-solid fa-briefcase"></i>

            </div>

        </div>


        <div class="stat-card pending">

            <div class="stat-content">

                <span>
                    Pending
                </span>

                <strong>
                    <?php echo $pending_internships; ?>
                </strong>

            </div>

            <div class="stat-icon">

                <i class="fa-solid fa-clock"></i>

            </div>

        </div>


        <div class="stat-card active-stat">

            <div class="stat-content">

                <span>
                    Active
                </span>

                <strong>
                    <?php echo $active_internships; ?>
                </strong>

            </div>

            <div class="stat-icon">

                <i class="fa-solid fa-circle-check"></i>

            </div>

        </div>


        <div class="stat-card closed">

            <div class="stat-content">

                <span>
                    Closed
                </span>

                <strong>
                    <?php echo $closed_internships; ?>
                </strong>

            </div>

            <div class="stat-icon">

                <i class="fa-solid fa-lock"></i>

            </div>

        </div>


    </section>


    <!-- ==================================================
         FILTER / SEARCH
    ================================================== -->

    <section class="filter-card">


        <form
            method="GET"
            action="admin-internships.php"
            class="search-form"
        >

            <div class="search-box">

                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="text"
                    name="search"
                    placeholder="Search internship, company, category or skill..."
                    value="<?php echo htmlspecialchars($search); ?>"
                >

                <?php if ($search !== ""): ?>

                    <a
                        href="admin-internships.php"
                        class="clear-search"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </a>

                <?php endif; ?>

            </div>


            <div class="status-select">

                <i class="fa-solid fa-filter"></i>

                <select
                    name="status"
                    onchange="this.form.submit()"
                >

                    <option value="">
                        All Status
                    </option>

                    <option
                        value="pending"
                        <?php echo $status_filter === "pending" ? "selected" : ""; ?>
                    >
                        Pending
                    </option>

                    <option
                        value="active"
                        <?php echo $status_filter === "active" ? "selected" : ""; ?>
                    >
                        Active
                    </option>

                    <option
                        value="closed"
                        <?php echo $status_filter === "closed" ? "selected" : ""; ?>
                    >
                        Closed
                    </option>

                </select>

            </div>


            <button
                type="submit"
                class="search-button"
            >

                <i class="fa-solid fa-magnifying-glass"></i>

                Search

            </button>

        </form>


    </section>


    <!-- ==================================================
         INTERNSHIP LIST
    ================================================== -->

    <section class="internships-card">


        <div class="card-header">

            <div>

                <h2>
                    Internship Posts
                </h2>

                <p>
                    Review and manage all internship opportunities.
                </p>

            </div>


            <span class="result-count">

                <?php echo mysqli_num_rows($result); ?>

                result<?php echo mysqli_num_rows($result) !== 1 ? "s" : ""; ?>

            </span>

        </div>


        <!-- ==================================================
             TABLE
        ================================================== -->

        <?php if (mysqli_num_rows($result) > 0): ?>

            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>
                                Internship
                            </th>

                            <th>
                                Company
                            </th>

                            <th>
                                Location
                            </th>

                            <th>
                                Type
                            </th>

                            <th>
                                Deadline
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php while ($internship = mysqli_fetch_assoc($result)): ?>


                            <tr>


                                <!-- Internship -->

                                <td>

                                    <div class="internship-cell">

                                        <div class="internship-icon">

                                            <i class="fa-solid fa-briefcase"></i>

                                        </div>

                                        <div>

                                            <strong>
                                                <?php
                                                echo htmlspecialchars(
                                                    $internship['title']
                                                );
                                                ?>
                                            </strong>

                                            <span>
                                                <?php
                                                echo htmlspecialchars(
                                                    $internship['category']
                                                );
                                                ?>
                                            </span>

                                        </div>

                                    </div>

                                </td>


                                <!-- Company -->

                                <td>

                                    <div class="company-cell">

                                        <i class="fa-solid fa-building"></i>

                                        <span>
                                            <?php
                                            echo htmlspecialchars(
                                                $internship['company_name']
                                            );
                                            ?>
                                        </span>

                                    </div>

                                </td>


                                <!-- Location -->

                                <td>

                                    <div class="location-cell">

                                        <i class="fa-solid fa-location-dot"></i>

                                        <span>
                                            <?php
                                            echo htmlspecialchars(
                                                $internship['location']
                                            );
                                            ?>
                                        </span>

                                    </div>

                                </td>


                                <!-- Type -->

                                <td>

                                    <span class="type-badge">

                                        <?php
                                        echo htmlspecialchars(
                                            $internship['type']
                                        );
                                        ?>

                                    </span>

                                </td>


                                <!-- Deadline -->

                                <td>

                                    <?php if (!empty($internship['application_deadline'])): ?>

                                        <span class="deadline">

                                            <i class="fa-regular fa-calendar"></i>

                                            <?php
                                            echo htmlspecialchars(
                                                date(
                                                    "M d, Y",
                                                    strtotime(
                                                        $internship['application_deadline']
                                                    )
                                                )
                                            );
                                            ?>

                                        </span>

                                    <?php else: ?>

                                        <span class="no-deadline">
                                            No deadline
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- Status -->

                                <td>

                                    <?php if ($internship['status'] === 'pending'): ?>

                                        <span class="status-badge pending-badge">

                                            <span class="status-dot"></span>

                                            Pending

                                        </span>


                                    <?php elseif ($internship['status'] === 'active'): ?>

                                        <span class="status-badge active-badge">

                                            <span class="status-dot"></span>

                                            Active

                                        </span>


                                    <?php else: ?>

                                        <span class="status-badge closed-badge">

                                            <span class="status-dot"></span>

                                            Closed

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- Actions -->

                                <td>

                                    <div class="action-buttons">


                                        <?php if ($internship['status'] === 'pending'): ?>

                                            <a
                                                href="admin-internships.php?approve=<?php echo $internship['id']; ?>"
                                                class="action-button approve"
                                                onclick="return confirm('Approve this internship?');"
                                                title="Approve"
                                            >

                                                <i class="fa-solid fa-check"></i>

                                            </a>


                                        <?php elseif ($internship['status'] === 'active'): ?>

                                            <a
                                                href="admin-internships.php?close=<?php echo $internship['id']; ?>"
                                                class="action-button close"
                                                onclick="return confirm('Close this internship?');"
                                                title="Close"
                                            >

                                                <i class="fa-solid fa-lock"></i>

                                            </a>


                                        <?php elseif ($internship['status'] === 'closed'): ?>

                                            <a
                                                href="admin-internships.php?reopen=<?php echo $internship['id']; ?>"
                                                class="action-button reopen"
                                                onclick="return confirm('Reopen this internship?');"
                                                title="Reopen"
                                            >

                                                <i class="fa-solid fa-rotate-right"></i>

                                            </a>

                                        <?php endif; ?>


                                        <a
                                            href="admin-internship-details.php?id=<?php echo $internship['id']; ?>"
                                            class="action-button view"
                                            title="View"
                                        >

                                            <i class="fa-solid fa-eye"></i>

                                        </a>


                                        <a
                                            href="admin-internships.php?delete=<?php echo $internship['id']; ?>"
                                            class="action-button delete"
                                            onclick="return confirm('Are you sure you want to permanently delete this internship?');"
                                            title="Delete"
                                        >

                                            <i class="fa-solid fa-trash"></i>

                                        </a>


                                    </div>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                    </tbody>

                </table>

            </div>


        <?php else: ?>


            <!-- Empty -->

            <div class="empty-state">

                <div class="empty-icon">

                    <i class="fa-solid fa-briefcase"></i>

                </div>

                <h3>
                    No internships found
                </h3>

                <p>
                    There are no internship posts matching your search.
                </p>

                <?php if ($search !== "" || $status_filter !== ""): ?>

                    <a
                        href="admin-internships.php"
                        class="clear-filter-button"
                    >
                        Clear Filters
                    </a>

                <?php endif; ?>

            </div>


        <?php endif; ?>


    </section>


</main>


</body>

</html>
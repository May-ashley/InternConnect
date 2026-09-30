<?php

session_start();

require_once "db.php";

/* ==================================================
   COMPANY ACCESS PROTECTION
================================================== */

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (
    !isset($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'company'
) {
    header("Location: login.php");
    exit();
}


/* ==================================================
   DATABASE
================================================== */

$conn = connect();

$user_id = (int) $_SESSION['user_id'];

$company_name_session = $_SESSION['user_name'] ?? 'Company';


/* ==================================================
   GET COMPANY
================================================== */

$company_id = 0;
$company_name = $company_name_session;
$company_logo = '';

$sql = "
    SELECT
        id,
        company_name,
        logo
    FROM companies
    WHERE user_id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $user_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {

        $company_id = (int) $row['id'];

        $company_name =
            $row['company_name'] ?: $company_name_session;

        $company_logo =
            $row['logo'] ?? '';
    }

    mysqli_stmt_close($stmt);
}


/* ==================================================
   FILTER
================================================== */

$status_filter = $_GET['status'] ?? 'all';

$allowed_statuses = [
    'all',
    'active',
    'pending',
    'closed'
];

if (!in_array($status_filter, $allowed_statuses, true)) {
    $status_filter = 'all';
}


/* ==================================================
   SEARCH
================================================== */

$search = trim($_GET['search'] ?? '');


/* ==================================================
   STATISTICS
================================================== */

$total_internships = 0;
$active_internships = 0;
$pending_internships = 0;
$closed_internships = 0;

if ($company_id > 0) {

    $sql = "
        SELECT
            COUNT(*) AS total,

            SUM(
                CASE
                    WHEN status = 'active'
                    THEN 1
                    ELSE 0
                END
            ) AS active,

            SUM(
                CASE
                    WHEN status = 'pending'
                    THEN 1
                    ELSE 0
                END
            ) AS pending,

            SUM(
                CASE
                    WHEN status = 'closed'
                    THEN 1
                    ELSE 0
                END
            ) AS closed

        FROM internships

        WHERE company_id = ?
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $company_id
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {

            $total_internships =
                (int) ($row['total'] ?? 0);

            $active_internships =
                (int) ($row['active'] ?? 0);

            $pending_internships =
                (int) ($row['pending'] ?? 0);

            $closed_internships =
                (int) ($row['closed'] ?? 0);
        }

        mysqli_stmt_close($stmt);
    }
}


/* ==================================================
   GET INTERNSHIPS
================================================== */

$internships = [];

if ($company_id > 0) {

    $sql = "
        SELECT
            i.id,
            i.title,
            i.category,
            i.location,
            i.type,
            i.description,
            i.skills,
            i.application_deadline,
            i.status,
            i.created_at,

            (
                SELECT COUNT(*)
                FROM applications a
                WHERE a.internship_id = i.id
            ) AS applicant_count

        FROM internships i

        WHERE i.company_id = ?
    ";


    /* STATUS FILTER */

    if ($status_filter !== 'all') {

        $sql .= "
            AND i.status = ?
        ";
    }


    /* SEARCH FILTER */

    if ($search !== '') {

        $sql .= "
            AND (
                i.title LIKE ?
                OR i.category LIKE ?
                OR i.location LIKE ?
                OR i.skills LIKE ?
            )
        ";
    }


    $sql .= "
        ORDER BY i.created_at DESC
    ";


    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {

        /* STATUS + SEARCH */

        if (
            $status_filter !== 'all' &&
            $search !== ''
        ) {

            $search_value = "%" . $search . "%";

            mysqli_stmt_bind_param(
                $stmt,
                "isssss",
                $company_id,
                $status_filter,
                $search_value,
                $search_value,
                $search_value,
                $search_value
            );

        }

        /* STATUS ONLY */

        elseif ($status_filter !== 'all') {

            mysqli_stmt_bind_param(
                $stmt,
                "is",
                $company_id,
                $status_filter
            );

        }

        /* SEARCH ONLY */

        elseif ($search !== '') {

            $search_value = "%" . $search . "%";

            mysqli_stmt_bind_param(
                $stmt,
                "issss",
                $company_id,
                $search_value,
                $search_value,
                $search_value,
                $search_value
            );

        }

        /* NO FILTER */

        else {

            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $company_id
            );
        }


        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {

            $internships[] = $row;
        }

        mysqli_stmt_close($stmt);
    }
}


/* ==================================================
   COMPANY INITIAL
================================================== */

$company_initial = strtoupper(
    substr(
        $company_name,
        0,
        1
    )
);


/* ==================================================
   AJAX LIVE SEARCH
================================================== */

if (
    isset($_GET['ajax']) &&
    $_GET['ajax'] === '1'
) {

    if (!empty($internships)) {

        foreach ($internships as $internship) {

            $status = strtolower(
                $internship['status']
            );
            ?>

            <article class="internship-card">

                <div class="internship-card-left">

                    <div class="internship-card-icon">
                        <i class="fa-solid fa-briefcase"></i>
                    </div>


                    <div class="internship-card-content">

                        <div class="title-row">

                            <h2>
                                <?php
                                echo htmlspecialchars(
                                    $internship['title']
                                );
                                ?>
                            </h2>

                            <span
                                class="status-badge status-<?php
                                echo htmlspecialchars($status);
                                ?>"
                            >
                                <?php
                                echo ucfirst(
                                    htmlspecialchars($status)
                                );
                                ?>
                            </span>

                        </div>


                        <p class="category">
                            <?php
                            echo htmlspecialchars(
                                $internship['category']
                            );
                            ?>
                        </p>


                        <p class="description">
                            <?php
                            echo htmlspecialchars(
                                $internship['description']
                            );
                            ?>
                        </p>


                        <div class="internship-details">

                            <span>
                                <i class="fa-solid fa-location-dot"></i>

                                <?php
                                echo htmlspecialchars(
                                    $internship['location']
                                );
                                ?>
                            </span>


                            <span>
                                <i class="fa-solid fa-clock"></i>

                                <?php
                                echo htmlspecialchars(
                                    $internship['type']
                                );
                                ?>
                            </span>


                            <span>
                                <i class="fa-solid fa-users"></i>

                                <?php
                                echo (int)
                                    $internship['applicant_count'];
                                ?>

                                Applicants
                            </span>


                            <?php
                            if (
                                !empty(
                                    $internship[
                                        'application_deadline'
                                    ]
                                )
                            ):
                            ?>

                                <span>

                                    <i
                                        class="fa-regular fa-calendar"
                                    ></i>

                                    Deadline:

                                    <?php
                                    echo date(
                                        'M d, Y',
                                        strtotime(
                                            $internship[
                                                'application_deadline'
                                            ]
                                        )
                                    );
                                    ?>

                                </span>

                            <?php endif; ?>

                        </div>


                        <?php
                        if (!empty($internship['skills'])):
                        ?>

                            <div class="skills">

                                <?php

                                $skills = explode(
                                    ',',
                                    $internship['skills']
                                );

                                foreach ($skills as $skill):

                                    $skill = trim($skill);

                                    if ($skill === '') {
                                        continue;
                                    }

                                ?>

                                    <span>
                                        <?php
                                        echo htmlspecialchars(
                                            $skill
                                        );
                                        ?>
                                    </span>

                                <?php endforeach; ?>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>


                <div class="internship-card-actions">

                    <a
                        href="company-internship-edit.php?id=<?php echo (int) $internship['id']; ?>"
                        class="action-button edit-button"
                    >

                        <i
                            class="fa-regular fa-pen-to-square"
                        ></i>

                        Edit

                    </a>


                    <a
                        href="company-application-details.php?internship_id=<?php echo (int) $internship['id']; ?>"
                        class="action-button applicant-button"
                    >

                        <i class="fa-solid fa-users"></i>

                        Applicants

                    </a>

                </div>

            </article>

            <?php
        }

    } else {
        ?>

        <div class="internships-empty live-empty">

            <div class="empty-large-icon">
                <i class="fa-solid fa-magnifying-glass"></i>
            </div>

            <h2>
                No internships found
            </h2>

            <p>
                We couldn't find any internship matching
                "<?php echo htmlspecialchars($search); ?>".
            </p>

        </div>

        <?php
    }

    exit();
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
        My Internships | InternConnect
    </title>


    <!-- Google Font -->

    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- Dashboard CSS -->

    <link
        rel="stylesheet"
        href="company-dashboard.css"
    >


    <!-- Internship CSS -->

    <link
        rel="stylesheet"
        href="company-internships.css"
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
                Company Portal
            </p>

        </div>


        <nav class="sidebar-nav">

            <a href="company-dashboard.php">

                <i class="fa-solid fa-chart-line"></i>

                <span>
                    Dashboard
                </span>

            </a>


            <a
                href="company-internships.php"
                class="active"
            >

                <i class="fa-solid fa-briefcase"></i>

                <span>
                    My Internships
                </span>

            </a>


            <a href="company-applications.php">

                <i class="fa-regular fa-file-lines"></i>

                <span>
                    Applications
                </span>

            </a>


            <a href="company-accepted.php">

                <i class="fa-solid fa-user-check"></i>

                <span>
                    Accepted Interns
                </span>

            </a>


            <a href="company-profile.php">

                <i class="fa-regular fa-building"></i>

                <span>
                    Company Profile
                </span>

            </a>

        </nav>


        <div class="sidebar-bottom">

            <a
                href="index.php"
                class="back-to-site"
            >

                <i class="fa-solid fa-arrow-left"></i>

                <span>
                    Back to Site
                </span>

            </a>


            <a
                href="logout.php"
                class="logout"
            >

                <i
                    class="fa-solid fa-right-from-bracket"
                ></i>

                <span>
                    Logout
                </span>

            </a>

        </div>

    </aside>


    <!-- ==================================================
         MAIN
    ================================================== -->

    <main class="dashboard-main internship-main">


        <!-- PAGE HEADER -->

        <header class="page-header">

            <div>

                <p class="dashboard-label">
                    Recruitment
                </p>

                <h1>
                    My Internships
                </h1>

                <p class="dashboard-subtitle">
                    Manage your internship opportunities,
                    track applicants, and update your listings.
                </p>

            </div>


            <a
                href="company-internship-create.php"
                class="create-button"
            >

                <i class="fa-solid fa-plus"></i>

                Create Internship

            </a>

        </header>


        <!-- ==================================================
             STATISTICS
        ================================================== -->

        <section class="internship-stats">


            <div class="mini-stat">

                <div class="mini-stat-icon">

                    <i class="fa-solid fa-briefcase"></i>

                </div>

                <div>

                    <span>
                        Total
                    </span>

                    <strong>
                        <?php echo $total_internships; ?>
                    </strong>

                </div>

            </div>


            <div class="mini-stat">

                <div class="mini-stat-icon active-icon">

                    <i class="fa-solid fa-circle-check"></i>

                </div>

                <div>

                    <span>
                        Active
                    </span>

                    <strong>
                        <?php echo $active_internships; ?>
                    </strong>

                </div>

            </div>


            <div class="mini-stat">

                <div class="mini-stat-icon pending-icon">

                    <i class="fa-regular fa-clock"></i>

                </div>

                <div>

                    <span>
                        Pending
                    </span>

                    <strong>
                        <?php echo $pending_internships; ?>
                    </strong>

                </div>

            </div>


            <div class="mini-stat">

                <div class="mini-stat-icon closed-icon">

                    <i class="fa-solid fa-circle-xmark"></i>

                </div>

                <div>

                    <span>
                        Closed
                    </span>

                    <strong>
                        <?php echo $closed_internships; ?>
                    </strong>

                </div>

            </div>


        </section>


        <!-- ==================================================
             SEARCH + FILTER
        ================================================== -->

        <section class="tools-card">


            <div class="search-form">

                <div class="search-box">

                    <i
                        class="fa-solid fa-magnifying-glass"
                    ></i>

                    <input
                        type="text"
                        id="internshipSearch"
                        placeholder="Search by title, category, location or skill..."
                        value="<?php echo htmlspecialchars($search); ?>"
                        autocomplete="off"
                    >

                    <button
                        type="button"
                        id="clearSearch"
                        class="clear-search"
                        aria-label="Clear search"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>

                </div>

            </div>


            <!-- FILTER TABS -->

            <div class="filter-tabs">


                <a
                    href="company-internships.php"
                    class="<?php
                    echo $status_filter === 'all'
                        ? 'active'
                        : '';
                    ?>"
                >

                    All

                    <span>
                        <?php echo $total_internships; ?>
                    </span>

                </a>


                <a
                    href="company-internships.php?status=active"
                    class="<?php
                    echo $status_filter === 'active'
                        ? 'active'
                        : '';
                    ?>"
                >

                    Active

                    <span>
                        <?php echo $active_internships; ?>
                    </span>

                </a>


                <a
                    href="company-internships.php?status=pending"
                    class="<?php
                    echo $status_filter === 'pending'
                        ? 'active'
                        : '';
                    ?>"
                >

                    Pending

                    <span>
                        <?php echo $pending_internships; ?>
                    </span>

                </a>


                <a
                    href="company-internships.php?status=closed"
                    class="<?php
                    echo $status_filter === 'closed'
                        ? 'active'
                        : '';
                    ?>"
                >

                    Closed

                    <span>
                        <?php echo $closed_internships; ?>
                    </span>

                </a>


            </div>

        </section>


        <!-- ==================================================
             INTERNSHIP LIST
        ================================================== -->

        <section
            class="internships-wrapper"
            id="internshipsWrapper"
        >


            <?php if (!empty($internships)): ?>


                <?php foreach ($internships as $internship): ?>

                    <?php

                    $status = strtolower(
                        $internship['status']
                    );

                    ?>


                    <article class="internship-card">


                        <!-- LEFT -->

                        <div class="internship-card-left">


                            <div class="internship-card-icon">

                                <i
                                    class="fa-solid fa-briefcase"
                                ></i>

                            </div>


                            <div class="internship-card-content">


                                <div class="title-row">

                                    <h2>

                                        <?php
                                        echo htmlspecialchars(
                                            $internship['title']
                                        );
                                        ?>

                                    </h2>


                                    <span
                                        class="status-badge status-<?php
                                        echo htmlspecialchars($status);
                                        ?>"
                                    >

                                        <?php
                                        echo ucfirst(
                                            htmlspecialchars($status)
                                        );
                                        ?>

                                    </span>

                                </div>


                                <p class="category">

                                    <?php
                                    echo htmlspecialchars(
                                        $internship['category']
                                    );
                                    ?>

                                </p>


                                <p class="description">

                                    <?php
                                    echo htmlspecialchars(
                                        $internship['description']
                                    );
                                    ?>

                                </p>


                                <div class="internship-details">


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
                                            class="fa-solid fa-users"
                                        ></i>

                                        <?php
                                        echo (int)
                                            $internship[
                                                'applicant_count'
                                            ];
                                        ?>

                                        Applicants

                                    </span>


                                    <?php
                                    if (
                                        !empty(
                                            $internship[
                                                'application_deadline'
                                            ]
                                        )
                                    ):
                                    ?>

                                        <span>

                                            <i
                                                class="fa-regular fa-calendar"
                                            ></i>

                                            Deadline:

                                            <?php
                                            echo date(
                                                'M d, Y',
                                                strtotime(
                                                    $internship[
                                                        'application_deadline'
                                                    ]
                                                )
                                            );
                                            ?>

                                        </span>

                                    <?php endif; ?>


                                </div>


                                <?php
                                if (!empty($internship['skills'])):
                                ?>

                                    <div class="skills">

                                        <?php

                                        $skills = explode(
                                            ',',
                                            $internship['skills']
                                        );

                                        foreach ($skills as $skill):

                                            $skill = trim($skill);

                                            if ($skill === '') {
                                                continue;
                                            }

                                        ?>

                                            <span>

                                                <?php
                                                echo htmlspecialchars(
                                                    $skill
                                                );
                                                ?>

                                            </span>

                                        <?php endforeach; ?>

                                    </div>

                                <?php endif; ?>


                            </div>

                        </div>


                        <!-- RIGHT -->

                        <div class="internship-card-actions">


                            <a
                                href="company-internship-edit.php?id=<?php echo (int) $internship['id']; ?>"
                                class="action-button edit-button"
                            >

                                <i
                                    class="fa-regular fa-pen-to-square"
                                ></i>

                                Edit

                            </a>


                            <a
                                href="company-application-details.php?internship_id=<?php echo (int) $internship['id']; ?>"
                                class="action-button applicant-button"
                            >

                                <i
                                    class="fa-solid fa-users"
                                ></i>

                                Applicants

                            </a>


                        </div>


                    </article>


                <?php endforeach; ?>


            <?php else: ?>


                <div class="internships-empty">


                    <div class="empty-large-icon">

                        <i
                            class="fa-solid fa-briefcase"
                        ></i>

                    </div>


                    <?php if ($search !== ''): ?>

                        <h2>
                            No internships found
                        </h2>

                        <p>
                            We couldn't find any internship
                            matching
                            "<?php echo htmlspecialchars($search); ?>"
                        </p>


                        <a
                            href="company-internships.php"
                            class="secondary-button empty-button"
                        >

                            Clear Search

                        </a>


                    <?php elseif ($status_filter !== 'all'): ?>


                        <h2>
                            No
                            <?php
                            echo htmlspecialchars(
                                $status_filter
                            );
                            ?>
                            internships
                        </h2>

                        <p>
                            You don't have any internships
                            with this status yet.
                        </p>


                        <a
                            href="company-internships.php"
                            class="secondary-button empty-button"
                        >

                            View All Internships

                        </a>


                    <?php else: ?>


                        <h2>
                            No internships yet
                        </h2>

                        <p>
                            Create your first internship
                            opportunity and start finding
                            talented students.
                        </p>


                        <a
                            href="company-internship-create.php"
                            class="primary-button"
                        >

                            <i class="fa-solid fa-plus"></i>

                            Create Your First Internship

                        </a>


                    <?php endif; ?>


                </div>


            <?php endif; ?>


        </section>


    </main>

</div>


<!-- ==================================================
     LIVE SEARCH JAVASCRIPT
================================================== -->

<script>

const searchInput =
    document.getElementById('internshipSearch');

const wrapper =
    document.getElementById('internshipsWrapper');

const clearButton =
    document.getElementById('clearSearch');

let searchTimer = null;


/* ==================================================
   LIVE SEARCH
================================================== */

searchInput.addEventListener('input', function () {

    clearTimeout(searchTimer);

    const searchValue =
        this.value.trim();


    /* Show / hide clear button */

    if (searchValue.length > 0) {

        clearButton.classList.add('show');

    } else {

        clearButton.classList.remove('show');
    }


    /*
       Small delay so database isn't
       queried for every single keystroke.
    */

    searchTimer = setTimeout(function () {

        performSearch(searchValue);

    }, 300);

});


/* ==================================================
   SEARCH FUNCTION
================================================== */

function performSearch(searchValue) {

    const currentStatus =
        "<?php echo htmlspecialchars($status_filter); ?>";


    let url =
        "company-internships.php?ajax=1";


    if (searchValue !== '') {

        url +=
            "&search=" +
            encodeURIComponent(searchValue);
    }


    if (currentStatus !== 'all') {

        url +=
            "&status=" +
            encodeURIComponent(currentStatus);
    }


    /* Loading effect */

    wrapper.classList.add('search-loading');


    fetch(url, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })

    .then(function (response) {

        if (!response.ok) {
            throw new Error('Search request failed.');
        }

        return response.text();

    })

    .then(function (html) {

        wrapper.innerHTML = html;

        wrapper.classList.remove('search-loading');

    })

    .catch(function (error) {

        console.error(error);

        wrapper.classList.remove('search-loading');

    });

}


/* ==================================================
   CLEAR SEARCH
================================================== */

clearButton.addEventListener('click', function () {

    searchInput.value = '';

    clearButton.classList.remove('show');

    performSearch('');

    searchInput.focus();

});


/* ==================================================
   INITIAL CLEAR BUTTON STATE
================================================== */

if (searchInput.value.trim() !== '') {

    clearButton.classList.add('show');

}

</script>


</body>

</html>
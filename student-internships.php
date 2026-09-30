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

if ($_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit();
}


/* ==================================================
   STUDENT INFORMATION
================================================== */

$student_id    = $_SESSION['user_id'];
$student_name  = $_SESSION['user_name'];
$student_email = $_SESSION['user_email'];


/* ==================================================
   DATABASE CONNECTION
================================================== */

$conn = connect();


/* ==================================================
   SEARCH AND FILTER VALUES
================================================== */

$search    = isset($_GET['search']) ? trim($_GET['search']) : '';
$category  = isset($_GET['category']) ? trim($_GET['category']) : '';
$location  = isset($_GET['location']) ? trim($_GET['location']) : '';
$type      = isset($_GET['type']) ? trim($_GET['type']) : '';


/* ==================================================
   GET CATEGORIES
================================================== */

$category_sql = "
    SELECT DISTINCT category
    FROM internships
    WHERE status = 'active'
    ORDER BY category ASC
";

$category_result = mysqli_query($conn, $category_sql);

$categories = [];

if ($category_result) {
    while ($row = mysqli_fetch_assoc($category_result)) {
        $categories[] = $row['category'];
    }
}


/* ==================================================
   GET LOCATIONS
================================================== */

$location_sql = "
    SELECT DISTINCT location
    FROM internships
    WHERE status = 'active'
    ORDER BY location ASC
";

$location_result = mysqli_query($conn, $location_sql);

$locations = [];

if ($location_result) {
    while ($row = mysqli_fetch_assoc($location_result)) {
        $locations[] = $row['location'];
    }
}


/* ==================================================
   GET ACTIVE INTERNSHIPS
================================================== */

$sql = "
    SELECT
        internships.id,
        internships.title,
        internships.category,
        internships.location,
        internships.type,
        internships.description,
        internships.skills,
        internships.application_deadline,
        internships.created_at,

        companies.id AS company_id,
        companies.company_name,
        companies.logo

    FROM internships

    INNER JOIN companies
        ON internships.company_id = companies.id

    WHERE internships.status = 'active'
";


/* ==================================================
   SEARCH
================================================== */

if ($search !== '') {

    $search_safe = mysqli_real_escape_string($conn, $search);

    $sql .= "
        AND (
            internships.title LIKE '%$search_safe%'
            OR internships.category LIKE '%$search_safe%'
            OR internships.skills LIKE '%$search_safe%'
            OR companies.company_name LIKE '%$search_safe%'
        )
    ";
}


/* ==================================================
   CATEGORY FILTER
================================================== */

if ($category !== '') {

    $category_safe = mysqli_real_escape_string($conn, $category);

    $sql .= "
        AND internships.category = '$category_safe'
    ";
}


/* ==================================================
   LOCATION FILTER
================================================== */

if ($location !== '') {

    $location_safe = mysqli_real_escape_string($conn, $location);

    $sql .= "
        AND internships.location = '$location_safe'
    ";
}


/* ==================================================
   TYPE FILTER
================================================== */

if ($type !== '') {

    $type_safe = mysqli_real_escape_string($conn, $type);

    $sql .= "
        AND internships.type = '$type_safe'
    ";
}


/* ==================================================
   ORDER
================================================== */

$sql .= "
    ORDER BY internships.created_at DESC
";


/* ==================================================
   EXECUTE
================================================== */

$result = mysqli_query($conn, $sql);

$internships = [];

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $internships[] = $row;

    }
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

    <title>Browse Internships | InternConnect</title>

    <link
        rel="stylesheet"
        href="student-internships.css"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
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
                Student Portal
            </p>

        </div>


        <nav class="sidebar-nav">

            <a href="student-dashboard.php">

                <i class="fa-solid fa-chart-line"></i>

                <span>Dashboard</span>

            </a>


            <a
                href="student-internships.php"
                class="active"
            >

                <i class="fa-solid fa-briefcase"></i>

                <span>Browse Internships</span>

            </a>


            <a href="student-applications.php">

                <i class="fa-regular fa-file-lines"></i>

                <span>My Applications</span>

            </a>


            


            <a href="student-profile.php">

                <i class="fa-regular fa-user"></i>

                <span>My Profile</span>

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

                <span>Logout</span>

            </a>

        </div>

    </aside>



    <!-- ==================================================
         MAIN CONTENT
    ================================================== -->

    <main class="dashboard-main">


        <!-- ==================================================
             HEADER
        ================================================== -->

        <header class="dashboard-header">

            <div>

                <p class="dashboard-label">
                    Internship Opportunities
                </p>

                <h1>
                    Browse Internships
                </h1>

                <p class="dashboard-subtitle">

                    Discover internship opportunities
                    and find the right one for your career.

                </p>

            </div>


            <div class="student-info">

                <div class="student-avatar">

                    <?php
                    echo strtoupper(
                        substr($student_name, 0, 1)
                    );
                    ?>

                </div>


                <div>

                    <strong>
                        <?php
                        echo htmlspecialchars($student_name);
                        ?>
                    </strong>

                    <span>
                        <?php
                        echo htmlspecialchars($student_email);
                        ?>
                    </span>

                </div>

            </div>

        </header>



        <!-- ==================================================
             SEARCH AND FILTER
        ================================================== -->
        <section class="search-card">

    <form
        method="GET"
        action="student-internships.php"
        class="search-form"
        id="internshipSearchForm"
    >

        <div class="search-input-wrapper">

            <i class="fa-solid fa-magnifying-glass search-icon"></i>

            <input
                type="text"
                name="search"
                id="searchInput"
                placeholder="Search internships, companies or skills..."
                value="<?php echo htmlspecialchars($search); ?>"
                autocomplete="off"
            >

            <?php if ($search !== ''): ?>

                <a
                    href="student-internships.php"
                    class="search-clear"
                    aria-label="Clear search"
                >
                    <i class="fa-solid fa-xmark"></i>
                </a>

            <?php endif; ?>

        </div>

    </form>

</section>


        <!-- ==================================================
             RESULTS HEADER
        ================================================== -->

        <div class="results-header">

            <div>

                <p class="card-label">
                    Available Opportunities
                </p>

                <h2>
                    <?php echo count($internships); ?>
                    Internship<?php echo count($internships) !== 1 ? 's' : ''; ?>
                    Found
                </h2>

            </div>

        </div>



        <!-- ==================================================
             INTERNSHIP LIST
        ================================================== -->

        <?php if (!empty($internships)): ?>


            <section class="internship-grid">


                <?php foreach ($internships as $internship): ?>


                    <article class="internship-card">


                        <!-- COMPANY LOGO -->

                        <div class="card-top">


                            <div class="company-logo">

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


                            <span class="type-badge">

                                <?php
                                echo htmlspecialchars(
                                    $internship['type']
                                );
                                ?>

                            </span>

                        </div>



                        <!-- INFORMATION -->

                        <div class="internship-content">


                            <span class="category-badge">

                                <?php
                                echo htmlspecialchars(
                                    $internship['category']
                                );
                                ?>

                            </span>


                            <h3>

                                <?php
                                echo htmlspecialchars(
                                    $internship['title']
                                );
                                ?>

                            </h3>


                            <p class="company-name">

                                <?php
                                echo htmlspecialchars(
                                    $internship['company_name']
                                );
                                ?>

                            </p>


                            <div class="internship-meta">

                                <span>

                                    <i class="fa-solid fa-location-dot"></i>

                                    <?php
                                    echo htmlspecialchars(
                                        $internship['location']
                                    );
                                    ?>

                                </span>


                                <?php if (!empty($internship['application_deadline'])): ?>

                                    <span>

                                        <i class="fa-regular fa-calendar"></i>

                                        Deadline:

                                        <?php
                                        echo date(
                                            'M d, Y',
                                            strtotime(
                                                $internship['application_deadline']
                                            )
                                        );
                                        ?>

                                    </span>

                                <?php endif; ?>

                            </div>


                            <?php if (!empty($internship['skills'])): ?>

                                <div class="skills">

                                    <?php

                                    $skills = explode(
                                        ',',
                                        $internship['skills']
                                    );

                                    $skills = array_slice(
                                        $skills,
                                        0,
                                        3
                                    );

                                    foreach ($skills as $skill):

                                        $skill = trim($skill);

                                        if ($skill === '') {
                                            continue;
                                        }

                                    ?>

                                        <span>
                                            <?php
                                            echo htmlspecialchars($skill);
                                            ?>
                                        </span>

                                    <?php endforeach; ?>

                                </div>

                            <?php endif; ?>


                        </div>



                        <!-- ACTION -->

                        <div class="card-action">

                            <a
                                href="student-internship-details.php?id=<?php echo $internship['id']; ?>"
                                class="view-button"
                            >

                                View Details

                                <i class="fa-solid fa-arrow-right"></i>

                            </a>

                        </div>


                    </article>


                <?php endforeach; ?>


            </section>


        <?php else: ?>


            <!-- ==================================================
                 EMPTY STATE
            ================================================== -->

            <section class="empty-state">


                <div class="empty-icon">

                    <i class="fa-solid fa-magnifying-glass"></i>

                </div>


                <h3>
                    No Internships Found
                </h3>


                <p>

                    We couldn't find any internship opportunities
                    matching your search or filters.

                </p>


                <a
                    href="student-internships.php"
                    class="primary-button"
                >

                    View All Internships

                </a>


            </section>


        <?php endif; ?>


    </main>

</div>



</main>

</div>
<script>

document.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById("internshipSearchForm");

    const searchInput = document.getElementById("searchInput");

    let searchTimer;


    searchInput.addEventListener("input", function () {

        clearTimeout(searchTimer);

        searchTimer = setTimeout(function () {

            form.submit();

        }, 500);

    });

});

</script>

</body>



</html>
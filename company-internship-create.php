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
   DATABASE CONNECTION
================================================== */

$conn = connect();

$user_id = (int) $_SESSION['user_id'];

$company_name_session = $_SESSION['user_name'] ?? 'Company';

$company_email = $_SESSION['user_email'] ?? '';


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
            !empty($row['company_name'])
            ? $row['company_name']
            : $company_name_session;

        $company_logo =
            !empty($row['logo'])
            ? $row['logo']
            : '';
    }

    mysqli_stmt_close($stmt);
}


/* ==================================================
   DEFAULT FORM VALUES
================================================== */

$title = '';
$category = '';
$location = '';
$type = '';
$description = '';
$requirements = '';
$skills = '';
$application_deadline = '';

$error = '';


/* ==================================================
   HANDLE FORM SUBMISSION
================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title =
        trim($_POST['title'] ?? '');

    $category =
        trim($_POST['category'] ?? '');

    $location =
        trim($_POST['location'] ?? '');

    $type =
        trim($_POST['type'] ?? '');

    $description =
        trim($_POST['description'] ?? '');

    $requirements =
        trim($_POST['requirements'] ?? '');

    $skills =
        trim($_POST['skills'] ?? '');

    $application_deadline =
        trim($_POST['application_deadline'] ?? '');


    /* ==================================================
       VALIDATION
    ================================================== */

    if ($company_id <= 0) {

        $error =
            "Company profile could not be found.";

    } elseif (
        empty($title) ||
        empty($category) ||
        empty($location) ||
        empty($type) ||
        empty($description) ||
        empty($requirements) ||
        empty($skills)
    ) {

        $error =
            "Please complete all required fields.";

    } elseif (
        !empty($application_deadline) &&
        strtotime($application_deadline) === false
    ) {

        $error =
            "Please enter a valid application deadline.";

    } else {

        /* ==================================================
           CREATE INTERNSHIP
           New posts start as PENDING
        ================================================== */

        $sql = "
            INSERT INTO internships
            (
                company_id,
                title,
                category,
                location,
                type,
                description,
                requirements,
                skills,
                application_deadline,
                status
            )
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "issssssss",
                $company_id,
                $title,
                $category,
                $location,
                $type,
                $description,
                $requirements,
                $skills,
                $application_deadline
            );

            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);

                header(
                    "Location: company-internships.php?created=1"
                );

                exit();

            } else {

                $error =
                    "Unable to create internship. Please try again.";

                mysqli_stmt_close($stmt);
            }

        } else {

            $error =
                "Something went wrong while preparing the internship.";
        }
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

    <title>
        Create Internship | InternConnect
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


    <!-- CSS -->

    <link
        rel="stylesheet"
        href="company-internship-create.css"
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

            <i class="fa-solid fa-right-from-bracket"></i>

            <span>
                Logout
            </span>

        </a>

    </div>

</aside>


<!-- ==================================================
     PAGE
================================================== -->

<div class="page-container">


    <!-- ==================================================
         HEADER
    ================================================== -->

    <header class="create-header">


        <!-- BACK TO MY INTERNSHIPS -->

        <a
            href="company-internships.php"
            class="back-link"
        >

            <i class="fa-solid fa-arrow-left"></i>

            Back to My Internships

        </a>


       
    </header>


    <!-- ==================================================
         MAIN
    ================================================== -->

    <main class="create-main">


        <!-- PAGE INTRO -->

        <section class="page-intro">

            <p class="page-label">
                RECRUITMENT
            </p>

            <h1>
                Create Internship
            </h1>

            <p>
                Create a new internship opportunity
                and find talented students for your team.
            </p>

        </section>


        <!-- ==================================================
             FORM CARD
        ================================================== -->

        <section class="form-card">


            <?php if (!empty($error)): ?>

                <div class="alert alert-error">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    <span>
                        <?= htmlspecialchars($error) ?>
                    </span>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                action=""
                autocomplete="off"
            >


                <!-- ==================================================
                     INTERNSHIP INFORMATION
                ================================================== -->

                <div class="form-section">


                    <div class="section-heading">

                        <div class="section-icon">

                            <i class="fa-solid fa-briefcase"></i>

                        </div>


                        <div>

                            <h2>
                                Internship Information
                            </h2>

                            <p>
                                Tell students about the opportunity.
                            </p>

                        </div>

                    </div>


                    <div class="form-grid">


                        <!-- TITLE -->

                        <div class="form-group full-width">

                            <label for="title">

                                Internship Title

                                <span>*</span>

                            </label>

                            <input
                                type="text"
                                id="title"
                                name="title"
                                placeholder="e.g. Software Engineering Intern"
                                value="<?= htmlspecialchars($title) ?>"
                                required
                            >

                        </div>


                        <!-- CATEGORY -->

                        <div class="form-group">

                            <label for="category">

                                Category

                                <span>*</span>

                            </label>

                            <select
                                id="category"
                                name="category"
                                required
                            >

                                <option
                                    value=""
                                    disabled
                                    <?= empty($category) ? 'selected' : '' ?>
                                >
                                    Select category
                                </option>


                                <?php

                                $categories = [
                                    'Software Development',
                                    'Web Development',
                                    'Design',
                                    'Data Science',
                                    'Artificial Intelligence',
                                    'Cybersecurity',
                                    'Cloud Computing',
                                    'Mobile Development',
                                    'Engineering',
                                    'Marketing',
                                    'Business',
                                    'Other'
                                ];

                                foreach ($categories as $item):

                                ?>

                                    <option
                                        value="<?= htmlspecialchars($item) ?>"
                                        <?= $category === $item ? 'selected' : '' ?>
                                    >

                                        <?= htmlspecialchars($item) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- TYPE -->

                        <div class="form-group">

                            <label for="type">

                                Internship Type

                                <span>*</span>

                            </label>

                            <select
                                id="type"
                                name="type"
                                required
                            >

                                <option
                                    value=""
                                    disabled
                                    <?= empty($type) ? 'selected' : '' ?>
                                >
                                    Select type
                                </option>


                                <option
                                    value="Full-time"
                                    <?= $type === 'Full-time' ? 'selected' : '' ?>
                                >
                                    Full-time
                                </option>


                                <option
                                    value="Part-time"
                                    <?= $type === 'Part-time' ? 'selected' : '' ?>
                                >
                                    Part-time
                                </option>


                                <option
                                    value="Remote"
                                    <?= $type === 'Remote' ? 'selected' : '' ?>
                                >
                                    Remote
                                </option>


                                <option
                                    value="Hybrid"
                                    <?= $type === 'Hybrid' ? 'selected' : '' ?>
                                >
                                    Hybrid
                                </option>

                            </select>

                        </div>


                        <!-- LOCATION -->

                        <div class="form-group">

                            <label for="location">

                                Location

                                <span>*</span>

                            </label>

                            <div class="input-icon">

                                <i class="fa-solid fa-location-dot"></i>

                                <input
                                    type="text"
                                    id="location"
                                    name="location"
                                    placeholder="e.g. Yangon, Myanmar"
                                    value="<?= htmlspecialchars($location) ?>"
                                    required
                                >

                            </div>

                        </div>


                        <!-- DEADLINE -->

                        <div class="form-group">

                            <label for="application_deadline">

                                Application Deadline

                                <span class="optional">
                                    Optional
                                </span>

                            </label>

                            <div class="input-icon">

                                <i class="fa-regular fa-calendar"></i>

                                <input
                                    type="date"
                                    id="application_deadline"
                                    name="application_deadline"
                                    value="<?= htmlspecialchars($application_deadline) ?>"
                                >

                            </div>

                        </div>

                    </div>

                </div>


                <!-- ==================================================
                     INTERNSHIP DETAILS
                ================================================== -->

                <div class="form-section">


                    <div class="section-heading">

                        <div class="section-icon">

                            <i class="fa-regular fa-file-lines"></i>

                        </div>


                        <div>

                            <h2>
                                Internship Details
                            </h2>

                            <p>
                                Describe what the intern will do.
                            </p>

                        </div>

                    </div>


                    <!-- DESCRIPTION -->

                    <div class="form-group">

                        <label for="description">

                            Description

                            <span>*</span>

                        </label>

                        <textarea
                            id="description"
                            name="description"
                            rows="6"
                            placeholder="Describe the internship role, responsibilities, and what the intern will work on..."
                            required
                        ><?= htmlspecialchars($description) ?></textarea>

                    </div>


                    <!-- REQUIREMENTS -->

                    <div class="form-group">

                        <label for="requirements">

                            Requirements

                            <span>*</span>

                        </label>

                        <textarea
                            id="requirements"
                            name="requirements"
                            rows="6"
                            placeholder="Describe the education, experience, knowledge, or qualifications required..."
                            required
                        ><?= htmlspecialchars($requirements) ?></textarea>

                    </div>


                    <!-- SKILLS -->

                    <div class="form-group">

                        <label for="skills">

                            Skills

                            <span>*</span>

                        </label>

                        <input
                            type="text"
                            id="skills"
                            name="skills"
                            placeholder="e.g. PHP, MySQL, HTML, CSS, JavaScript"
                            value="<?= htmlspecialchars($skills) ?>"
                            required
                        >

                        <small class="field-help">
                            Separate skills with commas.
                        </small>

                    </div>

                </div>


                <!-- ==================================================
                     ADMIN REVIEW
                ================================================== -->

                <div class="review-notice">

                    <div class="notice-icon">

                        <i class="fa-solid fa-shield-halved"></i>

                    </div>


                    <div>

                        <strong>
                            Admin Review
                        </strong>

                        <p>
                            Your internship will be submitted for
                            admin review. It will become visible to
                            students after it has been approved.
                        </p>

                    </div>

                </div>


                <!-- ==================================================
                     BUTTONS
                ================================================== -->

                <div class="form-actions">


                    <a
                        href="company-internships.php"
                        class="cancel-button"
                    >
                        Cancel
                    </a>


                    <button
                        type="submit"
                        class="submit-button"
                    >

                        <i class="fa-solid fa-paper-plane"></i>

                        Submit Internship

                    </button>

                </div>


            </form>

        </section>

    </main>

</div>


</body>

</html>
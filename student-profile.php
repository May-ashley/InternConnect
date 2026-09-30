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

if (
    !isset($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'student'
) {
    header("Location: login.php");
    exit();
}


/* ==================================================
   STUDENT INFORMATION
================================================== */

$student_id = (int) $_SESSION['user_id'];

$student_name = $_SESSION['user_name'] ?? 'Student';
$student_email = $_SESSION['user_email'] ?? '';


/* ==================================================
   DATABASE CONNECTION
================================================== */

$conn = connect();


/* ==================================================
   DEFAULT PROFILE VALUES
================================================== */

$phone = '';
$university = '';
$major = '';
$year_of_study = '';
$portfolio_url = '';
$linkedin_url = '';

$success_message = '';
$error_message = '';

/*
   This is used only for the form.
   After successful save, these fields will be cleared.
*/
$clear_form = false;


/* ==================================================
   LOAD STUDENT PROFILE
================================================== */

$sql = "
    SELECT
        u.name,
        u.email,
        s.phone,
        s.university,
        s.major,
        s.year_of_study,
        s.portfolio_url,
        s.linkedin_url
    FROM users u
    LEFT JOIN students_profile s
        ON u.id = s.user_id
    WHERE u.id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $student_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {

        $student_name = $row['name'] ?? $student_name;
        $student_email = $row['email'] ?? $student_email;

        $phone = $row['phone'] ?? '';
        $university = $row['university'] ?? '';
        $major = $row['major'] ?? '';
        $year_of_study = $row['year_of_study'] ?? '';
        $portfolio_url = $row['portfolio_url'] ?? '';
        $linkedin_url = $row['linkedin_url'] ?? '';
    }

    mysqli_stmt_close($stmt);
}


/* ==================================================
   UPDATE PROFILE
================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $phone = trim($_POST['phone'] ?? '');
    $university = trim($_POST['university'] ?? '');
    $major = trim($_POST['major'] ?? '');
    $year_of_study = trim($_POST['year_of_study'] ?? '');
    $portfolio_url = trim($_POST['portfolio_url'] ?? '');
    $linkedin_url = trim($_POST['linkedin_url'] ?? '');


    /* ==================================================
       CHECK IF PROFILE EXISTS
    ================================================== */

    $check_sql = "
        SELECT id
        FROM students_profile
        WHERE user_id = ?
        LIMIT 1
    ";

    $check_stmt = mysqli_prepare(
        $conn,
        $check_sql
    );

    $profile_exists = false;

    if ($check_stmt) {

        mysqli_stmt_bind_param(
            $check_stmt,
            "i",
            $student_id
        );

        mysqli_stmt_execute($check_stmt);

        $check_result = mysqli_stmt_get_result(
            $check_stmt
        );

        if (mysqli_num_rows($check_result) > 0) {
            $profile_exists = true;
        }

        mysqli_stmt_close($check_stmt);
    }


    /* ==================================================
       UPDATE EXISTING PROFILE
    ================================================== */

    if ($profile_exists) {

        $update_sql = "
            UPDATE students_profile
            SET
                phone = ?,
                university = ?,
                major = ?,
                year_of_study = ?,
                portfolio_url = ?,
                linkedin_url = ?
            WHERE user_id = ?
        ";

        $update_stmt = mysqli_prepare(
            $conn,
            $update_sql
        );

        if ($update_stmt) {

            mysqli_stmt_bind_param(
                $update_stmt,
                "ssssssi",
                $phone,
                $university,
                $major,
                $year_of_study,
                $portfolio_url,
                $linkedin_url,
                $student_id
            );

            if (mysqli_stmt_execute($update_stmt)) {

                $success_message =
                    "Your profile has been updated successfully.";

                /*
                   Keep saved values for the summary,
                   but clear only the editable form.
                */
                $saved_phone = $phone;
                $saved_university = $university;
                $saved_major = $major;
                $saved_year_of_study = $year_of_study;
                $saved_portfolio_url = $portfolio_url;
                $saved_linkedin_url = $linkedin_url;

                $clear_form = true;

            } else {

                $error_message =
                    "Something went wrong while updating your profile.";
            }

            mysqli_stmt_close($update_stmt);

        } else {

            $error_message =
                "Unable to prepare the profile update.";
        }


    /* ==================================================
       CREATE NEW PROFILE
    ================================================== */

    } else {

        $insert_sql = "
            INSERT INTO students_profile
            (
                user_id,
                phone,
                university,
                major,
                year_of_study,
                portfolio_url,
                linkedin_url
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";

        $insert_stmt = mysqli_prepare(
            $conn,
            $insert_sql
        );

        if ($insert_stmt) {

            mysqli_stmt_bind_param(
                $insert_stmt,
                "issssss",
                $student_id,
                $phone,
                $university,
                $major,
                $year_of_study,
                $portfolio_url,
                $linkedin_url
            );

            if (mysqli_stmt_execute($insert_stmt)) {

                $success_message =
                    "Your profile has been created successfully.";

                $saved_phone = $phone;
                $saved_university = $university;
                $saved_major = $major;
                $saved_year_of_study = $year_of_study;
                $saved_portfolio_url = $portfolio_url;
                $saved_linkedin_url = $linkedin_url;

                $clear_form = true;

            } else {

                $error_message =
                    "Something went wrong while saving your profile.";
            }

            mysqli_stmt_close($insert_stmt);

        } else {

            $error_message =
                "Unable to prepare the profile.";
        }
    }


    /* ==================================================
       CLEAR FORM ONLY AFTER SUCCESSFUL SAVE
    ================================================== */

    if ($clear_form) {

        /*
           Keep the saved values separately.
           The form itself will display blank fields.
        */

        $form_phone = '';
        $form_university = '';
        $form_major = '';
        $form_year_of_study = '';
        $form_portfolio_url = '';
        $form_linkedin_url = '';

    } else {

        $form_phone = $phone;
        $form_university = $university;
        $form_major = $major;
        $form_year_of_study = $year_of_study;
        $form_portfolio_url = $portfolio_url;
        $form_linkedin_url = $linkedin_url;
    }

} else {

    /*
       Normal page load:
       form displays current database information.
    */

    $form_phone = $phone;
    $form_university = $university;
    $form_major = $major;
    $form_year_of_study = $year_of_study;
    $form_portfolio_url = $portfolio_url;
    $form_linkedin_url = $linkedin_url;

    $saved_phone = $phone;
    $saved_university = $university;
    $saved_major = $major;
    $saved_year_of_study = $year_of_study;
    $saved_portfolio_url = $portfolio_url;
    $saved_linkedin_url = $linkedin_url;
}


/* ==================================================
   SAVED VALUES FOR PROFILE SUMMARY
================================================== */

if (!isset($saved_phone)) {
    $saved_phone = $phone;
}

if (!isset($saved_university)) {
    $saved_university = $university;
}

if (!isset($saved_major)) {
    $saved_major = $major;
}

if (!isset($saved_year_of_study)) {
    $saved_year_of_study = $year_of_study;
}

if (!isset($saved_portfolio_url)) {
    $saved_portfolio_url = $portfolio_url;
}

if (!isset($saved_linkedin_url)) {
    $saved_linkedin_url = $linkedin_url;
}


/* ==================================================
   PROFILE COMPLETION
================================================== */

$profile_fields = [
    $saved_phone,
    $saved_university,
    $saved_major,
    $saved_year_of_study,
    $saved_portfolio_url,
    $saved_linkedin_url
];

$completed_fields = 0;

foreach ($profile_fields as $field) {

    if (!empty(trim($field))) {
        $completed_fields++;
    }
}

$profile_completion = (int) round(
    ($completed_fields / count($profile_fields)) * 100
);


/* ==================================================
   STUDENT INITIAL
================================================== */

$name_for_initial = trim($student_name);

$student_initial = !empty($name_for_initial)
    ? strtoupper(substr($name_for_initial, 0, 1))
    : 'S';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Profile | InternConnect</title>

    <!-- Outfit Font -->
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <!-- Profile CSS -->
    <link
        rel="stylesheet"
        href="student-profile.css"
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

            <a href="student-internships.php">
                <i class="fa-solid fa-briefcase"></i>
                <span>Browse Internships</span>
            </a>

            <a href="student-applications.php">
                <i class="fa-regular fa-file-lines"></i>
                <span>My Applications</span>
            </a>

            <a
                href="student-profile.php"
                class="active"
            >
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
                    Student Profile
                </p>

                <h1>
                    My Profile
                </h1>

                <p class="dashboard-subtitle">
                    Manage your personal information and
                    keep your profile ready for internship
                    opportunities.
                </p>

            </div>


            <div class="student-info">

                <div class="student-avatar">
                    <?php echo htmlspecialchars($student_initial); ?>
                </div>

                <div>

                    <strong>
                        <?php echo htmlspecialchars($student_name); ?>
                    </strong>

                    <span>
                        <?php echo htmlspecialchars($student_email); ?>
                    </span>

                </div>

            </div>

        </header>


        <!-- ==================================================
             MESSAGES
        ================================================== -->

        <?php if (!empty($success_message)): ?>

            <div class="alert success-alert">

                <i class="fa-solid fa-circle-check"></i>

                <span>
                    <?php echo htmlspecialchars($success_message); ?>
                </span>

            </div>

        <?php endif; ?>


        <?php if (!empty($error_message)): ?>

            <div class="alert error-alert">

                <i class="fa-solid fa-circle-exclamation"></i>

                <span>
                    <?php echo htmlspecialchars($error_message); ?>
                </span>

            </div>

        <?php endif; ?>


        <!-- ==================================================
             PROFILE LAYOUT
        ================================================== -->

        <section class="profile-layout">


            <!-- ==================================================
                 PROFILE SUMMARY
            ================================================== -->

            <div class="profile-summary-card">

                <div class="profile-avatar">
                    <?php echo htmlspecialchars($student_initial); ?>
                </div>


                <h2>
                    <?php echo htmlspecialchars($student_name); ?>
                </h2>


                <p class="profile-email">
                    <?php echo htmlspecialchars($student_email); ?>
                </p>


                <div class="student-role">

                    <i class="fa-solid fa-user-graduate"></i>

                    Student

                </div>


                <!-- PROFILE COMPLETION -->

                <div class="completion-box">

                    <div class="completion-header">

                        <span>
                            Profile Completion
                        </span>

                        <strong>
                            <?php echo $profile_completion; ?>%
                        </strong>

                    </div>


                    <div class="completion-bar">

                        <div
                            class="completion-fill"
                            style="width: <?php echo $profile_completion; ?>%;"
                        ></div>

                    </div>


                    <p>
                        Complete your profile to help
                        companies learn more about you.
                    </p>

                </div>


                <div class="summary-divider"></div>


                <!-- UNIVERSITY -->

                <div class="summary-item">

                    <i class="fa-solid fa-building-columns"></i>

                    <div>

                        <span>
                            University
                        </span>

                        <strong>
                            <?php
                            echo !empty($saved_university)
                                ? htmlspecialchars($saved_university)
                                : 'Not added';
                            ?>
                        </strong>

                    </div>

                </div>


                <!-- MAJOR -->

                <div class="summary-item">

                    <i class="fa-solid fa-graduation-cap"></i>

                    <div>

                        <span>
                            Major
                        </span>

                        <strong>
                            <?php
                            echo !empty($saved_major)
                                ? htmlspecialchars($saved_major)
                                : 'Not added';
                            ?>
                        </strong>

                    </div>

                </div>


                <!-- YEAR -->

                <div class="summary-item">

                    <i class="fa-regular fa-calendar"></i>

                    <div>

                        <span>
                            Year of Study
                        </span>

                        <strong>
                            <?php
                            echo !empty($saved_year_of_study)
                                ? htmlspecialchars($saved_year_of_study)
                                : 'Not added';
                            ?>
                        </strong>

                    </div>

                </div>


                <!-- PHONE -->

                <div class="summary-item">

                    <i class="fa-solid fa-phone"></i>

                    <div>

                        <span>
                            Phone
                        </span>

                        <strong>
                            <?php
                            echo !empty($saved_phone)
                                ? htmlspecialchars($saved_phone)
                                : 'Not added';
                            ?>
                        </strong>

                    </div>

                </div>

            </div>


            <!-- ==================================================
                 PROFILE FORM
            ================================================== -->

            <div class="profile-form-card">


                <div class="profile-card-header">

                    <div>

                        <p class="card-label">
                            Personal Information
                        </p>

                        <h2>
                            Profile Details
                        </h2>

                        <p>
                            Update your information below.
                        </p>

                    </div>


                    <div class="header-icon">

                        <i class="fa-regular fa-user"></i>

                    </div>

                </div>


                <form
                    method="POST"
                    action=""
                    class="profile-form"
                >


                    <!-- NAME -->

                    <div class="form-group">

                        <label>
                            Full Name
                        </label>

                        <div class="input-wrapper">

                            <i class="fa-regular fa-user"></i>

                            <input
                                type="text"
                                value="<?php echo htmlspecialchars($student_name); ?>"
                                readonly
                            >

                        </div>

                        <small>
                            Your name is managed from your account.
                        </small>

                    </div>


                    <!-- EMAIL -->

                    <div class="form-group">

                        <label>
                            Email Address
                        </label>

                        <div class="input-wrapper">

                            <i class="fa-regular fa-envelope"></i>

                            <input
                                type="email"
                                value="<?php echo htmlspecialchars($student_email); ?>"
                                readonly
                            >

                        </div>

                        <small>
                            Your email is managed from your account.
                        </small>

                    </div>


                    <!-- PHONE -->

                    <div class="form-group">

                        <label for="phone">
                            Phone Number
                        </label>

                        <div class="input-wrapper">

                            <i class="fa-solid fa-phone"></i>

                            <input
                                type="text"
                                id="phone"
                                name="phone"
                                placeholder="Enter your phone number"
                                value="<?php echo htmlspecialchars($form_phone); ?>"
                            >

                        </div>

                    </div>


                    <!-- UNIVERSITY -->

                    <div class="form-group">

                        <label for="university">
                            University
                        </label>

                        <div class="input-wrapper">

                            <i class="fa-solid fa-building-columns"></i>

                            <input
                                type="text"
                                id="university"
                                name="university"
                                placeholder="Enter your university"
                                value="<?php echo htmlspecialchars($form_university); ?>"
                            >

                        </div>

                    </div>


                    <!-- MAJOR -->

                    <div class="form-group">

                        <label for="major">
                            Major / Field of Study
                        </label>

                        <div class="input-wrapper">

                            <i class="fa-solid fa-graduation-cap"></i>

                            <input
                                type="text"
                                id="major"
                                name="major"
                                placeholder="e.g. Computer Science"
                                value="<?php echo htmlspecialchars($form_major); ?>"
                            >

                        </div>

                    </div>


                    <!-- YEAR -->

                    <div class="form-group">

                        <label for="year_of_study">
                            Year of Study
                        </label>

                        <div class="input-wrapper">

                            <i class="fa-regular fa-calendar"></i>

                            <input
                                type="text"
                                id="year_of_study"
                                name="year_of_study"
                                placeholder="e.g. Year 3"
                                value="<?php echo htmlspecialchars($form_year_of_study); ?>"
                            >

                        </div>

                    </div>


                    <!-- PORTFOLIO -->

                    <div class="form-group">

                        <label for="portfolio_url">
                            Portfolio URL
                        </label>

                        <div class="input-wrapper">

                            <i class="fa-solid fa-link"></i>

                            <input
                                type="url"
                                id="portfolio_url"
                                name="portfolio_url"
                                placeholder="https://yourportfolio.com"
                                value="<?php echo htmlspecialchars($form_portfolio_url); ?>"
                            >

                        </div>

                    </div>


                    <!-- LINKEDIN -->

                    <div class="form-group">

                        <label for="linkedin_url">
                            LinkedIn URL
                        </label>

                        <div class="input-wrapper">

                            <i class="fa-brands fa-linkedin-in"></i>

                            <input
                                type="url"
                                id="linkedin_url"
                                name="linkedin_url"
                                placeholder="https://linkedin.com/in/yourname"
                                value="<?php echo htmlspecialchars($form_linkedin_url); ?>"
                            >

                        </div>

                    </div>


                    <!-- BUTTONS -->

                    <div class="form-actions">

                        <a
                            href="student-dashboard.php"
                            class="cancel-button"
                        >
                            Cancel
                        </a>


                        <button
                            type="submit"
                            class="save-button"
                        >

                            <i class="fa-solid fa-check"></i>

                            Save Changes

                        </button>

                    </div>


                </form>

            </div>

        </section>

    </main>

</div>


<?php if ($clear_form): ?>

<script>

/*
   Clear editable fields after successful save.
   The saved information remains in the database
   and is displayed in the profile summary.
*/

document.addEventListener("DOMContentLoaded", function () {

    const fields = [
        "phone",
        "university",
        "major",
        "year_of_study",
        "portfolio_url",
        "linkedin_url"
    ];

    fields.forEach(function (fieldId) {

        const field = document.getElementById(fieldId);

        if (field) {
            field.value = "";
        }

    });

});

</script>

<?php endif; ?>

</body>
</html>
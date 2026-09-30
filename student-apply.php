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

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
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
   INTERNSHIP ID
================================================== */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: student-internships.php");
    exit();
}

$internship_id = (int) $_GET['id'];


/* ==================================================
   DATABASE CONNECTION
================================================== */

$conn = connect();


/* ==================================================
   GET INTERNSHIP INFORMATION
================================================== */

$sql = "
    SELECT
        internships.id,
        internships.title,
        internships.category,
        internships.location,
        internships.type,
        companies.company_name,
        companies.logo

    FROM internships

    INNER JOIN companies
        ON internships.company_id = companies.id

    WHERE internships.id = ?
      AND internships.status = 'active'

    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database error.");
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $internship_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$internship = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* ==================================================
   INTERNSHIP NOT FOUND
================================================== */

if (!$internship) {
    header("Location: student-internships.php");
    exit();
}


/* ==================================================
   CHECK ALREADY APPLIED
================================================== */

$already_applied = false;

$sql = "
    SELECT id
    FROM applications
    WHERE student_id = ?
      AND internship_id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $student_id,
        $internship_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $already_applied = true;
    }

    mysqli_stmt_close($stmt);
}


/* ==================================================
   FORM VARIABLES
================================================== */

$success = "";
$error = "";

$cover_letter = "";


/* ==================================================
   PROCESS APPLICATION
================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already_applied) {

    $cover_letter = trim($_POST['cover_letter'] ?? "");


    /* ----------------------------------------------
       VALIDATE COVER LETTER
    ---------------------------------------------- */

    if (empty($cover_letter)) {

        $error = "Please write a cover letter before applying.";

    } elseif (!isset($_FILES['resume']) || $_FILES['resume']['error'] === UPLOAD_ERR_NO_FILE) {

        $error = "Please upload your resume.";

    } else {

        $resume = $_FILES['resume'];


        /* ------------------------------------------
           FILE SETTINGS
        ------------------------------------------ */

        $allowed_extensions = [
            'pdf',
            'doc',
            'docx'
        ];

        $max_size = 5 * 1024 * 1024; // 5MB

        $file_extension = strtolower(
            pathinfo(
                $resume['name'],
                PATHINFO_EXTENSION
            )
        );


        /* ------------------------------------------
           VALIDATE FILE
        ------------------------------------------ */

        if ($resume['error'] !== UPLOAD_ERR_OK) {

            $error = "There was a problem uploading your resume.";

        } elseif (!in_array($file_extension, $allowed_extensions)) {

            $error = "Resume must be PDF, DOC, or DOCX.";

        } elseif ($resume['size'] > $max_size) {

            $error = "Resume must be smaller than 5MB.";

        } else {


            /* --------------------------------------
               CREATE UPLOAD DIRECTORY
            -------------------------------------- */

            $upload_directory = "uploads/resumes/";

            if (!is_dir($upload_directory)) {

                mkdir(
                    $upload_directory,
                    0777,
                    true
                );
            }


            /* --------------------------------------
               UNIQUE FILE NAME
            -------------------------------------- */

            $new_file_name =
                "resume_" .
                $student_id .
                "_" .
                time() .
                "." .
                $file_extension;


            $resume_path =
                $upload_directory .
                $new_file_name;


            /* --------------------------------------
               MOVE FILE
            -------------------------------------- */

            if (!move_uploaded_file(
                $resume['tmp_name'],
                $resume_path
            )) {

                $error = "Unable to upload your resume.";

            } else {


                /* ----------------------------------
                   INSERT APPLICATION
                ---------------------------------- */

                $sql = "
                    INSERT INTO applications
                    (
                        student_id,
                        internship_id,
                        cover_letter,
                        resume,
                        status
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        'pending'
                    )
                ";

                $stmt = mysqli_prepare($conn, $sql);

                if ($stmt) {

                    mysqli_stmt_bind_param(
                        $stmt,
                        "iiss",
                        $student_id,
                        $internship_id,
                        $cover_letter,
                        $resume_path
                    );

                    if (mysqli_stmt_execute($stmt)) {

                        $success =
                            "Your application has been submitted successfully.";

                        $already_applied = true;

                        $cover_letter = "";

                    } else {

                        $error =
                            "Something went wrong while submitting your application.";

                        /* Remove uploaded file if database insert fails */

                        if (file_exists($resume_path)) {
                            unlink($resume_path);
                        }
                    }

                    mysqli_stmt_close($stmt);

                } else {

                    $error =
                        "Unable to prepare application.";

                    if (file_exists($resume_path)) {
                        unlink($resume_path);
                    }
                }
            }
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
        Apply for Internship | InternConnect
    </title>


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


    <!-- Application CSS -->

    <link
        rel="stylesheet"
        href="student-apply.css"
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

                <span>
                    Dashboard
                </span>

            </a>


            <a
                href="student-internships.php"
                class="active"
            >

                <i class="fa-solid fa-briefcase"></i>

                <span>
                    Browse Internships
                </span>

            </a>


            <a href="student-applications.php">

                <i class="fa-regular fa-file-lines"></i>

                <span>
                    My Applications
                </span>

            </a>


           


            <a href="student-profile.php">

                <i class="fa-regular fa-user"></i>

                <span>
                    My Profile
                </span>

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

                <span>
                    Logout
                </span>

            </a>

        </div>

    </aside>


    <!-- ==================================================
         MAIN
    ================================================== -->

    <main class="dashboard-main">


        <!-- BACK -->

        <a
            href="student-internship-details.php?id=<?php echo $internship_id; ?>"
            class="back-button"
        >

            <i class="fa-solid fa-arrow-left"></i>

            Back to Internship-details

        </a>


        <!-- ==================================================
             PAGE HEADER
        ================================================== -->

        <div class="page-header">

            <p class="page-label">
                APPLICATION
            </p>

            <h1>
                Apply for this Internship
            </h1>

            <p>
                Complete your application and submit your resume
                and cover letter.
            </p>

        </div>


        <!-- ==================================================
             APPLICATION LAYOUT
        ================================================== -->

        <div class="apply-layout">


            <!-- ==================================================
                 INTERNSHIP SUMMARY
            ================================================== -->

            <aside class="internship-summary">


                <p class="summary-label">
                    INTERNSHIP
                </p>


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


                <h2>
                    <?php
                    echo htmlspecialchars(
                        $internship['title']
                    );
                    ?>
                </h2>


                <p class="company-name">
                    <?php
                    echo htmlspecialchars(
                        $internship['company_name']
                    );
                    ?>
                </p>


                <div class="internship-details">


                    <div class="detail-item">

                        <i class="fa-solid fa-location-dot"></i>

                        <div>

                            <span>
                                Location
                            </span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $internship['location']
                                );
                                ?>
                            </strong>

                        </div>

                    </div>


                    <div class="detail-item">

                        <i class="fa-solid fa-briefcase"></i>

                        <div>

                            <span>
                                Type
                            </span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $internship['type']
                                );
                                ?>
                            </strong>

                        </div>

                    </div>


                    <div class="detail-item">

                        <i class="fa-solid fa-tag"></i>

                        <div>

                            <span>
                                Category
                            </span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $internship['category']
                                );
                                ?>
                            </strong>

                        </div>

                    </div>


                </div>

            </aside>


            <!-- ==================================================
                 APPLICATION FORM
            ================================================== -->

            <section class="application-card">


                <?php if (!empty($success)): ?>

                    <div class="message success-message">

                        <div class="message-icon">

                            <i class="fa-solid fa-check"></i>

                        </div>

                        <div>

                            <strong>
                                Application Submitted
                            </strong>

                            <p>
                                <?php echo htmlspecialchars($success); ?>
                            </p>


                            <div class="success-actions">

                                <a
                                    href="student-applications.php"
                                    class="primary-button"
                                >
                                    View My Applications
                                </a>

                                <a
                                    href="student-internships.php"
                                    class="secondary-button"
                                >
                                    Browse Internships
                                </a>

                            </div>

                        </div>

                    </div>


                <?php elseif ($already_applied): ?>


                    <div class="already-applied">

                        <div class="already-icon">

                            <i class="fa-solid fa-check"></i>

                        </div>

                        <h2>
                            Already Applied
                        </h2>

                        <p>
                            You have already submitted an application
                            for this internship.
                        </p>

                        <a
                            href="student-applications.php"
                            class="primary-button"
                        >
                            View My Applications
                        </a>

                    </div>


                <?php else: ?>


                    <div class="application-header">

                        <p class="application-label">
                            YOUR APPLICATION
                        </p>

                        <h2>
                            Submit Your Application
                        </h2>

                        <p>
                            Please provide your information and
                            application documents below.
                        </p>

                    </div>


                    <?php if (!empty($error)): ?>

                        <div class="message error-message">

                            <div class="message-icon">

                                <i class="fa-solid fa-xmark"></i>

                            </div>

                            <div>

                                <strong>
                                    Application Error
                                </strong>

                                <p>
                                    <?php
                                    echo htmlspecialchars($error);
                                    ?>
                                </p>

                            </div>

                        </div>

                    <?php endif; ?>


                    <form
                        method="POST"
                        enctype="multipart/form-data"
                        class="application-form"
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

                        </div>


                        <!-- RESUME -->

                        <div class="form-group">

                            <label>

                                Resume

                                <span class="required">
                                    Required
                                </span>

                            </label>


                            <div class="upload-box">

                                <input
                                    type="file"
                                    name="resume"
                                    id="resume"
                                    accept=".pdf,.doc,.docx"
                                    required
                                >

                                <label
                                    for="resume"
                                    class="upload-content"
                                >

                                    <div class="upload-icon">

                                        <i class="fa-solid fa-cloud-arrow-up"></i>

                                    </div>

                                    <div class="upload-text">

                                        <strong>
                                            Upload your resume
                                        </strong>

                                        <span>
                                            PDF, DOC or DOCX • Maximum 5MB
                                        </span>

                                    </div>

                                </label>

                            </div>


                            <div
                                class="selected-file"
                                id="selectedFile"
                            >

                                <i class="fa-regular fa-file"></i>

                                <span id="fileName">
                                </span>

                                <button
                                    type="button"
                                    class="remove-file"
                                    id="removeFile"
                                    title="Remove file"
                                >
                                    <i class="fa-solid fa-xmark"></i>
                                </button>

                            </div>

                        </div>


                        <!-- COVER LETTER -->

                        <div class="form-group">

                            <label>

                                Cover Letter

                                <span class="required">
                                    Required
                                </span>

                            </label>


                            <textarea
                                name="cover_letter"
                                id="cover_letter"
                                placeholder="Write your cover letter here..."
                                required
                            ><?php echo htmlspecialchars($cover_letter); ?></textarea>


                            <small class="field-help">
                                Explain why you are interested in this
                                internship and why you would be a good fit.
                            </small>

                        </div>


                        <!-- NOTE -->

                        <div class="application-note">

                            <i class="fa-solid fa-circle-info"></i>

                            <p>
                                Your application will be reviewed by the
                                company. Your application status will
                                appear in My Applications after submission.
                            </p>

                        </div>


                        <!-- SUBMIT -->

                        <button
                            type="submit"
                            class="submit-button"
                        >

                            <i class="fa-solid fa-paper-plane"></i>

                            Submit Application

                        </button>


                    </form>

                <?php endif; ?>


            </section>

        </div>


    </main>

</div>


<script>

const resumeInput = document.getElementById("resume");
const selectedFile = document.getElementById("selectedFile");
const fileName = document.getElementById("fileName");
const removeFile = document.getElementById("removeFile");


/* ==================================================
   SHOW SELECTED FILE
================================================== */

if (resumeInput) {

    resumeInput.addEventListener("change", function () {

        if (this.files.length > 0) {

            fileName.textContent = this.files[0].name;

            selectedFile.classList.add("show");

        } else {

            selectedFile.classList.remove("show");

        }

    });

}


/* ==================================================
   REMOVE SELECTED FILE
================================================== */

if (removeFile) {

    removeFile.addEventListener("click", function () {

        /* Clear the actual file input */
        resumeInput.value = "";

        /* Clear filename */
        fileName.textContent = "";

        /* Hide selected file */
        selectedFile.classList.remove("show");

    });

}

</script>


</body>

</html>
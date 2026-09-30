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

$conn = connect();

$user_id = (int) $_SESSION['user_id'];

$message = "";
$message_type = "";

/* ==================================================
   GET COMPANY PROFILE
================================================== */

$stmt = mysqli_prepare(
    $conn,
    "SELECT 
        c.id,
        c.company_name,
        c.description,
        c.industry,
        c.location,
        c.website,
        c.logo,
        u.email
     FROM companies c
     INNER JOIN users u ON c.user_id = u.id
     WHERE c.user_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$company = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$company) {
    die("Company profile not found.");
}

/* ==================================================
   UPDATE PROFILE
================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $company_name = trim($_POST['company_name'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $industry     = trim($_POST['industry'] ?? '');
    $location     = trim($_POST['location'] ?? '');
    $website      = trim($_POST['website'] ?? '');

    if ($company_name === '') {

        $message = "Company name is required.";
        $message_type = "error";

    } else {

        $logo_name = $company['logo'];

        /* ==================================================
           LOGO UPLOAD
        ================================================== */

        if (
            isset($_FILES['logo']) &&
            $_FILES['logo']['error'] === UPLOAD_ERR_OK
        ) {

            $allowed_extensions = [
                'jpg',
                'jpeg',
                'png',
                'webp'
            ];

            $file_name = $_FILES['logo']['name'];
            $tmp_name  = $_FILES['logo']['tmp_name'];
            $file_size = $_FILES['logo']['size'];

            $extension = strtolower(
                pathinfo($file_name, PATHINFO_EXTENSION)
            );

            if (!in_array($extension, $allowed_extensions)) {

                $message = "Only JPG, JPEG, PNG and WEBP images are allowed.";
                $message_type = "error";

            } elseif ($file_size > 5 * 1024 * 1024) {

                $message = "Logo image must be smaller than 5MB.";
                $message_type = "error";

            } else {

                $upload_directory = "uploads/company_logos/";

                if (!is_dir($upload_directory)) {
                    mkdir($upload_directory, 0777, true);
                }

                $new_file_name =
                    "company_" .
                    $user_id .
                    "_" .
                    time() .
                    "." .
                    $extension;

                $upload_path =
                    $upload_directory .
                    $new_file_name;

                if (move_uploaded_file($tmp_name, $upload_path)) {

                    /* Delete old logo */
                    if (
                        !empty($company['logo']) &&
                        file_exists($company['logo'])
                    ) {
                        unlink($company['logo']);
                    }

                    $logo_name = $upload_path;

                } else {

                    $message = "Failed to upload the company logo.";
                    $message_type = "error";
                }
            }
        }

        /* ==================================================
           UPDATE DATABASE
        ================================================== */

        if ($message_type !== "error") {

            $update_stmt = mysqli_prepare(
                $conn,
                "UPDATE companies
                 SET company_name = ?,
                     description = ?,
                     industry = ?,
                     location = ?,
                     website = ?,
                     logo = ?
                 WHERE user_id = ?"
            );

            mysqli_stmt_bind_param(
                $update_stmt,
                "ssssssi",
                $company_name,
                $description,
                $industry,
                $location,
                $website,
                $logo_name,
                $user_id
            );

            if (mysqli_stmt_execute($update_stmt)) {

                $message = "Company profile updated successfully.";
                $message_type = "success";

                /* Update displayed data */
                $company['company_name'] = $company_name;
                $company['description'] = $description;
                $company['industry'] = $industry;
                $company['location'] = $location;
                $company['website'] = $website;
                $company['logo'] = $logo_name;

                $_SESSION['company_name'] = $company_name;

            } else {

                $message = "Failed to update company profile.";
                $message_type = "error";
            }

            mysqli_stmt_close($update_stmt);
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

    <title>Company Profile | InternConnect</title>

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

    <!-- Company Theme -->
    <link
        rel="stylesheet"
        href="company-profile.css"
    >

</head>

<body>

<!-- ==================================================
     BACKGROUND EFFECTS
================================================== -->

<div class="background-decoration decoration-one"></div>
<div class="background-decoration decoration-two"></div>




    <!-- ==================================================
         SIDEBAR
    ================================================== -->

    <aside class="sidebar">


        <!-- LOGO -->

        <div class="sidebar-logo">

            <h2>
                Intern<span>Connect</span>
            </h2>

            <p>
                Company Portal
            </p>

        </div>


        <!-- NAVIGATION -->

        <nav class="sidebar-nav">


            <a
                href="company-dashboard.php"
               
            >

                <i class="fa-solid fa-chart-line"></i>

                <span>
                    Dashboard
                </span>

            </a>


            <a href="company-internships.php">

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


            <a href="company-profile.php"  class="active">

                <i class="fa-regular fa-building"></i>

                <span>
                    Company Profile
                </span>

            </a>


        </nav>


        <!-- BOTTOM -->

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
     MAIN CONTENT
================================================== -->

<main class="main-content">

    <!-- PAGE HEADER -->

    <div class="page-header">

        <div>

            <div class="page-badge">
                <i class="fa-solid fa-building"></i>
                COMPANY PROFILE
            </div>

            <h1>
                Company <span>Profile</span>
            </h1>

            <p>
                Manage your company information and profile details.
            </p>

        </div>

    </div>


    <!-- ==================================================
         MESSAGE
    ================================================== -->

    <?php if (!empty($message)): ?>

        <div
            class="message <?= $message_type === 'success'
                ? 'message-success'
                : 'message-error'
            ?>"
        >

            <i class="fa-solid
                <?= $message_type === 'success'
                    ? 'fa-circle-check'
                    : 'fa-circle-exclamation'
                ?>"
            ></i>

            <span>
                <?= htmlspecialchars($message) ?>
            </span>

        </div>

    <?php endif; ?>


    <!-- ==================================================
         PROFILE LAYOUT
    ================================================== -->

    <div class="profile-layout">


        <!-- ==================================================
             PROFILE CARD
        ================================================== -->

        <div class="profile-card">

            <div class="profile-card-header">

                <div class="header-icon">
                    <i class="fa-solid fa-building"></i>
                </div>

                <div>

                    <h2>Company Information</h2>

                    <p>
                        Your company information displayed to students.
                    </p>

                </div>

            </div>


            <!-- COMPANY LOGO -->

            <div class="company-logo-section">

                <div class="company-logo">

                    <?php if (!empty($company['logo']) && file_exists($company['logo'])): ?>

                        <img
                            src="<?= htmlspecialchars($company['logo']) ?>"
                            alt="Company Logo"
                        >

                    <?php else: ?>

                        <i class="fa-solid fa-building"></i>

                    <?php endif; ?>

                </div>

                <div class="logo-info">

                    <h3>
                        Company Logo
                    </h3>

                    <p>
                        Upload a professional logo for your company.
                    </p>

                    <small>
                        JPG, PNG or WEBP · Maximum 5MB
                    </small>

                </div>

            </div>


            <!-- FORM -->

            <form
                method="POST"
                enctype="multipart/form-data"
                class="profile-form"
            >

                <!-- LOGO -->

                <div class="form-group full-width">

                    <label for="logo">
                        <i class="fa-solid fa-image"></i>
                        Change Logo
                    </label>

                    <input
                        type="file"
                        id="logo"
                        name="logo"
                        accept=".jpg,.jpeg,.png,.webp"
                    >

                </div>


                <!-- COMPANY NAME -->

                <div class="form-group">

                    <label for="company_name">
                        <i class="fa-solid fa-building"></i>
                        Company Name
                    </label>

                    <input
                        type="text"
                        id="company_name"
                        name="company_name"
                        value="<?= htmlspecialchars($company['company_name']) ?>"
                        placeholder="Enter company name"
                        required
                    >

                </div>


                <!-- EMAIL -->

                <div class="form-group">

                    <label for="email">
                        <i class="fa-solid fa-envelope"></i>
                        Company Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        value="<?= htmlspecialchars($company['email']) ?>"
                        readonly
                        class="readonly-input"
                    >

                    <small>
                        Account email cannot be changed here.
                    </small>

                </div>


                <!-- INDUSTRY -->

                <div class="form-group">

                    <label for="industry">
                        <i class="fa-solid fa-layer-group"></i>
                        Industry
                    </label>

                    <input
                        type="text"
                        id="industry"
                        name="industry"
                        value="<?= htmlspecialchars($company['industry'] ?? '') ?>"
                        placeholder="e.g. Technology"
                    >

                </div>


                <!-- LOCATION -->

                <div class="form-group">

                    <label for="location">
                        <i class="fa-solid fa-location-dot"></i>
                        Location
                    </label>

                    <input
                        type="text"
                        id="location"
                        name="location"
                        value="<?= htmlspecialchars($company['location'] ?? '') ?>"
                        placeholder="e.g. Yangon, Myanmar"
                    >

                </div>


                <!-- WEBSITE -->

                <div class="form-group full-width">

                    <label for="website">
                        <i class="fa-solid fa-globe"></i>
                        Company Website
                    </label>

                    <input
                        type="url"
                        id="website"
                        name="website"
                        value="<?= htmlspecialchars($company['website'] ?? '') ?>"
                        placeholder="https://example.com"
                    >

                </div>


                <!-- DESCRIPTION -->

                <div class="form-group full-width">

                    <label for="description">
                        <i class="fa-solid fa-align-left"></i>
                        Company Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="6"
                        placeholder="Tell students about your company..."
                    ><?= htmlspecialchars($company['description'] ?? '') ?></textarea>

                </div>


                <!-- BUTTON -->

                <div class="form-actions">

                    <button
                        type="submit"
                        class="save-button"
                    >

                        <i class="fa-solid fa-floppy-disk"></i>

                        Save Changes

                    </button>

                </div>

            </form>

        </div>


        <!-- ==================================================
             PROFILE PREVIEW
        ================================================== -->

        <div class="preview-card">

            <div class="preview-header">

                <div class="preview-icon">
                    <i class="fa-solid fa-eye"></i>
                </div>

                <div>

                    <h2>Profile Preview</h2>

                    <p>
                        How students can see your company.
                    </p>

                </div>

            </div>


            <div class="preview-company">

                <div class="preview-logo">

                    <?php if (!empty($company['logo']) && file_exists($company['logo'])): ?>

                        <img
                            src="<?= htmlspecialchars($company['logo']) ?>"
                            alt="Company Logo"
                        >

                    <?php else: ?>

                        <i class="fa-solid fa-building"></i>

                    <?php endif; ?>

                </div>


                <h2>
                    <?= htmlspecialchars(
                        $company['company_name'] ?: 'Company Name'
                    ) ?>
                </h2>


                <?php if (!empty($company['industry'])): ?>

                    <span class="preview-industry">

                        <i class="fa-solid fa-layer-group"></i>

                        <?= htmlspecialchars($company['industry']) ?>

                    </span>

                <?php endif; ?>


                <?php if (!empty($company['location'])): ?>

                    <div class="preview-info">

                        <i class="fa-solid fa-location-dot"></i>

                        <span>
                            <?= htmlspecialchars($company['location']) ?>
                        </span>

                    </div>

                <?php endif; ?>


                <?php if (!empty($company['website'])): ?>

                    <div class="preview-info">

                        <i class="fa-solid fa-globe"></i>

                        <a
                            href="<?= htmlspecialchars($company['website']) ?>"
                            target="_blank"
                        >
                            Visit Website
                        </a>

                    </div>

                <?php endif; ?>


                <?php if (!empty($company['description'])): ?>

                    <div class="preview-description">

                        <h3>About the Company</h3>

                        <p>
                            <?= nl2br(
                                htmlspecialchars($company['description'])
                            ) ?>
                        </p>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

</main>

</body>
</html>
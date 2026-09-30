<?php

session_start();

include("db.php");

$conn = connect();

$error = "";
$success = "";

$selectedRole = $_POST['role'] ?? 'student';


/* =========================================================
   REGISTER
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $role = $_POST['role'] ?? 'student';

    $name = trim($_POST['name'] ?? "");
    $email = trim($_POST['email'] ?? "");

    $password = $_POST['password'] ?? "";
    $confirmPassword = $_POST['confirm_password'] ?? "";


    /* Company fields */

    $companyName = trim($_POST['company_name'] ?? "");
    $industry = trim($_POST['industry'] ?? "");
    $location = trim($_POST['location'] ?? "");
    $website = trim($_POST['website'] ?? "");
    $description = trim($_POST['description'] ?? "");


    /* =====================================================
       VALIDATE ROLE
    ===================================================== */

    if (!in_array($role, ["student", "company"])) {

        $error = "Please select a valid account type.";

        $selectedRole = "student";

    } elseif (

        $name === "" ||
        $email === "" ||
        $password === "" ||
        $confirmPassword === ""

    ) {

        $error = "Please fill in all required fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif ($password !== $confirmPassword) {

        $error = "Passwords do not match.";

    } elseif (strlen($password) < 6) {

        $error = "Password must be at least 6 characters.";

    } elseif ($role === "company" && $companyName === "") {

        $error = "Please enter your company name.";

    } else {


        /* =================================================
           CHECK EXISTING EMAIL
        ================================================= */

        $check = $conn->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $check->bind_param("s", $email);

        $check->execute();

        $result = $check->get_result();

        $existingUser = $result->fetch_assoc();

        $check->close();


        if ($existingUser) {

            $error = "An account with this email already exists.";

        } else {


            /* =========================================
               START TRANSACTION
            ========================================= */

            $conn->begin_transaction();


            try {


                /* =========================================
                   HASH PASSWORD
                ========================================= */

                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


                /* =========================================
                   CREATE USER
                ========================================= */

                $stmt = $conn->prepare("
                    INSERT INTO users
                    (
                        name,
                        email,
                        password,
                        role
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?
                    )
                ");

                $stmt->bind_param(
                    "ssss",
                    $name,
                    $email,
                    $hashedPassword,
                    $role
                );

                $stmt->execute();

                $userId = $conn->insert_id;

                $stmt->close();


                /* =========================================
                   CREATE COMPANY PROFILE
                   ONLY FOR COMPANY
                ========================================= */

                if ($role === "company") {

                    $companyStmt = $conn->prepare("
                        INSERT INTO companies
                        (
                            user_id,
                            company_name,
                            description,
                            industry,
                            location,
                            website
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?
                        )
                    ");

                    $companyStmt->bind_param(
                        "isssss",
                        $userId,
                        $companyName,
                        $description,
                        $industry,
                        $location,
                        $website
                    );

                    $companyStmt->execute();

                    $companyStmt->close();
                }


                /* =========================================
                   COMPLETE REGISTRATION
                ========================================= */

                $conn->commit();

                $success = "Account created successfully! You can now login.";


                /* Clear fields */

                $name = "";
                $email = "";
                $password = "";
                $confirmPassword = "";

                $companyName = "";
                $industry = "";
                $location = "";
                $website = "";
                $description = "";


            } catch (mysqli_sql_exception $e) {

                $conn->rollback();

                $error = "Something went wrong. Please try again.";
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

    <title>Register | InternConnect</title>


    <!-- Google Font -->

    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- Remix Icons -->

    <link
        href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css"
        rel="stylesheet"
    >


    <!-- Register CSS -->

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>


<body>


<!-- =========================================================
     BACKGROUND
========================================================= -->

<div class="register-bg bg-one"></div>
<div class="register-bg bg-two"></div>


<!-- =========================================================
     REGISTER PAGE
========================================================= -->

<section class="register-page">


    <!-- =====================================================
         LEFT SIDE
         THIS NEVER CHANGES
    ====================================================== -->

    <div class="register-info">


        <!-- BACK TO HOME -->

        <a
            href="index.php"
            class="back-to-home"
        >

            <i class="ri-arrow-left-line"></i>

            Back to Home

        </a>


        <!-- BADGE -->

        <div class="register-badge">

            JOIN INTERNCONNECT

        </div>


        <!-- TITLE -->

        <h1>

            Start Your

            <span>
                Journey Today
            </span>

        </h1>


        <p class="register-description">

            Create your InternConnect account and connect
            with internship opportunities, talented students
            and organizations.

        </p>


        <!-- STUDENT -->

        <div class="register-feature">

            <div class="register-feature-icon">

                <i class="ri-graduation-cap-line"></i>

            </div>

            <div>

                <h3>
                    Student Portal
                </h3>

                <p>
                    Discover internships and build
                    your professional experience.
                </p>

            </div>

        </div>


        <!-- COMPANY -->

        <div class="register-feature">

            <div class="register-feature-icon">

                <i class="ri-building-line"></i>

            </div>

            <div>

                <h3>
                    Company Portal
                </h3>

                <p>
                    Find talented students and post
                    internship opportunities.
                </p>

            </div>

        </div>


        <!-- ADMIN -->

        <div class="register-feature">

            <div class="register-feature-icon">

                <i class="ri-shield-user-line"></i>

            </div>

            <div>

                <h3>
                    Admin Portal
                </h3>

                <p>
                    Manage users, companies and
                    internship activities.
                </p>

            </div>

        </div>


    </div>



    <!-- =====================================================
         RIGHT SIDE
         ONLY THIS SIDE CHANGES
    ====================================================== -->

    <div class="register-card">


        <!-- TITLE -->

        <div class="register-title">

            <div class="title-icon">

                <i class="ri-user-add-line"></i>

            </div>

            <div>

                <h2>
                    Create Account
                </h2>

                <p>
                    Join the InternConnect community
                </p>

            </div>

        </div>


        <!-- SUCCESS -->

        <?php if (!empty($success)): ?>

            <div class="register-success">

                <i class="ri-checkbox-circle-line"></i>

                <span>
                    <?= htmlspecialchars($success) ?>
                </span>

            </div>

        <?php endif; ?>


        <!-- ERROR -->

        <?php if (!empty($error)): ?>

            <div class="register-error">

                <i class="ri-error-warning-line"></i>

                <span>
                    <?= htmlspecialchars($error) ?>
                </span>

            </div>

        <?php endif; ?>


        <!-- =================================================
             ROLE SELECTOR
        ================================================== -->

        <div class="role-box">


            <!-- STUDENT -->

            <label class="role-option">

                <input
                    type="radio"
                    name="account_role"
                    value="student"
                    <?= $selectedRole === "student" ? "checked" : "" ?>
                >

                <i class="ri-graduation-cap-line"></i>

                <span>
                    Student
                </span>

            </label>


            <!-- COMPANY -->

            <label class="role-option">

                <input
                    type="radio"
                    name="account_role"
                    value="company"
                    <?= $selectedRole === "company" ? "checked" : "" ?>
                >

                <i class="ri-building-line"></i>

                <span>
                    Company
                </span>

            </label>

        </div>



        <!-- =================================================
             STUDENT FORM
        ================================================== -->

        <form
            method="POST"
            class="register-form <?= $selectedRole === "student" ? "active" : "" ?>"
            id="student-form"
        >

            <input
                type="hidden"
                name="role"
                value="student"
            >


            <div class="input-box">

                <i class="ri-user-line"></i>

                <input
                    type="text"
                    name="name"
                    placeholder="Full Name"
                    value="<?= htmlspecialchars($name ?? '') ?>"
                    required
                >

            </div>


            <div class="input-box">

                <i class="ri-mail-line"></i>

                <input
                    type="email"
                    name="email"
                    placeholder="Email Address"
                    value="<?= htmlspecialchars($email ?? '') ?>"
                    required
                >

            </div>


            <div class="input-box">

                <i class="ri-lock-line"></i>

                <input
                    type="password"
                    name="password"
                    placeholder="Create Password"
                    required
                >

            </div>


            <div class="input-box">

                <i class="ri-lock-password-line"></i>

                <input
                    type="password"
                    name="confirm_password"
                    placeholder="Confirm Password"
                    required
                >

            </div>


            <button
                type="submit"
                class="register-submit"
            >

                <i class="ri-user-add-line"></i>

                Create Student Account

            </button>

        </form>



        <!-- =================================================
             COMPANY FORM
        ================================================== -->

        <form
            method="POST"
            class="register-form <?= $selectedRole === "company" ? "active" : "" ?>"
            id="company-form"
        >

            <input
                type="hidden"
                name="role"
                value="company"
            >


            <div class="form-row">


                <div class="input-box">

                    <i class="ri-user-line"></i>

                    <input
                        type="text"
                        name="name"
                        placeholder="Contact Name"
                        value="<?= htmlspecialchars($name ?? '') ?>"
                        required
                    >

                </div>


                <div class="input-box">

                    <i class="ri-building-line"></i>

                    <input
                        type="text"
                        name="company_name"
                        placeholder="Company Name"
                        value="<?= htmlspecialchars($companyName ?? '') ?>"
                        required
                    >

                </div>

            </div>


            <div class="input-box">

                <i class="ri-mail-line"></i>

                <input
                    type="email"
                    name="email"
                    placeholder="Company Email"
                    value="<?= htmlspecialchars($email ?? '') ?>"
                    required
                >

            </div>


            <div class="form-row">


                <div class="input-box">

                    <i class="ri-briefcase-line"></i>

                    <input
                        type="text"
                        name="industry"
                        placeholder="Industry"
                        value="<?= htmlspecialchars($industry ?? '') ?>"
                    >

                </div>


                <div class="input-box">

                    <i class="ri-map-pin-line"></i>

                    <input
                        type="text"
                        name="location"
                        placeholder="Location"
                        value="<?= htmlspecialchars($location ?? '') ?>"
                    >

                </div>

            </div>


            <div class="input-box">

                <i class="ri-global-line"></i>

                <input
                    type="url"
                    name="website"
                    placeholder="Website (https://example.com)"
                    value="<?= htmlspecialchars($website ?? '') ?>"
                >

            </div>


            <div class="input-box textarea-box">

                <i class="ri-file-text-line"></i>

                <textarea
                    name="description"
                    placeholder="Company Description"
                ><?= htmlspecialchars($description ?? '') ?></textarea>

            </div>


            <div class="form-row">


                <div class="input-box">

                    <i class="ri-lock-line"></i>

                    <input
                        type="password"
                        name="password"
                        placeholder="Create Password"
                        required
                    >

                </div>


                <div class="input-box">

                    <i class="ri-lock-password-line"></i>

                    <input
                        type="password"
                        name="confirm_password"
                        placeholder="Confirm Password"
                        required
                    >

                </div>

            </div>


            <button
                type="submit"
                class="register-submit"
            >

                <i class="ri-building-2-line"></i>

                Create Company Account

            </button>

        </form>


        <!-- LOGIN -->

        <div class="login-link">

            Already have an account?

            <a href="login.php">
                Login
            </a>

        </div>


    </div>

</section>



<!-- =========================================================
     ROLE SWITCHING
     LEFT SIDE NEVER CHANGES
========================================================= -->

<script>

const roleOptions = document.querySelectorAll(
    'input[name="account_role"]'
);

const registerForms = document.querySelectorAll(
    ".register-form"
);


roleOptions.forEach(function(roleInput) {

    roleInput.addEventListener("change", function() {

        const selectedRole = this.value;


        /* Hide every form */

        registerForms.forEach(function(form) {

            form.classList.remove("active");

        });


        /* Show selected form */

        const selectedForm = document.getElementById(
            selectedRole + "-form"
        );


        if (selectedForm) {

            selectedForm.classList.add("active");

        }

    });

});

</script>


</body>

</html>
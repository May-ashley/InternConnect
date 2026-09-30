<?php

session_start();

include("db.php");

$conn = connect();


/* =========================================================
   LOGIN
========================================================= */

$error = "";


if (isset($_POST['login'])) {

    $email = trim($_POST['email'] ?? "");
    $userPassword = $_POST['password'] ?? "";
    $role = $_POST['role'] ?? "";


    /* =====================================================
       VALIDATION
    ===================================================== */

    if (empty($email) || empty($userPassword)) {

        $error = "Please enter your email and password.";

    } elseif (!in_array($role, ["student", "company", "admin"])) {

        $error = "Please select a valid account type.";

    } else {


        /* =================================================
           FIND USER
        ================================================= */

        $sql = "

            SELECT
                id,
                name,
                email,
                password,
                role

            FROM users

            WHERE email = ?
            AND role = ?

            LIMIT 1

        ";


        $stmt = $conn->prepare($sql);


        if (!$stmt) {

            $error = "Something went wrong. Please try again.";

        } else {

            $stmt->bind_param(
                "ss",
                $email,
                $role
            );

            $stmt->execute();

            $result = $stmt->get_result();

            $user = $result->fetch_assoc();

            $stmt->close();


            /* =================================================
               CHECK ACCOUNT
            ================================================= */

            if (
                $user &&
                password_verify(
                    $userPassword,
                    $user['password']
                )
            ) {


                /* =============================================
                   STORE USER INFORMATION IN SESSION
                ============================================= */

                $_SESSION['user_id'] = $user['id'];

                $_SESSION['user_name'] = $user['name'];

                $_SESSION['user_email'] = $user['email'];

                $_SESSION['user_role'] = $user['role'];


                /* =============================================
                   REDIRECT BASED ON ROLE
                ============================================= */

                if ($user['role'] === 'student') {

                    header("Location: student-dashboard.php");

                    exit();

                } elseif ($user['role'] === 'company') {

                    header("Location: company-dashboard.php");

                    exit();

                } elseif ($user['role'] === 'admin') {

                    header("Location: admin-dashboard.php");

                    exit();

                }

            } else {

                $error = "Invalid email, password, or account type.";

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

    <title>Login | InternConnect</title>


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


    <!-- Main CSS -->

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>


<body>


<!-- =========================================================
     BACKGROUND
========================================================= -->

    <div class="bg-circle one"></div>
    <div class="bg-circle two"></div>



<!-- =========================================================
     LOGIN PAGE
========================================================= -->

<section class="login-page">


    <!-- =====================================================
         LEFT SIDE
    ====================================================== -->

    

    <div class="login-content">


        <!-- BACK TO HOME -->

        <a href="index.php" class="back-to-home">
            <i class="ri-arrow-left-line"></i>
            Back to Home
        </a>


        <h1>

            Welcome Back to

            <span>
                InternConnect
            </span>

        </h1>


        <p>

            Connect with internship opportunities,
            build your career and discover talented people.

        </p>



        <!-- STUDENT -->

        <div class="login-feature">

            <div class="login-feature-icon">

                <i class="ri-graduation-cap-line"></i>

            </div>

            <div>

                <h3>
                    Student Portal
                </h3>

                <p>
                    Find internships and manage applications.
                </p>

            </div>

        </div>



        <!-- COMPANY -->

        <div class="login-feature">

            <div class="login-feature-icon">

                <i class="ri-building-line"></i>

            </div>

            <div>

                <h3>
                    Company Portal
                </h3>

                <p>
                    Post internships and find talented students.
                </p>

            </div>

        </div>



        <!-- ADMIN -->

        <div class="login-feature">

            <div class="login-feature-icon">

                <i class="ri-shield-user-line"></i>

            </div>

            <div>

                <h3>
                    Admin Portal
                </h3>

                <p>
                    Manage internships, companies and users.
                </p>

            </div>

        </div>


    </div>



    <!-- =====================================================
         LOGIN CARD
    ====================================================== -->

    <div class="login-card">


     <div class="login-title">
    <div class="title-icon">
        <i class="ri-login-circle-line"></i>
    </div>

    <div>
        <h2>Welcome Back</h2>
        <p>Login to your InternConnect account</p>
    </div>
</div>



        <!-- ERROR -->

        <?php if (!empty($error)): ?>

            <div class="login-error">

                <i class="ri-error-warning-line"></i>

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>



        <!-- =================================================
             FORM
        ================================================== -->

        <form
            method="POST"
            action=""
        >


            <!-- ROLE SELECTION -->

            <div class="role-box">


                <!-- STUDENT -->

                <label class="role-option">

                    <input
                        type="radio"
                        name="role"
                        value="student"
                        checked
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
                        name="role"
                        value="company"
                    >

                    <i class="ri-building-line"></i>

                    <span>
                        Company
                    </span>

                </label>



                <!-- ADMIN -->

                <label class="role-option">

                    <input
                        type="radio"
                        name="role"
                        value="admin"
                    >

                    <i class="ri-shield-user-line"></i>

                    <span>
                        Admin
                    </span>

                </label>


            </div>



            <!-- EMAIL -->

            <div class="input-box">

                <i class="ri-mail-line"></i>

                <input
                    type="email"
                    name="email"
                    placeholder="Email Address"
                    required
                >

            </div>



            <!-- PASSWORD -->

            <div class="input-box">

                <i class="ri-lock-line"></i>

                <input
                    type="password"
                    name="password"
                    placeholder="Password"
                    required
                >

            </div>






            <!-- LOGIN BUTTON -->

            <button
                type="submit"
                name="login"
                class="login-submit"
            >

                <i class="ri-login-circle-line"></i>

                Login

            </button>



            <!-- REGISTER -->

            <div class="create-account">

                Don't have an account?

                <a href="register.php">
                    Register
                </a>

            </div>


        </form>


    </div>


</section>



</body>

</html>
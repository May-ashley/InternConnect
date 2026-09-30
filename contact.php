<?php

session_start();

$message_sent = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");

    $email = trim($_POST["email"] ?? "");

    $subject = trim($_POST["subject"] ?? "");

    $message = trim($_POST["message"] ?? "");

    if ($name !== "" && $email !== "" && $subject !== "" && $message !== "") {

        // For now, just show success message

        $message_sent = true;

    }

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Contact | InternConnect</title>


    <!-- External CSS -->

    <link rel="stylesheet" href="style.css">


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

</head>


<body>


<!-- ================= Background ================= -->

<div class="bg-circle one"></div>

<div class="bg-circle two"></div>



<!-- ================= Navbar ================= -->

<header class="menu-toggle">

    <nav class="navbar">

        <div class="logo">

            <h2>
                Intern<span>Connect</span>
            </h2>

        </div>


        <ul class="nav-menu">

            <li>
                <a href="index.php">
                    Home
                </a>
            </li>

            <li>
                <a href="internships.php">
                    Internships
                </a>
            </li>

            <li>
                <a href="companies.php">
                    Companies
                </a>
            </li>

            <li>
                <a href="about.php">
                    About
                </a>
            </li>

            <li>
                <a href="contact.php" class="active">
                    Contact
                </a>
            </li>

        </ul>

        <div class="nav-buttons">

        <?php if (isset($_SESSION['user_id'])): ?>

            <?php if ($_SESSION['user_role'] === 'student'): ?>

                <a href="student-dashboard.php" class="login-btn">
                    Dashboard
                </a>

            <?php elseif ($_SESSION['user_role'] === 'company'): ?>

                <a href="company-dashboard.php" class="login-btn">
                    Dashboard
                </a>

            <?php elseif ($_SESSION['user_role'] === 'admin'): ?>

                <a href="admin-dashboard.php" class="login-btn">
                    Dashboard
                </a>

            <?php endif; ?>

            <a href="logout.php" class="register-btn">
                Logout
            </a>

        <?php else: ?>

            <a href="login.php" class="login-btn">
                Login
            </a>

            <a href="register.php" class="register-btn">
                Sign Up
            </a>

        <?php endif; ?>

    </div>

    </nav>

</header>



<!-- =========================================================
     CONTACT HERO
========================================================= -->

<section class="contact-hero">

    <div class="contact-hero-content">

        <span class="about-badge">
            GET IN TOUCH
        </span>


        <h1>

            Let's

            <span>
                Connect
            </span>

        </h1>


        <p>

            Have a question, suggestion, or need help?

            We're here to help you get the most out of

            your internship journey with InternConnect.

        </p>

    </div>

</section>



<!-- =========================================================
     CONTACT INFORMATION
========================================================= -->

<section class="contact-info-section">

    <div class="contact-info-container">


        <!-- Email -->

        <div class="contact-info-card">

            <div class="contact-icon">

                <i class="ri-mail-line"></i>

            </div>


            <div>

                <h3>
                    Email Us
                </h3>


                <p>

                    Have a question? Send us an email and

                    we'll get back to you.

                </p>


                <a href="mailto:hello@internconnect.com">

                    hello@internconnect.com

                </a>

            </div>

        </div>



        <!-- Location -->

        <div class="contact-info-card">

            <div class="contact-icon">

                <i class="ri-map-pin-line"></i>

            </div>


            <div>

                <h3>
                    Our Location
                </h3>


                <p>

                    We're building a better internship

                    experience for students and companies.

                </p>


                <span>

                    Yangon, Myanmar

                </span>

            </div>

        </div>



        <!-- Support Hours -->

        <div class="contact-info-card">

            <div class="contact-icon">

                <i class="ri-time-line"></i>

            </div>


            <div>

                <h3>
                    Support Hours
                </h3>


                <p>

                    Our team is available to help with

                    your questions and concerns.

                </p>


                <span>

                    Monday – Friday

                </span>

            </div>

        </div>


    </div>

</section>



<!-- =========================================================
     CONTACT FORM
========================================================= -->

<section class="contact-form-section">

    <div class="contact-form-container">


        <!-- LEFT CONTENT -->

        <div class="contact-form-intro">

            <span class="about-badge">

                SEND A MESSAGE

            </span>


            <h2>

                How Can We

                <span>
                    Help?
                </span>

            </h2>


            <p>

                Whether you're a student looking for an

                internship or a company looking for talented

                interns, we'd love to hear from you.

            </p>

        </div>



        <!-- =================================================
             RIGHT SIDE
        ================================================= -->

        <?php if ($message_sent): ?>


            <!-- =========================
                 SUCCESS MESSAGE
            ========================== -->

            <div class="contact-form contact-success">


                <div class="contact-success-icon">

                    <i class="ri-checkbox-circle-line"></i>

                </div>


                <h3>

                    Message Sent Successfully!

                </h3>


                <p>

                    Thank you for contacting

                    <strong>InternConnect</strong>.

                    We've received your message and

                    will get back to you soon.

                </p>


                <a
                    href="contact.php"
                    class="about-primary-btn"
                >

                    Send Another Message

                    <i class="ri-send-plane-line"></i>

                </a>


            </div>


        <?php else: ?>


            <!-- =========================
                 ORIGINAL CONTACT FORM
            ========================== -->

            <form
                class="contact-form"
                action="contact.php"
                method="POST"
            >


                <div class="form-row">


                    <!-- Name -->

                    <div class="form-group">

                        <label for="name">

                            Your Name

                        </label>


                        <input
                            type="text"
                            id="name"
                            name="name"
                            placeholder="Enter your name"
                            required
                        >

                    </div>



                    <!-- Email -->

                    <div class="form-group">

                        <label for="email">

                            Email Address

                        </label>


                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Enter your email"
                            required
                        >

                    </div>


                </div>



                <!-- Subject -->

                <div class="form-group">

                    <label for="subject">

                        Subject

                    </label>


                    <input
                        type="text"
                        id="subject"
                        name="subject"
                        placeholder="What would you like to ask?"
                        required
                    >

                </div>



                <!-- Message -->

                <div class="form-group">

                    <label for="message">

                        Message

                    </label>


                    <textarea
                        id="message"
                        name="message"
                        rows="7"
                        placeholder="Write your message here..."
                        required
                    ></textarea>

                </div>



                <!-- Submit Button -->

                <button
                    type="submit"
                    class="about-primary-btn"
                >

                    Send Message

                    <i class="ri-send-plane-line"></i>

                </button>


            </form>


        <?php endif; ?>


    </div>

</section>



<!-- =========================================================
     CTA
========================================================= -->

<section class="contact-cta">

    <div class="contact-cta-content">


        <span class="about-badge">

            START YOUR JOURNEY

        </span>


        <h2>

            Ready to Take the

            <span>
                Next Step?
            </span>

        </h2>


        <p>

            Create your InternConnect account and discover

            opportunities that can help shape your future career.

        </p>


        <a
            href="register.php"
            class="about-primary-btn"
        >

            Get Started

            <i class="ri-arrow-right-line"></i>

        </a>


    </div>

</section>



<!-- ==========================================
                FOOTER
========================================== -->

<?php include("footer.php"); ?>


</body>

</html>


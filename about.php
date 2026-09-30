<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About | InternConnect</title>
    <!-- External CSS-->
    <link rel="stylesheet" href="style.css">
   
     <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Remix Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css"
        rel="stylesheet">

</head>


<body>


<body>
    
  
<!-- ================= Background ================= -->

    <div class="bg-circle one"></div>
    <div class="bg-circle two"></div>

    <!-- ================= Navbar ================= -->


    <header class="menu-toggle">
        <nav class="navbar">

            <div class="logo">
                <h2>Intern<span>Connect</span></h2>
            </div>

            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="internships.php">Internships</a></li>
                <li><a href="companies.php">Companies</a></li>
                <li><a href="about.php" class="active">About</a></li>
                <li><a href="contact.php">Contact</a></li>
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
     ABOUT HERO
========================================================= -->

<section class="about-hero">

    <div class="about-hero-content">

        <span class="about-badge">
            ABOUT INTERNCONNECT
        </span>

        <h1>
            Connecting
            <span>Students</span>
            With Opportunities
        </h1>

        <p>
           InternConnect is a web-based internship platform designed to connect students with companies through a centralized online system.
        </p>

        <div class="about-hero-buttons">

            <a href="internships.php" class="about-primary-btn">

                Explore Internships

                <i class="ri-arrow-right-line"></i>

            </a>

            <a href="#about-platform" class="about-secondary-btn">

                Learn More

                <i class="ri-arrow-down-line"></i>

            </a>

        </div>

    </div>

</section>



<!-- =========================================================
     ABOUT PLATFORM
========================================================= -->

<section class="about-platform" id="about-platform">

    <div class="about-container">


        <!-- Text -->

        <div class="about-content">

            <span class="section-label">
                OUR PLATFORM
            </span>

            <h2>
                Making Internship
                <span>Management Easier</span>
            </h2>

            <p>
                InternConnect is designed to make the internship
                process easier and more organized for students and 
                companies.
            </p>

            <p>
                Instead of relying on scattered information from
                different websites, social media pages, emails,
                or paper-based processes, InternConnect provides
                one centralized platform for managing internship
                opportunities and applications.
            </p>

            <p>
                Students can discover suitable internship
                opportunities, while companies can manage
                internship recruitment through the same system.
            </p>

        </div>


        <!-- Platform Card -->

        <div class="platform-card">

            <div class="platform-card-icon">

                <i class="ri-links-line"></i>

            </div>

            <h3>
                One Centralized Platform
            </h3>

            <p>
                Connecting the different people involved in the
                internship process through an organized system.
            </p>


            <div class="platform-points">

                <div>

                    <i class="ri-user-line"></i>

                    <span>
                        Students
                    </span>

                </div>

                <div>

                    <i class="ri-building-line"></i>

                    <span>
                        Companies
                    </span>

                </div>

               

            </div>

        </div>

    </div>

</section>



<!-- =========================================================
     THE PROBLEM
========================================================= -->

<section class="about-problem">

    <div class="section-heading">

        <span class="section-label">
            THE PROBLEM
        </span>

        <h2>
            Why
            <span>InternConnect?</span>
        </h2>

        <p>
            The internship recruitment process can be difficult
            when information and applications are managed through
            different channels.
        </p>

    </div>


    <div class="problem-grid">


        <!-- Problem 1 -->

        <article class="problem-card">

            <div class="problem-icon">

                <i class="ri-search-line"></i>

            </div>

            <h3>
                Scattered Opportunities
            </h3>

            <p>
                Internship opportunities may be shared through
                different websites, social media pages, company
                websites, and personal connections.
            </p>

        </article>


        <!-- Problem 2 -->

        <article class="problem-card">

            <div class="problem-icon">

                <i class="ri-file-list-3-line"></i>

            </div>

            <h3>
                Manual Processes
            </h3>

            <p>
                Managing internships through emails, paper
                documents, and spreadsheets can make it difficult
                to organize applications and student progress.
            </p>

        </article>


        <!-- Problem 3 -->

        <article class="problem-card">

            <div class="problem-icon">

                <i class="ri-user-search-line"></i>

            </div>

            <h3>
                Difficult Connections
            </h3>

            <p>
                Students and companies may have difficulty
                connecting efficiently when internship information
                is not available in one centralized system.
            </p>

        </article>

    </div>

</section>



<!-- =========================================================
     OUR SOLUTION
========================================================= -->

<section class="about-solution">

    <div class="about-container reverse">


        <!-- Solution Card -->

        <div class="solution-card">

            <div class="solution-icon">

                <i class="ri-global-line"></i>

            </div>

            <div class="solution-line">

                <i class="ri-check-line"></i>

                <span>
                    Centralized internship platform
                </span>

            </div>

            <div class="solution-line">

                <i class="ri-check-line"></i>

                <span>
                    Internship search and filtering
                </span>

            </div>

            <div class="solution-line">

                <i class="ri-check-line"></i>

                <span>
                    Online internship applications
                </span>

            </div>

            <div class="solution-line">

                <i class="ri-check-line"></i>

                <span>
                    Company internship management
                </span>

            </div>

            <div class="solution-line">

                <i class="ri-check-line"></i>

                <span>
                    Application tracking
                </span>

            </div>

        </div>


        <!-- Text -->

        <div class="about-content">

            <span class="section-label">
                OUR SOLUTION
            </span>

            <h2>
                A More Organized
                <span>Internship Process</span>
            </h2>

            <p>
                InternConnect provides a centralized web-based
                platform where students can search for and apply
                for internships, companies can recruit interns.
            </p>

            <p>
                The platform is designed to simplify the internship
                recruitment and application process while improving
                communication between students and companies.
            </p>

        </div>

    </div>

</section>



<!-- =========================================================
     SYSTEM USERS
========================================================= -->

<section class="system-users">

    <div class="section-heading">

        <span class="section-label">
            SYSTEM USERS
        </span>

        <h2>
            One Platform,
            <span>Two Roles</span>
        </h2>

        <p>
            InternConnect provides different functions based on
            each user's role and responsibilities.
        </p>

    </div>


    <div class="users-grid">


        <!-- STUDENT -->

        <article class="user-card">

            <div class="user-icon">

                <i class="ri-user-line"></i>

            </div>

            <span class="user-label">
                STUDENT
            </span>

            <h3>
                Find & Apply
            </h3>

            <p>
                Students can discover internship opportunities
                and manage their internship applications.
            </p>

            <ul>

                <li>
                    <i class="ri-check-line"></i>
                    Create and update profile
                </li>

                <li>
                    <i class="ri-check-line"></i>
                    Upload and manage CV
                </li>

                <li>
                    <i class="ri-check-line"></i>
                    Search internships
                </li>

                <li>
                    <i class="ri-check-line"></i>
                    Filter opportunities
                </li>

                <li>
                    <i class="ri-check-line"></i>
                    Apply for internships
                </li>

                <li>
                    <i class="ri-check-line"></i>
                    Track application status
                </li>

            </ul>

        </article>



        <!-- COMPANY -->

        <article class="user-card">

            <div class="user-icon">

                <i class="ri-building-line"></i>

            </div>

            <span class="user-label">
                COMPANY
            </span>

            <h3>
                Post & Recruit
            </h3>

            <p>
                Companies can manage internship opportunities
                and review student applications.
            </p>

            <ul>

                <li>
                    <i class="ri-check-line"></i>
                    Manage company profile
                </li>

                <li>
                    <i class="ri-check-line"></i>
                    Create internship postings
                </li>

                <li>
                    <i class="ri-check-line"></i>
                    Edit and delete postings
                </li>

                <li>
                    <i class="ri-check-line"></i>
                    Review applications
                </li>

                <li>
                    <i class="ri-check-line"></i>
                    View student profiles and CVs
                </li>

                <li>
                    <i class="ri-check-line"></i>
                    Accept or reject applicants
                </li>

            </ul>

        </article>



    </div>

</section>



<!-- =========================================================
     HOW IT WORKS
========================================================= -->

<section class="about-process">

    <div class="section-heading">

        <span class="section-label">
            HOW IT WORKS
        </span>

        <h2>
            A Simple Internship
            <span>Journey</span>
        </h2>

    </div>


    <div class="process-grid">


        <div class="process-card">

            <span class="process-number">
                01
            </span>

            <div class="process-icon">
                <i class="ri-user-add-line"></i>
            </div>

            <h3>
                Create an Account
            </h3>

            <p>
                Students and companies register on the
                InternConnect platform according to their role.
            </p>

        </div>



        <div class="process-card">

            <span class="process-number">
                02
            </span>

            <div class="process-icon">
                <i class="ri-search-line"></i>
            </div>

            <h3>
                Discover Opportunities
            </h3>

            <p>
                Students can search, filter, and view internship
                opportunities that match their interests and skills.
            </p>

        </div>



        <div class="process-card">

            <span class="process-number">
                03
            </span>

            <div class="process-icon">
                <i class="ri-send-plane-line"></i>
            </div>

            <h3>
                Apply Online
            </h3>

            <p>
                Students can submit applications for suitable
                internship opportunities through the platform.
            </p>

        </div>



        <div class="process-card">

            <span class="process-number">
                04
            </span>

            <div class="process-icon">
                <i class="ri-file-search-line"></i>
            </div>

            <h3>
                Manage Applications
            </h3>

            <p>
                Companies can review applications, view student
                profiles and CVs, and manage applicants.
            </p>

        </div>

    </div>

</section>



<!-- =========================================================
     VISION
========================================================= -->

<section class="about-vision">

    <div class="vision-container">

        <div class="vision-icon">

            <i class="ri-eye-line"></i>

        </div>

        <span class="section-label">
            OUR VISION
        </span>

        <h2>
            Bridging the Gap Between
            <span>Education & Industry</span>
        </h2>

        <p>
            To become a trusted digital platform that bridges
            the gap between education and industry by providing
            students with accessible internship opportunities
            and helping companies discover talented future
            professionals.
        </p>

    </div>

</section>



<!-- =========================================================
     MISSION
========================================================= -->

<section class="about-mission">

    <div class="section-heading">

        <span class="section-label">
            OUR MISSION
        </span>

        <h2>
            What
            <span>InternConnect</span>
            Aims to Achieve
        </h2>

    </div>


    <div class="mission-grid">

        <div class="mission-item">

            <i class="ri-layout-grid-line"></i>

            <p>
                Provide a centralized platform for internship
                management.
            </p>

        </div>


        <div class="mission-item">

            <i class="ri-flow-chart"></i>

            <p>
                Simplify the internship application and
                recruitment process.
            </p>

        </div>


        <div class="mission-item">

            <i class="ri-building-4-line"></i>

            <p>
                Enable companies to publish internship
                opportunities and manage applicants efficiently.
            </p>

        </div>


        <div class="mission-item">

            <i class="ri-user-search-line"></i>

            <p>
                Help students discover and apply for suitable
                internships based on their interests and skills.
            </p>

        </div>


        <div class="mission-item">

            <i class="ri-links-line"></i>

            <p>
                Improve communication between students and companies through an organized and transparent internship system.
            </p>

        </div>


        <div class="mission-item">

            <i class="ri-shield-check-line"></i>

            <p>
                Deliver a secure, user-friendly, and responsive
                web application.
            </p>

        </div>

    </div>

</section>



<!-- =========================================================
     CTA
========================================================= -->

<section class="about-cta">

    <div class="about-cta-content">

        <span class="about-badge">
            START YOUR JOURNEY
        </span>

        <h2>
            Your Future
            <span>Starts Here</span>
        </h2>

        <p>
            Join InternConnect today and connect with internship
            opportunities that help you gain practical experience,
            build your skills, and take the next step toward your career.
        </p>

        <a href="register.php" class="about-primary-btn">

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
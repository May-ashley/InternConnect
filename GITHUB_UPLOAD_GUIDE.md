# GitHub Upload Guide

## Recommended repository

**Repository name:** `internconnect`

**Description:**
> PHP/MySQL internship management platform with role-based access, internship workflows, application tracking, and admin approval.

**Suggested topics:**
`php` `mysql` `web-development` `internship-management` `role-based-access` `crud` `xampp` `html` `css` `javascript`

## Before pushing

1. Keep `config.php` local only.
2. Do not add `uploads/resumes/`.
3. Do not add the original development SQL dump.
4. Use `database/schema.sql`.
5. Open the project locally and test login, registration, internship browsing, application, company management, and admin functions.

## Git commands

Open Git Bash or PowerShell inside the `InternConnect` project folder:

```bash
git init
git add .
git status
git commit -m "Initial release: InternConnect internship management platform"
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/internconnect.git
git push -u origin main
```

Replace `YOUR_USERNAME` with your GitHub username.

## Good commit messages after the first upload

```text
feat: improve student application workflow
feat: add company internship management
feat: add admin internship approval
fix: improve role-based access validation
fix: improve resume upload validation
style: refine dashboard interface
docs: update project setup instructions
```

## Important

GitHub hosts the source code, but it does not automatically run this PHP/MySQL application. For an HR reviewer, the repository is useful for inspecting the source code and architecture. A separate live demo can be added later if desired.

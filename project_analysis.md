### Project Analysis: Maxfielder

`Maxfielder` is a PHP-based web application built with the **Symfony** framework. It serves as a specialized frontend for the [maxfield](https://github.com/tvwenger/maxfield) tool, which is used to calculate optimal "fielding" plans for the mobile game *Ingress*.

#### 🛠 Core Tech Stack
- **Framework:** Symfony (modern version, requiring PHP 8.5+).
- **Database:** Doctrine ORM (PostgreSQL/MySQL/SQLite compatible).
- **Frontend:** Twig templates, Stimulus (via Symfony StimulusBundle), and Asset Mapper.
- **Authentication:** OAuth2 (Google and GitHub) via `knpuniversity/oauth2-client-bundle`.
- **Admin:** EasyAdminBundle for administrative tasks.
- **Analysis/Quality:** PHPStan, Rector, and PHPUnit are integrated for CI/CD.

#### 🏗 Architecture & Core Components
1.  **Entities & Data Model:**
    - `User`: Manages user accounts, OAuth IDs, and user settings.
    - `Maxfield`: Represents a generated plan. It stores the plan's name, file path, and two types of JSON data:
        - `jsonData`: The calculated plan details (waypoints, links, fields).
        - `userData`: Progress tracking (which agent has which keys, current location, completed tasks).
    - `Waypoint`: Represents Ingress portals (Name, Lat/Lon, GUID, Image).

2.  **Services:**
    - `MaxFieldGenerator`: The heart of the application's logic. It builds and executes commands to generate plans. It can:
        - Call an external Python script (`maxfield-plan`).
        - Run inside a **Docker container**.
        - Use a built-in PHP implementation if `USE_PHP_MAXFIELD` is enabled.

3.  **Controllers:**
    - `MaxFieldsController`: Handles the lifecycle of a fielding plan (Import → Generate → View → Play → Delete).
    - `Admin/DashboardController`: Provides a CRUD interface for managing Users, Waypoints, and Maxfield records.

#### 🎯 Key Features
- **Data Import:** Supports importing portal data from IITC plugins (KExport and Multi Export JSON formats).
- **Multi-Agent Planning:** Allows generating plans for multiple agents (players).
- **Interactive "Play" Mode:** Provides a real-time tracking interface for agents to follow the plan steps on the ground.
- **Image Management:** Can download and host portal images locally for better visibility during fielding.
- **Deployment:** Includes Docker Compose and GitHub Actions (for status pages/documentation).

#### 📁 Project Structure Highlights
- `src/Service/MaxFieldGenerator.php`: Logic for bridging PHP with the Python calculation engine.
- `src/Controller/MaxFieldsController.php`: API and UI routing for plan management.
- `bin/`: Utility scripts for environment management (`start`, `stop`, `deploy`).
- `templates/`: Twig templates for the web interface.
- `assets/`: Stimulus controllers and CSS for the interactive frontend.

### Summary
The project is a robust, production-ready tool for the Ingress community, moving complex CLI-based calculations into a user-friendly, collaborative web environment. It is well-maintained with modern PHP standards and a strong focus on developer experience (Makefile, Rector, PHPStan).

# School Management Portal - Activity Programs

A modernized, year-aware school management system for handling activity programs, certificate generation, and annual archiving.

## Features
- **SSO Integration**: Authenticates users via the Greek School Network (phpCAS).
- **Year-Aware Metadata**: Dedicated management of protocol numbers and dates for each school year.
- **Automated Year Rollover**: Features for archiving active programs and generating metadata placeholders for future years.
- **Certificate Engine**: Generates Word documents (.docx) from templates with dynamic placeholder injection (`${protocol}`, `${protocol_date}`, `${sxetos}`).
- **High-Fidelity Printing**: Custom A4 print engine for program summaries.
- **DataTables Dashboard**: Advanced sorting, filtering, and export capabilities (Excel/PDF).

## Tech Stack
- **Backend**: PHP 8.x, MySQL (MariaDB).
- **Frontend**: Bootstrap 5, jQuery, DataTables.net, Select2, SweetAlert2.
- **Libraries**: 
  - `PHPOffice/PHPWord` for document manipulation.
  - `phpCAS` for authentication.

## Installation
1.  Clone the repository to your `htdocs` directory.
2.  Install dependencies: `composer install`.
3.  Import `files/progs.sql` and `files/schools.sql` into your database.
4.  Configure `conf.php` with your database credentials.
5.  Set your CAS server parameters in `index.php`.

## Project Structure
- `index.php`: Main dashboard and routing.
- `db.php`: Backend logic for data persistence and metadata management.
- `exp.php`: Certificate generation engine.
- `script.js`: Frontend logic, UI interactions, and A4 print engine.
- `files/`: Contains SQL schemas, Word templates, and generated files.
- `vendor/`: Composer dependencies.

## Manuals
- [User Manual (Greek)](USER_MANUAL.md) - For school units.
- [Admin Manual (Greek)](ADMIN_MANUAL.md) - For DIPE administrators.

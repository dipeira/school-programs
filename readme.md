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

## Database Architecture
- **`progs` / `progs_YYYY-YY`**: Main tables containing activity program records.
- **`progs_schools`**: Lookup table for school units, codes, and contact emails.
- **`progs_metadata`**: Stores year-specific administrative metadata (protocols/dates) used in certificates.

## Installation & Setup
1.  **Environment**: Requires PHP 8.x with `mysqli` and `zip` extensions enabled.
2.  **Clone**: Clone the repository to your `htdocs` or equivalent directory.
3.  **Dependencies**: Run `composer install` to fetch libraries.
4.  **Database**: Import `files/progs.sql` and `files/schools.sql`.
5.  **Configuration**: 
    - Rename `conf-sample.php` to `conf.php` and set your database credentials.
    - Use `dev_setup.php` (accessible only from `localhost`) to quickly toggle debug mode and simulate CAS users.
6.  **CAS Authentication**: Set your CAS server parameters in `index.php`.

## Security
- **CAS SSO**: All user authentication is handled via the Greek School Network SSO.
- **Restricted Tools**: `dev_setup.php` and `save_config.php` have built-in security checks to prevent unauthorized access.
- **Input Sanitization**: All database interactions use prepared statements or strict escaping.

## Development
- **Templates**: The certificate template is located at `files/vev_tmpl.docx`. You can modify it using standard Word placeholders.
- **Debug Mode**: Enable `$prDebug = 1` in `conf.php` to bypass SSO for local testing.

## Manuals
- [User Manual (Greek)](USER_MANUAL.md) - For school units.
- [Admin Manual (Greek)](ADMIN_MANUAL.md) - For DIPE administrators.


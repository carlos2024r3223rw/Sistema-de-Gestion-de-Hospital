# MediFlow — Hospital Management System

**A unified workspace for patient records, clinical workflows, and hospital operations.**

MediFlow is a portfolio project that brings common clinic and hospital workflows into one web application. Medical staff can review patient history and record visits, while administrators can manage appointments, admissions, billing, pharmacy inventory, and operational reports.

> **Portfolio demonstration only:** MediFlow is not a certified clinical system. Do not use it for real patient data, diagnosis, treatment, billing, or hospital operations.

## Product capabilities

### Clinical workflows

- Patient records with contact details, medical history, allergies, and emergency contacts.
- A timeline that brings visits, hospitalizations, prescriptions, and laboratory results together.
- Visit records for symptoms, diagnoses, prescriptions, examinations, and follow-up appointments.
- Hospital admission records with room, status, diagnosis, treatment, and notes.
- A pathology catalog with categories, severity levels, and suggested treatments.

### Administration and operations

- Billing records for services and medicines, with payment status and method.
- Pharmacy inventory with stock levels and low-stock monitoring.
- Role-based access for administrators, doctors, nursing staff, and reception.
- Dashboard metrics, hospital occupancy reports, and frequent-pathology summaries.
- Print-friendly prescriptions and payment receipts.

## Technology

| Area | Technology |
| --- | --- |
| Backend | PHP 8, modular native PHP |
| Database | MySQL or MariaDB with InnoDB |
| Frontend | HTML5, CSS3, modern JavaScript |
| Icons and typography | Lucide Icons, Inter |

## Run locally

### Requirements

- PHP 8 or later.
- MySQL or MariaDB.
- A local server such as Laragon or XAMPP.

### Setup

1. Clone the repository into your local server's document root.
2. Start the web server and MySQL/MariaDB.
3. Import `base de datos utilizada.sql` using phpMyAdmin or another database client. The SQL file creates and selects the `hospital` database.
4. Configure the database connection in `includes/db.php`. Use local credentials and do not commit them.
5. Open the application through your local server in a browser.
6. Use the seeded demo accounts for a local walkthrough. Change or remove sample passwords before sharing a deployment.

## Project scope and data safety

The seed data is for demonstration and development. Do not enter real patient information or expose the app or database publicly. A real healthcare deployment would require a separate security, privacy, clinical, and regulatory review, including access controls, auditability, backups, and operational safeguards.

## License

No license terms are specified in this repository. Contact the author before reusing or distributing the project.
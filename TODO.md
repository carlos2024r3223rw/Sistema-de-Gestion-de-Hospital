# TODO: Improve Hospital Management System (Simplified)

## Phase 1: Authentication with Employee Table
- [ ] Modify login to authenticate against `empleado` table using `correoempleado` and `clave`
- [ ] Update session to store employee info
- [ ] Remove separate user table and MVC structure

## Phase 2: Simplify Structure
- [ ] Remove models/, controllers/, views/ directories
- [ ] Keep single PHP files for each section
- [ ] Update includes/db.php to use PDO

## Phase 3: Security Improvements
- [ ] Hash passwords in empleado table (update existing records)
- [ ] Use prepared statements in all files
- [ ] Add input validation

## Phase 4: UI Improvements
- [ ] Apply consistent modern CSS to all pages
- [ ] Add navigation header to all pages
- [ ] Improve forms and tables

## Phase 5: Testing
- [ ] Test login with employee credentials
- [ ] Test all CRUD operations
- [ ] Verify security improvements

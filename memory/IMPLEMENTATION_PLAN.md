# Institution Management Portal - Implementation Plan

## Project Overview
- **Project Name**: Institution Management Portal (IMP)
- **Type**: Enterprise Web Application (Laravel 12)
- **Institution Type**: Polytechnic
- **Target Users**: 100,000+ students, 1,000+ staff
- **Tech Stack**: Laravel 12, PHP 8.3+, MySQL, Bootstrap 5, HTML5, CSS3, JavaScript, AJAX

---

## Database Architecture

### Core Entities

#### Users Table
- id, name, email, password, role_id, passport, gender, dob, phone, address, state, lga, next_of_kin, matric_number, staff_id, department_id, school_id, programme_id, level, email_verified_at, two_factor_secret, is_active, created_at, updated_at

#### Roles Table
- id, name, slug, description, permissions (JSON), created_at, updated_at

#### Schools Table
- id, name, code, dean_id, created_at, updated_at

#### Departments Table
- id, name, code, school_id, hod_id, created_at, updated_at

#### Programmes Table
- id, name, code, type (ND/HND/Degree/PGD/Masters/PhD), created_at, updated_at

#### Sessions Table
- id, name, is_active, is_current, start_date, end_date, created_at, updated_at

#### Applicants Table
- id, user_id, application_number, school_id, department_id, programme_id, session_id, status, created_at, updated_at

#### Students Table
- id, user_id, matric_number, school_id, department_id, programme_id, level, session_id, status, created_at, updated_at

#### Courses Table
- id, code, title, units, semester, school_id, department_id, programme_id, level, created_at, updated_at

#### CourseAssignments Table
- id, course_id, lecturer_id, session_id, created_at, updated_at

#### StudentCourses Table
- id, student_id, course_id, session_id, semester, status, created_at, updated_at

#### Timetables Table
- id, course_assignment_id, venue, day, start_time, end_time, week, session_id, status, approved_by, created_at, updated_at

#### Fees Table
- id, name, amount, school_id, department_id, programme_id, level, session_id, due_date, created_at, updated_at

#### Payments Table
- id, student_id, fee_id, amount, reference, transaction_id, gateway, status, created_at, updated_at

#### Grades Table
- id, min_score, max_score, grade, grade_point, remark, created_at, updated_at

#### Results Table
- id, student_course_id, ca, test, assignment, exam, total_score, grade, grade_point, gpa, approved_by, approved_at, status, created_at, updated_at

#### Attendances Table
- id, student_course_id, date, status, marked_by, created_at, updated_at

#### Announcements Table
- id, title, content, target_roles, posted_by, created_at, updated_at

#### ActivityLogs Table
- id, user_id, action, description, ip_address, user_agent, created_at, updated_at

#### Notifications Table
- id, user_id, title, message, type, is_read, created_at, updated_at

---

## Module Architecture

### 1. AUTHENTICATION MODULE
- Login (role-based redirect)
- Logout
- Forgot Password
- Password Reset
- Change Password
- User Registration
- Email Verification
- Two-Factor Authentication (optional per role)
- Rate Limiting

### 2. USER MANAGEMENT MODULE
- CRUD Operations
- Role Assignment
- Bulk Import (Excel)
- User Profile Management
- Password Reset by Admin
- Account Activation/Deactivation

### 3. INSTITUTION CONFIGURATION MODULE
- School Management
- Department Management
- Programme Management (ND, HND)
- Session Management (only one active at a time)

### 4. STUDENT MANAGEMENT MODULE
- Single Registration
- Bulk Import (Excel)
- Duplicate Validation
- Student Profile

### 5. APPLICANT & ADMISSION MODULE
- Applicant Registration Portal
- Application Form
- Document Upload
- Admission Processing
- Dashboard Statistics

### 6. COURSE MANAGEMENT MODULE
- Course CRUD
- Unique constraint: School + Department + Programme + Level + Course Code

### 7. COURSE ASSIGNMENT MODULE
- HOD assigns courses to lecturers
- Workload tracking

### 8. COURSE REGISTRATION MODULE
- Student course registration
- Validation: Max/Min units, Prerequisites, Carryovers
- PDF generation

### 9. TIMETABLE MODULE
- Lecturer proposal → HOD approval
- Clash detection (venue, lecturer, student, department)
- Daily/Weekly views

### 10. PAYMENT MODULE
- Fee configuration by school/department/programme/level/session
- Student payment dashboard
- Receipt generation

### 11. PAYMENT GATEWAY MODULE
- Paystack integration
- Flutterwave integration
- Transaction management

### 12. RESULT MANAGEMENT MODULE
- Configurable grading system
- Score entry (CA, Test, Assignment, Exam)
- Approval workflow (Lecturer → HOD → Dean → Senate)
- GPA/CGPA calculation
- Transcript generation

### 13. ATTENDANCE MODULE
- Manual marking
- QR Code attendance

### 14. ANNOUNCEMENTS MODULE
- Multi-role targeting

### 15. REPORTS MODULE
- PDF, Excel, CSV exports

### 16. ACTIVITY LOGS MODULE
- Comprehensive audit trail

---

## Route Structure

```
/api/v1/auth/*          - Authentication APIs
/admin                  - Super Admin Dashboard
/admin/users            - User Management
/admin/schools          - School Management
/admin/departments      - Department Management
/admin/programmes       - Programme Management
/admin/sessions         - Session Management
/admin/fees             - Fee Configuration
/admin/grades           - Grading System
/admin/reports          - Reports

/registrar             - Registrar Portal
/registrar/applicants   - Applicant Management
/registrar/admissions  - Admission Processing

/bursar                 - Bursar Portal
/bursar/payments       - Payment Management

/dean                   - Dean Portal
/dean/approvals        - Result Approvals

/hod                    - HOD Portal
/hod/courses           - Course Assignment
/hod/timetable         - Timetable Management
/hod/approvals         - Result Approvals

/lecturer               - Lecturer Portal
/lecturer/courses      - My Courses
/lecturer/results      - Result Entry
/lecturer/attendance  - Attendance

/student                - Student Portal
/student/courses       - Course Registration
/student/results      - My Results
/student/payments     - My Payments
/student/timetable    - My Timetable

/applicant              - Applicant Portal
/applicant/apply       - Application Form
```

---

## Security Implementation

1. **CSRF Protection** - Laravel built-in
2. **SQL Injection** - Eloquent ORM
3. **XSS Protection** - Blade escaping
4. **RBAC** - Middleware + Policies
5. **Audit Logs** - ActivityLog model
6. **Password Hashing** - bcrypt
7. **Session Management** - Laravel auth
8. **Rate Limiting** - Laravel throttle

---

## UI/UX Requirements

### Color Scheme
- Primary: Dark Blue (#1a237e)
- Secondary: Purple (#6a1b9a)
- Accent: White (#ffffff)
- Success: Green (#28a745)
- Danger: Red (#dc3545)
- Warning: Orange (#fd7e14)

### Dashboard Components
- Statistics Cards
- Charts (Chart.js)
- DataTables
- SweetAlert2 Notifications
- Tooltips
- Icons (FontAwesome)

---

## Implementation Phases

### Phase 1: Foundation
1. Laravel 12 Setup
2. Database Configuration
3. Authentication System
4. Role Management
5. Basic Dashboard Layout

### Phase 2: Core Modules
1. User Management
2. Institution Configuration
3. Student Management
4. Applicant Module

### Phase 3: Academic Modules
1. Course Management
2. Course Assignment
3. Course Registration
4. Timetable Module

### Phase 4: Financial Module
1. Fee Configuration
2. Payment Gateway Integration
3. Student Payments

### Phase 5: Results & Attendance
1. Result Entry
2. Grading System
3. Approval Workflow
4. GPA/CGPA
5. Attendance

### Phase 6: Reports & Final
1. Report Generation
2. Activity Logs
3. Notifications
4. Testing
5. Documentation

---

## File Structure

```
app/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/
├── Models/
├── Policies/
├── Services/
├── Repositories/
├── Observers/
└── Providers/

config/
database/
├── migrations/
└── seeders/

resources/
├── views/
│   ├── layouts/
│   ├── auth/
│   ├── admin/
│   ├── student/
│   ├── lecturer/
│   └── ...
│   └── components/
├── css/
├── js/
└── images/

routes/
storage/
tests/
```

---

## Key Technical Decisions

1. **Repository Pattern**: For data access abstraction
2. **Service Layer**: For business logic
3. **AJAX**: For all dynamic operations
4. **DataTables**: For table management
5. **SweetAlert2**: For notifications
6. **Chart.js**: For dashboard charts
7. **DOMPDF**: For PDF generation
8. **Maatwebsite Excel**: For Excel import/export
9. **Simple QRCode**: For QR code generation
10. **Laravel Scout**: For full-text search

---

## Acceptance Criteria

- [ ] All 11 user roles functional with proper redirects
- [ ] RBAC working with middleware
- [ ] Course uniqueness validated by (School + Dept + Prog + Level + Code)
- [ ] Timetable clash detection working
- [ ] Payment gateway integration complete
- [ ] Result approval workflow complete
- [ ] GPA/CGPA calculation accurate
- [ ] Activity logs capture all actions
- [ ] Responsive on all devices
- [ ] All AJAX operations working
- [ ] Export functionality (PDF, Excel, CSV)
- [ ] Ready for 100,000+ users
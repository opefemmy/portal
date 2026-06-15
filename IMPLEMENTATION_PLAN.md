# Student Information System - Implementation Plan

## Overview
This is a comprehensive Laravel-based Student Information System with multiple portals (Admin, Bursar, Dean, HOD, Lecturer, Registrar, Student, Applicant). The user has requested numerous enhancements and bug fixes.

---

## Phase 1: Critical Bug Fixes & Session Issues

### 1.1 Fix "Page Expired" Error on Login
**Problem**: CSRF token expiration or session configuration issues
**Solution**:
- Check and update `config/session.php` - increase lifetime or adjust settings
- Ensure CSRF token is properly included in all forms
- Add `VerifyCsrfToken` middleware exceptions if needed for API calls
- Configure session cookie settings properly

### 1.2 Fix Fee Configuration Table "Incorrect Column Count" Error
**Problem**: DataTables error - mismatch between TH count and TD count
**Current**: Table has 6 columns but some rows may have different counts
**Solution**:
- Review and fix `resources/views/admin/fees/index.blade.php`
- Ensure every row has exactly 6 `<td>` elements
- Check for any conditional rendering issues

---

## Phase 2: Institution Setup Enhancements

### 2.1 Merge School, Department, Programme Under Single Menu
**Current State**: Separate menu items for each
**Solution**: Create a unified "Institution Setup" menu with dropdown containing:
- Schools (with nested departments and programmes)
- Add/Edit/Delete functionality

### 2.2 Add Action Buttons with Hints on Hover
**Implementation**:
- Add Edit, Modify, Delete buttons to all tables
- Add Bootstrap tooltips with hints (e.g., "Edit this school", "Delete this department")
- Example tooltip: `<a href="#" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Edit this school">`

### 2.3 Activate "Add New School" Functionality
**Current**: Likely disabled or incomplete
**Solution**: Ensure the create form is functional and routes are active

### 2.4 Session Setup Enhancement
**Enhancement**: Add semester dropdown (First/Second) and year selection
**Changes Needed**:
- Update Session model to include `semester` field
- Update SessionController to handle semester
- Update session creation form

---

## Phase 3: Course Registration Enhancements

### 3.1 Level Configuration for Nigerian System
**Mapping Required**:
- ND1 = 100L (100 Level)
- ND = 200L (200 Level)  
- HND1 = 300L (300 Level)
- HND2 = 400L (400 Level)

**Implementation**:
- Update Course model with level validation
- Update course registration form to accept these level names
- Store both display name and numeric level in database

### 3.2 Course Form Printout
**Current**: Basic print functionality exists
**Enhancement**: Improve print layout to be more professional
- Add institution header/footer
- Include student details on printout

### 3.3 Course Upload with Configuration
**Requirements**:
- Upload courses via Excel/CSV
- Fields: course_code, course_title, course_unit, school, department, programme, level
- Tag courses with departments
- Prevent duplicate course codes

---

## Phase 4: Fee Management Enhancements

### 4.1 Activate Fee Addition
**Current**: May be inactive
**Solution**: Ensure all fee CRUD operations work properly

### 4.2 Payment Type Definition
**Types to Support**:
- Tuition Fee
- Departmental Fee
- Other fees attached to students

**Implementation**: Add `payment_type` field to Fee model

### 4.3 Fee Table Enhancements
**Add Features**:
- Export button (Excel/CSV)
- Verify Payment button
- Confirm Payment button

### 4.4 Fee Payment Flow Enhancement
**Feature**: Close payment page after defaulting
**Implementation**: Add redirect/close functionality after payment completion or failure

### 4.5 Configure Exact Fee for All Students
**Feature**: Add bulk fee configuration for all non-paying students
- Set exact amount due
- Configure due date

---

## Phase 5: Student Management

### 5.1 Manage Students Menu
**Features**:
- Edit student information
- Upload students (bulk import)
- Reset student passwords

### 5.2 Course Registration Report
**Features**:
- View all registered students
- Add "Unsubmit" button to drop courses
- Export functionality

### 5.3 Student Portal - Required Modules
**Feature**: Transfer all required modules to student portal
- Display required courses for student's programme/level
- Show completion status

---

## Phase 6: Staff Management

### 6.1 Manage Staff Menu
**Features**:
- Add/Edit/Delete staff
- Assign roles based on functionality:
  - Lecturer
  - HOD
  - Dean
  - Registrar
  - Bursar
  - Admin
- Modify staff details

---

## Phase 7: Course Assignment

### 7.1 OnCourses Menu
**Features**:
- Assign courses to lecturers
- View assigned courses
- These should also appear on lecturer's page

---

## Phase 8: Notifications & Messages

### 8.1 Login Notification
**Feature**: Display notification when student accesses portal

### 8.2 Post-Login Popup
**Feature**: Show information page popup after login

### 8.3 Flash Scrolling Message
**Feature**: Add scrolling marquee message for students on dashboard

### 8.4 Hover Hints on All Action Buttons
**Implementation**: Add Bootstrap tooltips to all buttons across the portal

---

## Phase 9: Password Management & Email

### 9.1 Institution Email Configuration
**Current**: Basic SMTP configured in .env
**Enhancement**: Enable password reset via email

### 9.2 Password Reset with Secret Questions
**Features**:
- Add secret questions to user profile
- Require answers before password reset
- Send reset link via email

### 9.3 Password Management for All Users
**Features**:
- Reset passwords from admin panel
- Password change prompts
- Password strength requirements

---

## Phase 10: Admission Management

### 10.1 Activate Admission Menu
**Features**:
- Manage admission process
- Upload admissions (bulk)
- Track admission status
- Update admission list
- Admission letter configuration

---

## Phase 11: Bursary Management

### 11.1 Upload Student Payments
**Feature**: Bulk upload payment records from Excel

### 11.2 Payment Calculation
**Feature**: Track paid vs expected amount
- Subtract uploaded payments from expected fee

### 11.3 Regime Payment Template
**Configuration Options**:
- Indigene vs Non-Indigene (different fees)
- 2-Installment Payment (60% + 40%)
- Enforcement: Cannot pay 40% without paying 60% first

---

## Phase 12: State/Origin Data

### 12.1 Excel Format for Location Data
**Requirements**:
- State of Origin
- Local Government Area
- Nationality (Nigerian/Non-Nigerian)

**Implementation**: Create database seeders from the Excel file provided

---

## Phase 13: Reports & Data Management

### 13.1 Report Sheet
**Features**:
- Generate reports for all uploaded data
- Export to Excel/PDF
- Filter by date range, type, etc.

---

## Database Changes Required

### New Tables/Fields:

1. **fees table**: Add `payment_type` field
2. **sessions table**: Add `semester` field  
3. **users table**: Add `secret_question`, `secret_answer` fields
4. **users table**: Add `institution_email` field
5. **payments table**: Add `payment_type`, `installment` fields
6. **Create new table**: `regime_payments` (indigene/non-indigene rules)
7. **Create new table**: `local_governments` (with state_id foreign key)
8. **Create new table**: `states` (states of Nigeria)
9. **Create new table**: `nationalities`
10. **students table**: Add `state_id`, `lga_id`, `nationality_id` fields

---

## File Modifications Summary

### Controllers to Update:
- `Admin/FeeController.php` - Add payment type, export functionality
- `Admin/SessionController.php` - Add semester handling
- `Admin/SchoolController.php` - Add nested departments/programmes
- `Admin/DepartmentController.php` - Add programme relationship
- `Student/CourseRegistrationController.php` - Add unsubmit, print
- `Bursar/PaymentController.php` - Add upload, verify, confirm
- `Auth/LoginController.php` - Fix session issues
- `Registrar/AdmissionController.php` - Activate admission

### Views to Update:
- All admin index views - Add action buttons with hints
- Fee views - Fix table, add payment type, export buttons
- Student views - Add course print, unsubmit
- Auth views - Improve login form
- Layouts - Add scrolling messages, tooltips

### Models to Update:
- Fee - Add payment_type, installment
- Session - Add semester
- User - Add secret questions, institution email
- Student - Add location fields

---

## Implementation Priority

### Priority 1 (Critical):
1. Fix "page expired" error
2. Fix fee table error

### Priority 2 (High):
3. Activate fee addition
4. Add session with semester
5. Course registration level configuration
6. Activate admission menu

### Priority 3 (Medium):
7. Merge institution setup menu
8. Add action buttons with hints
9. Staff management
10. Student management

### Priority 4 (Normal):
11. Notifications
12. Password reset with email
13. Bursary regime payments

### Priority 5 (Enhancement):
14. Reports
15. State/LGA data

---

## Testing Checklist

- [ ] Login works without page expired error
- [ ] Fee table displays correctly without DataTables error
- [ ] Can add/edit/delete schools, departments, programmes
- [ ] Can add sessions with semester selection
- [ ] Course registration accepts ND1/ND/HND1/HND2 levels
- [ ] Fee addition works with payment types
- [ ] Fee table has export, verify, confirm buttons
- [ ] All action buttons show hints on hover
- [ ] Student can unsubmit courses
- [ ] Course form prints correctly
- [ ] Password reset email works
- [ ] Admission menu is fully functional
- [ ] Bursar can upload payments
- [ ] Regime payment (60%/40%) works correctly

---

## Notes

- All changes should maintain backward compatibility
- Use Laravel's built-in features where possible
- Follow existing code conventions and patterns
- Add proper validation for all user inputs
- Ensure responsive design for mobile access
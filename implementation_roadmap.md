# Implementation Roadmap - BJUKA Internship Management System

This document outlines the detailed technical plan for implementing the BJUKA Internship Management System.

## 🏗️ Phase 1: Core Architecture & Database
**Goal:** Establish the foundation with database schemas and relationships.

### 📅 Laravel (Backend)
- **Primary Keys:** 
  - Use **UUIDs** for all new tables listed below.
  - Keep standard increments for the existing `users` table.

- **Migrations:**
  - `create_internship_batches_table`: `uuid`, `name`, `description`, `start_date`, `end_date`, `expected_days`, `status`.
  - `create_approved_networks_table`: `uuid`, `batch_id` (FK UUID), `name`, `ssid`, `bssid`.
  - `create_interns_table`: `uuid`, `user_id` (FK BigInt), `batch_id` (FK UUID), `phone`, `institution`, `course`, `registration_number`, `status`.
  - `create_attendances_table`: 
    - `uuid`
    - `intern_id` (FK UUID)
    - `date`
    - `check_in_device_time`, `check_in_server_time`
    - `check_out_device_time`, `check_out_server_time`
    - `work_duration_minutes` (Nullable integer for cached analytics)
    - `status` (present, late, partial, absent)
    - `wifi_ssid`, `wifi_bssid`
  - `create_daily_learning_logs_table`: `uuid`, `attendance_id` (FK UUID), `studied_content`, `tasks_completed`, `challenges`.
  - `create_supervisor_notes_table`: `uuid`, `intern_id` (FK UUID), `supervisor_id` (FK BigInt), `category`, `content`, `visibility`, `note_date`.
  - `create_evaluations_table`: 
    - `uuid`
    - `intern_id` (FK UUID)
    - `supervisor_id` (FK BigInt)
    - `evaluation_type` (midterm, final, periodic)
    - `technical_score`, `communication_score`, `teamwork_score`, `problem_solving_score`, `conduct_score`, `attendance_score`
    - `strengths`, `improvement_areas`, `remarks`

- **Models & Relationships:**
  - `InternshipBatch`: `hasMany(Intern)`, `hasMany(ApprovedNetwork)`.
  - `Intern`: `belongsTo(User)`, `belongsTo(InternshipBatch)`, `hasMany(Attendance)`, `hasMany(SupervisorNote)`, `hasMany(Evaluation)`.
  - `Attendance`: `belongsTo(Intern)`, `hasOne(DailyLearningLog)`.
  - `DailyLearningLog`: `belongsTo(Attendance)`.
  - `ApprovedNetwork`: `belongsTo(InternshipBatch)`.
  - `SupervisorNote`: `belongsTo(Intern)`, `belongsTo(User, 'supervisor_id')`.
  - `Evaluation`: `belongsTo(Intern)`, `belongsTo(User, 'supervisor_id')`.

---

## 🔐 Phase 2A: Roles & Permissions
**Goal:** Define and enforce access levels for Admin, Supervisor, and Intern.

### 🌐 Web (Laravel)
- **Implementation:**
  - Add `role` column to `users` table or use a `Role` Enum.
  - Create `RoleMiddleware` to restrict routes based on user type.
  - Define gates/policies for sensitive actions (e.g., creating batches).

---

## 🔐 Phase 2B: API Authentication
**Goal:** Prepare the backend for secure mobile access with refined security and semantics.

### 📅 Laravel (API)
- **Setup:**
  - Install and configure Laravel Sanctum.
  - **Account Validation:** Login must verify user/intern status is `ACTIVE`.
  - **Token Abilities:** Issue tokens with `['mobile-access']` ability.
- **Endpoints:**
  - `POST /api/login`: Returns standardized JSON `{token, user}`.
  - `GET /api/me`: Returns current user profile (preferred over /user).
  - `POST /api/logout`: Revokes current token.
- **Reserved for Future:**
  - `GET /api/devices`: List all active tokens/devices.
  - `DELETE /api/devices/{token_id}`: Revoke a specific session.

---

## 🔐 Phase 2C: Mobile Authentication
**Goal:** Implement the login flow in the Flutter application.

### 📱 Mobile (Flutter)
- **Screens:** `lib/screens/auth/login_screen.dart`.
- **Services:** `lib/services/auth_service.dart`.
- **Storage:** Securely store tokens using `flutter_secure_storage`.
- **State:** Implement basic Auth state management.

---

## 📦 Phase 3 & 4: Management Modules (Web)
**Goal:** Admin CRUD for Batches, Approved Networks, and Interns.

### 🌐 Web (Laravel/Vue)
- **Controllers:**
  - `InternshipBatchController`: Includes managing `ApprovedNetwork` records.
  - `InternController`: `index`, `store`, `update`, `toggleStatus`.
- **Vue Pages:**
  - `resources/js/pages/Batches/Index.vue`
  - `resources/js/pages/Batches/Show.vue` (Network management).
  - `resources/js/pages/Interns/Index.vue`

---

## 📡 Phase 5: Attendance Module (Mobile Focus)
**Goal:** WiFi-validated check-in/out with dual timestamps and scoped abilities.

### 📅 Laravel (API)
- **Endpoints:**
  - `POST /api/attendance/check-in`: Requires `attendance:create` ability.
  - `POST /api/attendance/check-out`: Requires `attendance:create` ability.
- **Controller:** `Api/AttendanceController`.

### 📱 Mobile (Flutter)
- **Screens:** `lib/screens/attendance/attendance_screen.dart`.
- **Services:** `lib/services/wifi_service.dart`.
- **Logic:** Fetch approved networks for the intern's batch from API; validate local WiFi details before submission.

---

## 📝 Phase 6 & 7: Learning Logs & Progress (Mobile)
**Goal:** Post-attendance reporting and dashboard analytics.

### 📅 Laravel (API)
- **Endpoints:**
  - `POST /api/learning-logs`: Linked to `attendance_id`.
  - `GET /api/intern/stats`: Returns calculated progress (completion %, streak, hours, etc.).
- **Controller:** `Api/InternDashboardController`.

### 📱 Mobile (Flutter)
- **Screens:** 
  - `lib/screens/dashboard/home_screen.dart` (Progress widgets).
  - `lib/screens/logs/log_entry_screen.dart`.
  - `lib/screens/history/attendance_history_screen.dart`.

---

## 📊 Phase 8 & 9: Analytics Dashboard (Web)
**Goal:** High-level overview and deep-dive analytics.

### 🌐 Web (Laravel/Vue)
- **Controller:** `AnalyticsController`.
- **Vue Pages:**
  - `resources/js/pages/Dashboard.vue` (Batch/Intern widgets).
  - `resources/js/pages/Analytics/InternDetail.vue` (Progress tracking & history).

---

## 📋 Phase 9.5: Supervisor Module
**Goal:** Multi-supervisor feedback and evaluations.

### 📅 Laravel
- **Controllers:** `Supervisor/EvaluationController`, `Supervisor/NoteController`.
- **Vue Pages:** `resources/js/pages/Interns/Show.vue` (Timeline with notes/evaluations from multiple supervisors).

---

## 📄 Phase 10: Report Generator
**Goal:** PDF export for internship completion.

### 📅 Laravel
- **Service:** `app/Services/ReportGenerator.php`.
- **Logic:** Aggregate attendance, learning logs, and evaluations into a structured PDF.
- **View:** `resources/views/reports/internship_final.blade.php`.

---

## ✅ Phase 11: QA & Polish
- **Web:** Pint, PHPStan, ESLint, TypeScript check.
- **Mobile:** `flutter analyze`, Unit/Widget tests.

## 🚀 Implementation Order
1. **Core:** Database Migrations & Models.
2. **Phase 2A:** Roles & Permissions (Web).
3. **Phase 2B:** API Authentication (Backend).
4. **Phase 2C:** Mobile Authentication (Flutter).
5. **Admin Web:** Batch & Intern CRUD.
6. **Attendance Flow:** WiFi validation -> Check-in -> Check-out -> Learning Log.
7. **Dashboard & Analytics:** Both Web and Mobile stats.
8. **Supervisor Module:** Notes and Evaluations.
9. **Reporting:** PDF Generation.

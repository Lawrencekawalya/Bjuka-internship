# BJUKA Internship Project

This repository contains a full-stack project consisting of a Laravel web application and a Flutter mobile application.

## Project Structure

- `Webapp/`: The backend and web frontend built with Laravel, Vue.js, and Inertia.js.
- `bjuka_mobile/`: The mobile application built with Flutter.

---
## 🎯 Project Vision

BJUKA Internship is an Internship Management System designed to manage:

* Internship Batches
* Intern Management
* Attendance Tracking
* Daily Learning Logs
* Internship Progress Tracking
* Attendance Analytics
* Supervisor Evaluations
* Internship Report Generation

The system consists of:

* Laravel Web Administration Portal
* Flutter Mobile Application for Interns

All development decisions should support this vision.

---

## 🎨 UI Consistency Rules

The existing web application's design system is the source of truth.

Rules:

* Maintain the current visual theme and styling.
* Reuse existing layouts whenever possible.
* Reuse existing tables, cards, forms, badges, and navigation patterns.
* Do not introduce a new design system.
* Do not redesign existing screens unless explicitly instructed.
* New pages should appear as a natural extension of the current application.
* Prioritize consistency over creativity.

For mobile development:

* Maintain a clean and professional design.
* Keep visual language aligned with the web platform.
* Reuse colors, spacing, typography, and interaction patterns where appropriate.

---

## 🏗 Architecture Rules

Laravel is the system of record.

Rules:

* All business logic belongs in Laravel.
* Flutter must consume APIs only.
* Business rules must never be duplicated across platforms.
* Laravel remains the authoritative source of truth.
* All mobile actions must be validated server-side.

Use a feature-based architecture whenever practical.

---

## 📊 Domain Rules

Core entities:

* InternshipBatch
* Intern
* Attendance
* DailyLearningLog
* SupervisorNote
* PerformanceEvaluation

Core relationships:

* A batch contains many interns.
* An intern belongs to a batch.
* An intern has many attendance records.
* An attendance record may contain a learning log.
* An intern may have many supervisor notes.
* An intern may have many evaluations.

Development should preserve these relationships.

---

## 📱 Mobile Attendance Rules

Attendance is a controlled operation.

Check-In and Check-Out should only be permitted when:

* Connected to an approved WiFi network.
* WiFi SSID matches approved values.
* WiFi BSSID matches approved values.

Store:

* Device timestamp
* Server timestamp

The server timestamp is the authoritative timestamp.

Never trust device time alone.

---

## 🗄 Database Standards

Rules:

* Use proper foreign key relationships.
* Use soft deletes where appropriate.
* Store audit timestamps.
* Normalize data where practical.
* Avoid duplicate business data.
* Prefer UUIDs for externally referenced entities.
* Create indexes for frequently queried columns.

Design for long-term maintainability.

---

## 📈 Reporting Standards

All attendance and internship data should be reportable.

Every reporting feature should support:

* Daily reports
* Monthly reports
* Batch reports
* Individual student reports

Generated reports should support PDF export.

Final internship reports must aggregate:

* Attendance data
* Learning logs
* Analytics
* Supervisor notes
* Evaluations
* Final remarks

---

## 👨‍🏫 Future Scalability Rules

Design for growth from the beginning.

Rules:

* Support multiple supervisors per batch.
* Support multiple evaluations per student.
* Support multiple supervisor notes per student.
* Keep supervisor notes separate from evaluations.
* Do not hardcode assumptions about internship duration.
* Do not assume a single institution or university.
* Design entities to support future multi-branch internship centers.

Avoid architectural decisions that limit future expansion.

---

## ✅ Development Workflow

Before implementing any feature:

1. Analyze the existing codebase.
2. Identify reusable components.
3. Follow established project patterns.
4. Create an implementation plan.
5. Implement the feature.
6. Run linting.
7. Run type checking.
8. Run tests.
9. Verify functionality manually.

Never bypass quality checks.

---

## 🔍 Code Quality Requirements

Laravel:

* Follow Laravel conventions.
* Run Pint before completion.
* Run PHPStan before completion.

Vue:

* Use TypeScript for all new code.
* Run ESLint.
* Run TypeScript checks.

Flutter:

* Follow flutter_lints.
* Run flutter analyze.
* Keep widgets modular and reusable.

Code should be production-ready before being considered complete.

---

## 📚 Documentation Requirements

When introducing:

* New modules
* New architecture
* New integrations
* Major database changes

Update documentation accordingly.

Documentation should remain synchronized with implementation.

---

## 🌐 Web Application (`Webapp`)

The web application is built with modern Laravel (v13+) and a Vue 3 frontend using Inertia.js.

### Technologies
- **Backend:** Laravel 13 (PHP 8.3+)
- **Frontend:** Vue 3, TypeScript, Inertia.js
- **Styling:** Tailwind CSS 4
- **Database:** SQLite (default for development)
- **Tooling:** Vite, Composer, pnpm/npm

### Key Commands

| Task | Command |
| :--- | :--- |
| **Setup** | `composer setup` (Installs deps, generates key, migrates) |
| **Development** | `composer dev` (Starts server, Vite, queue, and logs concurrently) |
| **Linting (PHP)** | `composer lint` |
| **Linting (JS/TS)** | `npm run lint` |
| **Type Checking** | `composer types:check` or `npm run types:check` |
| **Testing** | `composer test` |

### Development Conventions
- **PHP Linting:** Handled by [Laravel Pint](https://laravel.com/docs/pint).
- **Static Analysis:** Handled by [PHPStan](https://phpstan.org/).
- **Frontend Quality:** ESLint and Prettier are configured for consistent styling.
- **TypeScript:** Required for all new frontend components. Use `vue-tsc` for template type checking.
- **Routes:** Web routes are in `routes/web.php` and `routes/settings.php`.

---

## 📱 Mobile Application (`bjuka_mobile`)

The mobile application is a Flutter project designed for multi-platform support.

### Technologies
- **Framework:** Flutter
- **Language:** Dart

### Key Commands

| Task | Command |
| :--- | :--- |
| **Setup** | `flutter pub get` |
| **Run** | `flutter run` |
| **Test** | `flutter test` |
| **Analyze** | `flutter analyze` |

### Development Conventions
- **Lints:** Follows standard `flutter_lints` as defined in `analysis_options.yaml`.
- **Architecture:** (Currently standard Flutter boilerplate).

---

## 🛠 Shared Standards

- **Git:** Maintain clean commit messages.
- **Code Quality:** Always run linting and type checks before pushing changes.
- **Documentation:** Update this `GEMINI.md` or adding specific `README.md` files in subdirectories if major architectural changes occur.

# YardMaster Fleet Management System

## Overview
YardMaster is a web-based system for managing employee timeclocks and fleet operations. The new fleet management section allows tracking of vehicles, documents, maintenance schedules, and pre-trip inspections, integrated seamlessly with the existing system.

## Features
- **Timeclock**: Track employee clock-in and clock-out times.
- **Fleet Management**:
  - Manage vehicle details and assignments.
  - Upload and track documents with expiration notifications.
  - Schedule and monitor vehicle maintenance.
  - Submit DOT-compliant pre-trip inspections (drivers only).
- **Role-Based Access**: Admins manage everything; drivers access limited features.
- **Notifications**: Email alerts for expiring documents and maintenance due dates.

## Setup Instructions
1. **Database**:
   - Create a MySQL database named `your_database`.
   - Run `schema.sql` to set up tables and triggers.
2. **Configuration**:
   - Update `includes/db_connect.php` with your database credentials.
3. **Dependencies**:
   - Ensure PHP and MySQL are installed.
   - Bootstrap 5.3.0 is included via CDN.
4. **Deployment**:
   - Upload files to your web server.
   - Test access at `http://your-domain.com/frontend/dashboard.php`.

## Usage
- **Admins**: Log in, navigate to "Fleet Management" from the dashboard to manage vehicles and settings.
- **Drivers**: Log in, use "Pre-Trip Inspection" to submit daily reports.
- **API**: Access fleet data via RESTful endpoints in `api/fleet/`.

## Directory Structure
- `api/`: API endpoints for backend operations.
- `frontend/`: User interface files.
- `includes/`: Shared functions and configurations.
- `assets/`: CSS and JS files for styling and interactivity.
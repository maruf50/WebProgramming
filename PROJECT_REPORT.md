# UniRoom - University Room Booking System
## Project Report

---

## Table of Contents
1. [Objective / Motivation](#1-objective--motivation)
2. [Requirements & Technologies Used](#2-requirements--technologies-used)
3. [How It Works (Step by Step)](#3-how-it-works-step-by-step)
4. [Limitations & Challenges](#4-limitations--challenges)
5. [Conclusion](#5-conclusion)

---

## 1. Objective / Motivation

### 1.1 Project Overview

**UniRoom** is a web-based University Room Booking System designed to streamline the process of reserving campus facilities such as classrooms, meeting rooms, and seminar halls. The system provides a centralized platform for students, faculty members, clubs, and administrators to manage room bookings efficiently.

### 1.2 Problem Statement

In traditional university settings, room booking is often managed through:
- Paper-based reservation logs
- Email requests to administrative staff
- Phone calls and manual scheduling
- Spreadsheets with limited accessibility

These methods lead to:
- Double bookings and scheduling conflicts
- Time-consuming approval processes
- Lack of real-time availability information
- No priority management for different user types
- Difficulty in tracking booking history

### 1.3 Motivation

The motivation behind UniRoom is to:

1. **Automate Room Booking**: Eliminate manual processes and reduce administrative workload
2. **Prevent Conflicts**: Real-time availability checking prevents double bookings
3. **Implement Priority System**: Faculty and club events take precedence over individual student bookings
4. **Provide Transparency**: Users can track their booking status in real-time
5. **Enable Issue Reporting**: Users can report room problems for quick maintenance
6. **Centralize Management**: Admins have a single dashboard to manage all bookings and rooms

### 1.4 Target Users

| User Type | Description |
|-----------|-------------|
| **Students** | Book rooms for study groups, presentations, project meetings |
| **Faculty** | Reserve classrooms, labs, meeting rooms for lectures and consultations |
| **Clubs** | Book venues for club activities, events, and meetings |
| **Administrators** | Manage rooms, approve/reject bookings, handle reports |

---

## 2. Requirements & Technologies Used

### 2.1 Functional Requirements

#### User Management
- [x] User registration with role selection (Student/Faculty/Club)
- [x] Secure login with username or email
- [x] Password encryption
- [x] Session management
- [x] Role-based access control

#### Room Booking
- [x] Search available rooms by date
- [x] View room details (capacity, building, floor)
- [x] Select time slots for booking
- [x] Submit booking requests with purpose
- [x] Priority-based booking system

#### Booking Management
- [x] View personal booking history
- [x] Filter bookings by status and date
- [x] Cancel bookings
- [x] Receive notifications on booking status changes

#### Admin Features
- [x] Approve/Reject booking requests
- [x] Add, edit, delete rooms
- [x] Block rooms for specific dates
- [x] View and manage room issue reports

### 2.2 Non-Functional Requirements

- **Performance**: AJAX for asynchronous data loading without page refresh
- **Security**: SQL injection prevention, XSS protection, password hashing
- **Usability**: Responsive design, intuitive navigation
- **Reliability**: Server-side validation, error handling

### 2.3 Technologies Used

#### Backend Technologies

| Technology | Purpose |
|------------|---------|
| **PHP 7+** | Server-side scripting and business logic |
| **MySQL** | Relational database for data storage |
| **MySQLi** | PHP extension for MySQL database connection |
| **Session Management** | User authentication and state management |

#### Frontend Technologies

| Technology | Purpose |
|------------|---------|
| **HTML5** | Page structure and semantic markup |
| **CSS3** | Styling, layouts, animations |
| **JavaScript (ES6+)** | Client-side interactivity and AJAX |
| **Boxicons** | Icon library for UI elements |

#### Development Tools

| Tool | Purpose |
|------|---------|
| **XAMPP** | Local development server (Apache + MySQL) |
| **phpMyAdmin** | Database management interface |
| **VS Code** | Code editor |
| **Git** | Version control |

### 2.4 Database Schema

#### Tables Overview

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│     users       │     │     rooms       │     │   time_slots    │
├─────────────────┤     ├─────────────────┤     ├─────────────────┤
│ id              │     │ id              │     │ id              │
│ username        │     │ room_number     │     │ slot_name       │
│ email           │     │ room_name       │     │ start_time      │
│ password        │     │ capacity        │     │ end_time        │
│ role            │     │ building        │     └─────────────────┘
│ first_name      │     │ floor           │
│ last_name       │     │ is_available    │
│ student_id      │     └─────────────────┘
│ club_name       │
└─────────────────┘
         │
         │
         ▼
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│    bookings     │     │  blocked_rooms  │     │  room_reports   │
├─────────────────┤     ├─────────────────┤     ├─────────────────┤
│ id              │     │ id              │     │ id              │
│ user_id     ────┼──►  │ room_id         │     │ user_id         │
│ room_id         │     │ blocked_date    │     │ room_id         │
│ slot_id         │     │ reason          │     │ title           │
│ booking_date    │     │ blocked_by      │     │ description     │
│ purpose         │     └─────────────────┘     │ severity        │
│ status          │                             │ status          │
│ priority        │                             └─────────────────┘
└─────────────────┘
```

#### User Roles & Priority

| Role | Priority Level | Permissions |
|------|----------------|-------------|
| Student | 1 (Lowest) | Book rooms, view own bookings, report issues |
| Club | 2 (Medium) | Same as student + can override student bookings |
| Faculty | 3 (Highest) | Same as student + can override student & club bookings |
| Admin | N/A | Full system access, manage rooms & bookings |

### 2.5 File Structure

```
frontend/
├── ajax/                    # AJAX API endpoints
│   ├── admin_requests.php   # Handle booking approvals
│   ├── dashboard_stats.php  # Dashboard statistics
│   ├── get_bookings.php     # Fetch user bookings
│   ├── get_notifications.php# Fetch notifications
│   ├── get_reports.php      # Fetch room reports
│   ├── get_rooms.php        # Search available rooms
│   ├── manage_rooms.php     # Room data for admin
│   ├── manage_rooms_admin.php# Room CRUD operations
│   └── report_room.php      # Submit room reports
│
├── css/                     # Stylesheets
│   ├── style.css            # Main styles
│   ├── admin.css            # Admin panel styles
│   ├── loginstudent.css     # Login page styles
│   ├── register.css         # Registration styles
│   ├── manage_rooms.css     # Room management styles
│   ├── admin_reports.css    # Reports page styles
│   └── report_room.css      # Report form styles
│
├── database/
│   ├── db.php               # Database connection
│   └── db.sql               # Schema & seed data
│
├── includes/
│   ├── auth_functions.php   # Authentication helper
│   └── room_functions.php   # Room & booking logic
│
├── js/
│   └── main.js              # Client-side utilities
│
├── pages/
│   ├── loginstudent.php     # Login page
│   ├── register.php         # Registration page
│   ├── home.php             # Dashboard
│   ├── search.php           # Room search & booking
│   ├── bookings.php         # My bookings
│   ├── report_room.php      # Report room issue
│   ├── admin.php            # Admin panel
│   ├── manage_rooms.php     # Room management
│   ├── admin_reports.php    # View reports
│   └── process_booking.php  # Booking processor
│
└── logout.php               # Session termination
```

---

## 3. How It Works (Step by Step)

### 3.1 User Registration

**Step 1**: Navigate to the registration page

**Step 2**: Fill in the registration form:
- First Name & Last Name
- Username (unique)
- Email address
- Password (minimum 6 characters)
- Select user type (Student/Faculty/Club)
- Additional fields based on user type:
  - Student: Student ID
  - Club: Club Name
  - Faculty: Department

**Step 3**: Click "Create Account"

**Step 4**: System validates inputs and creates account

```
[Registration Form] → [Server Validation] → [Password Hashing] → [Database Insert] → [Success Message]
```

---

### 3.2 User Login

**Step 1**: Go to login page (loginstudent.php)

**Step 2**: Enter username/email and password

**Step 3**: Click "Sign In"

**Step 4**: System authenticates and redirects:
- Regular users → Dashboard (home.php)
- Admins → Admin Panel (admin.php)

```
[Login Form] → [Verify Credentials] → [Create Session] → [Redirect to Dashboard]
```

---

### 3.3 Searching & Booking a Room

**Step 1**: Click "Search Rooms" in the sidebar

**Step 2**: Select a date using the date picker

**Step 3**: Click "Search Available Rooms"

**Step 4**: View available rooms with details:
- Room name and number
- Capacity
- Building and floor
- Available time slots

**Step 5**: Click on a time slot to book

**Step 6**: Enter booking purpose/description

**Step 7**: Submit booking request

```
[Select Date] → [AJAX: Fetch Rooms] → [Display Available Slots] → [Select Slot] → [Enter Purpose] → [Submit] → [Booking Created (Pending)]
```

**Booking Status Flow**:
```
Pending → Approved (by Admin)
       → Rejected (by Admin)
       → Cancelled (by User)
```

---

### 3.4 Viewing My Bookings

**Step 1**: Click "My Bookings" in the sidebar

**Step 2**: View all bookings in card format showing:
- Room name and date
- Time slot
- Status (Pending/Approved/Rejected/Cancelled)
- Purpose description
- "Upcoming" badge for future bookings

**Step 3**: Use filters to narrow results:
- Filter by status
- Filter by date range

**Step 4**: Actions available:
- Cancel pending/approved bookings
- View booking details

```
[Load Page] → [AJAX: Fetch Bookings] → [Display Cards] → [Apply Filters] → [AJAX: Fetch Filtered] → [Update Display]
```

---

### 3.5 Reporting Room Issues

**Step 1**: Click "Report Room" in the sidebar

**Step 2**: Fill in the report form:
- Select the room
- Enter issue title
- Select severity (Low/Medium/High)
- Describe the issue in detail

**Step 3**: Submit the report

**Step 4**: Admin receives the report for review

```
[Select Room] → [Enter Details] → [AJAX: Submit Report] → [Database Insert] → [Confirmation Message]
```

---

### 3.6 Admin: Approving/Rejecting Bookings

**Step 1**: Admin logs in and sees the verification queue

**Step 2**: View pending booking requests showing:
- Requester name and priority level
- Room details
- Date and time slot
- Purpose (expandable)

**Step 3**: Click row to expand details

**Step 4**: Click "Approve" or "Deny" button

**Step 5**: User receives notification of decision

```
[View Queue] → [Expand Details] → [Approve/Deny] → [AJAX: Update Status] → [User Notified]
```

---

### 3.7 Admin: Managing Rooms

**Step 1**: Click "Manage Rooms" in admin sidebar

**Step 2**: Add new room:
- Enter room number, name
- Set capacity
- Specify building and floor
- Click "Add Room"

**Step 3**: View all rooms in table format

**Step 4**: Actions per room:
- Toggle availability
- Delete room

**Step 5**: Block room for specific date:
- Select room
- Choose date
- Enter reason
- Click "Block Room"

```
[Add Room Form] → [AJAX: Insert Room] → [Refresh Table]
[Block Room] → [AJAX: Insert Block] → [Update Blocked List]
```

---

### 3.8 Dashboard & Notifications

**Dashboard Statistics**:
- Pending bookings count
- Upcoming bookings count
- Approved bookings total

**Campus Events**:
- University-wide announcements
- Filtered by user type

**Notification Bell**:
- Shows unread notification count
- Click to view notifications
- Notifications for:
  - Booking approved
  - Booking rejected
  - Booking cancelled
  - Higher priority override

```
[Page Load] → [AJAX: Fetch Stats] → [AJAX: Fetch Events] → [AJAX: Fetch Notifications] → [Update UI]
```

---

## 4. Limitations & Challenges

### 4.1 Current Limitations

#### Technical Limitations

| Limitation | Description |
|------------|-------------|
| **No Email Notifications** | Users must check the app for booking status updates; no email/SMS alerts |
| **Single Campus Support** | System designed for one campus; multi-campus would require modifications |
| **No Recurring Bookings** | Users cannot book rooms for recurring schedules (e.g., every Monday) |
| **No Calendar Integration** | No export to Google Calendar, Outlook, or iCal |
| **No Mobile App** | Web-only; no native mobile application |
| **No Room Images** | Rooms don't have photos or virtual tours |
| **Fixed Time Slots** | Time slots are predefined; no custom duration booking |

#### Functional Limitations

| Limitation | Description |
|------------|-------------|
| **No Payment Integration** | Some universities charge for room usage; not supported |
| **No Waiting List** | If a room is booked, users cannot join a waiting list |
| **No Conflict Resolution** | Admin must manually handle overlapping requests |
| **No Usage Analytics** | No reports on room utilization, peak hours, etc. |
| **No Equipment Booking** | Cannot book projectors, microphones, etc. along with rooms |

### 4.2 Challenges Faced

#### Development Challenges

1. **AJAX Implementation**
   - *Challenge*: Managing asynchronous requests and updating UI without page refresh
   - *Solution*: Used `fetch()` API with async/await for clean asynchronous code

2. **Priority-Based Booking System**
   - *Challenge*: Implementing fair priority system without conflicts
   - *Solution*: Added priority column to bookings table; higher priority users can override lower priority pending bookings

3. **Session Management**
   - *Challenge*: Maintaining user state across multiple pages
   - *Solution*: Implemented PHP sessions with role-based access control using `AuthHelper` class

4. **CSS Organization**
   - *Challenge*: Inline styles causing maintenance issues
   - *Solution*: Separated CSS into dedicated files per page component

5. **SQL Injection Prevention**
   - *Challenge*: Securing database queries from malicious input
   - *Solution*: Used prepared statements throughout the application

#### Design Challenges

1. **Responsive Design**
   - *Challenge*: Making the interface work on all screen sizes
   - *Solution*: Used CSS Grid, Flexbox, and media queries for responsive layouts

2. **Intuitive Admin Interface**
   - *Challenge*: Displaying complex booking data in an accessible way
   - *Solution*: Used expandable table rows and clear action buttons

3. **User Feedback**
   - *Challenge*: Informing users of action results without page refresh
   - *Solution*: Implemented toast notifications and inline status updates

---

## 5. Conclusion

### 5.1 Project Summary

UniRoom successfully addresses the challenges of university room booking by providing:

- ✅ **Centralized Booking Platform**: Single source of truth for all room reservations
- ✅ **Priority-Based System**: Fair allocation favoring academic activities
- ✅ **Real-Time Availability**: Instant room availability checking
- ✅ **Administrative Control**: Comprehensive admin dashboard for management
- ✅ **Issue Reporting**: Built-in system for reporting room problems
- ✅ **Modern UI/UX**: Responsive, intuitive interface with AJAX interactions

### 5.2 Key Achievements

| Feature | Benefit |
|---------|---------|
| Online Booking | Accessible 24/7 from any device |
| Role-Based Access | Security and appropriate permissions |
| AJAX Integration | Smooth, app-like user experience |
| Notification System | Users stay informed of booking status |
| Admin Dashboard | Efficient management of rooms and requests |

### 5.3 Future Enhancements

If continued, the project could include:

1. **Email/SMS Notifications** - Alert users of booking status changes
2. **Recurring Bookings** - Allow weekly/monthly recurring reservations
3. **Calendar Integration** - Export to Google Calendar, Outlook
4. **Mobile Application** - Native iOS/Android apps
5. **Analytics Dashboard** - Room utilization reports and insights
6. **Equipment Booking** - Book equipment alongside rooms
7. **Waiting List** - Queue system for popular rooms
8. **Multi-Campus Support** - Extend to multiple campus locations
9. **Payment Gateway** - For rooms requiring booking fees
10. **QR Code Check-in** - Verify physical presence at booked rooms

### 5.4 Learning Outcomes

Through this project, the following skills were developed:

- **Full-Stack Development**: PHP backend with JavaScript frontend
- **Database Design**: Normalized MySQL schema design
- **Security Practices**: SQL injection prevention, password hashing, XSS protection
- **AJAX/REST APIs**: Asynchronous data communication
- **Responsive Design**: Mobile-friendly CSS layouts
- **Session Management**: User authentication and authorization
- **Project Organization**: Clean file structure and code separation

### 5.5 Final Remarks

UniRoom demonstrates that a well-designed web application can significantly improve university operations. The priority-based booking system ensures fair resource allocation, while the admin tools provide necessary oversight. The use of modern web technologies (PHP, MySQL, JavaScript, CSS3) creates a responsive and maintainable application suitable for deployment in a real university environment.

---

## Appendix

### A. How to Install

1. Install XAMPP (Apache + MySQL)
2. Copy project folder to `htdocs/`
3. Create database using phpMyAdmin
4. Import `db.sql` file
5. Update database credentials in `database/db.php`
6. Access via `http://localhost/WebProgramProj/WebProgramming-main/frontend/pages/loginstudent.php`

### B. Default Test Accounts

| Username | Password | Role |
|----------|----------|------|
| admin | admin123 | Administrator |
| john_doe | password | Student |
| dr_smith | password | Faculty |
| techclub | password | Club |

### C. Browser Compatibility

- Google Chrome (Recommended)
- Mozilla Firefox
- Microsoft Edge
- Safari

---

*Report prepared for Web Programming Project*
*UniRoom - University Room Booking System*

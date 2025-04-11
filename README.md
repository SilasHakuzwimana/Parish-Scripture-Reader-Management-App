
# Parish Scripture Reader Management System

A responsive web application designed to help parishes efficiently manage Holy Scripture readers for Masses from Monday to Sunday. Coordinators can assign readers, track availability, send automated reminders, and print monthly or weekly reading plans for display or distribution.

---

## 🛠️ Built With

- **Frontend**: HTML, CSS, JavaScript, Bootstrap 5
- **Backend**: PHP 8+
- **Database**: MySQL
- **Notifications**: PHPMailer (Email Reminders)
- **PDF Reports**: FPDF / Dompdf
- **Design Approach**: User-Centered Design (UCD), Mobile-first responsive layout

---

## 📌 Features

- 📅 **Mass Scheduling**: Add and manage daily and Sunday Mass schedules.
- 👤 **Reader Management**: Assign scripture readers for specific roles (First Reading, Second Reading, Preaching).
- ✅ **Availability Tracking**: Readers indicate their availability days.
- 📤 **Automated Reminders**: Email notifications sent:
  - Immediately upon assignment
  - 2 days before the mass
  - 1 day before the mass
- 🖨️ **Downloadable Plans**: Export and print reader schedules weekly or monthly for display.
- 🔒 **Authentication & Roles**:
  - Coordinator (Admin): Full control
  - Reader: View assignments, set availability
- 📱 **Responsive Design**: Fully optimized for desktops, tablets, and phones.

---

## 🧰 Setup Instructions

### 1. Clone the Repository

```bash
git clone https://github.com/SilasHakuzwimana/Parish-Scripture-Reader-Management-App.git
cd Parish-Scripture-Reader-Management-App
```

### 2. Configure the Database

- Import the SQL file located at `database/schema.sql` into your MySQL database.
- Update `config/database.php` with your DB credentials.

### 3. Configure PHPMailer

Edit `config/mail.php`:

```php
$mail->Host = 'smtp.gmail.com';
$mail->Username = 'info.stbasile@gmail.com';
$mail->Password = ''; // App password
```

### 4. Start the App

Open the project via `localhost/Parish-Scripture-Reader-Management-App` or your server directory.

---

## 🧪 Sample Credentials (for Demo)

| Role        | Email                | Password |
| ----------- | -------------------- | -------- |
| Coordinator | coordinator@mail.com | 123456   |
| Reader      | reader@mail.com      | 123456   |

---

## 📸 Screenshots

*Add screenshots of dashboard, calendar view, assignment page, and reminder email preview here.*

---

## 📅 Roadmap

- [X] Assignment Management
- [X] Email Notifications with PHPMailer
- [X] PDF Export of Plans
- [ ] SMS Notification Integration
- [ ] Reader Feedback & Confirm Participation

---

## 👌 Contributing

Contributions are welcome! Please fork the repository and submit a pull request.

---

## 📄 License

This project is licensed under the MIT License.

---

## 📩 Contact

Built with ❤️ by **Silas HAKUZWIMANA**
📧 Email: hakuzwisilas@gmail.com
🌐 Portfolio: [https://silas-portfolio.ct.ws/](https://silas-portfolio.ct.ws/)

---

# 📘 Project Report: Parish Scripture Reader Management System

## 1. Project Title

**Parish Scripture Reader Management System**

## 2. Problem Statement

Parishes often struggle to effectively assign and manage readers for daily and Sunday Masses. Miscommunication and last-minute changes lead to unprepared readers, affecting the flow and sanctity of services.

## 3. Objective

To build a digital system that simplifies the coordination of scripture readers, ensures timely communication, automates reminders, and improves preparedness for each Mass.

## 4. Scope of the System

- Coordinate reader assignments from Monday to Sunday
- Automatically notify readers of their assignments
- Allow readers to set their availability in advance
- Generate printable reading schedules for parish boards
- Ensure mobile-friendly access and user-centered interaction

## 5. Tools & Technologies Used

| Layer         | Technology                       |
| ------------- | -------------------------------- |
| Frontend      | HTML, CSS, Bootstrap, JavaScript |
| Backend       | PHP                              |
| Database      | MySQL                            |
| Email Service | PHPMailer                        |
| PDF Export    | Dompdf / FPDF                    |
| Design Method | User-Centered Design (UCD)       |

## 6. System Modules

1. **User Authentication**

   - Secure login for readers and coordinators
   - Role-based access control
2. **Mass Scheduling**

   - Add/edit Masses (including time and date)
   - Define default structure for daily and Sunday Masses
3. **Reader Assignment**

   - Assign readers to First Reading, Second Reading, or Preaching
   - Allow updates for special occasions
4. **Reminder System**

   - Email reminders sent at assignment, 2 days, and 1 day before
   - Messages are professional and include assignment details
5. **Reports & Calendar**

   - Generate monthly or weekly reading schedules
   - Export to PDF for printing or distribution
6. **Reader Availability**

   - Readers can declare their availability weekly
   - Coordinators can prioritize available readers

## 7. Design Approach

The project follows a **User-Centered Design (UCD)** approach:

- Iterative feedback from parish coordinators
- Simple, clean, and intuitive UI
- Mobile-first responsive design using Bootstrap

## 8. Expected Outcomes

- Improved communication between coordinators and readers
- Timely reminders ensuring preparedness
- Digital records for future planning and history tracking
- Paperless and printable monthly schedules

## 9. Limitations and Future Improvements

- SMS notifications can be added
- Calendar drag-and-drop assignments
- Integration with Google Calendar or iCalendar
- Attendance tracking for assigned readers

## 10. Conclusion

This system bridges the communication and coordination gap in parish reader scheduling. It supports structured planning and ensures readers are well-informed and reminded, contributing to the smooth flow of parish Masses.

---

### Submitted by: Silas HAKUZWIMANA

**Parish Scripture Reader Management App**
**Date**: Friday April 11th, 2025

# Agricultural Equipment Rental Portal

A web-based platform for renting agricultural equipment, built with PHP and MySQL.

## Features

- User Registration & Authentication
- Equipment Listing Management
- Equipment Browsing & Rental
- Booking Management System
- Responsive Design

## Tech Stack

- Frontend: HTML, CSS, JavaScript
- Backend: PHP
- Database: MySQL
- Additional: Bootstrap for responsive design

## Project Structure

```
agri-rental/
├── assets/         # Static files (CSS, JS, images)
├── config/         # Database configuration
├── includes/       # PHP helper functions
├── models/         # Database models
├── controllers/    # Business logic
├── views/          # Frontend templates
└── public/         # Public facing files
```

## Setup Instructions

1. Clone the repository
2. Configure database settings in config/database.php
3. Import database schema from database/schema.sql
4. Start your PHP server
5. Access the application through your web browser

## Database Schema

- users (id, name, email, password, phone, address, role)
- equipment (id, owner_id, name, category, description, daily_rate, weekly_rate, monthly_rate)
- bookings (id, equipment_id, renter_id, start_date, end_date, status)
- categories (id, name)
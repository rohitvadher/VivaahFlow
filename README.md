# VivaahFlow

### Wedding Services & Event Management System

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php\&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?logo=bootstrap\&logoColor=white)](https://getbootstrap.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

> A modern desktop-first platform for managing wedding services, customers, bookings, events, payments, invoices, and reviews.

## Features

* Public wedding services website
* Customer portal
* Admin management panel
* Customer & enquiry management
* Services, packages & offers
* Quotations & bookings
* Events & staff assignments
* Payments & invoices
* Reviews & notifications
* Role-based access control
* First-run setup wizard
* REST API
* Demo data support

## Screenshots

### Home

![VivaahFlow Home](docs/screenshots/home.png)

### Services

![VivaahFlow Services](docs/screenshots/services.png)

### Gallery

![VivaahFlow Gallery](docs/screenshots/gallery.png)

## Tech Stack

| Layer    | Technology                   |
| -------- | ---------------------------- |
| Backend  | PHP 8.2+                     |
| Database | MariaDB / MySQL              |
| Frontend | HTML, CSS, JavaScript        |
| UI       | Bootstrap 5                  |
| Icons    | Lucide                       |
| Server   | Apache / PHP Built-in Server |

## Requirements

* PHP 8.2+
* MariaDB 10.4+ / MySQL 5.7+
* Apache or PHP built-in server
* `pdo_mysql`, `mbstring`, `fileinfo`
* Modern desktop browser

## Installation

```bash
git clone https://github.com/rohitvadher/VivaahFlow.git
cd VivaahFlow
```

Place the project in your XAMPP `htdocs` directory and start **Apache + MySQL**.

Then open:

```text
http://localhost/VivaahFlow
```

The setup wizard will guide you through the initial installation.

## Database

Default database:

```text
vivaahflow
```

Schema and demo data are available in:

```text
database/
├── schema.sql
├── demo_seed.sql
└── seed.php
```

## Demo Accounts

| Role     | Email                  | Password       |
| -------- | ---------------------- | -------------- |
| Admin    | `admin@example.com`    | `Admin@123`    |
| Manager  | `manager@example.com`  | `Manager@123`  |
| Staff    | `staff@example.com`    | `Staff@123`    |
| Customer | `customer@example.com` | `Customer@123` |

> Demo credentials are for local development only.

## Project Structure

```text
VivaahFlow/
├── backend/
├── config/
├── database/
├── frontend/
├── storage/
├── uploads/
├── index.php
├── router.php
└── README.md
```

## License

See [LICENSE](LICENSE) for details.

---

<div align="center">

**VivaahFlow** · Wedding Services & Event Management

</div>

# Employee Attendance Management System

A comprehensive web-based employee attendance management system built with Laravel and modern web technologies.

## Features

- **User Authentication & Authorization**
  - Role-based access control (Admin/Employee)
  - JWT token authentication with Laravel Passport
  - Secure user registration and login

- **Attendance Management**
  - Clock in/out functionality
  - Attendance tracking and reporting
  - Admin approval system

- **User Management**
  - Employee profile management
  - Role and permission management
  - Admin dashboard

## Tech Stack

- **Backend:** Laravel 11
- **Frontend:** Livewire + Flux (Blade Components) with Alpine.js
- **Database:** MySQL
- **Authentication:** Laravel Passport (OAuth2)
- **Authorization:** Spatie Laravel Permission
- **Styling:** Tailwind CSS
- **Build Tool:** Vite

## Prerequisites

- PHP >= 8.2
- Composer
- Node.js >= 18
- MySQL >= 8.0
- Git

## Installation Steps

### 1. Clone the Repository

```bash
git clone https://github.com/rasandilikshana/Employee-Attendance-Management-System.git
cd Employee-Attendance-Management-System
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install Node.js Dependencies

```bash
npm install
```

### 4. Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 5. Configure Database

Edit the `.env` file with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=employee_attendance
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 6. Database Migration and Seeding

```bash
# Run migrations and seed database
php artisan migrate:fresh --seed
```

### 7. Setup Laravel Passport

```bash
# Generate Passport encryption keys
php artisan passport:keys

# Create personal access client
php artisan passport:client --personal --name="Personal Access Client"
```

When prompted for user provider, select `users` (option 0).

### 8. Build Frontend Assets

```bash
# For development
npm run dev

# For production
npm run build
```

### 9. Start the Application

```bash
# Start Laravel development server
php artisan serve
```

The application will be available at `http://127.0.0.1:8000`

## 🐳 Docker Installation (Recommended)

### Prerequisites for Docker Setup
- Docker
- Docker Compose

### Quick Start with Docker

1. **Clone the Repository**
   ```bash
   git clone https://github.com/rasandilikshana/Employee-Attendance-Management-System.git
   cd Employee-Attendance-Management-System
   ```

2. **Build and Start Services**
   ```bash
   docker-compose up -d --build
   ```

3. **Wait for Services to Initialize**
   The application will automatically:
   - Set up the MySQL database
   - Run migrations
   - Generate application keys
   - Create Passport keys and clients
   - Seed the database with default users

4. **Access the Application**
   - **Web Application:** http://localhost:8000
   - **API Base URL:** http://localhost:8000/api
   - **phpMyAdmin:** http://localhost:8080 (optional database management)

### Docker Services

| Service | Port | Description |
|---------|------|-------------|
| Web App | 8000 | Laravel application |
| MySQL | 3307 | Database server |
| phpMyAdmin | 8080 | Database management (optional) |

### Docker Commands

```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# View logs
docker-compose logs -f app

# Rebuild and restart
docker-compose up -d --build

# Access application container
docker-compose exec app bash

# Run Laravel commands in container
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
```

### Environment Variables for Docker

The docker-compose.yml includes production-ready environment variables. For customization, you can override them or create a `.env.docker` file.

### 🚀 Docker Build Process

The Docker setup includes automatic initialization:

1. **Application Key Generation** - Automatically generates Laravel APP_KEY
2. **Database Setup** - Waits for MySQL to be ready, then runs migrations
3. **Passport Configuration** - Generates OAuth keys and creates personal access client
4. **Database Seeding** - Seeds default admin and employee users
5. **Cache Optimization** - Caches config, routes, and views for production
6. **Apache Server** - Starts Apache with proper PHP-FPM configuration

### 📋 Build Logs Overview

When you run `docker-compose up -d --build`, the system will:

```bash
# 1. Build the Docker image with PHP 8.2 + Apache
# 2. Install all PHP dependencies via Composer
# 3. Install Node.js dependencies and build frontend assets  
# 4. Set proper file permissions for Laravel
# 5. Create Apache virtual host configuration
# 6. Start MySQL database container
# 7. Start Laravel application container
# 8. Initialize database and generate keys
# 9. Seed database with default users
```

### 🔄 Container Initialization Process

On first startup, the application container automatically:

```bash
Generating application key...
Waiting for database...
Running migrations...
Generating Passport keys...
Creating personal access client...
Seeding database...
Caching configuration...
Starting Apache server...
```

### 🎯 Production Ready Features

- **Optimized PHP-FPM** with Apache for better performance
- **Production-grade MySQL 8.0** with persistent data storage
- **Automated SSL/TLS ready** configuration
- **Health checks** for container monitoring
- **Volume mounts** for persistent data
- **Network isolation** for security

## Default Users

After seeding, you can login with these default accounts:

**Admin User:**
- Email: `admin@attendance.com`
- Password: `admin123`

**Employee User:**
- Email: `employee@attendance.com`
- Password: `employee123`

## API Endpoints

### Authentication

#### Register User (Public)
```http
POST /api/auth/register
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Response:**
```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": 3,
            "name": "John Doe",
            "email": "john@example.com",
            "roles": ["employee"]
        },
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
        "token_type": "Bearer"
    }
}
```

#### Login (Public)
```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "john@example.com",
    "password": "password123"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 3,
            "name": "John Doe",
            "email": "john@example.com",
            "roles": ["employee"],
            "permissions": []
        },
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
        "token_type": "Bearer"
    }
}
```

#### Get User Profile (Authenticated)
```http
GET /api/auth/me
Authorization: Bearer {access_token_from_login_or_register}
Content-Type: application/json
```

**Note:** You must first login or register to get an `access_token`, then use it in the Authorization header.

#### Logout (Authenticated)
```http
POST /api/auth/logout
Authorization: Bearer {access_token_from_login_or_register}
Content-Type: application/json
```

**Note:** Requires valid Bearer token from login/register response.

#### Refresh Token (Authenticated)
```http
POST /api/auth/refresh
Authorization: Bearer {access_token_from_login_or_register}
Content-Type: application/json
```

**Note:** Requires valid Bearer token. Returns a new access token and revokes the current one.

## 📋 Postman API Documentation

**Complete API Documentation and Testing Collection:**

https://employee-attendance-api.postman.co/workspace/Employee-Attendance-API-Workspa~4f8cb2d3-4f9c-4037-b956-9fbeb5673ed3/collection/37989023-5f8a4957-80d0-4c69-ba4b-916243ebf39f?action=share&creator=37989023

### Authentication Flow Example

1. **Register or Login** to get access token:
   ```bash
   curl -X POST http://127.0.0.1:8000/api/auth/login \
   -H "Content-Type: application/json" \
   -d '{"email": "admin@attendance.com", "password": "admin123"}'
   ```

2. **Copy the access_token** from the response

3. **Use the token** for authenticated endpoints:
   ```bash
   curl -X GET http://127.0.0.1:8000/api/auth/me \
   -H "Authorization: Bearer YOUR_ACCESS_TOKEN_HERE" \
   -H "Content-Type: application/json"
   ```

## Development

### Running in Development Mode

1. **Backend (Laravel):**
   ```bash
   php artisan serve
   ```

2. **Frontend Assets (Vite - for Tailwind CSS & Alpine.js):**
   ```bash
   npm run dev
   ```

### Database Management

```bash
# Reset database
php artisan migrate:fresh --seed

# Create new migration
php artisan make:migration create_table_name

# Create new seeder
php artisan make:seeder SeederName
```

### Useful Commands

```bash
# Clear application cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Generate IDE helper files (if installed)
php artisan ide-helper:generate

# Run tests
php artisan test
```

## Project Structure

```
├── app/
│   ├── Http/Controllers/Api/    # API Controllers
│   ├── Livewire/               # Livewire Component Classes
│   ├── Models/                 # Eloquent Models
│   └── Providers/              # Service Providers
├── database/
│   ├── migrations/             # Database Migrations
│   └── seeders/               # Database Seeders
├── resources/
│   ├── js/                    # Alpine.js & JavaScript
│   ├── css/                   # Tailwind CSS
│   └── views/                 # Blade Templates & Livewire Components
│       ├── livewire/         # Livewire Component Views
│       ├── flux/             # Flux UI Components
│       └── components/       # Blade Components
├── routes/
│   ├── api.php               # API Routes
│   └── web.php               # Web Routes
└── storage/
    └── oauth/                # Passport Keys
```

## Troubleshooting

### Common Issues

1. **"Personal access client not found" Error:**
   ```bash
   php artisan passport:client --personal --name="Personal Access Client"
   ```

2. **OAuth Key Permission Issues:**
   ```bash
   chmod 600 storage/oauth-private.key storage/oauth-public.key
   ```

3. **Database Connection Issues:**
   - Verify database credentials in `.env`
   - Ensure MySQL service is running
   - Check if database exists

4. **Frontend Build Issues:**
   ```bash
   npm install
   npm run build
   ```

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Support

For support and questions, please open an issue on the GitHub repository.

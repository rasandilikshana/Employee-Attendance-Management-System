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
- **Frontend:** Vue.js with Vite
- **Database:** MySQL
- **Authentication:** Laravel Passport (OAuth2)
- **Authorization:** Spatie Laravel Permission
- **Styling:** Tailwind CSS

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

2. **Frontend (Vite):**
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
│   ├── Models/                  # Eloquent Models
│   └── Providers/              # Service Providers
├── database/
│   ├── migrations/             # Database Migrations
│   └── seeders/               # Database Seeders
├── resources/
│   ├── js/                    # Vue.js Components
│   └── views/                 # Blade Templates
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

---

**Happy Coding! 🚀**
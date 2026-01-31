# Admin Dashboard (Laravel)

This folder will contain the Laravel admin dashboard for managing affiliate ads.

## Setup Instructions

### Prerequisites

- PHP 8.2+ with required extensions (see Laravel docs)
- Composer (https://getcomposer.org/)
- MySQL database (from cPanel)

### Create Laravel Project

```bash
# Navigate to parent directory
cd affiliate-ad-sync

# Remove this placeholder and create fresh Laravel project
rm -rf admin-dashboard
composer create-project laravel/laravel admin-dashboard

# Navigate into the new project
cd admin-dashboard
```

### Configure Database

Edit `.env` in admin-dashboard:

```env
DB_CONNECTION=mysql
DB_HOST=your_cpanel_host
DB_PORT=3306
DB_DATABASE=affiliate_ads
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

### Install Laravel Breeze (Authentication)

```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
npm install && npm run build
php artisan migrate
```

### Run Development Server

```bash
php artisan serve
# Visit http://localhost:8000
```

## Features to Implement

1. **Dashboard** - Show ad counts by network, approval stats
2. **Ads Table** - List all ads with filtering/sorting/pagination
3. **Approval Workflow** - Approve/deny ads with optional reason
4. **CSV Export** - Export approved ads for AdRotate import
5. **Authentication** - Secure admin access with Laravel Breeze

## Deployment to cPanel

1. Upload files via FTP to your subdomain folder (e.g., `admin.thepartshops.com`)
2. Set document root to `public/` folder
3. Configure `.env` with production database credentials
4. Run `php artisan migrate` via SSH or hosting panel

## Laravel Commands

```bash
php artisan serve          # Start dev server
php artisan migrate        # Run migrations
php artisan make:model Ad  # Create model
php artisan make:controller AdController  # Create controller
php artisan tinker         # Interactive shell
```

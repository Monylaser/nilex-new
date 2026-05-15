# Nilex Platform - Development Context

## Project Overview
- **Type**: Laravel 13 PHP application with Filament admin panel
- **Stack**: Laravel 13, Filament 5.4, Livewire, Tailwind CSS
- **Key Features**: Listing management, point system, user roles, media library

## Key Commands

### Development
```bash
composer install          # Install PHP dependencies
npm install              # Install Node dependencies
npm run dev              # Start Vite dev server
php artisan serve        # Start Laravel dev server
```

### Database
```bash
php artisan migrate      # Run migrations
php artisan db:seed      # Seed database
php artisan migrate:fresh --seed  # Fresh install with seeds
```

### Code Quality
```bash
./vendor/bin/pint        # Run PHP formatter
./vendor/bin/phpunit     # Run tests
```

## Project Structure

### Models
- `User` - User with roles and points
- `Listing` - Main listing entity with images
- `Category` - Listing categories
- `Location` - Egypt locations (from JSON data)
- `CarBrand` / `CarModel` - Vehicle brands/models
- `PhoneBrand` / `PhoneModel` - Phone brands/models
- `PointTransaction` - Point system transactions

### Services
- `GeminiService` - AI integration for ad creation
- `PointService` - Point management logic

### Livewire Components
- `SmartAdCreator` - AI-powered ad creation

### Observers
- `UserObserver` - User lifecycle events
- `PointTransactionObserver` - Track point changes

### Policies
- `ListingPolicy`, `CategoryPolicy`, `LocationPolicy`, `RolePolicy`, `UserPolicy`

## Database
- SQLite for development (configured in `config/database.php`)
- Egypt locations seeded from `database/data/egypt_locations.json`

## Key Files
- Routes: `routes/web.php`, `routes/auth.php`
- Config: `config/app.php`, `config/filament`
- Admin: `app/Filament/Admin/`
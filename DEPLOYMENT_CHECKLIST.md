# Deployment Checklist for Namecheap

## Pre-Deployment (Local)

### 1. Update .env for Production
```env
APP_NAME="Institution Management Portal"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:Db41914Y7ARY/1hnAAkT5A9Q8nXHASvLO/VnNq2woNs=
APP_URL=https://eportal.personel.ink

# Database - Update with Namecheap MySQL details
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=file

# Mail (configure for production)
MAIL_MAILER=smtp
MAIL_HOST=smtp.yourprovider.com
MAIL_PORT=587
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_email_password
MAIL_FROM_ADDRESS="noreply@eportal.personel.ink"
MAIL_FROM_NAME="${APP_NAME}"
```

### 2. Optimize Dependencies
```bash
composer install --optimize-autoloader --no-dev
```

### 3. Clear All Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
```

---

## Deployment (Namecheap Server)

### 1. Upload Files
- Upload entire project to: `/home/username/public_html/` or your subdomain folder
- **Important**: Point domain to `public` folder

### 2. Create Database on Namecheap
```
1. Login to Namecheap cPanel
2. Go to MySQL Databases
3. Create database: portal
4. Create user with full privileges
5. Import your local database:
   mysql -u username -p portal < backup.sql
```

### 3. Configure .env
Edit `.env` file with production credentials

### 4. Run Setup Commands
```bash
# Navigate to project directory
cd /home/username/public_html

# Install dependencies
composer install --optimize-autoloader --no-dev

# Generate key (if needed)
php artisan key:generate

# Clear and cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Set permissions
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

### 5. Configure Domain Pointing
If using subdomain (e.g., eportal.personel.ink):
- Create subdomain in cPanel → Subdomains
- Set document root to: `/home/username/public_html/public`

---

## Post-Deployment Verification

### Check These:
- [ ] Site loads at https://eportal.personel.ink
- [ ] Login works with: admin@portal.edu / password
- [ ] Database connections work
- [ ] File uploads work (check storage folder)
- [ ] Email sending works (if configured)

### If Issues Occur:
```bash
# Check logs
tail -f storage/logs/laravel.log

# Reset cache
php artisan cache:clear
php artisan config:clear
```

---

## Quick Deploy Commands (SSH)
```bash
cd /home/username/public_html
git pull origin master  # If using git
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Backup Before Deploy
```bash
# Local
mysqldump -u root -p portal > backup_$(date +%Y%m%d).sql

# Server (via SSH)
mysqldump -u username -p portal > backup_$(date +%Y%m%d).sql
```

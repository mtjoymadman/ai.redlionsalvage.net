# /README.md

## Red Lion Salvage AI Deployment

### Prerequisites
- Shared hosting with PHP 7.4+ and MySQL 5.7+
- cPanel or similar control panel
- FTP client (e.g., FileZilla)

### Steps
1. **Database Setup**
   - Create a MySQL database via cPanel named `red_lion_salvage_ai`.
   - Import `database/schema.sql` using phpMyAdmin or MySQL CLI.
   - Update the initial admin password hash in the `employees` table.

2. **File Upload**
   - Upload the entire `RedLionSalvageAi` folder to your web root (e.g., `/public_html`) via FTP.

3. **Configuration**
   - Edit `api/config.php`:
     - Set `$user` and `$pass` to your MySQL credentials.
     - Set `$notification_email` to your notification email.

4. **Cron Job**
   - In cPanel, set up a cron job to run `api/fleet/check_expirations.php` daily:
     ```
     php /home/yourusername/public_html/RedLionSalvageAi/api/fleet/check_expirations.php
     ```

5. **Testing**
   - Visit `http://yourdomain.com/RedLionSalvageAi/frontend/index.html`.
   - Log in with `admin` and your set password.

### Notes
- Ensure the `/assets/uploads/` directory is writable (chmod 755).
- Replace `yourdomain.com` with your actual domain.
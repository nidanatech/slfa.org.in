# SLFA account access

## Set the administrator password

The requested admin account is `kannan@nidanatech.com`. If the migration created it, its password is random and unknown. On the **hosting server**, from the Laravel project directory, run the seeder with a password entered privately at the shell prompt:

```bash
read -r -s -p 'New admin password: ' SLFA_ADMIN_PASSWORD
printf '\n'
export SLFA_ADMIN_PASSWORD
php artisan db:seed --class=AdminUserSeeder --force
unset SLFA_ADMIN_PASSWORD
```

Use at least 12 characters with upper and lower case letters, a number, and a symbol. The seeder updates only this administrator account. Run it again to change the password. Do not put the password in source code, a shell command argument, or a chat message.

## Enable password reset email

The password reset form requires a working outbound mail transport. Laravel's example configuration uses `MAIL_MAILER=log`, which records mail in a server log and does **not** deliver it. Configure the production `.env` with a real SMTP transport and sender address (`MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, and `MAIL_FROM_NAME`), then run `php artisan config:cache` and request a reset link as a test.

If email remains unavailable, the seeder above can set a new password securely through Hostinger SSH.

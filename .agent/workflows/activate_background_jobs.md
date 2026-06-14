---
description: How to activate background jobs (Scheduler and Queue Worker) in production
---

# Activating Background Jobs in Production

To ensure your email sync and other scheduled tasks run automatically, you need to set up two background processes on your production server.

## 1. The Scheduler (Cron)

The scheduler handles periodic tasks like the 15-minute email sync we just configured.

**On a Linux Server (Standard Production):**
Add the following Cron entry to your server:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

This command runs every minute and checks if any scheduled tasks need to be executed.

**On Local Windows (Development):**
You can simulate the scheduler by running:
```powershell
php artisan schedule:work
```

## 2. The Queue Worker

The queue worker processes the actual heavy lifting (like syncing 500 emails) in the background so your app stays fast.

**On a Linux Server (CentOS/RHEL):**

1.  **Install Supervisor:**
    ```bash
    sudo yum install epel-release
    sudo yum install supervisor
    sudo systemctl enable supervisord
    sudo systemctl start supervisord
    ```

2.  **Create Configuration File:**
    Create a file at `/etc/supervisord.d/handsondeck-worker.ini` (Note the `.ini` extension on CentOS):

    ```ini
    [program:handsondeck-worker]
    process_name=%(program_name)s_%(process_num)02d
    command=php /var/www/html/handsondeck/artisan queue:work --sleep=3 --tries=3 --max-time=3600
    autostart=true
    autorestart=true
    user=nginx  ; Or your web user
    numprocs=2
    redirect_stderr=true
    stdout_logfile=/var/www/html/handsondeck/worker.log
    stopwaitsecs=3600
    ```

3.  **Update Supervisor:**
    ```bash
    sudo supervisorctl reread
    sudo supervisorctl update
    sudo supervisorctl start handsondeck-worker:*
    ```

**On Local Windows (Development):**
Run the worker manually in a separate terminal:
```powershell
php artisan queue:work
```

## Summary of Commands

| Function | Command | Production Setup |
| :--- | :--- | :--- |
| **Scheduler** (Triggers tasks) | `php artisan schedule:run` | Add to **Crontab** (runs every minute) |
| **Queue Worker** (Does work) | `php artisan queue:work` | Run with **Supervisor** (runs continuously) |

> [!IMPORTANT]
> Without these two processes running, your automatic email sync and other background tasks will **NOT** execute.

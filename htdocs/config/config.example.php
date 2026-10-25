<?php
/** Secret-free template. Copy to htdocs/_private/config.php using the file manager; never commit real values. */
return [
    'APP_BASE_URL' => 'https://YOUR-DOMAIN',
    // Use the exact MySQL hostname displayed in InfinityFree Control Panel (sqlNNN.infinityfree.com), not localhost.
    'DB_HOST' => 'sqlXXX.infinityfree.com',
    'DB_NAME' => 'YOUR_DATABASE_NAME',
    'DB_USER' => 'YOUR_DATABASE_USERNAME',
    'DB_PASS' => 'SET_IN_HOSTING_FILE_MANAGER',
    // Use a third-party SMTP provider. InfinityFree does not provide SMTP mailboxes.
    'SMTP_HOST' => 'smtp.your-provider.example',
    'SMTP_PORT' => 587,
    'SMTP_USERNAME' => 'YOUR_SMTP_USERNAME',
    'SMTP_PASSWORD' => 'SET_IN_HOSTING_FILE_MANAGER',
    'SMTP_ENCRYPTION' => 'tls',
    'SMTP_FROM_EMAIL' => 'verified-sender@your-domain.example',
    'SMTP_FROM_NAME' => 'Student Support Portal',
];

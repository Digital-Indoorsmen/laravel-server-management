{{ $site->domain }} {
    root * {{ $site->document_root }}
    encode gzip zstd

    php_fastcgi unix//run/php-fpm/{{ $site->system_user }}.sock

    header {
        X-Frame-Options "SAMEORIGIN"
        X-Content-Type-Options "nosniff"
        X-XSS-Protection "1; mode=block"
        Referrer-Policy "strict-origin-when-cross-origin"
    }

    @hiddenFiles path /.*
    respond @hiddenFiles 404

    file_server
}

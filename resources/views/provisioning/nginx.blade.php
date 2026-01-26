server {
    listen 80;
    listen [::]:80;
    server_name {{ $site->domain }};
    root {{ $site->document_root }};
    index index.php index.html index.htm;

    # Access and Error logs
    access_log /var/log/nginx/{{ $site->domain }}-access.log;
    error_log /var/log/nginx/{{ $site->domain }}-error.log;

    location / {
        @if($site->app_type === 'laravel')
            try_files $uri $uri/ /index.php?$query_string;
        @elseif($site->app_type === 'wordpress')
            try_files $uri $uri/ /index.php?$args;
        @else
            try_files $uri $uri/ =404;
        @endif
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php-fpm/{{ $site->system_user }}.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.ht {
        deny all;
    }
}

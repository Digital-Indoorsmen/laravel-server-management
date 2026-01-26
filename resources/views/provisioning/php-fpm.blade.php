[{{ $site->system_user }}]
user = {{ $site->system_user }}
group = {{ $site->system_user }}
listen = /run/php-fpm/{{ $site->system_user }}.sock
listen.owner = nginx
listen.group = nginx
listen.mode = 0660

pm = ondemand
pm.max_children = 10
pm.process_idle_timeout = 10s
pm.max_requests = 200

php_admin_value[error_log] = /var/log/php-fpm/{{ $site->system_user }}-error.log
php_admin_flag[log_errors] = on
php_value[session.save_handler] = files
php_value[session.save_path]    = /var/lib/php/session

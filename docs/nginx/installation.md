# Install NGINX for luxon framework

### Install NGINX, PHP and FastCGI
```sh
sudo apt install nginx php7.4 php7.4-fpm
```

### Configure NGINX
```nginx
server {
        listen 80 default_server;
        listen [::]:80 default_server;
        root /var/www/html;
        server_name _;
        try_files /index.php?$query_string /index.php?$query_string;
        location /index.php {
                include snippets/fastcgi-php.conf;
                include fastcgi_params;
                fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        }
        location ~ /\.ht {
                deny all;
        }
}
```
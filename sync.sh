rsync -crtv --exclude '.env' --exclude 'public/dev' ./ ./public/dev/
chown -R www-data:www-data ./public/dev
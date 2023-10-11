#!/bin/bash
# 
# ins richtige Verzeichnis wechseln
cd /var/www/hornung-baushop.de/web

# Datenbank neu exportieren
echo "Start: $(date +'%Y-%m-%d %H:%M:%S')"
echo "Export Datenbank 1 x Struktur, 1 x Daten"
echo "Start"
mysqldump -h 127.0.0.1 -u c1w4db1 -p --no-data --opt c1w5db1 > live-structure.sql
mysqldump -h 127.0.0.1 -u c1w4db1 -p --no-create-info --ignore-table=c1w5db1.cart c1w5db1 > live-data.sql
echo "Ende"

# Datenbank Dateien anpassen
echo "Datenbank Dateien anpassen"
echo "Start"
sed -i 's/DEFINER=`c1w5db1`@`localhost`/DEFINER=`c1w4db1`@`localhost`/g' live-data.sql
sed -i 's/DEFINER=`c1w5db1`@`localhost`/DEFINER=`c1w4db1`@`localhost`/g' live-structure.sql
sed -i 's/c1w5db1/c1staging/g' live-data.sql
sed -i 's/c1w5db1/c1staging/g' live-structure.sql
echo "Ende"

# Staging-Datenbank neu einlesen
echo "Neue Staging-Datenbank erzeugen";
echo "Start"
mysql -e "drop DATABASE staging;"
mysql -e "create DATABASE staging;"
mysql -h 127.0.0.1 -u c1w4db1 -p c1staging < live-structure.sql
mysql -h 127.0.0.1 -u c1w4db1 -p c1staging < live-data.sql
rm live-structure.sql
rm live-data.sql
echo "Ende";

# Files in staging Verzeichnis kopieren
# Nicht nötige Daten ausschließen
echo "Dateien kopieren"
echo "Start"
rsync -arv  --exclude '*.sql' --exclude '*.log' --exclude 'backup' --exclude '*.tar.gz' --exclude '.env' --exclude 'staging' --exclude 'staging02' --exclude 'files/documents' /var/www/hornung-baushop.de/web/ /var/www/hornung-baushop.de/web/staging/
echo "Ende"

echo "Staging Umgebung ist unter https://hornung-baushop.de/staging/ zu erreichen";
echo "ENDE: $(date +'%Y-%m-%d %H:%M:%S')";

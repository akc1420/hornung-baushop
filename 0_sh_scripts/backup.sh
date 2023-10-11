#!/bin/bash
# 
# ins richtige Verzeichnis wechseln
cd /var/www/hornung-baushop.de/web

# Datenbank neu exportieren
echo "Start: $(date +'%Y-%m-%d %H:%M:%S')"
echo "Start Export Datenbank backup.sql"
mysqldump shopware > backup.sql
echo "Ende Export Datenbank zu backup.sql"

# Backup-Datenbank neu einlesen
echo "Start neue Backup-Datenbank erzeugen";
mysql -e "drop DATABASE backup;"
mysql -e "create DATABASE backup;"
mysql backup < backup.sql
rm backup.sql
echo "Ende neue Backup-Datenbank erzeugen";

# Files in backup Verzeichnis kopieren
# Nicht nötige Daten ausschließen
echo "Start Dateien kopieren"
rsync -arv  --exclude '*.log' --exclude '*.tar.gz' --exclude '.env' --exclude 'staging' --exclude 'staging02' --exclude 'files/documents' /var/www/hornung-baushop.de/web/ /var/www/hornung-baushop.de/web/backup/
echo "Ende Dateien kopieren"
echo "Backup ENDE: $(date +'%Y-%m-%d %H:%M:%S')";

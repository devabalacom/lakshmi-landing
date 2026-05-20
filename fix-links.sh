#!/bin/bash
# Fix navigation links in index.html

# Полная карта карточек на файлы услуг
declare -A services
services["Защитные чехлы"]="services/chekhly-tenty.html"
services["Спецодежда"]="services/specodezhda.html"
services["Тактическое снаряжение"]="services/tactical.html"
services["Транспортировочные тенты"]="services/transport.html"
services["Огнеупорные изделия"]="services/fire.html"
services["Антистатические изделия"]="services/cleanroom.html"
services["Медицинский текстиль"]="services/medical.html"
services["Технический интерьерный текстиль B2B"]="services/interior.html"
services["Агропромышленный текстиль"]="services/agro.html"

echo "✓ Links mapping created"
for name in "${!services[@]}"; do
    echo "$name -> ${services[$name]}"
done

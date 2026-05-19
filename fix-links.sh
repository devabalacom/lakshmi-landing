#!/bin/bash
# Fix navigation links in index.html

# Создаем mapping карточек на файлы услуг
declare -A services
services["Транспортировочные тенты"]="services/transport.html"
services["Огнеупорные изделия"]="services/fire.html"
services["Антистатические изделия"]="services/interior.html"
services["Морозостойкие чехлы"]="services/agro.html"
services["Влагозащитные покрытия"]="services/medical.html"
services["Индивидуальные решения"]="services/cleanroom.html"

echo "✓ Links mapping created"
echo "Manually update remaining cards in index.html"

#!/bin/bash

# Генератор страниц услуг для Лакшми

# Массив услуг: filename|h1|subtitle|description
services=(
  "specodezhda|Спецодежда с защитными свойствами|Огнеупорная, антистатическая спецодежда для промышленности. Соответствие ГОСТ и ТУ.|Изготавливаем спецодежду для рабочих, охраны, строителей, энергетиков. Материалы: огнеупорные, антистатические, водо- и ветроустойчивые ткани."
  
  "tactical|Тактическое и outdoor-снаряжение|Надежное снаряжение из Cordura и Ripstop. Пошив под СТМ.|Производим снаряжение для охоты, туризма и силовых структур. Рюкзаки, чехлы для оружия, подсумки, пончо."
  
  "medical|Медицинский текстиль|Антимикробные изделия для медицины и стоматологии|Шьём изделия для клиник, ветеринарии. Халаты, накидки, защитные чехлы из антимикробных тканей."
  
  "interior|Технический интерьерный текстиль B2B|Огнестойкий текстиль для отелей и ресторанов|Текстиль для коммерческой недвижимости: шторы, обивка, драпировки. Огнестойкие, антивандальные материалы."
  
  "transport|Транспортный текстиль|Обивка и чехлы для жд, авиа, спецтехники|Изготавливаем текстильные изделия для транспорта: чехлы, обивки, органайзеры."
  
  "agro|Агропромышленный текстиль|Укрывные системы для ферм и теплиц|Решения для сельхозпредприятий: укрывные ткани, чехлы, защита оборудования. УФ-защита, влагостойкость."
  
  "fire|Пожарозащита, МЧС, охрана объектов|Огнестойкие изделия для аварийных служб|Огнестойкие палатки, тенты, чехлы для МЧС и силовых структур. Экстремальные условия."
  
  "cleanroom|Текстиль для чистых помещений|Антистатическая одежда для фармпроизводства|Пошив для фармы, биотеха, лабораторий. Антистатика, пыленепроницаемость, стерильность."
)

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

for service in "${services[@]}"; do
  IFS='|' read -r filename h1 subtitle description <<< "$service"
  
  SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

  cat > "$SCRIPT_DIR/services/${filename}.html" << 'EOF'
<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SERVICE_H1 | Лакшми</title>
    <meta name="description" content="SERVICE_SUBTITLE">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header class="fixed w-full bg-white shadow-md z-50">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <a href="../index.html" class="text-2xl font-bold"><span class="text-blue-600">Лакшми</span></a>
            <a href="tel:+74996477281" class="bg-blue-600 text-white px-6 py-2 rounded-lg">+7 499 647-72-81</a>
        </nav>
    </header>

    <section class="pt-24 pb-16 bg-gradient-to-br from-gray-50 to-blue-50">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto text-center">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">SERVICE_H1</h1>
                <p class="text-xl text-gray-700 mb-8">SERVICE_SUBTITLE</p>
                <a href="#contact" class="bg-blue-600 text-white px-8 py-4 rounded-lg text-lg font-semibold hover:bg-blue-700 transition inline-block">
                    Получить расчет
                </a>
            </div>
        </div>
    </section>

    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <p class="text-lg text-gray-700 mb-8">SERVICE_DESCRIPTION</p>
            </div>
        </div>
    </section>

    <section id="contact" class="py-16 bg-gradient-to-br from-blue-700 to-blue-950">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <div class="bg-white/95 backdrop-blur rounded-2xl shadow-2xl p-8 md:p-10">
                    <h2 class="text-3xl font-bold text-slate-900 mb-4">Свяжитесь с нами для расчета</h2>
                    <p class="text-slate-700 mb-8">Оставьте заявку по телефону или email, и мы быстро вернемся с уточнениями по ТЗ, срокам и стоимости.</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <a href="tel:+74996477281" class="rounded-xl border border-slate-200 p-4 hover:border-blue-500 hover:shadow-md transition">
                            <div class="text-sm uppercase tracking-[0.2em] text-slate-500">Телефон</div>
                            <div class="mt-2 text-lg font-semibold text-slate-900">+7 499 647-72-81</div>
                        </a>
                        <a href="mailto:info@lakshmi-textile.ru" class="rounded-xl border border-slate-200 p-4 hover:border-blue-500 hover:shadow-md transition">
                            <div class="text-sm uppercase tracking-[0.2em] text-slate-500">Email</div>
                            <div class="mt-2 text-lg font-semibold text-slate-900">info@lakshmi-textile.ru</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-gray-900 text-white py-12">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; 2026 Лакшми. Все права защищены.</p>
        </div>
    </footer>
</body>
</html>
EOF

  # Replace placeholders
  sed -i "s/SERVICE_H1/${h1}/g" "$SCRIPT_DIR/services/${filename}.html"
  sed -i "s/SERVICE_SUBTITLE/${subtitle}/g" "$SCRIPT_DIR/services/${filename}.html"
  sed -i "s/SERVICE_DESCRIPTION/${description}/g" "$SCRIPT_DIR/services/${filename}.html"
  
  echo "✓ Created ${filename}.html"
done

echo "✓ All service pages generated!"

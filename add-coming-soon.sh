#!/bin/bash
# Add "Coming Soon" banner to stub service pages

COMING_SOON='                <!-- Coming Soon Notice -->
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 mb-8 rounded-r-lg">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-yellow-600 mr-3 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Страница в разработке</h3>
                            <p class="text-gray-700 mb-3">Мы работаем над наполнением этого раздела. Подробная информация о продукции и услугах появится в ближайшее время.</p>
                            <p class="text-gray-700"><strong>Нужна консультация прямо сейчас?</strong> Позвоните нам по телефону <a href="tel:+74996477281" class="text-blue-600 font-semibold hover:underline">+7 499 647-72-81</a> или оставьте заявку ниже.</p>
                        </div>
                    </div>
                </div>
                '

# List of stub pages (exclude chekhly-tenты.html which is complete)
STUB_PAGES=(
    "services/cleanroom.html"
    "services/fire.html"
    "services/interior.html"
    "services/medical.html"
    "services/specodezhda.html"
    "services/tactical.html"
    "services/transport.html"
)

for page in "${STUB_PAGES[@]}"; do
    echo "Processing $page..."
    
    # Insert Coming Soon banner after <div class="max-w-4xl mx-auto">
    # Use perl for better multi-line handling
    perl -i -pe '
        if (/<div class="max-w-4xl mx-auto">/ && !$seen++) {
            $_ .= qq{                <!-- Coming Soon Notice -->
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 mb-8 rounded-r-lg">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-yellow-600 mr-3 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Страница в разработке</h3>
                            <p class="text-gray-700 mb-3">Мы работаем над наполнением этого раздела. Подробная информация о продукции и услугах появится в ближайшее время.</p>
                            <p class="text-gray-700"><strong>Нужна консультация прямо сейчас?</strong> Позвоните нам по телефону <a href="tel:+74996477281" class="text-blue-600 font-semibold hover:underline">+7 499 647-72-81</a> или оставьте заявку ниже.</p>
                        </div>
                    </div>
                </div>
                
};
        }
        BEGIN { $seen = 0 }
    ' "$page"
    
    echo "✓ $page updated"
done

echo ""
echo "✅ All stub pages updated with Coming Soon banners!"

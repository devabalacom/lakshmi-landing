// Mobile menu toggle
const mobileMenuBtn = document.getElementById('mobile-menu-btn');
const mobileMenu = document.getElementById('mobile-menu');

mobileMenuBtn.addEventListener('click', () => {
    mobileMenu.classList.toggle('hidden');
});

// Close mobile menu when clicking on a link
const mobileMenuLinks = mobileMenu.querySelectorAll('a');
mobileMenuLinks.forEach(link => {
    link.addEventListener('click', () => {
        mobileMenu.classList.add('hidden');
    });
});

// Contact form submission
const contactForm = document.getElementById('contact-form');

contactForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const submitButton = contactForm.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.innerHTML;
    
    // Disable button and show loading state
    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="inline-block animate-spin mr-2">⚙️</span> Отправка...';
    
    try {
        // Submit to Formspree
        const response = await fetch(contactForm.action, {
            method: 'POST',
            body: new FormData(contactForm),
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (response.ok) {
            // Show success message
            const successMessage = document.createElement('div');
            successMessage.className = 'bg-green-50 border-l-4 border-green-400 p-4 mb-4 rounded-r-lg';
            successMessage.innerHTML = `
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <p class="text-green-800 font-semibold">Спасибо! Ваша заявка принята. Мы свяжемся с вами в течение часа.</p>
                </div>
            `;
            contactForm.insertBefore(successMessage, contactForm.firstChild);
            
            // Clear form
            contactForm.reset();
            
            // Hide success message after 8 seconds
            setTimeout(() => {
                successMessage.remove();
            }, 8000);
        } else {
            throw new Error('Form submission failed');
        }
    } catch (error) {
        // Show error message
        const errorMessage = document.createElement('div');
        errorMessage.className = 'bg-red-50 border-l-4 border-red-400 p-4 mb-4 rounded-r-lg';
        errorMessage.innerHTML = `
            <div class="flex items-center">
                <svg class="w-5 h-5 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                <p class="text-red-800">Произошла ошибка. Пожалуйста, позвоните нам: <a href="tel:+74996477281" class="font-semibold underline">+7 499 647-72-81</a></p>
            </div>
        `;
        contactForm.insertBefore(errorMessage, contactForm.firstChild);
        
        setTimeout(() => {
            errorMessage.remove();
        }, 8000);
    } finally {
        // Re-enable button
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
    }
});

// Phone number formatting
const phoneInput = document.getElementById('phone');

phoneInput.addEventListener('input', (e) => {
    let value = e.target.value.replace(/\D/g, '');
    
    if (value.length > 0) {
        if (value[0] === '7' || value[0] === '8') {
            value = '7' + value.substring(1);
        } else {
            value = '7' + value;
        }
    }
    
    let formatted = '+7';
    if (value.length > 1) {
        formatted += ' (' + value.substring(1, 4);
    }
    if (value.length >= 5) {
        formatted += ') ' + value.substring(4, 7);
    }
    if (value.length >= 8) {
        formatted += '-' + value.substring(7, 9);
    }
    if (value.length >= 10) {
        formatted += '-' + value.substring(9, 11);
    }
    
    e.target.value = formatted;
});

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Header scroll effect
let lastScroll = 0;
const header = document.querySelector('header');

window.addEventListener('scroll', () => {
    const currentScroll = window.pageYOffset;
    
    if (currentScroll > 100) {
        header.classList.add('shadow-lg');
    } else {
        header.classList.remove('shadow-lg');
    }
    
    lastScroll = currentScroll;
});

// Intersection Observer for fade-in animations
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Observe all sections
document.querySelectorAll('section').forEach(section => {
    section.style.opacity = '0';
    section.style.transform = 'translateY(20px)';
    section.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    observer.observe(section);
});

// Console welcome message
console.log('%cЛакшми - Производство технического текстиля', 'color: #2563eb; font-size: 20px; font-weight: bold;');
console.log('%cСвяжитесь с нами: +7 499 647-72-81', 'color: #6b7280; font-size: 14px;');

// Bio Medical Dental Care - Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    
    // Mobile Menu Toggle
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
        });
        
        // Close mobile menu on link click
        const mobileLinks = mobileMenu.querySelectorAll('a');
        mobileLinks.forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.add('hidden');
            });
        });
    }
    
    // Navbar Scroll Effect
    const navbar = document.getElementById('navbar');
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
    
    // Show More Services - FIXED
    const showMoreBtn = document.getElementById('show-more-services');
    let servicesExpanded = false;
    
    if (showMoreBtn) {
        // Get all service cards
        const allServiceCards = document.querySelectorAll('[data-service-card]');
        
        showMoreBtn.addEventListener('click', function() {
            servicesExpanded = !servicesExpanded;
            const btnText = document.getElementById('btn-text');
            const btnIcon = document.getElementById('btn-icon');
            
            allServiceCards.forEach((card, index) => {
                if (index >= 6) {
                    if (servicesExpanded) {
                        card.classList.remove('hidden');
                        card.style.animation = 'fadeIn 0.5s ease-in-out';
                    } else {
                        card.classList.add('hidden');
                    }
                }
            });
            
            if (servicesExpanded) {
                btnText.textContent = 'Show Less Services';
                btnIcon.classList.remove('fa-chevron-down');
                btnIcon.classList.add('fa-chevron-up');
            } else {
                btnText.textContent = 'Show More Services';
                btnIcon.classList.remove('fa-chevron-up');
                btnIcon.classList.add('fa-chevron-down');
            }
        });
    }
    
    // Reviews Carousel
    const prevBtn = document.getElementById('prev-review');
    const nextBtn = document.getElementById('next-review');
    const reviewsTrack = document.getElementById('reviews-track');
    const reviewSlides = document.querySelectorAll('.review-slide');
    let currentSlide = 0;
    
    function updateCarousel() {
        const slideWidth = reviewSlides[0].offsetWidth;
        reviewsTrack.style.transform = `translateX(-${currentSlide * slideWidth}px)`;
    }
    
    if (prevBtn && nextBtn) {
        prevBtn.addEventListener('click', function() {
            currentSlide = currentSlide > 0 ? currentSlide - 1 : reviewSlides.length - 1;
            updateCarousel();
        });
        
        nextBtn.addEventListener('click', function() {
            currentSlide = currentSlide < reviewSlides.length - 1 ? currentSlide + 1 : 0;
            updateCarousel();
        });
        
        // Auto-play carousel
        setInterval(function() {
            currentSlide = currentSlide < reviewSlides.length - 1 ? currentSlide + 1 : 0;
            updateCarousel();
        }, 5000);
    }
    
    // Review Form Submission
    const reviewForm = document.getElementById('review-form');
    const reviewMessage = document.getElementById('review-message');
    
    if (reviewForm) {
        reviewForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(reviewForm);
            
            try {
                const response = await fetch('api/submit_review.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                reviewMessage.classList.remove('hidden');
                if (data.success) {
                    reviewMessage.className = 'mt-4 text-center message-success';
                    reviewMessage.innerHTML = '<i class="fas fa-check-circle mr-2"></i>' + data.message;
                    reviewForm.reset();
                } else {
                    reviewMessage.className = 'mt-4 text-center message-error';
                    reviewMessage.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>' + data.message;
                }
                
                setTimeout(() => {
                    reviewMessage.classList.add('hidden');
                }, 5000);
            } catch (error) {
                reviewMessage.classList.remove('hidden');
                reviewMessage.className = 'mt-4 text-center message-error';
                reviewMessage.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>An error occurred. Please try again.';
            }
        });
    }
    
    // Appointment Form Submission
    const appointmentForm = document.getElementById('appointment-form');
    const appointmentMessage = document.getElementById('appointment-message');
    
    if (appointmentForm) {
        appointmentForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(appointmentForm);
            
            // Validate phone number
            const phone = formData.get('phone');
            const phoneRegex = /^(\+92|0)?[0-9]{10}$/;
            
            if (!phoneRegex.test(phone.replace(/[-\s]/g, ''))) {
                appointmentMessage.classList.remove('hidden');
                appointmentMessage.className = 'mt-6 text-center message-error';
                appointmentMessage.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>Please enter a valid Pakistani phone number';
                return;
            }
            
            // Show loading
            appointmentMessage.classList.remove('hidden');
            appointmentMessage.className = 'mt-6 text-center';
            appointmentMessage.innerHTML = '<div class="spinner"></div><p class="mt-2">Booking your appointment...</p>';
            
            try {
                const response = await fetch('api/book_appointment.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    appointmentMessage.className = 'mt-6 text-center message-success';
                    appointmentMessage.innerHTML = '<i class="fas fa-check-circle mr-2"></i>' + data.message;
                    appointmentForm.reset();
                    
                    // Scroll to message
                    appointmentMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    appointmentMessage.className = 'mt-6 text-center message-error';
                    appointmentMessage.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>' + data.message;
                }
            } catch (error) {
                appointmentMessage.className = 'mt-6 text-center message-error';
                appointmentMessage.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>An error occurred. Please try again.';
            }
        });
    }
    
    // Scroll to Top Button
    const scrollTopBtn = document.createElement('div');
    scrollTopBtn.id = 'scroll-top';
    scrollTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
    document.body.appendChild(scrollTopBtn);
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 300) {
            scrollTopBtn.classList.add('visible');
        } else {
            scrollTopBtn.classList.remove('visible');
        }
    });
    
    scrollTopBtn.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    
    // Smooth Scroll for Anchor Links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                const offsetTop = target.offsetTop - 80;
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Intersection Observer for Animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observe service and doctor cards
    document.querySelectorAll('.service-card, .doctor-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });
});

// Prevent form resubmission on page refresh
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}
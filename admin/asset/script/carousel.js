document.addEventListener('DOMContentLoaded', function() {
    const carouselWrapper = document.querySelector('.carousel-wrapper');
    
    if (!carouselWrapper) return;
    
    const slides = document.querySelectorAll('.carousel-slide');
    const totalSlides = slides.length;
    
    if (totalSlides <= 1) return; // Don't animate if only one slide
    
    let currentSlide = 0;
    const slideInterval = 4000; // 4 seconds per slide
    const transitionDuration = 0.8; // 0.8 seconds for smooth transition
    
    function moveToSlide(index) {
        const offset = -index * 100;
        carouselWrapper.style.transition = `transform ${transitionDuration}s ease-in-out`;
        carouselWrapper.style.transform = `translateX(${offset}%)`;
        currentSlide = index;
    }
    
    function nextSlide() {
        const next = (currentSlide + 1) % totalSlides;
        moveToSlide(next);
    }
    
    // Auto-advance carousel
    setInterval(nextSlide, slideInterval);
    
    console.log("[v0] Carousel initialized with " + totalSlides + " slides");
});

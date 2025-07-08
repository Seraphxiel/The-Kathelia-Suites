// Preloader functionality
window.addEventListener('load', function() {
    document.body.classList.add('loaded');
});

// Polaroid image switching functionality + auto-scroll on rooms.php
document.addEventListener('DOMContentLoaded', function() {
    const images = [
        {
            image: 'room_twin.png',
            header: 'Twin Room',
            desc: 'Good for partners for life. Make sure your relationship has a label to fully enjoy Kathelia Suites!'
        },
        {
            image: 'room_group.png',
            header: 'Harmony Room',
            desc: 'Finals at PUP stressing your group? Visit Kathelia Suites and enjoy our 5.0-rated services!'
        },
        {
            image: 'room_harmony.png',
            header: 'Family Room',
            desc: 'Too many relatives to count? Our Family Room has beds for the whole squad—bring the chaos, leave with memories!'
        },
        {
            image: 'amenity_gym.png',
            header: 'Gym',
            desc: 'Ready for the glow by 2030? Kathelia Suites would love to see you gaslighting yourself!'
        },
        {
            image: 'amenity_pool.png',
            header: 'Swimming Pool',
            desc: 'Running out of water at home? Kathelia Suites swimming pool is open 24 hrs—at any age, even a fetus can swim!'
        },
        {
            image: 'amenity_basketball.png',
            header: 'Basketball',
            desc: 'Got game? Hit our court and slam-dunk your stress away!'
        },
        {
            image: 'amenity_billiards.png',
            header: 'Billiards',
            desc: 'Chalk up, rack em up—our billiards lounge is where champions break!'
        },
        {
            image: 'hotel_outside.png',
            header: 'Outside',
            desc: '"Kathelia Suites is open to anyone willing to pay—because the view is exactly what you paid for."'
        }
    ];

    let start = 0;
    const carousel     = document.getElementById('carousel-images');
    const hero         = document.querySelector('.hero');
    const heroSection  = document.querySelector('.hero-section');
    const imgWidth     = 220 + 24; // image width + gap (1.5rem = 24px)

    function updateHeroText(header, desc) {
        // Show the section if it's hidden
        if (heroSection.style.display === 'none') {
            heroSection.style.display = 'block';
        }

        // Add fade-out class
        heroSection.classList.add('fade-out');
        
        // Wait for fade-out animation
        setTimeout(() => {
            // Update content
            heroSection.querySelector('.hero-subtitle').textContent = header;
            heroSection.querySelector('.hero-desc').textContent     = desc;
            
            // Remove fade-out class to trigger fade-in
            heroSection.classList.remove('fade-out');
        }, 300);
    }

    function renderCarousel() {
        if (!carousel) return;
        
        carousel.innerHTML = '';
        images.forEach((data, i) => {
            const img = document.createElement('img');
            img.className     = 'carousel-img';
            img.src           = `/kathelia-suites/assets/images/${data.image}`;
            img.alt           = data.header;
            img.dataset.index = i;

            img.addEventListener('click', function(e) {
                e.preventDefault();
                const clickedIndex = parseInt(this.dataset.index, 10);
                
                // Only allow click if this is the first visible image
                if (clickedIndex !== start) return;

                // Change hero background
                hero.style.backgroundImage = `url('/kathelia-suites/assets/images/${data.image}')`;

                // Update hero text with animation
                updateHeroText(data.header, data.desc);

                // Pop out the clicked image
                this.classList.add('popping');

                // Slide the row left
                carousel.style.transform = `translateX(-${imgWidth}px)`;

                setTimeout(() => {
                    // Move the clicked image to the end
                    const removed = images.splice(start, 1)[0];
                    images.push(removed);
                    
                    // Reset transform and re-render
                    carousel.style.transition = 'none';
                    carousel.style.transform  = 'translateX(0)';
                    requestAnimationFrame(() => {
                        renderCarousel();
                        // Restore transition after reflow
                        setTimeout(() => {
                            carousel.style.transition = 'transform 0.5s cubic-bezier(.77,0,.18,1)';
                        }, 50);
                    });
                }, 500);
            });

            carousel.appendChild(img);
        });
    }

    // Initial render
    renderCarousel();

    // Slideshow functionality
    function initializeSlideshows() {
        const slideshows = document.querySelectorAll('.slideshow');
        if (!slideshows.length) {
            console.log('No slideshows found on the page');
            return;
        }

        slideshows.forEach(slideshow => {
            const slides = slideshow.querySelectorAll('.slide');
            if (!slides.length) {
                console.log('No slides found in slideshow');
                return;
            }

            let currentSlide = 0;
            
            function nextSlide() {
                if (!slides.length) return;
                
                slides[currentSlide].classList.remove('active');
                currentSlide = (currentSlide + 1) % slides.length;
                slides[currentSlide].classList.add('active');
            }

            // Start the slideshow
            const interval = setInterval(nextSlide, 2000);

            // Cleanup on page unload
            window.addEventListener('unload', () => {
                clearInterval(interval);
            });
        });
    }

    // Initialize slideshows
    initializeSlideshows();

    // --- ADDED: auto-scroll to the correct room section on rooms.php ---
    const path = window.location.pathname;
    if (path.endsWith('/rooms.php')) {
      const params = new URLSearchParams(window.location.search);
      const guests = parseInt(params.get('guests'), 10);
      let targetId = null;

      if      (guests >= 1 && guests <= 2)  targetId = 'twin';
      else if (guests >= 3 && guests <= 5)  targetId = 'family';
      else if (guests >= 6 && guests <= 10) targetId = 'harmony';

      if (targetId) {
        const section = document.getElementById(targetId);
        if (section) {
          // scroll to center instead of start
          section.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      }
    }

    var profileIcon = document.getElementById('profileIcon');
    if (profileIcon) {
        var dropdownLi = profileIcon.closest('.profile-dropdown');
        profileIcon.addEventListener('click', function(e) {
            e.preventDefault();
            dropdownLi.classList.toggle('open');
        });
        // Hide dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!dropdownLi.contains(e.target)) {
                dropdownLi.classList.remove('open');
            }
        });
    }
});

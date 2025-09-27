import { useState, useEffect } from 'react';
import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { route } from 'ziggy-js';

interface Promotion {
    id: number;
    welcome_text: string | null;
    title: string;
    subtitle: string | null;
    primary_button_text: string;
    primary_button_link: string;
    secondary_button_text: string | null;
    secondary_button_link: string | null;
    background_image: string | null;
    discount: string | null;
    promo_code: string | null;
}

interface HeroSliderProps {
    promotions?: Promotion[];
}

// Fallback slides using all 4 public slide images
const fallbackSlides: Promotion[] = [
    {
        id: 1,
        welcome_text: 'Kamotech Aircon Services',
        title: 'PRICE STARTS AT 450 PESOS!',
        subtitle: 'Find the affordable, Find your satisfaction!',
        primary_button_text: 'BOOK NOW',
        primary_button_link: '/booking',
        secondary_button_text: 'SIGN UP',
        secondary_button_link: '/register',
        background_image: '/images/slide/1.jpg',
        discount: null,
        promo_code: null
    },
    {
        id: 2,
        welcome_text: 'Professional AC Services',
        title: 'FREE SURVEY & FREE CHECKUP!',
        subtitle: 'Cleaning • Repair • Freon Charging • Installation • Relocation & More',
        primary_button_text: 'GET QUOTE',
        primary_button_link: '/booking',
        secondary_button_text: 'LEARN MORE',
        secondary_button_link: '#services',
        background_image: '/images/slide/2.jpg',
        discount: 'FREE SERVICE',
        promo_code: 'FREECHECK'
    },
    {
        id: 3,
        welcome_text: 'Quality & Reliability',
        title: 'EXPERT TECHNICIANS',
        subtitle: 'Licensed professionals with years of experience in AC maintenance and repair',
        primary_button_text: 'BOOK SERVICE',
        primary_button_link: '/booking',
        secondary_button_text: 'VIEW SERVICES',
        secondary_button_link: '/services',
        background_image: '/images/slide/3.jpg',
        discount: null,
        promo_code: null
    },
    {
        id: 4,
        welcome_text: 'Fast & Efficient',
        title: '24/7 EMERGENCY SERVICE',
        subtitle: 'Quick response time for urgent AC repairs and maintenance needs',
        primary_button_text: 'EMERGENCY CALL',
        primary_button_link: '/booking?urgent=1',
        secondary_button_text: 'CONTACT US',
        secondary_button_link: '/contact',
        background_image: '/images/slide/4.jpg',
        discount: null,
        promo_code: null
    }
];

export function HeroSlider({ promotions = [] }: HeroSliderProps) {
    // If we have fewer than 2 promos, use the predefined fallback set so the slider can rotate
    const defaultImages = ['/images/slide/1.jpg', '/images/slide/2.jpg', '/images/slide/3.jpg', '/images/slide/4.jpg'];
    const useFallback = !promotions || promotions.length < 2;
    const slides = useFallback ? fallbackSlides : promotions.map((p, i) => ({
        ...p,
        background_image: p.background_image || defaultImages[i % defaultImages.length],
    }));
    const [currentSlide, setCurrentSlide] = useState(0);
    const [isAutoPlaying, setIsAutoPlaying] = useState(true);

    // Auto-advance slides
    useEffect(() => {
        if (!isAutoPlaying) return;

        const interval = setInterval(() => {
            setCurrentSlide((prev) => (prev + 1) % slides.length);
        }, 5000); // Change slide every 5 seconds

        return () => clearInterval(interval);
    }, [isAutoPlaying, slides.length]);

    const goToSlide = (index: number) => {
        setCurrentSlide(index);
        setIsAutoPlaying(false);
        // Resume auto-play after 10 seconds of manual interaction
        setTimeout(() => setIsAutoPlaying(true), 10000);
    };

    const nextSlide = () => {
        goToSlide((currentSlide + 1) % slides.length);
    };

    const prevSlide = () => {
        goToSlide((currentSlide - 1 + slides.length) % slides.length);
    };

    return (
        <section className="hero-slider">
            <div className="slider-container">
                {slides.map((slide, index) => (
                    <div
                        key={slide.id}
                        className={`slide ${index === currentSlide ? 'active' : ''}`}
                        style={{
                            backgroundImage: `linear-gradient(
                              rgba(0, 63, 107, 0.5), 
                              rgba(30, 64, 175, 0.5)
                            ), url(${slide.background_image || defaultImages[index % defaultImages.length]})`,
                            backgroundSize: "cover",
                            backgroundPosition: "center"
                          }}                      
                    >
                        <div className="slide-overlay"></div>
                        <div className="slide-container">
                            <div className="slide-content">
                                {slide.welcome_text && (
                                    <p className="slide-welcome">{slide.welcome_text}</p>
                                )}
                                <h1 className="slide-title">{slide.title}</h1>
                                {slide.subtitle && (
                                    <p className="slide-subtitle">{slide.subtitle}</p>
                                )}
                                <div className="slide-buttons">
                                    {slide.primary_button_link.startsWith('/') && !slide.primary_button_link.startsWith('/#') ? (
                                        <Link 
                                            href={slide.primary_button_link} 
                                            className="slide-btn slide-btn-primary"
                                        >
                                            {slide.primary_button_text}
                                        </Link>
                                    ) : (
                                        <a 
                                            href={slide.primary_button_link} 
                                            className="slide-btn slide-btn-primary"
                                        >
                                            {slide.primary_button_text}
                                        </a>
                                    )}
                                    
                                    {slide.secondary_button_text && slide.secondary_button_link && (
                                        slide.secondary_button_link.startsWith('/') && !slide.secondary_button_link.startsWith('/#') ? (
                                            <Link 
                                                href={slide.secondary_button_link} 
                                                className="slide-btn slide-btn-secondary"
                                            >
                                                {slide.secondary_button_text}
                                            </Link>
                                        ) : (
                                            <a 
                                                href={slide.secondary_button_link} 
                                                className="slide-btn slide-btn-secondary"
                                            >
                                                {slide.secondary_button_text}
                                            </a>
                                        )
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            {/* Navigation Arrows */}
            <button 
                className="slider-nav slider-nav-prev" 
                onClick={prevSlide}
                aria-label="Previous slide"
            >
                <ChevronLeft size={24} />
            </button>
            <button 
                className="slider-nav slider-nav-next" 
                onClick={nextSlide}
                aria-label="Next slide"
            >
                <ChevronRight size={24} />
            </button>

            {/* Slide Indicators */}
            <div className="slider-indicators">
                {slides.map((_, index) => (
                    <button
                        key={index}
                        className={`indicator ${index === currentSlide ? 'active' : ''}`}
                        onClick={() => goToSlide(index)}
                        aria-label={`Go to slide ${index + 1}`}
                    />
                ))}
            </div>
        </section>
    );
}
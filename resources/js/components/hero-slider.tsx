import { useState, useEffect } from 'react';
import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

interface SlideData {
    id: number;
    backgroundImage: string;
    welcome: string;
    title: string;
    subtitle: string;
    primaryButton: {
        text: string;
        href: string;
    };
    secondaryButton: {
        text: string;
        href: string;
    };
}

const slides: SlideData[] = [
    {
        id: 1,
        backgroundImage: '/images/slide/1.jpg',
        welcome: 'Kamotech Aircon Services',
        title: 'PRICE STARTS AT 450 PESOS!',
        subtitle: 'Find the affordable, Find your satisfaction!',
        primaryButton: {
            text: 'BOOK NOW',
            href: '/booking'
        },
        secondaryButton: {
            text: 'SIGN UP',
            href: route('register')
        }
    },
    {
        id: 2,
        backgroundImage: '/images/slide/2.jpg',
        welcome: 'Reliable Aircon Services Anytime, Anywhere',
        title: 'FREE SURVEY & FREE CHECKUP!',
        subtitle: 'Cleaning • Repair • Freon Charging • Installation • Relocation & More',
        primaryButton: {
            text: 'GET QUOTE',
            href: route('booking')
        },
        secondaryButton: {
            text: 'LEARN MORE',
            href: '#services'
        }
    },
    {
        id: 3,
        backgroundImage: '/images/slide/3.jpg',
        welcome: 'Celebrate the Start of the Ber Months',
        title: 'BER MONTHS KICKOFF SALE – SEPT 30–OCT 1',
        subtitle: 'Aircon Cleaning Starts at ₱450 • Free Survey & Checkup',
        primaryButton: {
            text: 'VIEW SERVICES',
            href: '#services'
        },
        secondaryButton: {
            text: 'CONTACT US',
            href: '#contact'
        }
    },
    {
        id: 4,
        backgroundImage: '/images/slide/4.jpg',
        welcome: 'New Customers Get More Savings',
        title: 'GET 10% OFF YOUR FIRST SERVICE',
        subtitle: 'Book today and experience professional, affordable aircon care.',
        primaryButton: {
            text: 'SCHEDULE NOW',
            href: '#booking'
        },
        secondaryButton: {
            text: 'LEARN MORE',
            href: '#contact'
        }
    },
];

export function HeroSlider() {
    const [currentSlide, setCurrentSlide] = useState(0);
    const [isAutoPlaying, setIsAutoPlaying] = useState(true);

    // Auto-advance slides
    useEffect(() => {
        if (!isAutoPlaying) return;

        const interval = setInterval(() => {
            setCurrentSlide((prev) => (prev + 1) % slides.length);
        }, 5000); // Change slide every 5 seconds

        return () => clearInterval(interval);
    }, [isAutoPlaying]);

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
                            ), url(${slide.backgroundImage})`,
                            backgroundSize: "cover",
                            backgroundPosition: "center"
                          }}                      
                    >
                        <div className="slide-overlay"></div>
                        <div className="slide-container">
                            <div className="slide-content">
                                <p className="slide-welcome">{slide.welcome}</p>
                                <h1 className="slide-title">{slide.title}</h1>
                                <p className="slide-subtitle">{slide.subtitle}</p>
                                <div className="slide-buttons">
                                    <a 
                                        href={slide.primaryButton.href} 
                                        className="slide-btn slide-btn-primary"
                                    >
                                        {slide.primaryButton.text}
                                    </a>
                                    {slide.secondaryButton.href.startsWith('/') ? (
                                        <Link 
                                            href={route(slide.secondaryButton.href.slice(1))} 
                                            className="slide-btn slide-btn-secondary"
                                        >
                                            {slide.secondaryButton.text}
                                        </Link>
                                    ) : (
                                        <a 
                                            href={slide.secondaryButton.href} 
                                            className="slide-btn slide-btn-secondary"
                                        >
                                            {slide.secondaryButton.text}
                                        </a>
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
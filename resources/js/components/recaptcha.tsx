import { useEffect, useRef } from 'react';

declare global {
    interface Window {
        grecaptcha: any;
        onRecaptchaLoad: () => void;
    }
}

interface RecaptchaProps {
    siteKey: string;
    onChange: (token: string | null) => void;
    onError?: () => void;
}

export function Recaptcha({ siteKey, onChange, onError }: RecaptchaProps) {
    const containerRef = useRef<HTMLDivElement>(null);
    const widgetIdRef = useRef<number | null>(null);

    useEffect(() => {
        // Load reCAPTCHA script if not already loaded
        if (!window.grecaptcha) {
            const script = document.createElement('script');
            script.src = `https://www.google.com/recaptcha/api.js?render=explicit`;
            script.async = true;
            script.defer = true;
            
            window.onRecaptchaLoad = () => {
                renderRecaptcha();
            };
            
            script.onload = () => {
                if (window.grecaptcha && window.grecaptcha.render) {
                    renderRecaptcha();
                }
            };
            
            document.head.appendChild(script);
        } else {
            renderRecaptcha();
        }

        return () => {
            // Clean up widget on unmount
            if (widgetIdRef.current !== null && window.grecaptcha) {
                try {
                    window.grecaptcha.reset(widgetIdRef.current);
                } catch (e) {
                    console.error('Error resetting reCAPTCHA:', e);
                }
            }
        };
    }, [siteKey]);

    const renderRecaptcha = () => {
        if (!containerRef.current || !window.grecaptcha || !window.grecaptcha.render) {
            return;
        }

        try {
            // Clear any existing reCAPTCHA
            containerRef.current.innerHTML = '';
            
            widgetIdRef.current = window.grecaptcha.render(containerRef.current, {
                sitekey: siteKey,
                callback: (token: string) => {
                    onChange(token);
                },
                'expired-callback': () => {
                    onChange(null);
                },
                'error-callback': () => {
                    onError?.();
                    onChange(null);
                },
                theme: 'light',
                size: 'normal'
            });
        } catch (error) {
            console.error('Error rendering reCAPTCHA:', error);
        }
    };

    return (
        <div className="recaptcha-container">
            <div ref={containerRef}></div>
        </div>
    );
}
import { Head, useForm, Link } from '@inertiajs/react';
import { FormEventHandler, ChangeEvent, useState } from 'react';
import { PublicNavigation } from '@/components/public-navigation';
import { PublicFooter } from '@/components/public-footer';
import { Recaptcha } from '@/components/recaptcha';

type RegisterForm = {
    first_name: string;
    middle_initial: string;
    last_name: string;
    email: string;
    phone: string;
    date_of_birth: string;
    password: string;
    password_confirmation: string;
};

interface RegisterProps {
    recaptcha_site_key?: string;
}

export default function Register({ recaptcha_site_key }: RegisterProps) {
    const { data, setData, post, processing, errors, reset } = useForm<RegisterForm>({
        first_name: '',
        middle_initial: '',
        last_name: '',
        email: '',
        phone: '',
        date_of_birth: '',
        password: '',
        password_confirmation: '',
    });
    
    const [recaptchaToken, setRecaptchaToken] = useState<string | null>(null);
    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirmation, setShowPasswordConfirmation] = useState(false);

    // Format phone number to Philippine format
    const formatPhilippinePhone = (input: string): string => {
        // Remove all non-digit characters
        const digits = input.replace(/\D/g, '');
        
        // If starts with 63, remove it
        let formatted = digits;
        if (formatted.startsWith('63')) {
            formatted = formatted.substring(2);
        }
        
        // If starts with 9 (without 0), add 0
        if (formatted.length > 0 && formatted[0] === '9') {
            formatted = '0' + formatted;
        }
        
        // Limit to 11 digits
        formatted = formatted.substring(0, 11);
        
        // Format as 0917-123-4567
        if (formatted.length > 4 && formatted.length <= 7) {
            formatted = formatted.substring(0, 4) + '-' + formatted.substring(4);
        } else if (formatted.length > 7) {
            formatted = formatted.substring(0, 4) + '-' + formatted.substring(4, 7) + '-' + formatted.substring(7);
        }
        
        return formatted;
    };

    const handlePhoneChange = (e: ChangeEvent<HTMLInputElement>) => {
        const formatted = formatPhilippinePhone(e.target.value);
        setData('phone', formatted);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        
        // Include reCAPTCHA token if available
        const formData = {
            ...data,
            ...(recaptchaToken && { 'g-recaptcha-response': recaptchaToken })
        };
        
        post(route('register'), {
            data: formData,
            onFinish: () => {
                reset('password', 'password_confirmation');
                setRecaptchaToken(null);
            },
        });
    };

    const handleGoogleAuth = () => {
        // Handle Google authentication
        window.location.href = '/auth/google';
    };

    return (
        <>
            <Head title="Sign up - Kamotech">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
            </Head>
            
            <div className="auth-page register-page">
                <PublicNavigation />
                
                <div className="auth-content">
                    <div className="auth-container">
                        <div className="auth-card">
                            <div className="auth-left">
                                <div className="auth-brand">
                                    <img src="/images/logo-main.png" alt="Kamotech Logo" className="auth-brand-logo" />
                                    <h1 className="auth-brand-title">Kamotech</h1>
                                    <p className="auth-brand-subtitle">Air-Conditioning Services</p>
                                    <p className="auth-brand-tagline">"Your Comfort, Our Priority"</p>
                                </div>
                            </div>
                            
                            <div className="auth-right">
                                <div className="auth-header">
                                    <h2 className="auth-title">Create Your Account</h2>
                                    <p className="auth-description">Join Kamotech for professional AC services</p>
                                </div>

                                <form className="auth-form" onSubmit={submit}>
                                    <div className="form-row name-row">
                                        <div className="form-group">
                                            <label htmlFor="first_name" className="form-label">First Name *</label>
                                            <input
                                                id="first_name"
                                                type="text"
                                                required
                                                autoFocus
                                                tabIndex={1}
                                                autoComplete="given-name"
                                                value={data.first_name}
                                                onChange={(e) => setData('first_name', e.target.value)}
                                                disabled={processing}
                                                placeholder="First Name"
                                                className={`form-input ${errors.first_name ? 'error' : ''}`}
                                            />
                                            {errors.first_name && <div className="input-error">{errors.first_name}</div>}
                                        </div>
                                        <div className="form-group mi-field">
                                            <label htmlFor="middle_initial" className="form-label">M.I.</label>
                                            <input
                                                id="middle_initial"
                                                type="text"
                                                maxLength={5}
                                                tabIndex={2}
                                                autoComplete="additional-name"
                                                value={data.middle_initial}
                                                onChange={(e) => setData('middle_initial', e.target.value)}
                                                disabled={processing}
                                                placeholder="M.I."
                                                className={`form-input ${errors.middle_initial ? 'error' : ''}`}
                                            />
                                            {errors.middle_initial && <div className="input-error">{errors.middle_initial}</div>}
                                        </div>
                                    </div>
                                    
                                    <div className="form-group">
                                        <label htmlFor="last_name" className="form-label">Last Name *</label>
                                        <input
                                            id="last_name"
                                            type="text"
                                            required
                                            tabIndex={3}
                                            autoComplete="family-name"
                                            value={data.last_name}
                                            onChange={(e) => setData('last_name', e.target.value)}
                                            disabled={processing}
                                            placeholder="Last Name"
                                            className={`form-input ${errors.last_name ? 'error' : ''}`}
                                        />
                                        {errors.last_name && <div className="input-error">{errors.last_name}</div>}
                                    </div>

                                    <div className="form-group">
                                        <label htmlFor="email" className="form-label">Email Address *</label>
                                        <input
                                            id="email"
                                            type="email"
                                            required
                                            tabIndex={4}
                                            autoComplete="email"
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            disabled={processing}
                                            placeholder="Enter your email"
                                            className={`form-input ${errors.email ? 'error' : ''}`}
                                        />
                                        {errors.email && <div className="input-error">{errors.email}</div>}
                                    </div>

                                    <div className="form-group">
                                        <label htmlFor="phone" className="form-label">Mobile Number *</label>
                                        <input
                                            id="phone"
                                            type="tel"
                                            required
                                            tabIndex={5}
                                            autoComplete="tel"
                                            value={data.phone}
                                            onChange={handlePhoneChange}
                                            disabled={processing}
                                            placeholder="09XX-XXX-XXXX"
                                            maxLength={13}
                                            className={`form-input ${errors.phone ? 'error' : ''}`}
                                        />
                                        <div className="field-hint" style={{ fontSize: '0.75rem', color: '#6b7280', marginTop: '0.25rem' }}>
                                            Philippine mobile format (e.g., 0917-123-4567)
                                        </div>
                                        {errors.phone && <div className="input-error">{errors.phone}</div>}
                                    </div>

                                    <div className="form-group">
                                        <label htmlFor="date_of_birth" className="form-label">Date of Birth *</label>
                                        <input
                                            id="date_of_birth"
                                            type="date"
                                            required
                                            tabIndex={6}
                                            autoComplete="bday"
                                            value={data.date_of_birth}
                                            onChange={(e) => setData('date_of_birth', e.target.value)}
                                            disabled={processing}
                                            max={new Date(new Date().setFullYear(new Date().getFullYear() - 18)).toISOString().split('T')[0]}
                                            className={`form-input ${errors.date_of_birth ? 'error' : ''}`}
                                        />
                                        {errors.date_of_birth && <div className="input-error">{errors.date_of_birth}</div>}
                                    </div>

                                    <div className="form-group">
                                        <label htmlFor="password" className="form-label">Password *</label>
                                        <div className="password-input-container">
                                            <input
                                                id="password"
                                                type={showPassword ? "text" : "password"}
                                                required
                                                tabIndex={7}
                                                autoComplete="new-password"
                                                value={data.password}
                                                onChange={(e) => setData('password', e.target.value)}
                                                disabled={processing}
                                                placeholder="Create a strong password"
                                                className={`form-input password-input-with-toggle ${errors.password ? 'error' : ''}`}
                                            />
                                            <button
                                                type="button"
                                                className="password-toggle-button"
                                                onClick={() => setShowPassword(!showPassword)}
                                                tabIndex={-1}
                                                disabled={processing}
                                            >
                                                {showPassword ? (
                                                    <svg className="password-toggle-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                                                    </svg>
                                                ) : (
                                                    <svg className="password-toggle-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                )}
                                            </button>
                                        </div>
                                        {errors.password && <div className="input-error">{errors.password}</div>}
                                    </div>

                                    <div className="form-group">
                                        <label htmlFor="password_confirmation" className="form-label">Confirm Password *</label>
                                        <div className="password-input-container">
                                            <input
                                                id="password_confirmation"
                                                type={showPasswordConfirmation ? "text" : "password"}
                                                required
                                                tabIndex={8}
                                                autoComplete="new-password"
                                                value={data.password_confirmation}
                                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                                disabled={processing}
                                                placeholder="Confirm your password"
                                                className={`form-input password-input-with-toggle ${errors.password_confirmation ? 'error' : ''}`}
                                            />
                                            <button
                                                type="button"
                                                className="password-toggle-button"
                                                onClick={() => setShowPasswordConfirmation(!showPasswordConfirmation)}
                                                tabIndex={-1}
                                                disabled={processing}
                                            >
                                                {showPasswordConfirmation ? (
                                                    <svg className="password-toggle-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                                                    </svg>
                                                ) : (
                                                    <svg className="password-toggle-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                )}
                                            </button>
                                        </div>
                                        {errors.password_confirmation && <div className="input-error">{errors.password_confirmation}</div>}
                                    </div>

                                    {/* reCAPTCHA - Show only if site key is provided */}
                                    {recaptcha_site_key && (
                                        <div className="form-group" style={{ marginTop: '1rem' }}>
                                            <Recaptcha
                                                siteKey={recaptcha_site_key}
                                                onChange={setRecaptchaToken}
                                                onError={() => {
                                                    console.error('reCAPTCHA error');
                                                }}
                                            />
                                            {errors.recaptcha && (
                                                <div className="input-error" style={{ marginTop: '0.5rem' }}>
                                                    {errors.recaptcha}
                                                </div>
                                            )}
                                        </div>
                                    )}

                                    <button type="submit" className="auth-button" tabIndex={9} disabled={processing}>
                                        {processing && <div className="loading-spinner"></div>}
                                        Create Account
                                    </button>
                                    
                                    <div className="auth-divider">
                                        <span className="auth-divider-text">or</span>
                                    </div>
                                    
                                    <button type="button" onClick={handleGoogleAuth} className="google-button">
                                        <svg className="google-icon" viewBox="0 0 24 24">
                                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                        </svg>
                                        Sign up with Google
                                    </button>
                                </form>

                                <div className="auth-link">
                                    Already have an account? <Link href={route('login')} tabIndex={10}>Sign in</Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <PublicFooter />
            </div>
        </>
    );
}

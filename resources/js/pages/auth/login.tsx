import { Head, useForm, Link, usePage } from '@inertiajs/react';
import { FormEventHandler, useState, useEffect, useRef } from 'react';
import { PublicNavigation } from '@/components/public-navigation';
import { PublicFooter } from '@/components/public-footer';
import { Recaptcha } from '@/components/recaptcha';

type LoginForm = {
    email: string;
    password: string;
    remember: boolean;
};

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
    recaptcha_site_key?: string;
}

export default function Login({ status, canResetPassword, recaptcha_site_key }: LoginProps) {
    const { data, setData, post, processing, errors, reset } = useForm<Required<LoginForm>>({
        email: '',
        password: '',
        remember: false,
    });
    
    const [showStaffLinks, setShowStaffLinks] = useState(false);
    const [recaptchaToken, setRecaptchaToken] = useState<string | null>(null);
    const [showPassword, setShowPassword] = useState(false);
    
    // Check if the error message indicates a staff user tried to login
    useEffect(() => {
        if (errors.email) {
            const isStaffError = errors.email.includes('admin login page') || 
                               errors.email.includes('technician login page');
            setShowStaffLinks(isStaffError);
        }
    }, [errors.email]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        
        // Include reCAPTCHA token if available
        const formData = {
            ...data,
            ...(recaptchaToken && { 'g-recaptcha-response': recaptchaToken })
        };
        
        post(route('login'), {
            data: formData,
            onFinish: () => {
                reset('password');
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
            <Head title="Log in - Kamotech">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
            </Head>
            
            <div className="auth-page">
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
                                    <h2 className="auth-title">Welcome Back</h2>
                                    <p className="auth-description">Sign in to your account to continue</p>
                                </div>

                                {status && (
                                    <div className={`mb-4 text-center text-sm font-medium ${status.includes('Unable') ? 'text-red-600' : 'text-green-600'}`}>
                                        {status}
                                    </div>
                                )}

                                <form className="auth-form" onSubmit={submit}>
                                    <div className="form-group">
                                        <label htmlFor="email" className="form-label">Email address</label>
                                        <input
                                            id="email"
                                            type="email"
                                            required
                                            autoFocus
                                            tabIndex={1}
                                            autoComplete="email"
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            placeholder="Enter your email"
                                            className={`form-input ${errors.email ? 'error' : ''}`}
                                        />
                                        {errors.email && <div className="input-error">{errors.email}</div>}
                                    </div>

                                    <div className="form-group">
                                        <div className="flex items-center justify-between">
                                            <label htmlFor="password" className="form-label">Password</label>
                                            {canResetPassword && (
                                                <Link href={route('password.request')} className="forgot-password" tabIndex={5}>
                                                    Forgot password?
                                                </Link>
                                            )}
                                        </div>
                                        <div className="password-input-container">
                                            <input
                                                id="password"
                                                type={showPassword ? "text" : "password"}
                                                required
                                                tabIndex={2}
                                                autoComplete="current-password"
                                                value={data.password}
                                                onChange={(e) => setData('password', e.target.value)}
                                                placeholder="Enter your password"
                                                className={`form-input password-input-with-toggle ${errors.password ? 'error' : ''}`}
                                            />
                                            <button
                                                type="button"
                                                className="password-toggle-button"
                                                onClick={() => setShowPassword(!showPassword)}
                                                tabIndex={-1}
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

                                    <div className="remember-me">
                                        <input
                                            id="remember"
                                            type="checkbox"
                                            checked={data.remember}
                                            onChange={(e) => setData('remember', e.target.checked)}
                                            tabIndex={3}
                                            className="remember-checkbox"
                                        />
                                        <label htmlFor="remember" className="remember-label">Remember me</label>
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

                                    <button type="submit" className="auth-button" tabIndex={4} disabled={processing}>
                                        {processing && <div className="loading-spinner"></div>}
                                        Sign In
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
                                        Continue with Google
                                    </button>
                                </form>

                                <div className="auth-link">
                                    Don't have an account? <Link href={route('register')} tabIndex={6}>Sign up</Link>
                                </div>
                                
                                {showStaffLinks && (
                                    <div className="auth-role-links">
                                        <p className="text-sm text-gray-600 mt-4">Staff Login:</p>
                                        <div className="flex gap-4 mt-2">
                                            <a href="/admin/login" className="text-sm text-blue-600 hover:text-blue-700 font-medium">
                                                Admin Login →
                                            </a>
                                            <a href="/technician/login" className="text-sm text-blue-600 hover:text-blue-700 font-medium">
                                                Technician Login →
                                            </a>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
                
                <PublicFooter />
            </div>
        </>
    );
}

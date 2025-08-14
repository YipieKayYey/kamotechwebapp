import { Head, useForm, Link } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { PublicNavigation } from '@/components/public-navigation';
import { PublicFooter } from '@/components/public-footer';

type RegisterForm = {
    name: string;
    email: string;
    phone: string;
    password: string;
    password_confirmation: string;
};

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm<Required<RegisterForm>>({
        name: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
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
                                    <div className="form-group">
                                        <label htmlFor="name" className="form-label">Full Name</label>
                                        <input
                                            id="name"
                                            type="text"
                                            required
                                            autoFocus
                                            tabIndex={1}
                                            autoComplete="name"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            disabled={processing}
                                            placeholder="Enter your full name"
                                            className={`form-input ${errors.name ? 'error' : ''}`}
                                        />
                                        {errors.name && <div className="input-error">{errors.name}</div>}
                                    </div>

                                    <div className="form-group">
                                        <label htmlFor="email" className="form-label">Email Address</label>
                                        <input
                                            id="email"
                                            type="email"
                                            required
                                            tabIndex={2}
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
                                        <label htmlFor="phone" className="form-label">Phone Number</label>
                                        <input
                                            id="phone"
                                            type="tel"
                                            required
                                            tabIndex={3}
                                            autoComplete="tel"
                                            value={data.phone}
                                            onChange={(e) => setData('phone', e.target.value)}
                                            disabled={processing}
                                            placeholder="Enter your phone number"
                                            className={`form-input ${errors.phone ? 'error' : ''}`}
                                        />
                                        {errors.phone && <div className="input-error">{errors.phone}</div>}
                                    </div>

                                    <div className="form-group">
                                        <label htmlFor="password" className="form-label">Password</label>
                                        <input
                                            id="password"
                                            type="password"
                                            required
                                            tabIndex={4}
                                            autoComplete="new-password"
                                            value={data.password}
                                            onChange={(e) => setData('password', e.target.value)}
                                            disabled={processing}
                                            placeholder="Create a password"
                                            className={`form-input ${errors.password ? 'error' : ''}`}
                                        />
                                        {errors.password && <div className="input-error">{errors.password}</div>}
                                    </div>

                                    <div className="form-group">
                                        <label htmlFor="password_confirmation" className="form-label">Confirm Password</label>
                                        <input
                                            id="password_confirmation"
                                            type="password"
                                            required
                                            tabIndex={5}
                                            autoComplete="new-password"
                                            value={data.password_confirmation}
                                            onChange={(e) => setData('password_confirmation', e.target.value)}
                                            disabled={processing}
                                            placeholder="Confirm your password"
                                            className={`form-input ${errors.password_confirmation ? 'error' : ''}`}
                                        />
                                        {errors.password_confirmation && <div className="input-error">{errors.password_confirmation}</div>}
                                    </div>

                                    <button type="submit" className="auth-button" tabIndex={6} disabled={processing}>
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
                                    Already have an account? <Link href={route('login')} tabIndex={7}>Sign in</Link>
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

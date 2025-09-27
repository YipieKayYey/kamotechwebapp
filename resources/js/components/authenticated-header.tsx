import { Link, usePage, router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { LogOut } from 'lucide-react';
import { type SharedData } from '@/types';

export function AuthenticatedHeader() {
    const { auth } = usePage<SharedData>().props;

    const handleSignOut = () => {
        router.post(route('logout'));
    };

    if (!auth.user) {
        // Redirect to login if not authenticated
        router.visit('/login');
        return null;
    }

    return (
        <header className="dashboard-header">
            <div className="header-container">
                <div className="header-logo">
                    <Link href="/">
                        <img src="/images/logo-main.png" alt="Kamotech Logo" className="logo-image" />
                    </Link>
                </div>
                <div className="header-user">
                    <span className="user-welcome">Welcome, {auth.user.name}</span>
                    <button 
                        className="header-sign-out-btn"
                        onClick={handleSignOut}
                        title="Sign Out"
                    >
                        <LogOut className="w-5 h-5" />
                        Sign Out
                    </button>
                </div>
            </div>
        </header>
    );
}

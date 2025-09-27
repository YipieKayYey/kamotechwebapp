import React, { useState, useRef, useEffect } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Mail, Shield, ArrowRight, RefreshCw } from 'lucide-react';

interface Props {
    email: string;
    status?: string;
}

export default function VerifyOTP({ email, status }: Props) {
    const [otp, setOtp] = useState(['', '', '', '', '', '']);
    const [isResending, setIsResending] = useState(false);
    const inputRefs = useRef<(HTMLInputElement | null)[]>([]);

    const { data, setData, post, processing, errors, reset } = useForm({
        otp: '',
    });

    const { post: resendPost, processing: resendProcessing } = useForm();

    // Handle OTP input
    const handleOtpChange = (index: number, value: string) => {
        if (!/^\d*$/.test(value)) return; // Only allow digits
        
        const newOtp = [...otp];
        newOtp[index] = value;
        setOtp(newOtp);

        // Auto-focus next input
        if (value && index < 5) {
            inputRefs.current[index + 1]?.focus();
        }

        // Update form data
        setData('otp', newOtp.join(''));
    };

    // Handle backspace
    const handleKeyDown = (index: number, e: React.KeyboardEvent) => {
        if (e.key === 'Backspace' && !otp[index] && index > 0) {
            inputRefs.current[index - 1]?.focus();
        }
    };

    // Handle paste
    const handlePaste = (e: React.ClipboardEvent) => {
        e.preventDefault();
        const pastedData = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
        const newOtp = [...otp];
        
        for (let i = 0; i < pastedData.length; i++) {
            newOtp[i] = pastedData[i];
        }
        
        setOtp(newOtp);
        setData('otp', newOtp.join(''));
        
        // Focus the next empty input or the last one
        const nextIndex = Math.min(pastedData.length, 5);
        inputRefs.current[nextIndex]?.focus();
    };

    // Submit form
    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/verify-otp');
    };

    // Resend OTP
    const resendOtp = () => {
        setIsResending(true);
        resendPost('/resend-otp', {
            onFinish: () => {
                setIsResending(false);
                setOtp(['', '', '', '', '', '']);
                setData('otp', '');
                reset();
                inputRefs.current[0]?.focus();
            }
        });
    };

    // Auto-focus first input on mount
    useEffect(() => {
        inputRefs.current[0]?.focus();
    }, []);

    const isComplete = otp.every(digit => digit !== '');

    return (
        <>
            <Head title="Verify Your Email - Kamotech" />
            
            <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
                <div className="max-w-md w-full">
                    {/* Logo */}
                    <div className="text-center mb-8">
                        <div className="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-full mb-4">
                            <Shield className="w-8 h-8 text-white" />
                        </div>
                        <h1 className="text-3xl font-bold text-gray-900">KAMOTECH</h1>
                        <p className="text-gray-600 mt-1">Air Conditioning Services</p>
                    </div>

                    {/* Main Card */}
                    <div className="bg-white rounded-2xl shadow-xl p-8">
                        <div className="text-center mb-6">
                            <div className="inline-flex items-center justify-center w-12 h-12 bg-blue-100 rounded-full mb-4">
                                <Mail className="w-6 h-6 text-blue-600" />
                            </div>
                            <h2 className="text-2xl font-bold text-gray-900 mb-2">Verify Your Email</h2>
                            <p className="text-gray-600">
                                We've sent a 6-digit verification code to
                            </p>
                            <p className="font-semibold text-blue-600 mt-1">{email}</p>
                        </div>

                        {/* Status Messages */}
                        {status && (
                            <div className="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                                <p className="text-sm text-green-700 text-center">{status}</p>
                            </div>
                        )}

                        {errors.otp && (
                            <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                                <p className="text-sm text-red-700 text-center">{errors.otp}</p>
                            </div>
                        )}

                        <form onSubmit={submit}>
                            {/* OTP Input */}
                            <div className="mb-6">
                                <label className="block text-sm font-medium text-gray-700 mb-3 text-center">
                                    Enter Verification Code
                                </label>
                                <div className="flex justify-center space-x-3">
                                    {otp.map((digit, index) => (
                                        <input
                                            key={index}
                                            ref={el => { inputRefs.current[index] = el; }}
                                            type="text"
                                            inputMode="numeric"
                                            maxLength={1}
                                            value={digit}
                                            onChange={e => handleOtpChange(index, e.target.value)}
                                            onKeyDown={e => handleKeyDown(index, e)}
                                            onPaste={index === 0 ? handlePaste : undefined}
                                            className={`w-12 h-12 text-center text-xl font-bold border-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors ${
                                                errors.otp 
                                                    ? 'border-red-300 bg-red-50' 
                                                    : digit 
                                                    ? 'border-blue-500 bg-blue-50' 
                                                    : 'border-gray-300 hover:border-blue-400'
                                            }`}
                                            disabled={processing}
                                        />
                                    ))}
                                </div>
                            </div>

                            {/* Submit Button */}
                            <button
                                type="submit"
                                disabled={!isComplete || processing}
                                className={`w-full flex items-center justify-center px-4 py-3 border border-transparent rounded-lg text-sm font-medium transition-all duration-200 ${
                                    isComplete && !processing
                                        ? 'text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-500/20'
                                        : 'text-gray-400 bg-gray-100 cursor-not-allowed'
                                }`}
                            >
                                {processing ? (
                                    <>
                                        <RefreshCw className="animate-spin -ml-1 mr-2 h-4 w-4" />
                                        Verifying...
                                    </>
                                ) : (
                                    <>
                                        Verify Email
                                        <ArrowRight className="ml-2 h-4 w-4" />
                                    </>
                                )}
                            </button>
                        </form>

                        {/* Resend Section */}
                        <div className="mt-6 text-center">
                            <p className="text-sm text-gray-600 mb-3">
                                Didn't receive the code?
                            </p>
                            <button
                                type="button"
                                onClick={resendOtp}
                                disabled={resendProcessing || isResending}
                                className="inline-flex items-center px-4 py-2 text-sm font-medium text-blue-600 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {resendProcessing || isResending ? (
                                    <>
                                        <RefreshCw className="animate-spin -ml-1 mr-1 h-4 w-4" />
                                        Sending...
                                    </>
                                ) : (
                                    <>
                                        <RefreshCw className="-ml-1 mr-1 h-4 w-4" />
                                        Resend Code
                                    </>
                                )}
                            </button>
                        </div>

                        {/* Help Text */}
                        <div className="mt-6 p-4 bg-gray-50 rounded-lg">
                            <p className="text-xs text-gray-600 text-center">
                                The verification code expires in 10 minutes. Check your spam folder if you don't see the email.
                            </p>
                        </div>
                    </div>

                    {/* Footer */}
                    <div className="text-center mt-6">
                        <p className="text-xs text-gray-500">
                            © {new Date().getFullYear()} Kamotech. All rights reserved.
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}

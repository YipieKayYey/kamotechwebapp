<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use ReCaptcha\ReCaptcha;

class RecaptchaService
{
    protected $recaptcha;

    protected $skipEnvironments;

    public function __construct()
    {
        $this->recaptcha = new ReCaptcha(config('recaptcha.secret_key'));
        $this->skipEnvironments = config('recaptcha.skip_environments', []);
    }

    /**
     * Verify reCAPTCHA response
     *
     * @param  string|null  $response
     * @param  string|null  $ip
     * @return bool
     */
    public function verify($response, $ip = null)
    {
        // Skip verification in certain environments
        if (in_array(app()->environment(), $this->skipEnvironments)) {
            return true;
        }

        // No response provided
        if (empty($response)) {
            return false;
        }

        try {
            $resp = $this->recaptcha->verify($response, $ip);

            if (! $resp->isSuccess()) {
                Log::warning('reCAPTCHA verification failed', [
                    'errors' => $resp->getErrorCodes(),
                    'ip' => $ip,
                ]);
            }

            return $resp->isSuccess();
        } catch (\Exception $e) {
            Log::error('reCAPTCHA verification error', [
                'message' => $e->getMessage(),
                'ip' => $ip,
            ]);

            return false;
        }
    }
}

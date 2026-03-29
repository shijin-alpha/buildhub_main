<?php
/**
 * International Payment Configuration
 * 
 * Configuration for handling international payments and card restrictions
 */

// International payment settings
define('ENABLE_INTERNATIONAL_PAYMENTS', true);
define('SUPPORTED_CURRENCIES', ['INR', 'USD', 'EUR', 'GBP', 'AUD', 'CAD']);
define('DEFAULT_CURRENCY', 'INR');

// Supported countries for international payments
define('SUPPORTED_COUNTRIES', [
    'IN' => 'India',
    'US' => 'United States',
    'GB' => 'United Kingdom',
    'CA' => 'Canada',
    'AU' => 'Australia',
    'SG' => 'Singapore',
    'AE' => 'United Arab Emirates',
    'MY' => 'Malaysia'
]);

// Payment method preferences by country
define('PAYMENT_METHODS_BY_COUNTRY', [
    'IN' => ['card', 'netbanking', 'wallet', 'upi'],
    'US' => ['card'],
    'GB' => ['card'],
    'CA' => ['card'],
    'AU' => ['card'],
    'SG' => ['card'],
    'AE' => ['card'],
    'MY' => ['card']
]);

/**
 * Check if international payments are enabled
 */
function isInternationalPaymentEnabled() {
    return ENABLE_INTERNATIONAL_PAYMENTS;
}

/**
 * Get supported currencies
 */
function getSupportedCurrencies() {
    return SUPPORTED_CURRENCIES;
}

/**
 * Check if currency is supported
 */
function isCurrencySupported($currency) {
    return in_array(strtoupper($currency), SUPPORTED_CURRENCIES);
}

/**
 * Get supported countries
 */
function getSupportedCountries() {
    return SUPPORTED_COUNTRIES;
}

/**
 * Check if country is supported for payments
 */
function isCountrySupported($countryCode) {
    return array_key_exists(strtoupper($countryCode), SUPPORTED_COUNTRIES);
}

/**
 * Get payment methods for a specific country
 */
function getPaymentMethodsForCountry($countryCode) {
    $countryCode = strtoupper($countryCode);
    return PAYMENT_METHODS_BY_COUNTRY[$countryCode] ?? ['card'];
}

/**
 * Validate international payment request
 */
function validateInternationalPayment($amount, $currency, $countryCode = null) {
    $errors = [];
    
    // Check if international payments are enabled
    if (!isInternationalPaymentEnabled()) {
        $errors[] = 'International payments are currently disabled';
    }
    
    // Validate currency
    if (!isCurrencySupported($currency)) {
        $errors[] = "Currency {$currency} is not supported. Supported currencies: " . implode(', ', SUPPORTED_CURRENCIES);
    }
    
    // Validate country if provided
    if ($countryCode && !isCountrySupported($countryCode)) {
        $errors[] = "Payments from {$countryCode} are not currently supported";
    }
    
    // Validate amount
    if ($amount <= 0) {
        $errors[] = 'Payment amount must be greater than zero';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'supported_methods' => $countryCode ? getPaymentMethodsForCountry($countryCode) : ['card']
    ];
}

/**
 * Convert amount between currencies (simplified - in production use real exchange rates)
 */
function convertCurrency($amount, $fromCurrency, $toCurrency) {
    // Simplified conversion rates (use real API in production)
    $rates = [
        'INR' => 1.0,
        'USD' => 0.012,
        'EUR' => 0.011,
        'GBP' => 0.0095,
        'AUD' => 0.018,
        'CAD' => 0.016
    ];
    
    if (!isset($rates[$fromCurrency]) || !isset($rates[$toCurrency])) {
        throw new Exception("Unsupported currency conversion: {$fromCurrency} to {$toCurrency}");
    }
    
    // Convert to INR first, then to target currency
    $inrAmount = $amount / $rates[$fromCurrency];
    return $inrAmount * $rates[$toCurrency];
}
?>
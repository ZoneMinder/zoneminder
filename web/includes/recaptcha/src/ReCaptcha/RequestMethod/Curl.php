<?php

namespace ReCaptcha\RequestMethod;

use ReCaptcha\RequestMethod;
use ReCaptcha\RequestParameters;

/**
 * Sends cURL request to the reCAPTCHA service.
 */
class Curl implements RequestMethod
{
    /**
     * URL to which requests are sent via cURL.
     * @const string
     */
    const SITE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * Submit the cURL request with the specified parameters.
     *
     * @param RequestParameters $params Request parameters
     * @return string Body of the reCAPTCHA response
     */
    public function submit(RequestParameters $params)
    {
        $handle = curl_init(self::SITE_VERIFY_URL);

        $options = array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $params->toQueryString(),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded'
            ),
            CURLINFO_HEADER_OUT => false,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true
        );
        curl_setopt_array($handle, $options);

        $response = curl_exec($handle);
        curl_close($handle);

        return $response;
    }
}

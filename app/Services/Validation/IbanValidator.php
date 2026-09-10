<?php

namespace App\Services\Validation;

class IbanValidator
{
    /**
     * Standard IBAN lengths per country code (ISO 13616).
     *
     * @var array<string, int>
     */
    protected const COUNTRY_LENGTHS = [
        'AL' => 28, 'AD' => 24, 'AT' => 20, 'AZ' => 28, 'BH' => 22, 'BY' => 28,
        'BE' => 16, 'BA' => 20, 'BR' => 29, 'BG' => 22, 'CR' => 22, 'HR' => 21,
        'CY' => 28, 'CZ' => 24, 'DK' => 18, 'DO' => 28, 'EE' => 20, 'FO' => 18,
        'FI' => 18, 'FR' => 27, 'GE' => 22, 'DE' => 22, 'GI' => 23, 'GR' => 27,
        'GL' => 18, 'GT' => 28, 'HU' => 28, 'IS' => 26, 'IE' => 22, 'IL' => 23,
        'IT' => 27, 'JO' => 30, 'KZ' => 20, 'XK' => 20, 'KW' => 30, 'LV' => 21,
        'LB' => 28, 'LI' => 21, 'LT' => 20, 'LU' => 20, 'MK' => 19, 'MT' => 31,
        'MR' => 27, 'MU' => 30, 'MD' => 24, 'MC' => 27, 'ME' => 22, 'NL' => 18,
        'NO' => 15, 'PK' => 24, 'PS' => 29, 'PL' => 28, 'PT' => 25, 'QA' => 29,
        'RO' => 24, 'LC' => 32, 'SM' => 27, 'ST' => 25, 'SA' => 24, 'RS' => 22,
        'SC' => 31, 'SK' => 24, 'SI' => 19, 'ES' => 24, 'SE' => 24, 'CH' => 21,
        'TN' => 24, 'TR' => 26, 'UA' => 29, 'AE' => 23, 'GB' => 22, 'VA' => 22,
        'VG' => 24,
    ];

    public function validate(?string $iban): bool
    {
        return $iban !== null && $this->validateChecksum($iban);
    }


    /** 
     * Normalize IBAN: strip all whitespace and non-alphanumeric characters, convert to uppercase 
     */
    public function normalize(string $iban): string
    {
        return strtoupper((string)preg_replace('/[^A-Za-z0-9]/', '', $iban));
    }

    /**
     * Validate basic structure: country code + length  
     */
    public function validateStructure(string $iban): bool
    {
        if(strlen($iban) < 15 || strlen($iban) > 34) {
            return false;
        }

        //Must start with 2 uppecase letters
        if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/', $iban)) {
            return false;
        }

        $country = substr($iban, 0, 2);

        if(isset(self::COUNTRY_LENGTHS[$country]) && strlen($iban) !== self::COUNTRY_LENGTHS[$country]) {
            return false;
        }

        return true;
    }

    /**
     * Validate IBAN checksum using ISO 7064 MOD 97-10 algorithm
     */
    public function validateChecksum(string $iban): bool
    {

        $cleanedIban = $this->normalize($iban);

        if(!$this->validateStructure($cleanedIban)) {
            return false;
        }

        //Get first 4 chars for replacing letters and moving on end
        $rearrangedIban = substr($cleanedIban, 4) . substr($cleanedIban, 0, 4);
        
        $numericIban = '';
        foreach(str_split($rearrangedIban) as $char) {
            if(ctype_alpha($char)) {
                $numericIban .= (string) (ord($char) - 55);
            } else {
                $numericIban .= $char;
            }
        }
        
        //Compute modulo 97
        return $this->calculateMod97($numericIban) === 1;


    }

    /**
     * Safe chunking algorithm for mod 97 calculation.
     */
    public function calculateMod97(string $numericIban): int
    {
        if(function_exists('bcmod')) {
            return (int) bcmod($numericIban, '97');
        }

        $remainder = 0;
        $totalLength = strlen($numericIban);

        // The previous remainder (0–96) plus 7 digits always fits in an integer.
        for ($i = 0; $i < $totalLength; $i += 7) {
            $chunk = (string) $remainder . substr($numericIban, $i, 7);
            $remainder = ((int) $chunk) % 97;
        }

        return $remainder;
    }
}

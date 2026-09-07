<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class BannerImageSize implements Rule
{
    protected $bannerType;

    protected $dimensions = [
        'home_banner' => ['width' => 1440, 'height' => 550, 'min_size_mb' => 0,'max_size_mb' => 5],
        'about_banner' => ['width' => 585, 'height' => 605,'min_size_mb' => 1, 'max_size_mb' => 2],
        'purchase_banner' => ['width' => 1440, 'height' => 197, 'min_size_mb' => 0,'max_size_mb' => 3],
        'testimonial_banner' => ['width' => 498, 'height' => 472, 'min_size_mb' => 0,'max_size_mb' => 1],
    ];

    public function __construct($bannerType)
    {
        $this->bannerType = $bannerType;
    }

    public function passes($attribute, $value)
    
    {
        if (!isset($this->dimensions[$this->bannerType])) {
            return true;
        }

        $requiredWidth = $this->dimensions[$this->bannerType]['width'];
        $requiredHeight = $this->dimensions[$this->bannerType]['height'];
        $maxSizeMB = $this->dimensions[$this->bannerType]['max_size_mb'];
        $minSizeMB = $this->dimensions[$this->bannerType]['min_size_mb'];

        // dd($value);
        if (!is_file($value) || !getimagesize($value)) {
            return false;
        }

        list($width, $height) = getimagesize($value);
        $actualSizeMB = filesize($value) / (1024 * 1024); // Size in MB
        $roundedSizeMB = $actualSizeMB > 0.5 ? ceil($actualSizeMB) : $actualSizeMB;
        // dd($actualSizeMB,$roundedSizeMB);
        // Check dimensions
        // if ($width < $requiredWidth || $height < $requiredHeight) {
        //     return false;
        // }
         // Check file size
         if ($roundedSizeMB > $maxSizeMB || $roundedSizeMB < $minSizeMB) {
            return false;
        }

        // Optionally, you could also validate the file size if you have a limit
        // e.g., $maxFileSizeMB = 2; // 2 MB limit
        // if ($actualSizeMB > $maxFileSizeMB) {
        //     return false;
        // }

        return true;
    }

    public function message()
    {
        $dimensions = $this->dimensions[$this->bannerType] ?? ['width' => 0, 'height' => 0];
        return "The image size must be not less then {$dimensions['min_size_mb']} and not exceed to {$dimensions['max_size_mb']} MB .";
    }
}
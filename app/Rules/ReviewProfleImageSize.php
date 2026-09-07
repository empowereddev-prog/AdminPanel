<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\Rule;

class ReviewProfleImageSize implements Rule
{
    protected $dimensions = [
        'image' => ['width' => 82, 'height' => 82, 'min_size_mb' => 0,'max_size_mb' => 1],
      
    ];
    
    public function passes($attribute, $value)
    {
        

        // Check if file is an image and get its dimensions
        if (is_null($value) || !file_exists($value)) {
            return false;
        }
        $requiredWidth = $this->dimensions['image']['width'];
        $requiredHeight = $this->dimensions['image']['height'];
        $maxSizeMB = $this->dimensions['image']['max_size_mb'];
        $minSizeMB = $this->dimensions['image']['min_size_mb'];

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

        return true;
    }

    public function message()
    {
        return 'The image dimensions must be at least 1 MB for profile image.';
    }
}

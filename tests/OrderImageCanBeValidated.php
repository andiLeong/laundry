<?php

namespace Tests;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

trait OrderImageCanBeValidated
{
    protected $imageName = 'image';
    protected $imageSize = 2048;
    protected $imageArraySize = 5;
    protected $imageCreation = 'createOrder';

    /** @test */
    public function image_type_validation()
    {
        $response = $this->{$this->imageCreation}([$this->imageName => ['string'],]);
        $this->assertValidateMessage("The {$this->imageName}.0 field must be an image.", $response,
            "{$this->imageName}.0");
    }

    /** @test */
    public function image_array_size_validation()
    {
        $size = $this->imageArraySize + 1;
        $response = $this->{$this->imageCreation}(['image' => range(1, $size),]);
        $response2 = $this->{$this->imageCreation}(['image' => $this->generateImageFiles($size),]);
        $this->assertValidateMessage("The image field must not have more than {$this->imageArraySize} items.", $response, $this->imageName);
        $this->assertValidateMessage("The image field must not have more than {$this->imageArraySize} items.", $response2, $this->imageName);
    }

    /** @test */
    public function image_size_validation()
    {
        $size = $this->imageSize + 2000;
        $response = $this->{$this->imageCreation}(['image' => [$this->generateImageFile($size)],]);
        $this->assertValidateMessage("The {$this->imageName}.0 field must not be greater than {$this->imageSize} kilobytes.",
            $response,
            "{$this->imageName}.0");
    }

    protected function generateImageFile($size = null)
    {
        $size ??= $this->imageSize;
        $name = Str::random() . '.jpg';
        return UploadedFile::fake()->create($name, $size);
//        return UploadedFile::fake()->image(Str::random() . '.jpg', $size);
    }

    protected function generateImageFiles($size = null)
    {
        $size ??= $this->imageArraySize;
        return array_map(function(){
            $this->generateImageFile();
        },range(1,$size));
    }
}

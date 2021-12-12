<?php

namespace App\Api;

use Symfony\Component\Validator\Constraints as Assert;

class ImageUploadModel
{

    /**
     * @Assert\NotBlank()
     */
    private $data;

    private $decodedData;

    public function setData(?string $data)
    {
        $this->data = $data;
        $this->decodedData = base64_decode($data);
    }

    public function getDecodedData(): ?string
    {
        return $this->decodedData;
    }
}
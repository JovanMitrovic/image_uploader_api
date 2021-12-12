<?php

namespace App\Controller;

use App\Api\ImageUploadModel;
use App\Entity\Image;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File as FileObject;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mime\MimeTypes;

class ImageUploaderController extends AbstractController
{
    /**
     * @Route("/image-upload", name="image_upload", methods={"POST"})
     */
    public function uploadImage(Request $request,ValidatorInterface $validator,EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        if ($request->headers->get('Content-Type') === 'application/json')
        {
            /** @var  ImageUploadModel $uploadApiModel */
            $uploadApiModel = $serializer->deserialize(
                $request->getContent(),
                ImageUploadModel::class,
                'json'
            );

            $violations = $validator->validate($uploadApiModel);
            if ($violations->count() > 0)
            {
                return $this->json($violations, 400);
            }

            $tmpPath = sys_get_temp_dir() . '/upload-' . time();
            file_put_contents($tmpPath, $uploadApiModel->getDecodedData());
            $uploadedFile = new FileObject($tmpPath);
        }


        $mimeTypes = new MimeTypes();

        if ( $uploadedFile->getSize() < 5)
        {
            return 'image is too small';
        }
        elseif ($mimeTypes->guessMimeType($tmpPath) != 'image/jpeg')
        {
            return 'Please send jpg format';
        }

        $violations = $validator->validate(
            $uploadedFile,
            [
                new NotBlank([
                    'message' => 'Empty file!'
                ])
            ]
        );

        if ($violations->count() > 0)
        {
            return new JsonResponse($violations, 400);
        }

        $image = new Image();
        $image->setData($uploadedFile->getPathname());
        $entityManager->persist($image);
        $entityManager->flush();

        return new JsonResponse(
            'Image saved! ID for getting your image: ' . $image->getId(),
            200
        );
    }

    /**
     * @Route("/image/{id}", methods="GET", name="get_image")
     */
    public function getImage(int $id)
    {
        $image = $this->getDoctrine()->getRepository(Image::class)->find($id);
        return new JsonResponse(
            $image->getData(),
            200
        );
    }

}

<?php

namespace App\Service\Upload;

use App\Service\Upload\FileUploadException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
    private $targetDirectory;
    private $slugger;

    public function __construct($targetDirectory, SluggerInterface $slugger)
    {
        $this->targetDirectory = $targetDirectory;
        $this->slugger = $slugger;
        if (!file_exists($this->getTargetDirectory()))
            mkdir($this->getTargetDirectory(), 0777, true);
    }

    public function upload(UploadedFile $file)
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
        try {
            $file->move($this->getTargetDirectory(), $fileName);
        } catch (FileException $e) {
            throw new FileUploadException(FileUploadException::SAVE_FAILED);
        }
        return $fileName;
    }

    /**
     * Supprime le fichier du répertoire
     *
     * @param string $fileName Nom du fichier
     * @return void
     */
    public function delete(string $fileName)
    {
        // On supprime le fichier du répertoire
        if (file_exists($this->getTargetDirectory() . '/' . $fileName))
            unlink($this->getTargetDirectory() . '/' . $fileName);
    }

    public function getTargetDirectory()
    {
        return $this->targetDirectory;
    }
}

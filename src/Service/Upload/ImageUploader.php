<?php

declare(strict_types=1);

namespace App\Service\Upload;


use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Stock dans un répertoire donné les images. (configuration dans services.yaml)
 * Doit être persisté en BDD en lien avec l'entité sinon utiliser setImgName() pour lui donner un nom.
 * Représente un fichier image indépendamment de son type et permet de le redimensionner en mode "contain" ou "cover".
 * Extension PHP 'gd2' requise.
 * 
 */
class ImageUploader extends FileUploader
{
	/**
	 * Constante du mode "contain".
	 */
	public const CONTAIN = 'CONTAIN';

	/**
	 * Constante du mode "cover". 
	 */
	public const COVER = 'COVER';

	/**
	 * Chemin absolu du fichier image.
	 */
	protected ?string $path = null;

	/**
	 * Largeur de l'image en pixels.
	 */
	protected ?int $width = null;

	/**
	 * Hauteur de l'image en pixels.
	 */
	protected ?int $height = null;

	/**
	 * Chemin du répertoire où sont stockées les images (voir services.yaml)
	 */
	private string $targetDirectory;
	private array $default;
	private ?array $small;
	private ?array $big;
	private array $allowed_mimeType;
	private int $JPEGQuality;
	private int $PNGQuality;
	private ?string $imgName = null;
	private string $mimeType;

	/**
	 * constructeur public
	 */
	public function __construct(string $targetDirectory, array $allowed_mimeType = ["image/jpeg"], array $default = [], ?array $small = [], ?array $big = [], int $JPEGQuality = 60, int $PNGQuality = -1)
	{
		$this->targetDirectory = $targetDirectory;
		$this->small = $small;
		$this->big = $big;
		$this->default = $default;
		$this->allowed_mimeType = $allowed_mimeType;
		$this->JPEGQuality = $JPEGQuality;
		$this->PNGQuality = $PNGQuality;
		if (!file_exists($this->getTargetDirectory()))
			mkdir($this->getTargetDirectory(), 0777, true);
	}

	/**
	 * Retourne le type MIME dédié.
	 * 
	 * @return array Type MIME.
	 */
	public function getAllowed_mimeType(): array
	{
		return $this->allowed_mimeType;
	}



	/**
	 * Accès public en lecture seule à la largeur et la hauteur.
	 *
	 * @param string $propertyName Nom de la propriété ("width" ou "height").
	 * @return integer|null Largeur ou hauteur en pixels. Null si autre.
	 */
	public function __get(string $propertyName): ?int
	{
		return $propertyName === 'width' || $propertyName === 'height' ? $this->$propertyName : null;
	}

	/**
	 * Crée un fichier correspondant au redimensionnement de l'image selon un mode donné pour l'inscrire dans un cadre donné. 
	 * Copie simplement le fichier source si le cadre cible est plus grand dans ses deux dimensions.
	 *
	 * @param int $frameWidth Largeur du cadre cible.
	 * @param int $frameHeight Hauteur du cadre cible.
	 * @param string $targetPath Chemin complet du fichier à créer.
	 * @param string $mode Mode de redimensionnement.
	 * @return void
	 * @throws ImageException Si la création de la ressource PHP cible ou le redimensionnement échouent.
	 */
	private function copyResize(int $frameWidth, int $frameHeight, string $targetPath, string $mode = self::CONTAIN): void
	{

		$array = explode('.', $targetPath);
		$type = array_pop($array);
		// Calculer les ratios largeur/hauteur de l'image source et du cadre cible.
		$sourceRatio = $this->width / $this->height;
		$frameRatio = $frameWidth / $frameHeight;
		// Créer un booléen pour déterminer si redimensionnement nécessaire.
		$resize = true;
		// Calculer selon ratio et mode de redimensionnement.
		if (($mode === self::CONTAIN && $sourceRatio > $frameRatio) || ($mode === self::COVER && $sourceRatio < $frameRatio)) {
			// Largeur prioritaire.
			$targetWidth = $frameWidth;
			// Déterminer la hauteur en conservant les proportions.
			$targetHeight = (int) ($targetWidth / $sourceRatio);
			// Si largeur source inférieure ou égale à largeur cible, pas de redimensionnement.
			if ($this->width <= $targetWidth)
				$resize = false;
		} else {
			// Hauteur prioritaire.
			$targetHeight = $frameHeight;
			// Déterminer la largeur en conservant les proportions.
			$targetWidth = (int) ($targetHeight * $sourceRatio);
			// Si hauteur source inférieure ou égale à hauteur cible, pas de redimensionnement.
			if ($this->height <= $targetHeight)
				$resize = false;
		}
		// Si pas de redimensionnement, faire une simple copie et arrêter.
		if (!$resize) {
			// Si la copie échoue, déclencher une exception.
			if (!copy($this->path, $targetPath))
				throw new ImageUploadException(ImageUploadException::IMAGE_COPY_FAILED);
			return;
		}
		// Créer la ressource PHP source à partir du fichier source.
		$source = $this->from($type);
		// Créer la ressource PHP cible.
		if (!($target = imagecreatetruecolor($targetWidth, $targetHeight)))
			throw new ImageUploadException(ImageUploadException::TARGET_IMAGE_CREATION_FAILED);
		if ($this->getMimeType() === "image/png") {
			imagealphablending($target, false);
			imagesavealpha($target, true);
			$transparent = imagecolorallocatealpha($target, 255, 255, 255, 127);
			imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);
		}
		// Redimensionner la ressource PHP source vers la ressource PHP cible.
		if (!imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $this->width, $this->height))
			throw new  ImageUploadException(ImageUploadException::IMAGE_RESIZING_FAILED);
		// Libérer la ressource PHP source.
		imagedestroy($source);
		// Créer le fichier cible.
		$this->to($target, $targetPath, $type);
		// Libérer la ressource PHP cible.
		imagedestroy($target);
	}

	/**
	 * Crée une image format small et une image format big
	 *
	 * @param UploadedFile $file
	 * @return array Tableau de noms d'image
	 */
	public function createAll(UploadedFile $file): array
	{
		try {
			$fileNames = [$this->create($file), $this->createSmall($file), $this->createBig($file)];
		} catch (ImageUploadException $e) {
			throw new ImageUploadException();
		}

		return $fileNames;
	}

	/**
	 * Crée une image au format par défaut
	 *
	 * @param UploadedFile $file
	 * @param string $mode 2 Modes de couverture possible.
	 * @return string Nom de l'image.
	 */
	public function create(UploadedFile $file, string $mode = self::CONTAIN): string
	{
		if (!$this->default)
			throw new ImageUploadException(ImageUploadException::IMAGE_SIZE_NOT_EXIST);
		$this->defineImage($file);
		$fileName = 'image_' . ($this->getImgName() ?? uniqid()) . '.' . $file->guessExtension();
		$this->copyResize($this->default[0], $this->default[1], $this->getTargetDirectory() . '/' . $fileName, $mode);
		return $fileName;
	}

	/**
	 * Crée une image au format Small
	 *
	 * @param UploadedFile $file
	 * @param string $mode 2 Modes de couverture possible.
	 * @return string Nom de l'image.
	 */
	public function createSmall(UploadedFile $file, string $mode = self::CONTAIN): string
	{
		if (!$this->small)
			throw new ImageUploadException(ImageUploadException::IMAGE_SIZE_NOT_EXIST);
		$this->defineImage($file);
		$fileName = 'small_' . ($this->getImgName() ?? uniqid()) . '.' . $file->guessExtension();
		$this->copyResize($this->small[0], $this->small[1], $this->getTargetDirectory() . '/' . $fileName, $mode);
		return $fileName;
	}

	/**
	 * Crée une image au format Big
	 *
	 * @param UploadedFile $file
	 * @param string $mode 2 Mode de couverture possible.
	 * @return string Nom de l'image.
	 */
	public function createBig(UploadedFile $file, string $mode = self::CONTAIN): string
	{
		if (!$this->big)
			throw new ImageUploadException(ImageUploadException::IMAGE_SIZE_NOT_EXIST);
		$this->defineImage($file);
		$fileName = 'big_' . ($this->getImgName() ?? uniqid()) . '.' . $file->guessExtension();
		$this->copyResize($this->big[0], $this->big[1], $this->getTargetDirectory() . '/' . $fileName, $mode);
		return $fileName;
	}

	/**
	 * Défini le typeMime, le path et les propriétés de l'image.
	 *
	 * @param UploadedFile $file
	 * @return void
	 */
	private function defineImage(UploadedFile $file): void
	{
		// Récupérer le type MIME du fichier.
		@$this->setMimeType($file->getMimeType());
		// Affecter le chemin.
		$this->path = $file->getPathName();
		// Si chemin ou type MIME invalides, déclencher une exception.
		if (!$this->getMimeType() || array_key_exists($this->getMimeType(), $this->getAllowed_mimeType()))
			throw new ImageUploadException(ImageUploadException::UNREADABLE_IMAGE);
		// Récupérer les dimensions de l'image.
		@[$this->width, $this->height] = getimagesize($this->path);
	}

	/**
	 * Crée la ressource PHP source à partir du fichier source.
	 *
	 * @return resource Ressource PHP créée.
	 */
	protected function from($type)
	{
		$source = "";
		switch (strtolower($type)) {
			case 'jpeg':
				if (@!($source = imagecreatefromjpeg($this->path)))
					throw new ImageJpegException(ImageJpegException::RESOURCE_FROM_JPEG_CREATION_FAILED);
				break;
			case 'png':
				if (@!($source = imagecreatefrompng($this->path)))
					throw new ImagePngException(ImagePngException::RESOURCE_FROM_PNG_CREATION_FAILED);
				break;
			default:
				break;
		}
		// Retourner la ressource.
		return $source;
	}

	/**
	 * Crée le fichier cible à partir de la ressource PHP source.
	 *
	 * @param resource $target Ressource PHP cible.
	 * @param string $targetPath Chemin complet du fichier cible à créer.
	 * @return void
	 */
	protected function to($target, string $targetPath, $type): void
	{
		switch (strtolower($type)) {
			case 'jpeg':
				if (@!imagejpeg($target, $targetPath, $this->JPEGQuality))
					throw new ImageJpegException(ImageJpegException::JPEG_FROM_RESOURCE_CREATION_FAILED);
				break;
			case 'png':
				if (@!imagepng($target, $targetPath, $this->PNGQuality))
					throw new ImagePngException(ImagePngException::PNG_FROM_RESOURCE_CREATION_FAILED);
				break;
			default:
				break;
		}
	}

	public function getTargetDirectory()
	{
		return $this->targetDirectory;
	}

	/**
	 * Get the value of imgName
	 */
	public function getImgName()
	{
		return $this->imgName;
	}

	/**
	 * Défini un nom à l'image sous la forme "image_imgName" ou/et "small_imgName" ou/et "big_imgName".
	 *
	 * @return  self
	 */
	public function setImgName($imgName)
	{
		$this->imgName = (string) $imgName;

		return $this;
	}

	/**
	 * Get the value of mimeType
	 */
	public function getMimeType()
	{
		return $this->mimeType;
	}

	/**
	 * Set the value of mimeType
	 *
	 * @return  self
	 */
	public function setMimeType($mimeType)
	{
		$this->mimeType = $mimeType;

		return $this;
	}
}

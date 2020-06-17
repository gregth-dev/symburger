<?php

declare(strict_types=1);

namespace App\Service\Upload;

use Exception;

/**
 * Exceptions en lien avec Image. Classe 100% statique.
 * 
 * @author Gilles Vanderstraeten <contact@gillesvds.com>
 * @copyright 2020 Gilles Vanderstraeten
 */
class ImageUploadException extends Exception
{
	/**
	 * La lecture du fichier image a échoué (utilisé par les enfants).
	 */
	public const UNREADABLE_IMAGE = "La lecture du fichier image a échoué.";

	/**
	 * La copie de l'image a échoué.
	 */
	public const IMAGE_COPY_FAILED = "La copie de l'image a échoué.";

	/**
	 * Le redimensionnement de l'image a échoué.
	 */
	public const IMAGE_RESIZING_FAILED = "Le redimensionnement de l'image a échoué.";

	/**
	 * La création de la ressource PHP cible a échoué.
	 */
	public const TARGET_IMAGE_CREATION_FAILED = "La création de la ressource PHP cible a échoué.";

	/**
	 * La taille de l'image n'est pas défini.
	 */
	public const IMAGE_SIZE_NOT_EXIST = "La taille de l'image n'est pas définie.";
}

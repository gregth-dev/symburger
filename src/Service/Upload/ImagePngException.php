<?php

declare(strict_types=1);

namespace App\Service\Upload;


/**
 * Exceptions en lien avec ImagePng. Classe 100% statique.
 * 
 * @author Gilles Vanderstraeten <contact@gillesvds.com>
 * @copyright 2020 Gilles Vanderstraeten
 */
final class ImagePngException extends ImageUploadException
{
	/**
	 * Le type de l'image n'est pas PNG.
	 */
	public const IMAGE_NOT_PNG = "Le type de l'image n'est pas PNG.";

	/**
	 * La création de la ressource PHP à partir de l'image PNG a échoué.
	 */
	public const RESOURCE_FROM_PNG_CREATION_FAILED = "La création de la ressource PHP à partir de l'image PNG a échoué.";

	/**
	 * La création de l'image PNG à partir de la ressource PHP a échoué.
	 */
	public const PNG_FROM_RESOURCE_CREATION_FAILED = "La création de l'image PNG à partir de la ressource PHP a échoué.";
}

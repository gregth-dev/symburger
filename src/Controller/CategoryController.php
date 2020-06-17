<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CategoryController extends AbstractController
{
    /**
     * @Route("/categories", name="category_index")
     */
    public function index(CategoryRepository $categoryRepository)
    {
        return $this->render('category/index.html.twig', [
            'categories' => $categoryRepository->findAllByOrder(),
        ]);
    }

    /**
     * Affiche les produits d'une catégorie
     * @Route("/category/{id}/show",name="category_show")
     * @param Category $category
     * @return Response
     */
    public function show(Category $category, CategoryRepository $categoryRepository)
    {
        return $this->render('category/show.html.twig', [
            'category' => $category,
            'categories' => $categoryRepository->findAllByOrder()
        ]);
    }
}

<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Service\Upload\ImageUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminCategoryController extends AbstractController
{
    /**
     * Affiche les catégories
     * @Route("/admin/categories", name="admin_category_index")
     * @return Response
     */
    public function index(CategoryRepository $categoryRepository)
    {
        return $this->render('admin/category/index.html.twig', [
            'categories' => $categoryRepository->findAllByOrder()
        ]);
    }

    /**
     * Enregistre une catégorie
     * @Route("/admin/categorie/new",name="admin_category_new")
     * @param EntityManagerInterface $manager
     * @param Request $request
     * @return Response
     */
    public function new(EntityManagerInterface $manager, Request $request, ImageUploader $imageUploader, CategoryRepository $categoryRepository)
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($image = $form->get('image')->getData()) {
                $imageName = $imageUploader->create($image) ?? "bientotdispo.jpg";
                $category->setImage($imageName);
            }
            $manager->persist($category);
            $manager->flush();
            $this->addFlash("success", "La catégorie a été ajoutée");
            return $this->redirectToRoute('admin_category_new');
        }
        return $this->render('admin/category/new.html.twig', [
            'form' => $form->createView(),
            'categories' => $categoryRepository->findAllByOrder()
        ]);
    }


    /**
     * Modifie une catégorie
     * @Route("/admin/categorie/{id}/edit",name="admin_category_edit", methods={"GET","POST"})
     * @param Category $category
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */
    public function edit(Category $category, Request $request, EntityManagerInterface $manager, ImageUploader $imageUploader, CategoryRepository $categoryRepository)
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($image = $form->get('image')->getData()) {
                $imageUploader->delete($category->getImage());
                $imageName = $imageUploader->create($image) ?? "bientotdispo.jpg";
                $category->setImage($imageName);
            }
            $manager->persist($category);
            $manager->flush();
            $this->addFlash("success", "La catégorie a été modifiée");
            return $this->redirectToRoute('admin_category_new');
        }
        return $this->render('admin/category/edit.html.twig', [
            'form' => $form->createView(),
            'categories' => $categoryRepository->findAllByOrder()
        ]);
    }

    /**
     * Affiche les produits d'une categorie
     * @Route("/admin/categorie/{id}/show",name="admin_category_show")
     * @param CategoryRepository $categoryRepository
     * @return Response
     */
    public function show(CategoryRepository $categoryRepository, Category $category)
    {
        return $this->render('admin/category/show.html.twig', [
            'categories' => $categoryRepository->findAllByOrder(),
            'category' => $category
        ]);
    }

    /**
     * Supprime une catégorie
     * @Route("/admin/categorie/{id}/delete",name="admin_category_delete", methods={"DELETE"})
     * @param Category $category
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */
    public function delete(Category $category, Request $request, EntityManagerInterface $manager, ImageUploader $imageUploader)
    {
        if ($this->isCsrfTokenValid('delete' . $category->getId(), $request->request->get('_token'))) {
            $imageUploader->delete($category->getImage());
            $manager->remove($category);
            $manager->flush();
            $this->addFlash("success", "La catégorie a été supprimée");
        }
        return $this->redirectToRoute('admin_category_index');
    }
}

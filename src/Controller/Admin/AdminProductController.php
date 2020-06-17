<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\Upload\ImageUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminProductController extends AbstractController
{
    /**
     * Affiche les produits
     * @Route("/admin/produits", name="admin_product_index")
     * @return Response
     */
    public function index(ProductRepository $productRepository, CategoryRepository $categoryRepository)
    {
        return $this->render('admin/product/index.html.twig', [
            'products' => $productRepository->findAll(),
            'categories' => $categoryRepository->findAllByOrder()
        ]);
    }

    /**
     * Enregistre un produit
     * @Route("/admin/produit/new",name="admin_product_new")
     * @param EntityManagerInterface $manager
     * @param Request $request
     * @return Response
     */
    public function new(EntityManagerInterface $manager, Request $request, ImageUploader $imageUploader, CategoryRepository $categoryRepository)
    {
        $product = new product();
        $categories = $categoryRepository->findAllByOrder();
        $form = $this->createForm(ProductType::class, $product, ['categories' => $categories]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($image = $form->get('image')->getData()) {
                $imageName = $imageUploader->create($image) ?? "bientotdispo.jpg";
                $product->setImage($imageName);
            }
            $manager->persist($product);
            $manager->flush();
            $this->addFlash("success", "Le produit a été ajouté");
            return $this->redirectToRoute('admin_product_new');
        }
        return $this->render('admin/product/new.html.twig', [
            'form' => $form->createView(),
            'categories' => $categoryRepository->findAllByOrder()
        ]);
    }


    /**
     * Modifie une catégorie
     * @Route("/admin/produit/{id}/edit",name="admin_product_edit", methods={"GET","POST"})
     * @param product $product
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */
    public function edit(product $product, Request $request, EntityManagerInterface $manager, ImageUploader $imageUploader, CategoryRepository $categoryRepository)
    {
        $categories = $categoryRepository->findAllByOrder();
        $form = $this->createForm(ProductType::class, $product, ['categories' => $categories]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($image = $form->get('image')->getData()) {
                $imageName = $imageUploader->create($image) ?? "bientotdispo.jpg";
                $product->setImage($imageName);
            }
            $manager->persist($product);
            $manager->flush();
            $this->addFlash("success", "Le produit a été modifié");
            return $this->redirectToRoute('admin_product_new');
        }
        return $this->render('admin/product/edit.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
            'categories' => $categoryRepository->findAllByOrder()
        ]);
    }

    /**
     * Supprime une catégorie
     * @Route("/admin/produit/{id}/delete",name="admin_product_delete", methods={"DELETE"})
     * @param product $product
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */
    public function delete(product $product, Request $request, EntityManagerInterface $manager, ImageUploader $imageUploader)
    {
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
            $imageUploader->delete($product->getImage());
            $manager->remove($product);
            $manager->flush();
            $this->addFlash("success", "La catégorie a été supprimée");
        }
        return $this->redirectToRoute('admin_product_index');
    }
}

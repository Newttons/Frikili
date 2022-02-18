<?php

namespace App\Controller;

use App\Entity\Posts;
use App\Form\PostsType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class PostsController extends AbstractController
{
    /**
     * @Route("/registrar-posts", name="RegistrarPosts")
     */
    public function index(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        $post = new Posts();
        $form = $this->createForm(PostsType::class, $post);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
             $brochureFile = $form->get('foto')->getData();
             // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($brochureFile) {
                $originalFilename = pathinfo($brochureFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$brochureFile->guessExtension();
                // Move the file to the directory where brochures are stored
                try {
                    $brochureFile->move(
                        $this->getParameter('photos_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    throw new \Exception('Ha ocurrido un error!');
                }
                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $post->setFoto($newFilename);
            }
             $user = $this->getUser();
             $post->setUser($user);
             $em->persist($post);
             $em->flush();
             return $this->redirectToRoute('dashboard');
        }
        return $this->render('posts/index.html.twig', [
            'controller_name' => 'PostsController',
            'form' => $form->createView()
        ]);
    }
}

<?php
namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;

/**
 * Class HomeController
 */
class HomeController extends Controller
{
    /**
     * @Route("/", name="home")
     */
    public function indexAction()
    {
        return $this->render('home/index.html.twig');
    }

    /**
     * @Route("/source", name="source")
     */
    public function sourceAction()
    {
        return $this->render('home/source.html.twig');
    }
}

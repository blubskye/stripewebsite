<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends Controller
{
    #[Route('/', name: 'home')]
    public function indexAction(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/source', name: 'source')]
    public function sourceAction(): Response
    {
        return $this->render('home/source.html.twig');
    }
}

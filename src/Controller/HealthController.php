<?php
declare(strict_types=1);

namespace App\Controller;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;

class HealthController extends AbstractFOSRestController
{
    /**
     * Show health.
     * @Rest\Get("/health")
     * @Rest\Get("/")
     *
     * @return Response
     */
    public function getHealth()
    {
        return $this->handleView($this->view([
            'health' => 'ok'
        ]));
    }
}

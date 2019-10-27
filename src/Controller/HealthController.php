<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;

class HealthController extends AbstractFOSRestController
{
    /**
     * Show health.
     * @Rest\Get("/health")
     * @Rest\Get("/")
     * @Rest\Get("/api/health")
     *
     * @return Response
     */
    public function getHealth()
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = ['health' => 'ok'];

        if ($user) {
            $data['me'] = [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'lastLogin' => $user->getLastLogin(),
            ];
        }

        return $this->handleView(
            $this->view($data)
        );
    }
}

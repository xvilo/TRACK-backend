<?php
declare(strict_types=1);

namespace App\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTManager;
use Symfony\Component\Translation\DataCollectorTranslator as Translator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use FOS\UserBundle\Form\Factory\FactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SecurityController extends AbstractFOSRestController
{
    /**
     * @var FactoryInterface
     */
    private $formFactory;

    /**
     * @var UserManagerInterface
     */
    private $userManager;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var Translator
     */
    private $translator;

    public function __construct(EventDispatcherInterface $eventDispatcher, FactoryInterface $formFactory, UserManagerInterface $userManager, JWTManager $jwtManager, Translator $translator)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->formFactory     = $formFactory;
        $this->userManager     = $userManager;
        $this->tokenStorage    = $jwtManager;
        $this->translator      = $translator;
    }
    /**
     * @Rest\Post("/register")
     */
    public function registerAction(Request $request)
    {
        $userManager = $this->userManager;
        $dispatcher = $this->eventDispatcher;
        $user = $userManager->createUser();
        $user->setEnabled(true);

        $event = new GetResponseUserEvent($user, $request);
        $dispatcher->dispatch(FOSUserEvents::REGISTRATION_INITIALIZE, $event);
        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $form = $this->formFactory->createForm([
            'csrf_protection'    => false
        ]);
        $form->setData($user);
        $form->submit($request->request->all());

        if ( ! $form->isValid()) {
            $event = new FormEvent($form, $request);
            $dispatcher->dispatch(FOSUserEvents::REGISTRATION_FAILURE, $event);
            if (null !== $response = $event->getResponse()) {
                return $response;
            }
            return $form;
        }

        $event = new FormEvent($form, $request);
        $dispatcher->dispatch(FOSUserEvents::REGISTRATION_SUCCESS, $event);
        if ($event->getResponse()) {
            return $event->getResponse();
        }

        $userManager->updateUser($user);
        $response = new JsonResponse(
            [
                'msg' => $this->translator->trans('registration.flash.user_created', [], 'FOSUserBundle'),
                'token' => $this->tokenStorage->create($user), // creates JWT
            ],
            Response::HTTP_CREATED
        );

        $dispatcher->dispatch(
            FOSUserEvents::REGISTRATION_COMPLETED,
            new FilterUserResponseEvent($user, $request, $response)
        );

        return $response;
    }
}

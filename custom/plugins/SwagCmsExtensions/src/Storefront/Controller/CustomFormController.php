<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Storefront\Controller;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Framework\Captcha\Annotation\Captcha;
use Swag\CmsExtensions\Form\Route\AbstractFormRoute;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"storefront"}})
 */
class CustomFormController extends StorefrontController
{
    private AbstractFormRoute $formRoute;

    public function __construct(AbstractFormRoute $formRoute)
    {
        $this->formRoute = $formRoute;
    }

    /**
     * @Route("/swag/cms-extensions/form", name="frontend.swag.cms-extensions.form.send", methods={"POST"}, defaults={"XmlHttpRequest"=true, "_captcha"=true})
     */
    public function sendForm(RequestDataBag $data, SalesChannelContext $context): JsonResponse
    {
        $response = [];

        try {
            $message = $this->formRoute
                ->send($data->toRequestDataBag(), $context)
                ->getResult()
                ->getSuccessMessage();

            if (!$message) {
                $message = $this->trans('contact.success');
            }

            $response[] = [
                'type' => 'success',
                'alert' => $message,
            ];
        } catch (ConstraintViolationException $formViolations) {
            $violations = [];
            foreach ($formViolations->getViolations() as $violation) {
                $violations[] = $violation->getMessage();
            }
            $response[] = [
                'type' => 'danger',
                'alert' => $this->renderView('@Storefront/storefront/utilities/alert.html.twig', [
                    'type' => 'danger',
                    'list' => $violations,
                ]),
            ];
        }

        return new JsonResponse($response);
    }
}

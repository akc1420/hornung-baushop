<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Util\Administration;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteException;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Swag\CmsExtensions\Util\Exception\FormValidationPassedException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"api"}})
 */
class FormValidationController extends AbstractController
{
    public const IS_FORM_VALIDATION = 'swagCmsExtensionsFormValidationStatus';

    private EntityRepository $formRepository;

    public function __construct(EntityRepository $formRepository)
    {
        $this->formRepository = $formRepository;
    }

    /**
     * @Route(
     *     "/api/_action/swag/cms-extensions/form/validate",
     *      name="api.action.swag.cms-extensions.form.validate",
     *      methods={"POST"},
     *      defaults={"_acl"={"cms:editor"}}
     * )
     */
    public function validateForm(Request $request, Context $context): Response
    {
        $form = $this->decodeRequest($request);

        if (empty($form)) {
            throw new BadRequestHttpException('Request body is empty or invalid.');
        }

        $context->addExtension(self::IS_FORM_VALIDATION, new ArrayStruct());

        try {
            $this->formRepository->upsert([$form], $context);
        } catch (WriteException $exception) {
            $this->filterWriteException($exception);
        }

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route(
     *     "/api/_action/swag/cms-extensions/form/validateAll",
     *      name="api.action.swag.cms-extensions.form.validate-all",
     *      methods={"POST"},
     *      defaults={"_acl"={"cms:editor"}}
     * )
     */
    public function validateAllForms(Request $request, Context $context): Response
    {
        $forms = $this->decodeRequest($request);
        $context->addExtension(self::IS_FORM_VALIDATION, new ArrayStruct());

        try {
            $this->formRepository->upsert($forms, $context);
        } catch (WriteException $exception) {
            $this->filterWriteException($exception);
        }

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeRequest(Request $request): array
    {
        $content = $request->getContent();

        if (!\is_string($content)) {
            throw new BadRequestHttpException('Request body is empty or invalid.');
        }

        $decoded = \json_decode($content, true);

        if (\json_last_error() !== \JSON_ERROR_NONE) {
            throw new BadRequestHttpException('Could not decode request body: ' . \json_last_error_msg());
        }

        return $decoded;
    }

    private function filterWriteException(WriteException $exception): void
    {
        $newException = new WriteException();

        foreach ($exception->getExceptions() as $subException) {
            if (!($subException instanceof FormValidationPassedException)) {
                $newException->add($subException);
            }
        }

        $newException->tryToThrow();
    }
}

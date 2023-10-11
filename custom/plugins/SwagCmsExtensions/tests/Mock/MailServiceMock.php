<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Mock;

use Shopware\Core\Content\Mail\Service\AbstractMailService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Symfony\Component\Mime\Email;

class MailServiceMock extends AbstractMailService
{
    /**
     * @var array<int, array{0: array<mixed>, 1: Context, 2: array<mixed>}>
     */
    private $sentMails = [];

    /**
     * @return array<int, mixed>
     */
    public function getSentMails(): array
    {
        return $this->sentMails;
    }

    /**
     * @param array<mixed> $data
     * @param array<mixed> $templateData
     */
    public function send(array $data, Context $context, array $templateData = []): ?Email
    {
        $this->sentMails[] = [$data, $context, $templateData];

        return null;
    }

    public function getDecorated(): AbstractMailService
    {
        throw new DecorationPatternException(self::class);
    }
}

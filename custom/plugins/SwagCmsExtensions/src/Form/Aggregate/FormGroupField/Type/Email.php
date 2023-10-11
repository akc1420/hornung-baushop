<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Aggregate\FormGroupField\Type;

class Email extends AbstractFieldType
{
    public const NAME = 'email';

    public function getName(): string
    {
        return self::NAME;
    }
}

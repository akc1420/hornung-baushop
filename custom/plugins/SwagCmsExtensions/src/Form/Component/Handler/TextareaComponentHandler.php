<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Component\Handler;

use Swag\CmsExtensions\Form\Aggregate\FormGroupField\Type\Textarea;

class TextareaComponentHandler extends TextComponentHandler
{
    public function getComponentType(): string
    {
        return Textarea::NAME;
    }
}

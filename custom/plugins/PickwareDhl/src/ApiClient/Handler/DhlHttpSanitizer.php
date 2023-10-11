<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\ApiClient\Handler;

use DOMDocument;
use DOMNode;
use DOMXPath;
use Pickware\HttpUtils\Sanitizer\AuthHttpSanitizer;
use Throwable;

class DhlHttpSanitizer extends AuthHttpSanitizer
{
    public function filterBody(string $body): string
    {
        $body = parent::filterBody($body);

        $dom = new DOMDocument();

        try {
            $dom->loadXML($body);
        } catch (Throwable $e) {
            return sprintf(
                'Could not parse XML. XML is truncated for security reasons. Length of XML in bytes: %s',
                mb_strlen($body, '8bit'),
            );
        }

        $xpath = new DOMXpath($dom);
        $xpath->registerNamespace('dhl', 'http://dhl.de/webservice/cisbase');

        // Remove DHL BCP password
        $elements = $xpath->query('//dhl:Authentification/dhl:signature');
        /** @var DOMNode $element */
        foreach ($elements as $element) {
            $element->textContent = '*HIDDEN*';
        }

        // Remove label data to save memory
        $elements = $xpath->query('//LabelData/labelData');
        /** @var DOMNode $element */
        foreach ($elements as $element) {
            $element->textContent = '*TRUNCATED*';
        }

        return $dom->saveXML();
    }
}

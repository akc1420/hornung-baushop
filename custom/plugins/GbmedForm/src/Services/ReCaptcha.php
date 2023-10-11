<?php declare(strict_types=1);
/**
 * gb media
 * All Rights Reserved.
 *
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * The content of this file is proprietary and confidential.
 *
 * @category       Shopware
 * @package        Shopware_Plugins
 * @subpackage     GbmedForm
 * @copyright      Copyright (c) 2020, gb media
 * @license        proprietary
 * @author         Giuseppe Bottino
 * @link           http://www.gb-media.biz
 */

namespace Gbmed\Form\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\ParameterBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\Response;

class ReCaptcha
{
    public const CAPTCHA_REQUEST_PARAMETER = 'g-recaptcha-response';

    private string $siteVerifyUrl = "https://www.recaptcha.net/recaptcha/api/siteverify";
    private Client $client;
    private SystemConfigService $systemConfigService;
    private Logger $logger;

    /**
     * @param SystemConfigService $systemConfigService
     * @param Logger $logger
     */
    public function __construct(SystemConfigService $systemConfigService, Logger $logger)
    {
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
    }

    /**
     * @param SalesChannelContext $context
     * @return bool
     */
    public function isConfigured(SalesChannelContext $context): bool
    {
        $sitekey = $this->getSitekey($context);
        $secret = $this->getSecret($context);

        return $sitekey !== null && $secret !== null;
    }

    /**
     * validate recaptcha request
     *
     * @param ParameterBag $request
     * @param SalesChannelContext|null $context
     * @return bool
     * @throws GuzzleException
     */
    public function validate(ParameterBag $request, ?SalesChannelContext $context = null): bool
    {
        $recaptchaUserInput = $request->get(static::CAPTCHA_REQUEST_PARAMETER);
        $secret = $this->getSecret($context);
        $error = null;

        if (!$secret || !$recaptchaUserInput) {
            $this->logger->error('GbmedForm: missing parameters', [
                static::CAPTCHA_REQUEST_PARAMETER => !$recaptchaUserInput ? 'is empty' : 'is set',
                'secret' => !$secret ? 'is empty' : 'is set',
            ]);
            return false;
        }

        try {
            $response = $this->getClient()->post($this->siteVerifyUrl, [
                'form_params' => [
                    'secret' => $secret,
                    'response' => $recaptchaUserInput,
                ]
            ]);
        } catch (GuzzleException $e) {
            $error = $e->getMessage();
            $response = null;
        }

        if (!$response || $response->getStatusCode() !== Response::HTTP_OK) {
            $this->logger->error('GbmedForm: google wrong response', [
                'url' => $this->siteVerifyUrl,
                'response' => [
                    'status' => $response !== null ? $response->getStatusCode() : null,
                    'content' => $response !== null ? $response->getBody()->getContents() : null,
                    'error' => $error,
                ]
            ]);
            return false;
        }

        $body = $response->getBody();
        $content = json_decode($body->getContents(), true);
        $score = array_key_exists('score', $content) ? $content['score'] : 0;
        $success = is_array($content)
            && array_key_exists('success', $content)
            && array_key_exists('score', $content)
            && $content['success'] === true
            && version_compare((string)$score, (string)$this->getScore($context), '>=');

        if (!$success) {
            $this->logger->info('GbmedForm: formular blocked', [
                'success' => $success,
                'score' => [
                    'response' => $score,
                    'configuration' => $this->getScore($context),
                    'success' => version_compare((string)$score, (string)$this->getScore($context), '>=')
                ],
                'response' => $content
            ]);
        }

        return $success;
    }

    /**
     * return guzzle http client
     *
     * @return Client
     */
    public function getClient(): Client
    {
        if (!isset($this->client)) {
            $this->client = new Client();
        }

        return $this->client;
    }

    /**
     * @param SalesChannelContext|null $salesChannelContext
     * @return string|null
     */
    public function getSitekey(?SalesChannelContext $salesChannelContext = null)
    {
        return $this->getConfigValue('sitekey', $salesChannelContext);
    }

    /**
     * @param SalesChannelContext|null $salesChannelContext
     * @return string|null
     */
    public function getSecret(?SalesChannelContext $salesChannelContext = null)
    {
        return $this->getConfigValue('secret', $salesChannelContext);
    }

    /**
     * @param SalesChannelContext|null $salesChannelContext
     * @return float
     */
    public function getScore(?SalesChannelContext $salesChannelContext = null): float
    {
        $score = (float)$this->getConfigValue('score', $salesChannelContext);

        return $score > 1
            ? 1.0
            : ($score < 0 ? 0 : $score);
    }

    /**
     * @param string $key
     * @param SalesChannelContext|null $salesChannelContext
     * @param mixed|null $default
     * @return mixed
     */
    private function getConfigValue(string $key, ?SalesChannelContext $salesChannelContext = null, $default = null)
    {
        $config = $this->getConfig($salesChannelContext);

        return !empty($config[$key]) ? $config[$key] : $default;
    }

    /**
     * get configuration
     *
     * @param SalesChannelContext|null $salesChannelContext
     * @return array
     */
    private function getConfig(?SalesChannelContext $salesChannelContext = null): array
    {
        $id = $salesChannelContext !== null ? $salesChannelContext->getSalesChannel()->getId() : null;

        return $this->systemConfigService->get(
            'GbmedForm.config',
            $id
        );
    }
}

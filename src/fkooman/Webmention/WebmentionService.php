<?php
namespace fkooman\Webmention;

use fkooman\Rest\Service;
use fkooman\Http\Request;
use fkooman\Http\Exception\BadRequestException;
use InvalidArgumentException;
use RuntimeException;
use fkooman\Http\Uri;
use GuzzleHttp\Client;
use DomDocument;
use fkooman\Http\JsonResponse;
use GuzzleHttp\Message\Response;

class WebmentionService extends Service
{
    /** @var GuzzleHttp\Client */
    private $client;

    /** @var array */
    private $plugins;

    public function __construct(Client $client = null)
    {
        parent::__construct();

        if (null === $client) {
            $client = new Client();
        }
        $this->client = $client;
        $this->plugins = array();
    
        $this->post(
            '/',
            function (Request $request) {
                return $this->handlePost($request);
            }
        );
    }

    public function registerPlugin(PluginInterface $plugin)
    {
        $this->plugins[] = $plugin;
    }

    public function handlePost(Request $request)
    {
        $source = $this->validateUrl($request->getPostParameter('source'), 'source');
        $target = $this->validateUrl($request->getPostParameter('target'), 'target');

        // check if target is ours
        $uriObj = new Uri($target);
        if ($request->getRequestUri()->getHost() !== $uriObj->getHost()) {
            // not ours :/
            throw new BadRequestException('not ours');
        }

        // check if target has webmention info
        if (!$this->hasWebmention($target, $request->getRequestUri()->getUri())) {
            throw new BadRequestException(
                'target is not webmention enabled or uses other webmention endpoint'
            );
        }

        if (!$this->hasTarget($source, $target)) {
            throw new BadRequestException('target link not found on source');
        }

        foreach ($this->plugins as $plugin) {
            $plugin->execute($source, $target);
        }

        $response = new JsonResponse();
        $response->setContent(array('status' => 'ok'));

        return $response;
    }

    /**
     * Check if target header or document has $thisEndpoint as webmention
     * endpoint
     */
    private function hasWebmention($target, $thisEndpoint)
    {
        // check header of $target for $thisEndpoint 'rel="webmention"'
        $webmentionFromLinkHeader = $this->getWebmentionFromLinkHeader($target);
        if (false !== $webmentionFromLinkHeader) {
            return $thisEndpoint === $webmentionFromLinkHeader;
        }

        // check document of $target for $thisEndpoint rel="webmention"
        $documentLinks = $this->getDocumentLinks($target, 'webmention');
        if (0 === count($documentLinks)) {
            throw new BadRequestException('document does not have a webmention link');
        }

        // return only the first rel="webmention" link found on the page
        return $thisEndpoint === $documentLinks[0];
    }

    private function getWebmentionFromLinkHeader($u)
    {
        $request = $this->client->createRequest('HEAD', $u);
        $response = $this->client->send($request);

        if (false === strpos($response->getHeader('Content-Type'), 'text/html')) {
            throw new RuntimeException('Content-Type MUST be text/html');
        }

        if (!$response->hasHeader('Link')) {
            return false;
        }

        // find the Link header with 'rel="webmention"', we use the FIRST Link
        // header that has 'rel="webmention"'
        $linkHeaders = Response::parseHeader($response, 'Link');
        foreach ($linkHeaders as $linkHeader) {
            if (array_key_exists('rel', $linkHeader)) {
                if ('webmention' === $linkHeader['rel']) {
                    $linkUrl = $linkHeader[0];
                    return substr($linkUrl, 1, strlen($linkUrl) - 2);
                }
            }
        }

        return false;
    }

    private function getDocumentLinks($source, $rel = null)
    {
        $request = $this->client->createRequest('GET', $source);
        $response = $this->client->send($request);
        if (false === strpos($response->getHeader('Content-Type'), 'text/html')) {
            throw new RuntimeException('unexpected content type from source URL');
        }

        $htmlString = $response->getBody();

        $dom = new DomDocument();
        // disable error handling by DomDocument so we handle them ourselves
        libxml_use_internal_errors(true);
        $dom->loadHTML($htmlString);
        // throw away all errors, we do not care about them anyway
        libxml_clear_errors();

        $linkTags = array('link', 'a');
        $documentLinks = array();
        foreach ($linkTags as $tag) {
            $elements = $dom->getElementsByTagName($tag);
            foreach ($elements as $element) {
                if (null !== $rel) {
                    $relAttr = $element->getAttribute('rel');
                    if ($rel === $relAttr) {
                        $documentLinks[] = $element->getAttribute('href');
                    }
                } else {
                    $documentLinks[] = $element->getAttribute('href');
                }
            }
        }

        return $documentLinks;
    }

    private function hasTarget($source, $target)
    {
        $documentLinks = $this->getDocumentLinks($source);
        foreach ($documentLinks as $link) {
            if ($target === $link) {
                return true;
            }
        }
        return false;
    }

    private function validateUrl($url, $type)
    {
        if (null === $url) {
            throw new BadRequestException(
                sprintf('no "%s" URL provided', $type)
            );
        }
        try {
            $urlObj = new Uri($url);
            return $urlObj->getUri();
        } catch (InvalidArgumentException $e) {
            throw new BadRequestException(
                sprintf('invalid "%s" URL provided', $type)
            );
        }
    }
}

<?php
namespace fkooman\WebMention;

use fkooman\Rest\Service;
use fkooman\Http\Request;
use fkooman\Http\Exception\BadRequestException;
use InvalidArgumentException;
use RuntimeException;
use fkooman\Http\Uri;
use GuzzleHttp\Client;
use DomDocument;
use fkooman\Http\JsonResponse;

class WebMentionService extends Service
{
    /** @var GuzzleHttp\Client */
    private $client;

    public function __construct(Client $client = null)
    {
        parent::__construct();

        if (null === $client) {
            $client = new Client();
        }
        $this->client = $client;

        $this->post(
            '/',
            function (Request $request) {
                return $this->handlePost($request);
            }
        );
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
        if (!$this->hasWebmentionRel($target, $request->getRequestUri()->getUri())) {
            throw new BadRequestException('target is not webmention enabled');
        }

        if (!$this->hasTarget($source, $target)) {
            throw new BadRequestException('target link not found on source');
        }

        $response = new JsonResponse();
        $response->setContent(array('status' => 'ok'));

        return $response;
    }

    private function hasWebmentionRel($target, $thisEndpoint)
    {
        $documentLinks = $this->getDocumentLinks($target, 'webmention');
        foreach ($documentLinks as $link) {
            if ($link === $thisEndpoint) {
                return true;
            }
        }

        return false;
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

    private function getDocumentLinks($source, $rel = null)
    {
        // find all a, link tags, find target url in source page
        // check that the type is text/html

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

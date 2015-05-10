<?php

namespace fkooman\Webmention\Plugin;

use fkooman\Webmention\PluginInterface;

class PhubblePlugin implements PluginInterface
{
    private $db;

    public function __construct(PhubbleStorage $db)
    {
        $this->db = $db;
    }

    public function execute($source, $target)
    {
        // only interested in the identifier of the target URL
        $target = substr($target, strrpos($target, '/') + 1);
        $this->db->mention($source, $target, time());
    }
}

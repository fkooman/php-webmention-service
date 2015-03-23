<?php

namespace fkooman\Webmention;

interface PluginInterface
{
    public function execute($source, $target);
}

<?php

namespace fkooman\WebMention;

interface PluginInterface
{
    public function execute($source, $target);
}

<?php

namespace fkooman\WebMention\Plugin;

use fkooman\WebMention\PluginInterface;

class LogPlugin implements PluginInterface
{
    public function execute($source, $target)
    {
        error_log(
            sprintf(
                'mention from "%s" to "%s"',
                $source,
                $target
            )
        );
    }
}

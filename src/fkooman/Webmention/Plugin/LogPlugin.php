<?php

namespace fkooman\Webmention\Plugin;

use fkooman\Webmention\PluginInterface;

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

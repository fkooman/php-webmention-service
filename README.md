# Introduction
A simple *webmention* service written in PHP with pluggable action handler on 
successful webmentions.

# Available Plugins
Currently there are some plugins that can be executed on a successful Webmention.

* Mail (send a mail on a successful webmention)
* Log (add an entry of the webmention to the log using `error_log`)

# Planned Plugins 
* HTML (modify static HTML code somewhere)
* Wordpress (add a "linkback" row or similar)
* ...

# Writing Plugins
To write a plugin one has to implement the `execute($source, $target)` method 
of the interface `PluginInterface`, e.g.:

    <?php
    namespace fkooman\Webmention\Plugin;

    use fkooman\Webmention\PluginInterface;

    class MyPlugin implements PluginInterface
    {
        public function execute($source, $target)
        {
            // do something with $source and/or $target
        }
    }

That's all!

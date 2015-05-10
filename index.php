<?php

/**
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'vendor/autoload.php';

use fkooman\Ini\IniReader;
use fkooman\Webmention\WebmentionService;
use GuzzleHttp\Client;
use fkooman\Webmention\Plugin\MailPlugin;
use fkooman\Webmention\Plugin\LogPlugin;
use fkooman\Webmention\Plugin\PhubblePlugin;
use fkooman\Webmention\Plugin\PhubbleStorage;

try {
    $iniReader = IniReader::fromFile(
        'config/config.ini'
    );

    // HTTP CLIENT
    $disableServerCertCheck = $iniReader->v('disableServerCertCheck', false, false);

    $client = new Client(
        array(
            'defaults' => array(
                'verify' => !$disableServerCertCheck,
                'timeout' => 5
            )
        )
    );

    $service = new WebmentionService($client);

    // FIXME: write a dynamic plugin loader without mentioning them all here...
    if ($iniReader->v('Mail', 'enabled')) {
        $service->registerPlugin(
            new MailPlugin(
                $iniReader->v('Mail', 'from'),
                $iniReader->v('Mail', 'to')
            )
        );
    }

    if ($iniReader->v('Log', 'enabled')) {
        $service->registerPlugin(
            new LogPlugin()
        );
    }

    if ($iniReader->v('Phubble', 'enabled')) {
        $pdo = new PDO(
            $iniReader->v('Phubble', 'dsn'),
            $iniReader->v('Phubble', 'username', false),
            $iniReader->v('Phubble', 'password', false)
        );
        $phubbleStorage = new PhubbleStorage($pdo);
        $service->registerPlugin(
            new PhubblePlugin($phubbleStorage)
        );
    }

    $service->run()->sendResponse();
} catch (Exception $e) {
    WebmentionService::handleException($e)->sendResponse();
}

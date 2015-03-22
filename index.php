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
use fkooman\WebMention\WebMentionService;
use GuzzleHttp\Client;

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

    $service = new WebMentionService($client);
    $service->run()->sendResponse();
} catch (Exception $e) {
    error_log(
        $e->getMessage()
    );
    WebMentionService::handleException($e)->sendResponse();
}

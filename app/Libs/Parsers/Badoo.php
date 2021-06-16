<?php

namespace App\Libs\Parsers;

use App\Libs\Contracts\Abstracts\Parser;
use App\Libs\Contracts\Interfaces\Parser as ParserInterface;

/*
INSERT INTO `User66` VALUES ('11917635', '62', '0', '8', '0', 'None', '67', '7636', '265791', '0', 'W', 'No', '..::_\\|/_::..', '', '', 'Default', 'Y3B0ZmluZHVzQHN1cGVyZXZhLml0', 'Yes', '0000-00-00 00:00:00', '', 'No', '0000-00-00 00:00:00', 'No', 'On', 'On', 'On', 'Default', 'Default', 'Default', 'On', 'On', 'On', 'Default', '11917635.onirc.cptfindus', '0e19a8bac63f97a513063dcb9a64442b', 'Default', 'UbLHyDFVtm', '1979-10-07', '29', 'M', 'No', '29', '568', '45661', '29', '0', '0', '0', '0', '0', '0', '', '0', '22555', 'enAg2oQmyS', '0', 'Yes', 'Yes', 'Yes', 'Email', '', '2013-03-14 15:03:11', '2006-12-02 00:10:37', 'No', 'Active', 'Deleted', '2009-06-05 09:38:16', '2006-12-02 00:16:14', '1990-01-01 00:00:00', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 'No', 'No', '2006-12-02 00:15:17', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 'Yes', 'Yes', '0', 'No', 'New', '2007-07-13 13:31:39', 'No', 'None', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 'Changed', 'No', 'Default', 'Default', 'Default', 'Default', 'Default', 'Default', 'Default', 'Default', 'Default', 'Default', 'Default', 'Default', 'Default', 'Default', 'NotActive', 'NotActive', '', 'Default', 'Default', 'Default', 'Default', 'Default', 'Default', 'Default', 'Default', 'Default', 'Default', '16777216', '0', '0', '0', '', '0', 'Default', 'No', 'Default', '0', '0', '0000-00-00 00:00:00', '0', '0000-00-00 00:00:00', 'On', 'Default', 'Yes', 'Web', 'Commercial', '0000-00-00 00:00:00', 'Yes', 'Yes', '', 'Default', 'Default', 'Default', 'Default', 'Default', 'Default', 'Default', 'No', '0000-00-00 00:00:00', 'F', '24', '39', '10001', null, 'NOT_SET', 'No', 'Default', null, '0', 'No', '0', '0', null, null);
139903:gcoquio@surfeu.ch:guillaume_tell:8c5b7bb6042110ac96c9ae351dbd7fbc:Comandante Ch?:::1962-04-22:50:M:66:729:67793
139906:helil38@yahoo.de:paar48of:4708471dadc188ddc28ca02ad3203c00:Ilse Stelz:::1959-08-18:53:F:18:835:121201
160216:fabpatsmash2000@yahoo.com:fabpatsmash:8155adbe513300ff7b12a99ee717d12e:Fabpatsmash:::0000-00-00:39:M:13:855:130435
179637:nosekeponer_666@hotmail.com:devil_cara:e6b28db7802e90c9372b2069ed9b3e47:Javi:::1986-12-22:26:M:28:650:62172
179640:maisesap@hotmail.com:diablilla1:06c0681c95e6d499eb653073e1ed4bb5:Carmen:::0000-00-00:39:F:28:650:62172
179641:eloy_malaguita@hotmail.com:cojone:50fccc7c8b7417438df5e34d019c9036:Yo:::0000-00-00:39:M:28:650:62172
218866:chikito200@hotmail.com:ckikito200:45c8d284d70198a61b45524a9ce29795:Preguntamelo:::1987-05-25:25:M:28:635:57405
*/

class Badoo extends Parser implements ParserInterface
{
    /**
     * {@inheritdoc }
     */
    protected $extensions = ['txt'];

    /**
     * {@inheritdoc }
     */
    protected $separator = ':';

    /**
     * {@inheritdoc }
     */
    public function processLine(string $line)
    {
        $line = $this->cleanLine($line);
        $parts = explode($this->separator, $line);

        $map = [
            0 => 'badoo_id',
            1 => 'email',
            2 => 'username',
            3 => 'hash',
            4 => 'name',
            9 => 'gender',
        ];

        $data = $this->parse($map, $parts);

        if ($parts[7] != '0000-00-00') {
            $data['birthdate'] = $parts[7];
        }

        return $data;
    }
}

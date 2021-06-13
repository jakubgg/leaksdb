<?php

namespace App\Libs\Parsers;

use App\Libs\Contracts\Parser;
use Carbon\Carbon;

/*

34600000001:1246804413:Alberto:Cea:male:Granada, Spain:Granada, Spain:::2005::
34600000007:100005092726480:Día:Diferente:male:Torrevieja:Torrevieja::DiaDiferente.com:::
34600000008:100011123021287:Adrian:Gomez:male:Murcia, Murcia::::::
34600000012:100022580055279:Angela:Buraga:female:San Pedro del Pinatar, Murcia:Chernivtsi:::::
34600000013:563071831:Monica:Diaz:female:Barcelona, Spain:9 De Julio, Buenos Aires, Argentina:::::
34600000124:100009647177090:Saray:Chao Gonzalez:female:Viveiro:Madrid, Spain:::2004::09/05/1985
34600000305:1290747812:Carlos:Dasilva:male:Villagarcía de Arosa:Villagarcía de Arosa:In a relationship:O Galpon:::
34600001616:100019459608365:Gustavo:Diaz Herrera:male:Mérida, Spain:Toledo, Spain:In a relationship:Copistería, Papelería. Libreria Los Escribas:::
34600015227:1581500209:Jaume:Cusidó Morral:male:Sabadell:Sabadell::Tallers de fotografia al bergueda::jaume.cusido@gmail.com:
34600017298:724772575:Albert:Pedrero:male:Barcelona, Spain:Barcelona, Spain::::albertpedrero@gmail.com:05/28/1985
34600017309:690097132:Albert:Garcia Gibert:male:El Prat de Llobregat:El Prat de Llobregat:In a relationship:Uvinum:1996::10/09/1978
34600017315:100005786590271:Francisco:Lopez:male:Cornellá:Cornellá::ies miquel marti i pol:::

https://twitter.com/UnderTheBreach/status/1378314424239460352
Phone number, Facebook ID, First Name, Last Name, Gender, Location, Past Location, Relationship Status, 
Email Address, Account Creation Date, Relationship Status, Bio.
Birthdate

*/

class FacebookPhones implements Parser
{
    public function processLine(string $line)
    {
        $parts = explode(':', trim($line));
        if (count($parts) != 12) {
            return false;
        }

        $data = [
            'phone' => $parts[0],
            'fb_id' => $parts[1],
        ];

        if ($parts[2]) {
            $data['first_name'] = $parts[2];
        }

        if ($parts[3]) {
            $data['last_name'] = $parts[3];
        }

        if ($parts[4]) {
            $data['gender'] = $parts[4];
        }

        if ($parts[5]) {
            $data['location'] = $parts[5];
        }

        if ($parts[6]) {
            $data['past_location'] = $parts[6];
        }

        if ($parts[7]) {
            $data['relationship_status'] = $parts[7];
        }

        if ($parts[8]) {
            $data['work'] = $parts[8];
        }

        if ($parts[9]) {
            $data['work_year'] = $parts[9];
        }

        if ($parts[10]) {
            $data['email'] = $parts[10];
        }

        if ($parts[11]) {
            if (preg_match('/\s{2}\/\s{2}\/\s{2}/', $parts[11])) {
                $data['birthdate'] = Carbon::createFromFormat('m/d/Y', $parts[11])->format('Y-m-d');
            }
        }

        return $data;
    }
}

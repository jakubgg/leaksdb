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

39330200173:100029159948200:Mausica:Piacentini:female:::::1/1/0001 12:00:00 AM::
39330115115:1633625864:Angelo S.:Musto:male:Pratola Serra:Avellino, Italy:Married:Ministero Dell'istruzione Dell'università E Della Ricerca Roma:::
39330115452:1825043453:Indirpreet:Kaur:female:Davao City:Ludhiana, Punjab, India::PiCrust Band:::
39330116677:100003495920227:Mauro:Pischedda::Pioltello:Sassari, Italy:Single::::
39330117207:100000268483814:Luiz:Fuganti:male::::Metasul Estruturas Metálicas:::
39330264088:100000107724246:Massimo:Marangoni:male:Cervia:Massa Lombarda:Married:Sono il datore di lavoro di me stesso:2/11/2019 12:00:00 AM:max@plasticiarchitettonici.com:06/22/1962
39330113159:100003306781370:Daniele:Mandolini:male:Rome, Italy:Rome, Italy:Separated:Ridarreda, camerette per bambini:::03/27/1968
39330204931:100009109423094:Angela:Vitale:female:Ceglie Messapico:Ceglie Messapico::Confezione:10/26/2015 12:00:00 AM::
39330277017:1625462848:Andrea:Simonelli:male:::Single:::andreasimonellincc@tiscali.it:12/28
393248081080:100004849480877:Sohel:Hossain:male:Bergamo, Italy:Dhaka, Bangladesh:Single:*Jana ojana onuvuti*

9377042163999:100035945963692:اسلامى معلومات:اسلامى معلومات:male:::::1/1/0001 12-00-00 AM::::True:True:7/26/2019 8-37-36 AM:1/1/0001 12-00-00 AM
93770421608:100001926893655:Sayyed Ali Sajjad:Musavi:male:Kabul, Afghanistan:Kabul, Afghanistan:Engaged:IEC Independent Election Commission:1/1/0001 12-00-00 AM:::2014:True:True:7/26/2019 8-37-36 AM:1/1/0001 12-00-00 AM
93770421567:100034897058314:امیر:الله:male:::::1/1/0001 12-00-00 AM::::True:True:7/26/2019 8-37-36 AM:1/1/0001 12-00-00 AM
93770421541:100004003887363:Adam:Ahmadzai:male::Mohammad Agha, Lowgar, Afghanistan::Student:1/1/0001 12-00-00 AM::::True:True:7/26/2019 8-37-36 AM:1/1/0001 12-00-00 AM
93770421519:100009017790231:Assadullah:Ramazani:male:Kabul, Afghanistan:Daykondi, Oruzgan, Afghanistan:Single:English Knowledge House:1/1/0001 12-00-00 AM::::True:True:7/26/2019 8-37-36 AM:1/1/0001 12-00-00 AM
93770423638:100014369702352:Fahad:Khan:male:Kabul, Afghanistan:Kabul, Afghanistan:::1/1/0001 12-00-00 AM::::True:True:7/26/2019 8-37-36 AM:1/1/0001 12-00-00 AM
93770424491:100033358564858:Badsha:Mashkoor:male:::::1/1/0001 12-00-00 AM::::True:True:7/26/2019 8-37-36 AM:1/1/0001 12-00-00 AM
93770424314:100027576462182:Noor:Kamalzai:male:Kabul, Afghanistan:Kabul, Afghanistan::Facebook:1/1/0001 12-00-00 AM::::True:True:7/26/2019 8-37-36 AM:1/1/0001 12-00-00 AM
93770424302:100001719228179:Fawad:Hematzada:male:Kuwait City:Cake Wardak, Vardak, Afghanistan:::1/1/0001 12-00-00 AM::::True:True:7/26/2019 8-37-36 AM:1/1/0001 12-00-00 AM
93770424259:100009664531022:Šåłëęm:Åfğhåņ:male:Kabul, Afghanistan:Kabul, Afghanistan:::1/1/0001 12-00-00 AM::::True:True:7/26/2019 8-37-36 AM:1/1/0001 12-00-00 AM

https://twitter.com/UnderTheBreach/status/1378314424239460352
Phone number, Facebook ID, First Name, Last Name, Gender, Location, Past Location, Relationship Status, 
Email Address, Account Creation Date, Relationship Status, Bio.
Birthdate

*/

class FacebookPhones implements Parser
{
    public function processLine(string $line)
    {

        // Remove time from date (as it cointains : separator)
        $line = preg_replace('/\s\d{2}[:|-]\d{2}[:|-]\d{2}\s([A|P]M)?/', '', $line);
        $parts = explode(':', trim($line));
        if (count($parts) < 12) {
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
            if ($parts[4] == 'male') {
                $data['gender'] = 'M';
            } elseif ($parts[4] == 'female') {
                $data['gender'] = 'F';
            }
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
            $parts[9] = trim($parts[9]);
            if (preg_match('/(\d{2})?\/\d{2}\/\d{4}/', $parts[9])) {
                $data['work_date'] = Carbon::createFromFormat('m/d/Y', $parts[9])->format('Y-m-d');
            }
        }

        if ($parts[10]) {
            $data['email'] = $parts[10];
        }

        if ($parts[11]) {
            $parts[11] = trim($parts[11]);
            if (preg_match('/(\d{2})?\/\d{2}\/\d{4}/', $parts[11])) {
                $data['birthdate'] = Carbon::createFromFormat('m/d/Y', $parts[11])->format('Y-m-d');
            }
        }

        return $data;
    }
}

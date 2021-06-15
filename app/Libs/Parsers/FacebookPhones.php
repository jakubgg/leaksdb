<?php

namespace App\Libs\Parsers;

use App\Libs\Contracts\Abstracts\Parser;
use App\Libs\Contracts\Interfaces\Parser as ParserInterface;
use Carbon\Carbon;
use Illuminate\Support\Arr;

/*

https://twitter.com/UnderTheBreach/status/1378314424239460352
Phone number, Facebook ID, First Name, Last Name, Gender, Location, Past Location, Relationship Status, 
Email Address, Account Creation Date, Relationship Status, Bio.
Birthdate

Verified:
- Sweden
- Moldova
- Italy
- Nigeria 2
- Nigeria 1
- Oman 3
- Oman 1
- El Salvador
- Turkey
- Cameroon
- Greece
- Oman 5
- Lithunia
- Oman 6
- UK 1
- Hong Kong
- South Korea
- USA 08
- China
- Afghanistan
- Guatemala
- Bolivia
- Italy 2
- UK 3
- UK 2
- Oman 4
- Sweden
- Hungary
- Iceland
- Poland
- Kazakhstan
- Argentina
- Netherland 01
- Czech Republic
- Denmark
- Palestine
- Albania
- Singapore
- Namibia
- Czech Republic 2
- Jamaica
- Malaysia 2
- Italy
- Brunei
- Philpine
- Estonia
- Netherland 02
- Angola
- Honduras
- Taiwan
- Oman 2
- Panama
- Nigeria 3
- Switzerland
- Croatia
- Brazil 1
- Jordan
- Luxemburj
- Brazil 2
- Indonesia
- Slovenia
- Mexico
- Norway
- Botswana
- Japan
- Malta
- Uruguay
- Ireland
- Finland
- Canada
- Colombia 04
- South Africa 1
- USA 01
- Russia 1
- Dibouti
- Azerbaijan
- South Africa 2
- USA 02
- Puerto Rico
- Russia 2
- USA 03
- Bulgaria
- Turkmenistan
- Costa Rica
- Chile 1
- Colombia 02
- USA 07
- Maldives
- USA 06
- Colombia 03
- Austria
- India 1
- Peru 2
- Macao
- Sudan
- Israel
- Burkina Faso
- Serbia
- Chile 2
- bangladesh
- USA 04
- Colombia 01
- Mauritius
- Portugal
- Spain
- USA 05
- India 2
- Peru 1
- tunisia

Verified, but non-eng relationship:
- Burundi
- Moldova
- Ecuador
- Ghana 2
- Ethopia
- Georgia
- Cambodia
- Fiji

Possible missing fields:
- Cyprus: School, Bio, Work role
- Alegria: Email, Rel, Work, School, Bio, Work role...

Issues:
- Iraq 3: Messy
- Iraq 1: Messy
- Iraq 6: Messy
- Iraq 2: Messy
- Iraq 4: Messy
- Iraq 5: Messy
- Libya: Some are messy, check header
- Syria: Some are messy, check header
- UAE 3: Messy
- UAE 1: Some are messy
- UAE 2: Messy
- Belgium: Birthdate seems incorrect. Mixed content in email.
- Germany 02: Messy
- Germany 01: Messy
- Egypt 1: Content escaped with ". Fields messy
- Egypt 2: Content escaped with ". Fields messy
- Egypt 3: Content escaped with ". Fields messy
- Egypt 4: Content escaped with ". Fields messy
- Saudi Arabia 1: Content escaped with ". Fields messy
- Saudi Arabia 2: Content escaped with ". Fields messy
- Saudi Arabia 3: Content escaped with ". Fields messy
- France 01: Messy
- France 02: Messy
- France 03: Messy
- France 04: Messy
- France 05: Messy
- Ghana 1: Messy
- Malaysia 1: Content escaped with ". Fields messy
- Lebanon: Messy
- Morocco: Messy
- Yemen: Messy
- Qatar: SOME are messy
- Haiti: Messy. Arab chars?
- Kuwait: SOME are messy
- Bahrain: Messy

Lajang = Single
Menikah = Married
Berpacaran = In a relationship
Menjanda/Menduda = Widowed
Berhubungan sipil = In a civil union
Bertunangan = Engaged
Menjalin hubungan tanpa status = In an open relationship
Rumit = It's complicated

Divorced
Separated
In a domestic partnership

*/

class FacebookPhones extends Parser implements ParserInterface
{
    /**
     * {@inheritdoc }
     */
    protected $extensions = ['txt'];

    /**
     * Country (based on the file name)
     *
     * @var string
     */
    protected $country;

    /**
     * {@inheritdoc }
     */
    protected function init()
    {
        $this->separator = $this->determineSeparator();
        $this->country = $this->getCountryByFileName();
    }

    /**
     * Determine the fields separator.
     * 
     * Sometimes , is used, some others :
     * 
     * @return string
     */
    private function determineSeparator()
    {
        // Read the first 3 lines
        $lines = '';
        $handle = fopen($this->getFilePath(), 'rb');
        for ($i = 0; $i < 3; $i++) {
            $lines .= fgets($handle);
        }
        fclose($handle);

        if (substr_count($lines, ':') > substr_count($lines, ',')) {
            return ':';
        } else {
            return ',';
        }
    }

    /**
     * Get the country name based on file name.
     *
     * @return string
     */
    private function getCountryByFileName()
    {
        $name = basename($this->getFilePath(), '.txt');

        return $name;

        $name = preg_replace('/[0-9]+/', '', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        $name = trim($name);
        $name = mb_convert_case(mb_strtolower($name), MB_CASE_TITLE, "UTF-8");

        return $name;
    }

    /**
     * {@inheritdoc }
     */
    public function processLine(string $line)
    {
        $originalLine = $line;

        $line = $this->cleanLine($line);

        // Remove time from date (as it might cointain :-, separators :/)
        $line = preg_replace('/\s\d{2}[:|\-|,]\d{2}[:|\-|,]\d{2}\s([A|P]M)?/', '', $line);

        $line = trim($line);

        $parts = explode($this->separator, $line);
        if (!count($parts)) {
            return false;
        }

        $parts = array_map('trim', $parts);

        // Remove useless values
        $parts = Arr::where($parts, function ($value, $key) {
            return $value && $value != 'None' && $value != 'Location*';
        });

        // Determine the processor
        if (isset($parts[3]) && str_starts_with($parts[3], '+')) {
            if (isset($parts[2]) && strstr($parts[2], '@')) {
                if (isset($parts[6]) && strstr($parts[6], '/')) {
                    $data = $this->processComa5($parts);
                } else {
                    $data = $this->processComa4($parts);
                }
            } else {
                $data = $this->processComa1($parts);
            }
        } elseif (isset($parts[1]) && str_starts_with($parts[1], '+')) {

            if (isset($parts[5]) && strstr($parts[5], 'facebook.com')) {
                // 100009459103379,+9647511390547,Abdulkerim,Ağırman,male,https://www.facebook.com/100009459103379,Abdulkerim Ağırman,,Mardin,100009459103379@facebook.com,0,0,0,1/1/0001 12:00:00 AM,1/1/0001 12:00:00 AM,,,,
                if (isset($parts[9]) && strstr($parts[9], '@')) {
                    $data = $this->processComa6($parts);
                } elseif (isset($parts[11]) && strstr($parts[11], '@')) {
                    $data = $this->processComa3($parts);
                }
                // 100000749751596,+9647722569463,Abdulla,Altemimi,male,https://www.facebook.com/abdulla.altemimi.5,,abdulla.altemimi.5,Abdulla Altemimi,,Iraqi ministry of defence,Iraqi Defense Ministry,Karradah  Baghdad  Iraq,,الكلية العسكرية الثالثه / قلاجولان,abdulla.altemimi.5@facebook.com,0,0,0,1/1/0001 12:00:00 AM,1/1/0001 12:00:00 AM,,,,
                // 100001781785609,+9647805727945,Abdul-kareem,Taban,male,https://www.facebook.com/abdulkareem.taban,,abdulkareem.taban,Abdul-kareem Taban,,Basrah  Al Basrah  Iraq,Al Basrah  Al Basrah  Iraq,St. George's High School,abdulkareem.taban@facebook.com,0,0,0,1/1/0001 12:00:00 AM,1/1/0001 12:00:00 AM,,,,
                elseif (isset($parts[13]) && strstr($parts[13], '@')) {
                    $data = $this->processComa7($parts);
                } else {
                }
            } elseif (isset($parts[7]) && strstr($parts[7], 'facebook.com')) {
                // 
            } else {
                $data = $this->processComa2($parts);
            }
        } else {
            $data = $this->processGeneric($parts);
        }

        if (isset($data)) {
            if (!isset($data['fb_id']) || !$data['fb_id'] || !is_numeric($data['fb_id'])) {
                return false;
            }
            $data['country'] = $this->country;
            $data['record'] = $originalLine;

            return $data;
        }

        return false;
    }

    /*
    100000761593283,,al_tamsahh@yahoo.com,+9647706075023,مسلم,02/15/1988,احمد,المكصوصي,male,https://www.facebook.com/al.tamsahh,,al.tamsahh,احمد المكصوصي,وما نيل المطالب ب التمني ولاكن تؤوخذ الدنيا غلابا,حيفا مول,,Baghdad  Iraq,Baghdad  Iraq,موسى بن نصير الابتدائيه,al.tamsahh@facebook.com,0,0,0,1/1/0001 12:00:00 AM,1/1/0001 12:00:00 AM,Single,,,
    */
    private function processComa5(array $parts)
    {
        $data = [];

        $data['parser'] = 'coma5';

        if (isset($parts[0])) {
            $data['fb_id'] = $parts[0];
        }

        if (isset($parts[2])) {
            $data['email'] = $parts[2];
        }

        if (isset($parts[3])) {
            $data['phone'] = $parts[3];
        }

        if (isset($parts[4])) {
            $data['first_name'] = $parts[4];
        }

        if (isset($parts[5])) {
            $data['last_name'] = $parts[5];
        }

        if (isset($parts[6])) {
            if (preg_match('/(\d{2})?\/\d{2}\/\d{4}/', $parts[6])) {
                $data['birthdate'] = Carbon::createFromFormat('m/d/Y', $parts[6])->format('Y-m-d');
            }
        }

        if (isset($parts[8])) {
            if ($parts[8] == 'male') {
                $data['gender'] = 'M';
            } elseif ($parts[8] == 'female') {
                $data['gender'] = 'F';
            }
        }

        if (isset($parts[13]) && $parts[13]) {
            $data['location'] = $parts[13];
        }

        if (isset($parts[14]) && $parts[14]) {
            $data['hometown'] = $parts[14];
        }

        if (isset($parts[22])) {
            $data['relationship_status'] = $parts[22];
        }

        return $data;
    }

    /*
    Full Name on 10
	100023791097366,,muhammadaljboury3.6@gmail.com,+9647511425133,Abbas,Aliraqe,male,https://www.facebook.com/mohamed.algbore.35,,mohamed.algbore.35,Abbas Aliraqe,,Mosul  Iraq,Mosul  Iraq,جامعة الموصل,mohamed.algbore.35@facebook.com,0,0,0,1/1/0001 12:00:00 AM,1/1/0001 12:00:00 AM,,,,
    Full Name on 8
    100022161249130,,4g6b8z@gmail.com,+9647804277063,Abbas,Realy,male,https://www.facebook.com/100022161249130,Abbas Al Realy,,,100022161249130@facebook.com,0,0,0,1/1/0001 12:00:00 AM,1/1/0001 12:00:00 AM,,,,
	100004457101264,,lion_150@yahoo.com,+9647715179948,Abbas,AL-Majmaay,male,https://www.facebook.com/100004457101264,Abbas AL-Majmaay,,جامعة بغداد,100004457101264@facebook.com,0,0,0,1/1/0001 12:00:00 AM,1/1/0001 12:00:00 AM,,,,
    */
    private function processComa4(array $parts)
    {
        $data = [];

        $data['parser'] = 'coma4';

        if (isset($parts[0])) {
            $data['fb_id'] = $parts[0];
        }

        if (isset($parts[2])) {
            $data['email'] = $parts[2];
        }

        if (isset($parts[3])) {
            $data['phone'] = $parts[3];
        }

        if (isset($parts[4])) {
            $data['first_name'] = $parts[4];
        }

        if (isset($parts[5])) {
            $data['last_name'] = $parts[5];
        }

        if (isset($parts[6])) {
            if ($parts[6] == 'male') {
                $data['gender'] = 'M';
            } elseif ($parts[6] == 'female') {
                $data['gender'] = 'F';
            }
        }

        // 10: Full name
        if (isset($parts[10]) && $parts[10] && isset($data['first_name']) && isset($data['last_name']) && $parts[10] == $data['first_name'] . ' ' . $data['last_name']) {
            if (isset($parts[12]) && $parts[12]) {
                $data['location'] = $parts[12];
            }

            if (isset($parts[13]) && $parts[13]) {
                $data['hometown'] = $parts[13];
            }
        }

        // Pending confirmation
        if (isset($parts[18])) {
            // $data['relationship_status'] = $parts[18];
        }

        return $data;
    }

    /*
    Iraq1
    100010026109277,+9647817891264,'ۦ,مہۧزآآجہۧي 'ۦ، 'ۦ،,male,https://www.facebook.com/100010026109277,'ۦ مہۧزآآجہۧي 'ۦ، 'ۦ،,,موظف حکومي,كاسب اعمال حرة,,Baghdad  Iraq,,100010026109277@facebook.com,0,0,0,1/1/0001 12:00:00 AM,1/1/0001 12:00:00 AM,,,,
    100025431331913,+9647815698564,"ۦٰۙۦٰۙ,ﭴٰ۫۬ﹻ۬ۆٰ۫۬رﻱٰٰ۫۬۫۬ۦٰۙۦٰۙ,female,https://www.facebook.com/100025431331913,"ۦٰۙۦٰۙ ﭴٰ۫۬ﹻ۬ۆٰ۫۬رﻱٰٰ۫۬۫۬ۦٰۙۦٰۙ,,وانته شكو حاشر نفسك,,Baghdad  Iraq,Baghdad  Iraq,الجامعة المستنصرية,100025431331913@facebook.com,0,0,0,1/1/0001 12:00:00 AM,1/1/0001 12:00:00 AM,,,,
    100009837197431,+9647817884898,"ۦٰٖٛۦ,هہٰٰٰۥﹻۥﹻۙﹻٰ۬ۛۛتلرﹻ,male,https://www.facebook.com/100009837197431,هہٰٰٰۥﹻۥﹻۙﹻٰ۬ۛۛتلرﹻ ۦٰٖٛۦ,,كلام فى الحب,نوم وبس�,An Nasiriyah  Iraq,An Nasiriyah  Iraq,,100009837197431@facebook.com,0,0,0,1/1/0001 12:00:00 AM,1/1/0001 12:00:00 AM,In an open relationship,,,
    100006789786070,+9647805832718,ﱞﱞ,ﱞﱞ,male,https://www.facebook.com/100006789786070,ﱞﱞ ﱞﱞ,,Facebook,,Bghailah  Wasit  Iraq,Bghailah  Wasit  Iraq,الكرار,100006789786070@facebook.com,0,0,0,1/1/0001 12:00:00 AM,1/1/0001 12:00:00 AM,,,,
    1056413087,+9647828766365,ﱞﱞ,ﱞﱞ,female,https://www.facebook.com/gianni.castillione,,gianni.castillione,ﱞﱞ,,,gianni.castillione@facebook.com,0,0,0,1/1/0001 12:00:00 AM,1/1/0001 12:00:00 AM,,,,
    */
    private function processComa3(array $parts)
    {
        $data = [];

        $data['parser'] = 'coma3';

        if (isset($parts[0])) {
            $data['fb_id'] = $parts[0];
        }

        if (isset($parts[1])) {
            $data['phone'] = $parts[1];
        }

        if (isset($parts[2])) {
            $data['first_name'] = $parts[2];
        }

        if (isset($parts[3])) {
            $data['last_name'] = $parts[3];
        }

        if (isset($parts[4])) {
            if ($parts[4] == 'male') {
                $data['gender'] = 'M';
            } elseif ($parts[4] == 'female') {
                $data['gender'] = 'F';
            }
        }

        // 5: Facebook URL

        if (isset($parts[6])) {
            $data['work'] = $parts[6]; // ???
        }

        if (isset($parts[8]) && $parts[8]) {
            $data['location'] = $parts[8];
        }

        if (isset($parts[9]) && $parts[9]) {
            $data['hometown'] = $parts[9];
        }

        // 10: Facebook ID again

        if (isset($parts[11])) {
            $data['email'] = $parts[11];
        }

        if (isset($parts[17])) {
            $data['relationship_status'] = $parts[17];
        }

        return $data;
    }

    /*
    id,phone,first_name,last_name,email,birthday,gender,locale,hometown,location,link
    100027836001192,+213555080106,Nã,Ssïm,None,None,male,fr_FR,None,Location*,None,link*,https://www.facebook.com/profile.php?id=100027836001192,
    100027461777769,+213557914999,Abdou,Jilat,None,None,male,fr_FR,None,Location*,None,link*,https://www.facebook.com/abdou.jilat,,,,,,,,,,,
    100005156027447,+213557914986,Imad,Bellaouel,None,None,male,fr_FR,Hammam Sousse,Location*,Annaba, Algeria,link*,https://www.facebook.com/profile.php?id=100005156027447,,,,,,,,,,
    1132055813,+213663682076,Rebai,Hicham,None,February 15, 1989,male,fr_FR,None,Location*,None,link*,https://www.facebook.com/rodre%  
    100015297636813,+9647706073245,احمد,الكعبي,male,https://www.facebook.com/100015297636813,احمد الكعبي,,,100015297636813@facebook.com,0,0,0,1/1/0001 12:00:00 AM,1/1/0001 12:00:00 AM,,,,
	
    */
    private function processComa2(array $parts)
    {
        $data = [];

        $data['parser'] = 'coma2';

        if (isset($parts[0])) {
            $data['fb_id'] = $parts[0];
        }

        if (isset($parts[1])) {
            $data['phone'] = $parts[1];
        }

        if (isset($parts[2])) {
            $data['first_name'] = $parts[2];
        }

        if (isset($parts[3])) {
            $data['last_name'] = $parts[3];
        }

        if (isset($parts[4])) {
            $data['email'] = $parts[4];
        }

        if (isset($parts[5])) {
            $data['birthdate'] = date('Y-m-d', strtotime($parts[5]));
        }

        if (isset($parts[6])) {
            if ($parts[6] == 'male') {
                $data['gender'] = 'M';
            } elseif ($parts[6] == 'female') {
                $data['gender'] = 'F';
            }
        }

        if (isset($parts[7]) && $parts[7]) {
            $data['lang'] = $parts[7];
        }

        if (isset($parts[8]) && $parts[8]) {
            $data['hometown'] = $parts[8];
        }

        if (isset($parts[10]) && $parts[10]) {
            $data['location'] = $parts[10];
        }

        return $data;
    }

    /*
    100000283768362,,,+97433989985,,10/28,A,Amer,male,https://www.facebook.com/ahmadamer09,,ahmadamer09,A M Amer,,,,Cairo  Egypt,Doha,,ahmadamer09@facebook.com,0,0,0,1/1/0001 12:00:00 AM,1/1/0001 12:00:00 AM,,,,,,,,,,
    100008312311436,,,+97450149585,,,Aashiq,Khan,male,https://www.facebook.com/aashiq.khan.31945,,aashiq.khan.31945,Aashiq Khan,,,,,Doha,Qatar University,aashiq.khan.31945@facebook.com,0,0,0,1/1/0001 12:00:00 AM,1/1/0001 12:00:00 AM,,,,,,,,,,
    523701054,,,+97455011166,,,Adnan,Mounajed,male,https://www.facebook.com/adnan.mounajed,,adnan.mounajed,Adnan Mounajed,,Bonne Maniere,CEO,,,Makassed - Al Horj,adnan.mounajed@facebook.com,0,0,0,1/1/0001 12:00:00 AM,1/1/0001 12:00:00 AM,,,,,,,,,,
    10000003379846^C5,,,+97433748308,,,Abdullah,Alkuwari,male,https://www.facebook.com/abdullah.alkuwari.507,,abdullah.alkuwari.507,Abdullah Alkuwari,,International School of Choueifat,Estudante,Doha,Doha,International School of Choueifat,abdullah.alkuwari.507@facebook.com,0,0,0,1/1/0001 12:00:00 AM,1/1/0001 12:00:00 AM,,,,,,,,,,
    100007905886900,,,+97450788144,,,Abdulla Al,Mamun,male,https://www.facebook.com/100007905886900,,,Abdulla Al Mamun,,Student,?stemen,Cox's Bazar  Bangladesh,Cox's Bazar  Bangladesh,Ukhiya Govt. High School - UGHS,100007905886900@facebook.com,0,0,0,1/1/0001 12:00:00 AM,1/1/0001 12:00:00 AM,Single,,,,,,,,,
    100004836301448,,,+97455128136,,,Abdulla Al,Mamun,male,https://www.facebook.com/abdullaal.mamun.3363334,,abdullaal.mamun.3363334,Abdulla Al Mamun,,privet service,allrounder,Feni  Barisl  Bangladesh,Doha,Shaheen Academy School & College Feni,abdullaal.mamun.3363334@facebook.com,0,0,0,1/1/0001 12:00:00 AM,1/1/0001 12:00:00 AM,,,,,,,,,,
    100014233666120,,,+97450520675,,,Abdulla Hil,Maruf Molla,male,https://www.facebook.com/abdullahil.marufmolla.58,,abdullahil.marufmolla.58,Abdulla Hil Maruf Molla,,HVAC Technician,,Diamond Harbour,Diamond Harbour,Fakir Chand College,abdullahil.marufmolla.58@facebook.com,0,0,0,1/1/0001 12:00:00 AM,1/1/0001 12:00:00 AM,,,,,,,,,,
    100001620759290,,,+97455133157,,,Aazath,Mohamed,male,https://www.facebook.com/Aazath,,Aazath,Aazath Mohamed,No thing 2 say about me but 1 day ?,classical palace interior design doha  qatar,Systems Engineer,Kaduwela  Sri Lanka,Doha,PLMCC,Aazath@facebook.com,0,0,0,1/1/0001 12:00:00 AM,1/1/0001 12:00:00 AM,Single,,,,,,,,,
    ??100011903084137,,,+97433912145,,,z?0000000000000000000000000z? z?0000000000000000000000000z?,z?0000000000000000000000000z? z?0000000000000000000000000z?,male,https://www.facebook.com/ghazok.baloch,,ghazok.baloch,z?0000000000000000000000000z? z?0000000000000000000000000z? z?0000000000000000000000000z? z?0000000000000000000000000z?,,,,,,,ghazok.baloch@facebook.com,0,0,0,1/1/0001 12:00:00 AM,1/1/0001 12:00:00 AM,,,,,,,,,,
    
    */
    private function processComa1(array $parts)
    {
        $data = [];

        $data['parser'] = 'coma1';

        if (isset($parts[0])) {
            $data['fb_id'] = $parts[0];
        }

        if (isset($parts[3])) {
            $data['phone'] = $parts[3];
        }

        if (isset($parts[6])) {
            $data['first_name'] = $parts[6];
        }

        if (isset($parts[7])) {
            $data['last_name'] = $parts[7];
        }

        if (isset($parts[8])) {
            if ($parts[8] == 'male') {
                $data['gender'] = 'M';
            } elseif ($parts[8] == 'female') {
                $data['gender'] = 'F';
            }
        }

        if (isset($parts[13])) {
            $data['bio'] = $parts[13];
        }

        if (isset($parts[14])) {
            $data['work'] = $parts[14];
        }

        if (isset($parts[15])) {
            $data['work_role'] = $parts[15];
        }

        if (isset($parts[16])) {
            $data['location'] = $parts[16];
        }

        if (isset($parts[17])) {
            $data['hometown'] = $parts[17];
        }

        if (isset($parts[18])) {
            $data['school'] = $parts[18];
        }

        if (isset($parts[19])) {
            $data['email'] = $parts[19];
        }

        if (isset($parts[25])) {
            $data['relationship_status'] = $parts[25];
        }

        return $data;
    }

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
    */
    private function processGeneric(array $parts)
    {
        $data = [];

        $data['parser'] = 'generic';

        if (isset($parts[0])) {
            $data['phone'] = $parts[0];
        }

        if (isset($parts[1])) {
            $data['fb_id'] = $parts[1];
        }

        if (isset($parts[2])) {
            $data['first_name'] = $parts[2];
        }

        if (isset($parts[3])) {
            $data['last_name'] = $parts[3];
        }

        if (isset($parts[4])) {
            if ($parts[4] == 'male') {
                $data['gender'] = 'M';
            } elseif ($parts[4] == 'female') {
                $data['gender'] = 'F';
            }
        }

        if (isset($parts[5])) {
            $data['location'] = $parts[5];
        }

        if (isset($parts[6])) {
            $data['hometown'] = $parts[6];
        }

        if (isset($parts[7])) {
            $data['relationship_status'] = $parts[7];
        }

        if (isset($parts[8])) {
            $data['work'] = $parts[8];
        }

        if (isset($parts[9])) {
            $parts[9] = trim($parts[9]);
            if (preg_match('/(\d{2})?\/\d{2}\/\d{4}/', $parts[9])) {
                $data['work_date'] = Carbon::createFromFormat('m/d/Y', $parts[9])->format('Y-m-d');
            }
        }

        if (isset($parts[10])) {
            $data['email'] = $parts[10];
        }

        if (isset($parts[11])) {
            $parts[11] = trim($parts[11]);
            if (preg_match('/(\d{2})?\/\d{2}\/\d{4}/', $parts[11])) {
                $data['birthdate'] = Carbon::createFromFormat('m/d/Y', $parts[11])->format('Y-m-d');
            }
        }

        return $data;
    }
}

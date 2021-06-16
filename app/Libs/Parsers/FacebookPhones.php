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

        $parts = str_getcsv($line, $this->separator);
        if (!count($parts)) {
            return false;
        }

        $parts = array_map('trim', $parts);

        // Remove useless values
        $parts = Arr::where($parts, function ($value, $key) {
            return $value && $value != 'None' && $value != 'Location*';
        });

        $data = [];
        $map = [];

        // Debug
        // $data = $parts;

        // Phone on part 3
        if (isset($parts[3]) && str_starts_with($parts[3], '+')) {
            // Status:
            // Email on part 2
            if (isset($parts[2]) && strstr($parts[2], '@')) {
                $data['parser'] = 'A';
                $map = [
                    0 => 'fb_id',
                    2 => 'email',
                    3 => 'phone',
                ];

                // Status: No lines processed
                // Date on part 6
                if (isset($parts[6]) && strstr($parts[6], '/')) {
                    $data['parser'] = 'B';
                    $map += [
                        4 => 'first_name',
                        5 => 'last_name',
                        8 => 'gender',
                        13 => 'location',
                        14 => 'hometown',
                        22 => 'relationship_status',
                    ];
                    if (preg_match('/(\d{2})?\/\d{2}\/\d{4}/', $parts[6])) {
                        $data['birthdate'] = Carbon::createFromFormat('m/d/Y', $parts[6])->format('Y-m-d');
                    }
                }
                // Status: Verified (samples)
                // Date on part 5
                elseif (isset($parts[5]) && strstr($parts[5], '/')) {
                    $data['parser'] = 'N';
                    $map += [
                        6 => 'first_name',
                        7 => 'last_name',
                        8 => 'gender',
                        11 => 'username',
                        13 => 'bio',
                        14 => 'work',
                        16 => 'location',
                        17 => 'hometown',
                        18 => 'school',
                        19 => 'email',
                        25 => 'relationship_status',
                    ];

                    $data['birthdate'] = date('Y-m-d', strtotime($parts[5]));
                }
                // Status: Needs attention
                else {
                    $data['parser'] = 'C';
                    $map += [
                        4 => 'first_name',
                        5 => 'last_name',
                        6 => 'gender',
                        9 => 'username',
                        11 => 'email', // not always
                    ];
                    if (isset($parts[10]) && $parts[10] && isset($data['first_name']) && isset($data['last_name']) && $parts[10] == $data['first_name'] . ' ' . $data['last_name']) {
                        $map += [
                            12 => 'location',
                            13 => 'hometown',
                        ];
                    }
                }
            }
            // Status: Verified (samples)
            else {
                $data['parser'] = 'M';
                $map = [
                    0 => 'fb_id',
                    3 => 'phone',
                    6 => 'first_name',
                    7 => 'last_name',
                    8 => 'gender',
                    13 => 'bio',
                    14 => 'work',
                    15 => 'work_role',
                    16 => 'location',
                    17 => 'hometown',
                    18 => 'school',
                    19 => 'email',
                    25 => 'relationship_status',
                ];
            }
        }

        // Status: No lines processed
        // Phone on part 1
        elseif (isset($parts[1]) && str_starts_with($parts[1], '+')) {
            $data['parser'] = 'D';
            $map = [
                0 => 'fb_id',
                1 => 'phone',
                2 => 'first_name',
                3 => 'last_name',
                4 => 'gender',
            ];

            // Status: 
            // Facebook URL on part 7
            if (isset($parts[7]) && strstr($parts[7], 'facebook.com')) {
                $data['parser'] = 'P';
                $map = [
                    0 => 'fb_id',
                    1 => 'phone',
                    2 => 'religion',
                    4 => 'first_name',
                    5 => 'last_name',
                    6 => 'gender',
                    9 => 'username',
                    11 => 'bio',
                    12 => 'work',
                    13 => 'work_role',
                    14 => 'location',
                    15 => 'hometown',
                    16 => 'school',
                    17 => 'email',
                    23 => 'relationship_status',
                ];
            }

            // Facebook URL on part 5
            elseif (isset($parts[5]) && strstr($parts[5], 'facebook.com')) {
                // Status: Verified (samples)
                // Email in part 9
                if (isset($parts[9]) && strstr($parts[9], '@')) {
                    $data['parser'] = 'E';
                    $map += [
                        7 => 'work',
                        8 => 'location',
                        9 => 'email',
                        15 => 'relationship_status',
                    ];
                }

                // Status: Verified (samples) (not perfect)
                // Email on part 11
                elseif (isset($parts[11]) && strstr($parts[11], '@')) {
                    $data['parser'] = 'F';
                    $map += [
                        7 => 'username',
                        // Sometimes 8 position is location, some others work...
                        // 8 => 'location',
                        // 9 => 'hometown',
                        8 => 'work',
                        9 => 'location',
                        10 => 'school',
                        11 => 'email',
                        17 => 'relationship_status',
                    ];
                }

                // Status: Verified (samples)
                // Email on part 13
                elseif (isset($parts[13]) && strstr($parts[13], '@')) {
                    $data['parser'] = 'J';
                    $map += [
                        7 => 'username',
                        // Sometimes 8 position is location, some others work...
                        8 => 'work',
                        9 => 'work_role',
                        10 => 'location',
                        11 => 'hometown',
                        12 => 'school',
                        13 => 'email',
                        19 => 'relationship_status',
                    ];
                }

                // Status: Verified (samples)
                else {
                    $data['parser'] = 'I';
                    $map += [
                        9 => 'bio',
                        10 => 'work',
                        11 => 'work_role',
                        12 => 'location',
                        13 => 'hometown',
                        14 => 'school',
                        15 => 'email',
                        21 => 'relationship_status',
                    ];
                }
            }
            // Status: Needs attention
            // Facebook URL on part 7
            elseif (isset($parts[7]) && strstr($parts[7], 'facebook.com')) {
                $data['parser'] = 'H';
                $map = [
                    2 => 'work',
                    4 => 'first_name',
                    5 => 'last_name',
                    6 => 'gender',
                    8 => 'full_name',
                    9 => 'username',
                    10 => 'location',
                    11 => 'hometown',
                    // Sometimes from 10 to 14 position is location
                    17 => 'relationship_status',
                    19 => 'relationship_status',
                    23 => 'relationship_status',
                ];

                if (isset($parts[3])) {
                    $data['birthdate'] = date('Y-m-d', strtotime($parts[3]));
                }
            }

            // Status: Verified (samples)
            // Lang on part 8
            elseif (isset($parts[8]) && strstr($parts[8], '_')) {
                $data['parser'] = 'O';
                $map += [
                    7 => 'gender',
                    8 => 'lang',
                    9 => 'hometown',
                    10 => 'location',
                ];
                if (isset($parts[5]) && isset($parts[6])) {
                    // 5: December 3
                    // 6: 1984
                    $data['birthdate'] = date('Y-m-d', strtotime($parts[5] . ' ' . $parts[6]));
                }
            }

            // Status: Verified (samples)
            else {
                $data['parser'] = 'G';
                $map += [
                    6 => 'gender',
                    7 => 'lang',
                    8 => 'hometown',
                    10 => 'location',
                ];
                if (isset($parts[5])) {
                    try {
                        $data['birthdate'] = Carbon::createFromFormat('m/d/Y', $parts[5])->format('Y-m-d');
                    } catch (\Exception $e) {
                        //
                    }
                }
            }
        }

        // Status: Verified (samples)
        else {
            $data['parser'] = 'K';
            $map = [
                0 => 'phone',
                1 => 'fb_id',
                2 => 'first_name',
                3 => 'last_name',
                4 => 'gender',
                5 => 'location',
                6 => 'hometown',
                7 => 'relationship_status',
                8 => 'work',
                // 9: Sometimes it stores a date, but not sure what is that, is quite recent
                10 => 'email',
            ];
            if (isset($parts[9])) {
                $parts[9] = trim($parts[9]);
                if (preg_match('/(\d{2})?\/\d{2}\/\d{4}^/', $parts[9])) {
                    $data['work_date'] = Carbon::createFromFormat('m/d/Y', $parts[9])->format('Y-m-d');
                }
            }
            if (isset($parts[11])) {
                $parts[11] = trim($parts[11]);
                if (preg_match('/(\d{2})?\/\d{2}\/\d{4}^/', $parts[11])) {
                    $data['birthdate'] = Carbon::createFromFormat('m/d/Y', $parts[11])->format('Y-m-d');
                }
            }
        }

        if (!empty($map)) {
            $this->parseArray($data, $parts, $map);
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

    protected function parseArray(array &$data, array $parts, array $map)
    {
        foreach ($map as $key => $field) {
            if ($field == 'gender') {
                $this->storeGender($data, $parts, $key);
            } elseif ($field == 'relationship_status') {
                $this->storeRelationship($data, $parts, $key);
            } else {
                $this->store($data, $field, $parts, $key);
            }
        }

        return $data;
    }

    private function store(array &$data, string $field, array $array, int $key)
    {
        if (isset($array[$key])) {
            $data[$field] = $array[$key];
        }
    }

    private function storeGender(array &$data, array $array, int $key)
    {
        if (isset($array[$key])) {
            if ($array[$key] == 'male') {
                $data['gender'] = 'M';
            } elseif ($array[$key] == 'female') {
                $data['gender'] = 'F';
            }
        }
    }

    private function storeRelationship(array &$data, array $array, int $key)
    {
        if (isset($array[$key])) {
            $data['relationship_status'] = str_replace([
                'Lajang',
                'Menikah',
                'Berpacaran',
                'Menjanda/Menduda',
                'Berhubungan sipil',
                'Bertunangan',
                'Menjalin hubungan tanpa status',
                'Rumit',
            ], [
                'Single',
                'Married',
                'In a relationship',
                'Widowed',
                'In a civil union',
                'Engaged',
                'In an open relationship',
                'It\'s complicated',
            ], $array[$key]);
        }
    }
}

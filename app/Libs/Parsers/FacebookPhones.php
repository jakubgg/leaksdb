<?php

namespace App\Libs\Parsers;

use App\Libs\Contracts\Abstracts\Parser;
use Carbon\Carbon;
use Illuminate\Support\Arr;

/**
 * Facebook Phones 2021
 * 
 * Description:
 *  In April 2021, a large data set of over 500 million Facebook users was 
 *  made freely available for download. Encompassing approximately 20% of Facebook's 
 *  subscribers, the data was allegedly obtained by exploiting a vulnerability Facebook 
 *  advises they rectified in August 2019. The primary value of the data is the association 
 *  of phone numbers to identities; whilst each record included phone, only 2.5 million 
 *  contained an email address. Most records contained names and genders with many 
 *  also including dates of birth, location, relationship status and employer.
 * 
 * Data:
 *  - Dates of birth, Email addresses, Employers, Genders, Geographic locations, 
 *    Names, Phone numbers, Relationship statuses
 * 
 * Records: 
 *  - Official: 533 M (509,458,528)
 *  - Lines: 370 M
 * 
 * Formats: 
 *  - Hell of a pain
 * 
 * References: 
 *  - https://www.troyhunt.com/the-facebook-phone-numbers-are-now-searchable-in-have-i-been-pwned/
 *  - https://twitter.com/UnderTheBreach/status/1378314424239460352
 */
class FacebookPhones extends Parser
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
            // $data['record'] = $originalLine;

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

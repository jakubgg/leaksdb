<?php

namespace App\Libs\Parsers;

use App\Libs\Contracts\Abstracts\Parser;
use App\Libs\Contracts\Interfaces\Parser as ParserInterface;

/**
 * Phone House Spain 2021 leak
 * 
 * Records: 16 M (or 5 M ?)
 *  - Policy Receipts: 2.672.025
 *  - Costumer Data: 12.797.782
 * 
 * Formats: 
 *  - csv
 * 
 * References: 
 *  - https://unaaldia.hispasec.com/2021/04/filtrados-13-millones-de-datos-de-phone-house.html
 *  - https://haveibeenpwned.com/PwnedWebsites#PhoneHouse
 *  - https://thetechzone.online/cyberattack-on-phone-house-with-ransomware-and-possible-data-breach/
 */
class PhoneHouse extends Parser implements ParserInterface
{
    /**
     * {@inheritdoc }
     */
    public function processLine(string $line)
    {
        $res = str_getcsv($line);

        if (count($res) == 67) {
            return $this->parseCostumerData($res);
        } elseif (count($res) == 7) {
            return $this->parsePolicyReceipts($res);
        }
    }

    private function parseCostumerData(array $res)
    {
        $data = [];

        if ($res[1]) {
            $data['id'] = $res[1];
        }
        if ($res[4]) {
            $data['email'] = mb_strtolower($res[4]);
        }
        if ($res[6]) {
            $data['phone'] = $res[6];
        }
        if ($res[8]) {
            $data['phone2'] = $res[8];
        }
        if ($res[10]) {
            $data['phone3'] = $res[10];
        }
        if ($res[12]) {
            $data['gender'] = $res[12];
        }
        if ($res[14]) {
            $data['first_name'] = mb_convert_case(mb_strtolower($res[14]), MB_CASE_TITLE, "UTF-8");
        }
        if ($res[15]) {
            $data['last_name'] = mb_convert_case(mb_strtolower($res[15]), MB_CASE_TITLE, "UTF-8");
        }
        if ($res[16]) {
            $data['birthdate'] = date('Y-m-d', strtotime($res[16]));
        }

        $location = [];
        for ($i = 18; $i <= 23; $i++) {
            if ($res[$i]) {
                $location[] = $res[$i];
            }
        }
        if (!empty($location)) {
            $data['location'] = mb_convert_case(mb_strtolower(implode(', ', $location)), MB_CASE_TITLE, "UTF-8");
        }

        if ($res[21]) {
            $data['city'] = mb_convert_case(mb_strtolower($res[21]), MB_CASE_TITLE, "UTF-8");
        }
        if ($res[23]) {
            $data['zip'] = $res[23];
        }
        if ($res[45]) {
            $data['nationality'] = mb_convert_case(mb_strtolower($res[45]), MB_CASE_TITLE, "UTF-8");
        }
        if ($res[44]) {
            $data['nationality_code'] = $res[44];
        }

        return $data;
    }

    /*
    [0] => 446
    [1] => 11920380D
    [2] => ROBERTO
    [3] => ALONSO
    [4] => CASTELLANOS
    [5] => AVIVA  VIDA  Y PENSIONES S.A. DE SEGUROS
    [6] => ES7600814356740001007306
    */
    private function parsePolicyReceipts(array $res)
    {
        $data = [];

        if ($res[1]) {
            $data['id'] = $res[1];
        }
        if ($res[2]) {
            $data['first_name'] = mb_convert_case(mb_strtolower($res[2]), MB_CASE_TITLE, "UTF-8");
        }
        $lastname = [];
        if ($res[3]) {
            $lastname[] = $res[3];
        }
        if ($res[4]) {
            $lastname[] = $res[4];
        }
        if (!empty($lastname)) {
            $data['last_name'] = mb_convert_case(mb_strtolower(implode(' ', $lastname)), MB_CASE_TITLE, "UTF-8");
        }
        if ($res[5]) {
            $data['location'] = mb_convert_case(mb_strtolower($res[5]), MB_CASE_TITLE, "UTF-8");
        }
        if (($res[6])) {
            $data['iban'] = $res[6];
        }

        return $data;
    }
}

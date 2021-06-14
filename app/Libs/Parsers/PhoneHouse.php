<?php

namespace App\Libs\Parsers;

use App\Libs\Contracts\Parser;

/*

"SCV_ID","LEGAL_IDENTIFIER","TYPE","SCV_MARKETING_FLAG","SCV_EMAIL_ADDRESS","VALID_EMAIL_ADDRESS_FLAG","SCV_PHONE1","VALID_PHONE1_FLAG","SCV_PHONE2","VALID_PHONE2_FLAG","SCV_PHONE3","VALID_PHONE3_FLAG","CUSTOMER_GENDER","CUSTOMER_TITLE","CUSTOMER_FORENAME","CUSTOMER_SURNAME","BIRTH_DATE","ROAD_TYPE","STREET","NUM","FLOOR","CITY","PROVINCE","POSTAL_CODE","DTR_SOURCE","DTR_SOURCE_DETAILS","DECEASED","PROSPECT_FLAG","CONTROL_GROUP","CONTROL_GROUP_DATE","CREATED_DATE","CREATED_BY","UPDATED_DATE","UPDATED_BY","DWH_CREATED_DATE","DWH_CREATED_DATE_KEY","DWH_UPDATED_DATE","DWH_UPDATED_DATE_KEY","DWH_DELETED","DWH_UNSUBSCRIBED","DTR_BEST_BRANCH_NUMBER","DTR_BEST_BRANCH_NAME","DTR_OB_LAST_CALL_DATE","DTR_OB_LAST_CALL_CAMPAIGN","NATIONALITY_CODE","NATIONALITY_NAME","NPS_FLAG","NPS_UPDATED_DATE","NPS_CONTACT_DATE","NPS_CONTACT_DATE_KEY","NPS_CONTACT_TYPE","NPS_SCORE","NPS_PHONE","SH_FLAG","SH_PRODUCT_FLAG","BCN","VALID_BCN","DFL_CUS_SMS_PERMISSION","DFL_CUS_OB_PERMISSION","DFL_CUS_EMAIL_PERMISSION","DFL_OB_PERM_DATE","DFL_SMS_PERM_DATE","DFL_EMAIL_PERM_DATE","COMUNICACION_TERCEROS","PERFIL_COMERCIAL","COMUNICACION_PH","GDPR_DATE"
2500320,AAA502007,R,1,a@a.com,N,"627850514",Y,"923289039",N,,N,F,SRA,ISABEL,SERNA GONZALEZ,1983-04-09 00:00:00,,VALDEPEGA,,,CABRERIZOS,SALAMANCA,"37193",PIE,Cliente de sistema Pie,0,N,N,2006-06-21 00:00:00,2006-06-20 00:00:00,MIG_UNICA_SCV,2014-12-17 00:00:00,,2016-12-15 13:33:03,"20161215",2016-12-15 13:33:03,"20161215",0,0,8155,"OSUNA, SAN AGUSTIN 9 F",,,ES,España,0,,,,,,,0,0,"627850514",Y,0,1,0,2018-04-19 22:11:02,2018-04-19 22:11:02,2018-04-19 22:11:02,0,0,0,
955157,AAA925848,R,1,arq.lopezfacundo@gmail.com,N,"983047346",N,"983047346",N,,N,M,SR,FACUNDO JOSE,LOPEZ ,1989-05-15 00:00:00,,JOAQUIN VELASCO MARTIN,4,"4D",VALLADOLID,Valladolid,"47015",PIE,Cliente sistema PIE,0,N,N,2019-10-28 13:38:56,2013-09-24 00:00:00,MIG_UNICA_SCV,2019-10-28 13:38:56,MK_DATA,2016-12-15 01:54:51,"20161215",2019-10-28 13:38:56,"20191028",0,0,113,Santiago 16(Valladolid)WL,,,AR,Argentina,0,,,,,,,0,0,"347",N,1,1,1,2018-04-19 22:11:02,2018-04-19 22:11:02,2018-04-19 22:11:02,0,0,1,2018-05-22 00:00:00

[1] => LEGAL_IDENTIFIER (ID)
[4] => SCV_EMAIL_ADDRESS (email)
[6] => SCV_PHONE1 (phone)
[8] => SCV_PHONE2 (phone2)
[10] => SCV_PHONE3 (phone3)
[12] => CUSTOMER_GENDER (gender) (M, F)
[14] => CUSTOMER_FORENAME (first_name)
[15] => CUSTOMER_SURNAME (last_name)
[16] => BIRTH_DATE (birthdate) (1989-05-15 00:00:00)
[18] => STREET
[19] => NUM
[20] => FLOOR
[21] => CITY
[22] => PROVINCE
[23] => POSTAL_CODE
[44] => NATIONALITY_CODE
[45] => NATIONALITY_NAME

*/

class PhoneHouse implements Parser
{
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
            if ($res[12] == 'M') {
                $data['gender'] = 'male';
            } else {
                $data['gender'] = 'female';
            }
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

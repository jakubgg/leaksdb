<?php

namespace App\Libs\Parsers;

use App\Libs\Contracts\Abstracts\Parser;

/**
 * Bank Mellat 2021
 * 
 * Description: 
 *  New leak: Database containing information of 33 million real and legal 
 *  customers of Bank Mellat, including national code, account number, name, 
 *  surname, father’s name, ID number, date of birth, city, province, city of 
 *  birth, province of birth, address, Card number and mobile phone number
 * 
 * Records: 
 *  - Official: 33 M
 *  - Lines: 700,0007 (?)
 * 
 * Download: 
 *  - http://3kp6j22pz3zkv76yutctosa6djpj4yib2icvdqxucdaxxedumhqicpad.onion/33m-bank-mellat-iran/
 *  - https://anonfiles.com/T2Fcs5w9u0/bamelat_zip
 * 
 * References: 
 *  - https://hacknotice.com/2021/05/15/33m-bank-mellat-iran/
 */
class BankMellat extends Parser
{
    /**
     * {@inheritdoc }
     */
    protected array $extensions = ['txt'];

    /**
     * {@inheritdoc }
     */
    protected $separator = ':';

    /**
     * {@inheritdoc }
     */
    public function processLine(string $line)
    {
        // TODO
    }
}

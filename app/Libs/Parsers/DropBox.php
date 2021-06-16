<?php

namespace App\Libs\Parsers;

use App\Libs\Contracts\Abstracts\Parser;
use App\Libs\Contracts\Interfaces\Parser as ParserInterface;

/*
bf_1.txt 
houstojo@bc.edu:$2a$08$OmDz2Q81G1UB.ftLUkn64eNqIkaxAtNxkXskzGboqNagetpA9Iri6
alpenandrew+dropbox@gmail.com:$2a$08$rMDGKqKPwUVn1CNGw1oo3.WleiIzSawYdvT5GOXTYX0JpzxaGWpDK
greinstein@alum.mit.edu:$2a$08$B/oozOpd8WPAesNmZLI5gembfCnCbT7ogGy1YPMZ2waUsS1kGEHe.
ztraina@gmail.com:$2a$08$f/Ztv60lgSRKIOTmXyfhdu4.UpUVWGUmu3toU37yvrWXdB05cOsua
rhenrikson@gmail.com:$2a$08$5qQxAWS3HEyQ0IWD.IuwQ.g1efNnHOBIt3Mt7fuVaInyzy4UEcihi

bf_2.txt 
lecie22@gmail.com:$2a$08$xiDuqn6BIXOZjeiB1CEPouLsW0uvNzuriEZsK4/MZETF2NsSoVI4W
briangladstein@yahoo.com:$2a$08$KjPsrCMB6pVAetjoT4TZUe6NHmKFYRkO1z9SCDeXyxGHCGxPwEHOq
jeff@magnetk.com:$2a$08$SlU1x.ypPo.6twGC/atGRuJGpwVnlt6D.JKzgIeXB3ExF52HJ89Um
sylviafeng@gmail.com:$2a$08$aPu3MtKMfqIEQV8KSETG8Ov17.fi373JRk9by0df.UYcme4IzUJ0m
mike.champion@gmail.com:$2a$08$5x9Bi/pF3THdiPO8NNTa0uOfg0OrDctJGFh5V1xUvdMF7rKEd3Xze

sha1.txt 
george@bit9.com:7315ac7ba3b60a5b053886fa49f98ed6
bob@bit9.com:098f6bcd4621d373cade4e832627b4f6
webinno@getdropbox.com:e153e234c3b06b2de8bce9130b9457ec91fb2b65
t5@slumnet.com:8a4a6eab1fc60ed8d8a2ec41c22dd445

sha2.txt
adam@viratech.com:82903e60fe318c2ccf3f810b46ca16a3c117798c
mattbrezina@gmail.com:b6fef27e53562b0d6a05308920a220b0f954083d
chris.simeone@gmail.com:420c0b1ba5f871da58914fb7e44af95381854941
releng@getdropbox.com:eb0562e8d7db11c02b660ca16681f914
Alexander.Kuo@gmail.com:6bca4286c4c10d152056fa890bb57d56cfbf102a
*/

class DropBox extends Parser implements ParserInterface
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

        $parts = array_map('trim', $parts);

        return [
            'email' => $parts[0],
            'hash' => $parts[1],
            // 'hash_type' => $this->getHashType($parts[1]),
        ];
    }

    private function getHashType($hash)
    {
        if (substr($hash, 0, 1) == '$') {
            return 'bcrypt';
        } elseif (strlen($hash) == 40) {
            return 'sha1';
        } else {
            return 'md5';
        }
    }
}

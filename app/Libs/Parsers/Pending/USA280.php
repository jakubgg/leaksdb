<?php

namespace App\Libs\Parsers\Pending;

use App\Libs\Contracts\Abstracts\Parser;

/**
 * USA 280M
 * 
 * Description:
 *  Data leaked: Full names, Home Addresses, Income/Salary, House cost, Amount of children, 
 *  Phone Numbers, Email Addresses (Some people have multiple linked), Amount of pets, and a lot 
 *  of other data. Look at the CSV headers. Note: There are no passwords in this leak.
 * 
 * Records: 
 *  - Official: 280M (250.808.966)
 * 
 * Formats: 
 * 
 * Download: 
 *  - http://3kp6j22pz3zkv76yutctosa6djpj4yib2icvdqxucdaxxedumhqicpad.onion/usa-280m/
 */
class USA280 extends Parser
{
    /**
     * {@inheritdoc }
     */
    protected array $extensions = ['csv'];

    /**
     * {@inheritdoc }
     */
    protected $separator = ':';

    /**
     * {@inheritdoc }
     */
    public function processLine(string $line)
    {
        $parts = str_getcsv($line, ',', '"');

        $map = [
            3 => 'first_name',
            6 => 'last_name',
            9 => 'address',
            12 => 'city',
            15 => 'state',
            87 => 'phone',
            126 => 'phone2',
            128 => 'email',
            183 => 'email2',
            184 => 'email3',
            185 => 'email4',
            186 => 'email5',
            139 => 'children',
            23 => 'lat',
            24 => 'lon',
            30 => 'gender',
            50 => 'income',
        ];

        $data = $this->parse($map, $parts);

        $data['zip'] = $parts[16] . $parts[17];

        return $data;
    }
}

/*

(
    [0] => HH_ID
    [1] => ID
    [2] => First_Name_01
    [3] => alphafirstname_sort
    [4] => Phonetic_First_Name
    [5] => Last_Name_01
    [6] => alphalastname_sort
    [7] => Phonetic_Last_Name
    [8] => Address
    [9] => alphaaddress_sort
    [10] => City
    [11] => CITY_PHRASE
    [12] => alphacity_sort
    [13] => Cities
    [14] => State
    [15] => alphastate_sort
    [16] => ZIP
    [17] => ZIP4
    [18] => Carrier_Route
    [19] => Delivery_Point
    [20] => Mail_Score_Code
    [21] => Geo_Level_Code
    [22] => Latitude
    [23] => Longitude
    [24] => Time_Zone_Code
    [25] => County_Code
    [26] => County_Description
    [27] => CBSA_Code
    [28] => CBSA_Description
    [29] => Scrubbed_Phoneable_Flag
    [30] => Ind_Gender_Code
    [31] => Ind_Date_Of_Birth_Year
    [32] => Ind_Age
    [33] => Ind_Household_Rank_Code
    [34] => Ind_Ethnic_Code
    [35] => Ind_Political_Party_Code
    [36] => Home_Value_Code
    [37] => Home_Value_Description
    [38] => Home_Median_Value_Code
    [39] => Home_Median_Value_Description
    [40] => Home_Owner_Renter_Code
    [41] => Length_Of_Residence_Code
    [42] => Home_Square_Footage
    [43] => Home_Square_Footage_Code
    [44] => Home_Dwelling_Type_Code
    [45] => Median_Income_Code
    [46] => Median_Income_Description
    [47] => Income_Code
    [48] => Income_Description
    [49] => NetWorth_Code
    [50] => Credit_Capacity
    [51] => Credit_Capacity_Code
    [52] => Credit_Capacity_Description
    [53] => Donor_Capacity_Code
    [54] => Marital_Status_Code
    [55] => Delivery_Point_CheckDigit
    [56] => Address_Number
    [57] => Pre_Direction
    [58] => Street_Name
    [59] => Post_Direction
    [60] => City_2
    [61] => State_City
    [62] => State_City_2
    [63] => Address_ID
    [64] => PO_Flag
    [65] => Mailable_Flag
    [66] => Location_Unique_Flag
    [67] => Number_of_Bedrooms
    [68] => Number_of_Bathrooms
    [69] => ProductionDate
    [70] => Ind_Age_Code
    [71] => Lat_Long
    [72] => Geo_Lat_Long
    [73] => Marketing
    [74] => Mailable
    [75] => Phoneable
    [76] => Mailable_Phoneable
    [77] => ZIP9
    [78] => Zip11
    [79] => Zip4Exists
    [80] => Address_Master
    [81] => LS_Green_Living_Flag
    [82] => _version_
    [83] => Lat_Long_0_coordinate
    [84] => Lat_Long_1_coordinate
    [85] => Middle_Name_01
    [86] => Area_Code
    [87] => Phone
    [88] => DNC_Flag
    [89] => CC_User_Flag
    [90] => Credit_Card_Mail_Order_Buyers
    [91] => CC_Bank_Flag
    [92] => CC_Gas_Dept_Retail_Flag
    [93] => CC_Unknown_Flag
    [94] => CC_Upscale_Dept_Flag
    [95] => Charitable_Flag
    [96] => Donor
    [97] => Christian_Family_Flag
    [98] => Family_Religion_Politics
    [99] => Reading_General_Flag
    [100] => Reading
    [101] => Reading_Magazines_Flag
    [102] => Cooking_General_Flag
    [103] => Cooking_Food
    [104] => Cooking_Gourmet_Flag
    [105] => Hobbies_Auto_Work_Flag
    [106] => Hobby_Interest
    [107] => Hobbies_Sewing_Knitting_Needlework_Flag
    [108] => Hobbies_Gardening_Flag
    [109] => Home_Improvement
    [110] => Home_Furnishings_Decorating_Flag
    [111] => Mail_Order_Buyer_Flag
    [112] => Mail_Order_Responder_Flag
    [113] => PC_Owner_Flag
    [114] => Computers_Electronics
    [115] => Consumer_Electronics_Flag
    [116] => Most_Recent_Home_Purchase_Date_Flag
    [117] => Home_Property_Type_Code_02
    [118] => Home_Purchase_Date
    [119] => Home_Purchase_Year
    [120] => Travel_RV_Flag
    [121] => Travel
    [122] => Outdoor_Hunting_Shooting_Flag
    [123] => Outdoor_Enthusiast
    [124] => Vehicle_Owned_Code
    [125] => Street_Suffix
    [126] => CellPhone
    [127] => Email_Present_Flag
    [128] => Email
    [129] => Ind_Date_Of_Birth_Month
    [130] => Political_Flag
    [131] => Political_Affiliation_Donor
    [132] => Email_01_MD5
    [133] => Home_Built_Year
    [134] => Home_Built_Year_Code
    [135] => Home_Built_Year_Description
    [136] => Home_Equity_Available_Code
    [137] => Home_Equity_Available_Description
    [138] => Health_Flag
    [139] => Number_Children_Code
    [140] => Children_Present_Flag
    [141] => Ent_Sweepstakes_Contests_Flag
    [142] => Investing_Finance
    [143] => Investments_Personal_Flag
    [144] => Movie_Collector_Flag
    [145] => Movie_Music
    [146] => Outdoor_Fishing_Flag
    [147] => Sports_Golf_Flag
    [148] => Sports
    [149] => Sports_Motorcycling_Flag
    [150] => Dog_Owner_Flag
    [151] => Animals_Pets
    [152] => LS_Home_Living_Flag
    [153] => LS_Highbrow_Living_Flag
    [154] => Secondary_Name
    [155] => Secondary_Number
    [156] => Music_Listener_Flag
    [157] => Self_Education_Online_Flag
    [158] => Career_Self_Improvement
    [159] => Ind_Occupation_Code
    [160] => Home_Loan_To_Value_Code
    [161] => Animal_Welfare_Flag
    [162] => Reading_Religious_Inspirational_Flag
    [163] => Foods_Natural_Flag
    [164] => Food_Wines_Flag
    [165] => Travel_Domestic_Flag
    [166] => Self_Exercise_Running_Jogging_Flag
    [167] => Health_and_Fitness
    [168] => Self_Exercise_Walking_Flag
    [169] => Self_Exercise_Aerobic_Flag
    [170] => Self_Dieting_Weight_Loss_Flag
    [171] => Hobbies_Crafts_Flag
    [172] => Spectator_Sports_Football_Flag
    [173] => Spectator_Sports_Basketball_Flag
    [174] => Sports_Equestrian_Flag
    [175] => Environmental_Issues_Flag
    [176] => Cat_Owner_Flag
    [177] => LS_Sporty_Living_Flag
    [178] => TV_Satellite_Dish_Flag
    [179] => Childrens_Flag
    [180] => Self_Health_Medical_Flag
    [181] => Spectator_Sports_Baseball_Flag
    [182] => Grandchildren_Flag
    [183] => Email_02
    [184] => Email_03
    [185] => Email_04
    [186] => Email_05
    [187] => Email_02_MD5
    [188] => Email_03_MD5
    [189] => Email_04_MD5
    [190] => Email_05_MD5
    [191] => Recently_Moved_Flag
    [192] => Recently_Moved_Year
    [193] => Recently_Moved_Month
    [194] => Investments_Real_Estate_Flag
    [195] => Investments_Stocks_Bonds_Flag
    [196] => Investments_Foreign_Flag
    [197] => Money_Seekers_Flag
    [198] => LS_Broader_Living_Flag
    [199] => Reading_Audio_Books_Flag
    [200] => Veterans_Flag
    [201] => Outdoor_Camping_Hiking_Flag
    [202] => Sports_Skiing_Flag
    [203] => CC_Premium_Flag
    [204] => Religious_Flag
    [205] => Ent_Theater_Performing_Arts_Flag
    [206] => Arts_History_Science
    [207] => Ent_Arts_Flag
    [208] => Reading_Science_Fiction_Flag
    [209] => Travel_International_Flag
    [210] => Collectibles_General_Flag
    [211] => Collectibles_And_Antiques
    [212] => Collectibles_Arts_Flag
    [213] => Collectibles_Antiques_Flag
    [214] => Spectator_Sports_TV_Sports_Flag
    [215] => Smoking_Tobacco_Flag
    [216] => Ailments
    [217] => LS_Upscale_Living_Flag
    [218] => Hobbies_Photography_Flag
    [219] => Childrens_Interests_Flag
    [220] => Outdoor_Boating_Sailing_Flag
    [221] => Self_Improvement_Flag
    [222] => Collector_Avid_Flag
    [223] => Sports_Collectibles_Memorabilia_Flag
    [224] => CC_Travel_Entertainment_Flag
    [225] => Self_Career_Improvement_Flag
    [226] => Hobbies_Woodworking_Flag
    [227] => Other_Pet_Owner_Flag
    [228] => Arts_Cultural_Flag
    [229] => Veteran_Present_HH_Flag
    [230] => Reading_Financial_Newsletter_Flag
    [231] => Travel_Cruises_Flag
    [232] => Current_Affairs_Politics_Flag
    [233] => LS_Common_Living_Flag
    [234] => LS_Professional_Living_Flag
    [235] => Music_Collector_Flag
    [236] => Religious_Inspirational_Flag
    [237] => Music_Player_Flag
    [238] => Collectibles_Coins_Flag
    [239] => Beauty_Cosmetics_Flag
    [240] => Beauty_Fashion
    [241] => Home_Improvement_DIY_Flag
    [242] => International_Aid_Flag
    [243] => Spectator_Sports_Soccer_Flag
    [244] => LS_DIY_Living_Flag
    [245] => Spectator_Sports_NASCAR_Flag
    [246] => New_Home_Owner_Flag
    [247] => Political_Conservative_Flag
    [248] => Ent_Gaming_Casino_Flag
    [249] => Sports_Tennis_Flag
    [250] => Hobbies_Science_Space_Flag
    [251] => Political_Liberal_Flag
    [252] => Mail_Order_Donor_Flag
    [253] => Music_Home_Stereo_Flag
    [254] => Hobbies_Games_Board_Puzzles_Flag
    [255] => Computer_And_Video_Games_Puzzles
    [256] => Games_Video_Games_Flag
    [257] => Spectator_Sports_Hockey_Flag
    [258] => Parenting_Flag
    [259] => Games_Computer_Games_Flag
    [260] => Collectibles_Stamps_Flag
    [261] => Spectator_Sports_Racing_Flag
    [262] => Hobbies_History_Military_Flag
    [263] => Hobbies_Aviation_Flag
    [264] => Ailment_Orthopedic_Flag
    [265] => Ailment_Diabetic_Flag
    [266] => Environment_Wildlife_Flag
    [267] => Hobbies_House_Plant_Flag
    [268] => Truck_Owner_Flag
    [269] => Motor_Vehicles
    [270] => Veteran_Present_Ind_Flag
    [271] => Outdoor_Scuba_Diving_Flag
    [272] => Walk_Sequence
    [273] => Boat_Owner_Flag
    [274] => RV_Owner_Flag
    [275] => Ailment_Arthritis_Flag
    [276] => Ailment_Allergy_Flag
    [277] => Motorcycle_Owner_Flag
    [278] => Ailment_Senior_Flag
    [279] => Ailment_Disabled_Flag
)

*/
<?php

namespace Database\Seeders;

use App\Models\DeliveryArea;
use App\Models\DeliveryCity;
use Illuminate\Database\Seeder;

/**
 * Seeds all 64 Bangladesh districts (delivery cities) and their
 * upazilas/thanas (delivery areas) with postal codes.
 *
 * Idempotent: uses updateOrCreate / firstOrCreate so it is safe to
 * run multiple times. Existing city is_active flags are preserved.
 */
class BangladeshLocationSeeder extends Seeder
{
    // Cities active by default on first install (admin can change later)
    private array $activeByDefault = ['Dhaka', 'Chittagong', 'Feni'];

    public function run(): void
    {
        foreach ($this->data() as $cityName => $areas) {
            $city = DeliveryCity::firstOrCreate(
                ['name' => $cityName],
                ['is_active' => in_array($cityName, $this->activeByDefault, true)]
            );

            foreach ($areas as [$areaName, $postal]) {
                DeliveryArea::updateOrCreate(
                    ['delivery_city_id' => $city->id, 'name' => $areaName],
                    ['postal_code' => $postal]
                );
            }
        }
    }

    /** [city => [[area, postal], ...]] */
    private function data(): array
    {
        return [
            // ── Dhaka Division ──
            'Dhaka' => [
                ['Banani', '1213'], ['Gulshan', '1212'], ['Mirpur', '1216'], ['Dhanmondi', '1209'],
                ['Mohammadpur', '1207'], ['Uttara', '1230'], ['Badda', '1212'], ['Tejgaon', '1208'],
                ['Motijheel', '1000'], ['Ramna', '1000'], ['Khilgaon', '1219'], ['Jatrabari', '1204'],
                ['Mohakhali', '1212'], ['Bashundhara', '1229'], ['Shyamoli', '1207'], ['Wari', '1203'],
                ['Lalbagh', '1211'], ['Demra', '1361'], ['Savar', '1340'], ['Keraniganj', '1310'],
            ],
            'Gazipur' => [
                ['Gazipur Sadar', '1700'], ['Tongi', '1710'], ['Kaliakair', '1750'],
                ['Kapasia', '1730'], ['Sreepur', '1740'], ['Kaliganj', '1720'],
            ],
            'Narayanganj' => [
                ['Narayanganj Sadar', '1400'], ['Fatullah', '1421'], ['Siddhirganj', '1430'],
                ['Bandar', '1410'], ['Rupganj', '1460'], ['Araihazar', '1450'], ['Sonargaon', '1440'],
            ],
            'Tangail' => [
                ['Tangail Sadar', '1900'], ['Mirzapur', '1940'], ['Ghatail', '1980'],
                ['Kalihati', '1973'], ['Sakhipur', '1950'], ['Madhupur', '1996'], ['Bhuapur', '1960'],
            ],
            'Narsingdi' => [
                ['Narsingdi Sadar', '1600'], ['Madhabdi', '1604'], ['Palash', '1610'],
                ['Shibpur', '1620'], ['Belabo', '1640'], ['Raipura', '1630'], ['Monohardi', '1650'],
            ],
            'Munshiganj' => [
                ['Munshiganj Sadar', '1500'], ['Sreenagar', '1550'], ['Sirajdikhan', '1540'],
                ['Louhajang', '1530'], ['Gajaria', '1510'], ['Tongibari', '1520'],
            ],
            'Manikganj' => [
                ['Manikganj Sadar', '1800'], ['Singair', '1820'], ['Saturia', '1810'],
                ['Shibalaya', '1850'], ['Ghior', '1840'], ['Daulatpur', '1860'], ['Harirampur', '1830'],
            ],
            'Kishoreganj' => [
                ['Kishoreganj Sadar', '2300'], ['Bhairab', '2350'], ['Katiadi', '2330'],
                ['Bajitpur', '2336'], ['Kuliarchar', '2340'], ['Pakundia', '2326'], ['Hossainpur', '2320'],
            ],
            'Faridpur' => [
                ['Faridpur Sadar', '7800'], ['Boalmari', '7860'], ['Bhanga', '7830'],
                ['Nagarkanda', '7810'], ['Madhukhali', '7850'], ['Alfadanga', '7870'], ['Sadarpur', '7820'],
            ],
            'Gopalganj' => [
                ['Gopalganj Sadar', '8100'], ['Tungipara', '8130'], ['Kotalipara', '8110'],
                ['Muksudpur', '8140'], ['Kashiani', '8120'],
            ],
            'Madaripur' => [
                ['Madaripur Sadar', '7900'], ['Shibchar', '7920'], ['Kalkini', '7910'],
                ['Rajoir', '7930'],
            ],
            'Rajbari' => [
                ['Rajbari Sadar', '7700'], ['Goalanda', '7710'], ['Pangsha', '7730'],
                ['Baliakandi', '7720'], ['Kalukhali', '7740'],
            ],
            'Shariatpur' => [
                ['Shariatpur Sadar', '8000'], ['Naria', '8020'], ['Zajira', '8010'],
                ['Bhedarganj', '8030'], ['Damudya', '8040'], ['Gosairhat', '8050'],
            ],

            // ── Chittagong Division ──
            'Chittagong' => [
                ['Kotwali', '4000'], ['Pahartali', '4202'], ['Panchlaish', '4203'], ['Halishahar', '4216'],
                ['Agrabad', '4100'], ['Nasirabad', '4000'], ['Patenga', '4204'], ['Chandgaon', '4212'],
                ['Bayezid', '4210'], ['Hathazari', '4330'], ['Sitakunda', '4310'], ['Patiya', '4370'],
            ],
            "Cox's Bazar" => [
                ["Cox's Bazar Sadar", '4700'], ['Chakaria', '4740'], ['Teknaf', '4760'],
                ['Ukhia', '4750'], ['Ramu', '4730'], ['Maheshkhali', '4710'], ['Pekua', '4744'],
            ],
            'Cumilla' => [
                ['Cumilla Sadar', '3500'], ['Laksam', '3570'], ['Daudkandi', '3516'],
                ['Chandina', '3510'], ['Burichang', '3520'], ['Debidwar', '3530'], ['Muradnagar', '3540'],
                ['Homna', '3546'], ['Chauddagram', '3550'], ['Barura', '3560'],
            ],
            'Feni' => [
                ['Feni Sadar', '3900'], ['Chhagalnaiya', '3910'], ['Daganbhuiyan', '3920'],
                ['Parshuram', '3940'], ['Fulgazi', '3930'], ['Sonagazi', '3950'],
            ],
            'Brahmanbaria' => [
                ['Brahmanbaria Sadar', '3400'], ['Ashuganj', '3402'], ['Nabinagar', '3410'],
                ['Sarail', '3430'], ['Kasba', '3460'], ['Nasirnagar', '3441'], ['Bancharampur', '3420'],
            ],
            'Chandpur' => [
                ['Chandpur Sadar', '3600'], ['Hajiganj', '3610'], ['Matlab', '3640'],
                ['Kachua', '3630'], ['Shahrasti', '3620'], ['Faridganj', '3650'],
            ],
            'Lakshmipur' => [
                ['Lakshmipur Sadar', '3700'], ['Raipur', '3710'], ['Ramganj', '3720'],
                ['Ramgati', '3730'], ['Kamalnagar', '3733'],
            ],
            'Noakhali' => [
                ['Noakhali Sadar', '3800'], ['Begumganj', '3820'], ['Senbagh', '3860'],
                ['Companiganj', '3850'], ['Chatkhil', '3870'], ['Sonaimuri', '3827'], ['Hatiya', '3890'],
            ],
            'Khagrachhari' => [
                ['Khagrachhari Sadar', '4400'], ['Dighinala', '4420'], ['Matiranga', '4450'],
                ['Panchhari', '4410'], ['Ramgarh', '4460'], ['Mahalchhari', '4430'],
            ],
            'Rangamati' => [
                ['Rangamati Sadar', '4500'], ['Kaptai', '4530'], ['Baghaichhari', '4590'],
                ['Kawkhali', '4510'], ['Naniarchar', '4520'],
            ],
            'Bandarban' => [
                ['Bandarban Sadar', '4600'], ['Lama', '4641'], ['Naikhongchhari', '4660'],
                ['Ruma', '4620'], ['Thanchi', '4630'],
            ],

            // ── Rajshahi Division ──
            'Rajshahi' => [
                ['Boalia', '6000'], ['Rajpara', '6100'], ['Motihar', '6204'], ['Shah Makhdum', '6000'],
                ['Paba', '6201'], ['Godagari', '6290'], ['Tanore', '6230'], ['Bagmara', '6250'],
                ['Puthia', '6260'], ['Charghat', '6270'],
            ],
            'Bogura' => [
                ['Bogura Sadar', '5800'], ['Sherpur', '5840'], ['Shibganj', '5810'],
                ['Sariakandi', '5830'], ['Dupchanchia', '5860'], ['Kahaloo', '5870'], ['Gabtali', '5820'],
            ],
            'Pabna' => [
                ['Pabna Sadar', '6600'], ['Ishwardi', '6620'], ['Bera', '6680'],
                ['Chatmohar', '6630'], ['Bhangura', '6640'], ['Santhia', '6670'], ['Sujanagar', '6650'],
            ],
            'Sirajganj' => [
                ['Sirajganj Sadar', '6700'], ['Shahjadpur', '6770'], ['Ullapara', '6760'],
                ['Belkuchi', '6740'], ['Kazipur', '6710'], ['Raiganj', '6750'], ['Tarash', '6780'],
            ],
            'Natore' => [
                ['Natore Sadar', '6400'], ['Bagatipara', '6412'], ['Baraigram', '6430'],
                ['Gurudaspur', '6440'], ['Lalpur', '6420'], ['Singra', '6450'],
            ],
            'Joypurhat' => [
                ['Joypurhat Sadar', '5900'], ['Akkelpur', '5940'], ['Kalai', '5930'],
                ['Khetlal', '5920'], ['Panchbibi', '5910'],
            ],
            'Chapai Nawabganj' => [
                ['Chapai Nawabganj Sadar', '6300'], ['Shibganj', '6340'], ['Gomastapur', '6320'],
                ['Nachole', '6310'], ['Bholahat', '6330'],
            ],
            'Naogaon' => [
                ['Naogaon Sadar', '6500'], ['Patnitala', '6540'], ['Manda', '6510'],
                ['Dhamoirhat', '6520'], ['Mahadebpur', '6530'], ['Sapahar', '6560'], ['Niamatpur', '6550'],
            ],

            // ── Khulna Division ──
            'Khulna' => [
                ['Khalishpur', '9000'], ['Sonadanga', '9100'], ['Daulatpur', '9202'], ['Khan Jahan Ali', '9203'],
                ['Dumuria', '9220'], ['Phultala', '9210'], ['Dighalia', '9220'], ['Rupsha', '9240'],
                ['Batiaghata', '9260'], ['Paikgachha', '9280'],
            ],
            'Jessore' => [
                ['Jessore Sadar', '7400'], ['Abhaynagar', '7460'], ['Jhikargachha', '7420'],
                ['Keshabpur', '7450'], ['Manirampur', '7440'], ['Bagherpara', '7470'], ['Sharsha', '7430'],
            ],
            'Satkhira' => [
                ['Satkhira Sadar', '9400'], ['Kalaroa', '9410'], ['Tala', '9420'],
                ['Assasuni', '9460'], ['Debhata', '9430'], ['Kaliganj', '9440'], ['Shyamnagar', '9450'],
            ],
            'Bagerhat' => [
                ['Bagerhat Sadar', '9300'], ['Mongla', '9350'], ['Morrelganj', '9320'],
                ['Fakirhat', '9370'], ['Mollahat', '9380'], ['Rampal', '9340'], ['Chitalmari', '9360'],
            ],
            'Narail' => [
                ['Narail Sadar', '7500'], ['Lohagara', '7510'], ['Kalia', '7520'],
            ],
            'Magura' => [
                ['Magura Sadar', '7600'], ['Mohammadpur', '7630'], ['Shalikha', '7620'], ['Sreepur', '7610'],
            ],
            'Jhenaidah' => [
                ['Jhenaidah Sadar', '7300'], ['Shailkupa', '7320'], ['Harinakunda', '7310'],
                ['Kaliganj', '7350'], ['Kotchandpur', '7330'], ['Maheshpur', '7340'],
            ],
            'Chuadanga' => [
                ['Chuadanga Sadar', '7200'], ['Alamdanga', '7210'], ['Damurhuda', '7220'], ['Jibannagar', '7230'],
            ],
            'Kushtia' => [
                ['Kushtia Sadar', '7000'], ['Kumarkhali', '7010'], ['Bheramara', '7040'],
                ['Mirpur', '7030'], ['Daulatpur', '7050'], ['Khoksa', '7020'],
            ],
            'Meherpur' => [
                ['Meherpur Sadar', '7100'], ['Gangni', '7110'], ['Mujibnagar', '7102'],
            ],

            // ── Barishal Division ──
            'Barishal' => [
                ['Barishal Sadar', '8200'], ['Bakerganj', '8280'], ['Babuganj', '8210'],
                ['Banaripara', '8250'], ['Gaurnadi', '8230'], ['Hizla', '8270'], ['Muladi', '8240'],
                ['Mehendiganj', '8260'], ['Wazirpur', '8220'], ['Agailjhara', '8240'],
            ],
            'Bhola' => [
                ['Bhola Sadar', '8300'], ['Borhanuddin', '8320'], ['Charfasson', '8340'],
                ['Daulatkhan', '8310'], ['Lalmohan', '8330'], ['Manpura', '8350'], ['Tazumuddin', '8360'],
            ],
            'Patuakhali' => [
                ['Patuakhali Sadar', '8600'], ['Bauphal', '8620'], ['Galachipa', '8640'],
                ['Dashmina', '8630'], ['Kalapara', '8650'], ['Mirzaganj', '8610'], ['Dumki', '8602'],
            ],
            'Pirojpur' => [
                ['Pirojpur Sadar', '8500'], ['Mathbaria', '8560'], ['Bhandaria', '8550'],
                ['Kawkhali', '8510'], ['Nazirpur', '8540'], ['Nesarabad', '8530'],
            ],
            'Barguna' => [
                ['Barguna Sadar', '8700'], ['Amtali', '8710'], ['Betagi', '8740'],
                ['Bamna', '8730'], ['Patharghata', '8720'], ['Taltali', '8712'],
            ],
            'Jhalokati' => [
                ['Jhalokati Sadar', '8400'], ['Nalchity', '8420'], ['Rajapur', '8410'], ['Kathalia', '8430'],
            ],

            // ── Sylhet Division ──
            'Sylhet' => [
                ['Zindabazar', '3100'], ['Ambarkhana', '3100'], ['Subid Bazar', '3100'], ['Beanibazar', '3170'],
                ['Golapganj', '3160'], ['Bishwanath', '3130'], ['Companiganj', '3140'], ['Fenchuganj', '3116'],
                ['Gowainghat', '3150'], ['Jaintiapur', '3156'], ['Kanaighat', '3180'], ['Zakiganj', '3190'],
            ],
            'Moulvibazar' => [
                ['Moulvibazar Sadar', '3200'], ['Sreemangal', '3210'], ['Kamalganj', '3220'],
                ['Kulaura', '3230'], ['Barlekha', '3250'], ['Rajnagar', '3240'], ['Juri', '3236'],
            ],
            'Habiganj' => [
                ['Habiganj Sadar', '3300'], ['Madhabpur', '3330'], ['Nabiganj', '3370'],
                ['Bahubal', '3310'], ['Chunarughat', '3320'], ['Lakhai', '3360'], ['Baniachong', '3350'],
            ],
            'Sunamganj' => [
                ['Sunamganj Sadar', '3000'], ['Chhatak', '3080'], ['Jagannathpur', '3060'],
                ['Dharmapasha', '3030'], ['Dowarabazar', '3040'], ['Tahirpur', '3020'], ['Bishwambarpur', '3010'],
            ],

            // ── Rangpur Division ──
            'Rangpur' => [
                ['Rangpur Sadar', '5400'], ['Badarganj', '5430'], ['Mithapukur', '5460'],
                ['Pirganj', '5470'], ['Gangachara', '5410'], ['Kaunia', '5440'], ['Taraganj', '5420'],
                ['Pirgachha', '5450'],
            ],
            'Dinajpur' => [
                ['Dinajpur Sadar', '5200'], ['Birampur', '5266'], ['Parbatipur', '5250'],
                ['Phulbari', '5260'], ['Bochaganj', '5210'], ['Birganj', '5220'], ['Kaharole', '5230'],
                ['Chirirbandar', '5240'],
            ],
            'Kurigram' => [
                ['Kurigram Sadar', '5600'], ['Nageshwari', '5660'], ['Bhurungamari', '5670'],
                ['Ulipur', '5620'], ['Chilmari', '5630'], ['Rajarhat', '5610'], ['Rowmari', '5640'],
            ],
            'Gaibandha' => [
                ['Gaibandha Sadar', '5700'], ['Gobindaganj', '5740'], ['Palashbari', '5730'],
                ['Sadullapur', '5710'], ['Sundarganj', '5720'], ['Phulchhari', '5750'], ['Saghata', '5760'],
            ],
            'Nilphamari' => [
                ['Nilphamari Sadar', '5300'], ['Saidpur', '5310'], ['Domar', '5340'],
                ['Dimla', '5350'], ['Jaldhaka', '5330'], ['Kishoreganj', '5320'],
            ],
            'Panchagarh' => [
                ['Panchagarh Sadar', '5000'], ['Boda', '5010'], ['Debiganj', '5020'],
                ['Atwari', '5030'], ['Tetulia', '5040'],
            ],
            'Thakurgaon' => [
                ['Thakurgaon Sadar', '5100'], ['Pirganj', '5110'], ['Ranisankail', '5130'],
                ['Baliadangi', '5140'], ['Haripur', '5120'],
            ],
            'Lalmonirhat' => [
                ['Lalmonirhat Sadar', '5500'], ['Patgram', '5540'], ['Hatibandha', '5530'],
                ['Kaliganj', '5520'], ['Aditmari', '5510'],
            ],

            // ── Mymensingh Division ──
            'Mymensingh' => [
                ['Mymensingh Sadar', '2200'], ['Trishal', '2220'], ['Bhaluka', '2240'],
                ['Muktagachha', '2210'], ['Fulbaria', '2216'], ['Gauripur', '2270'], ['Gafargaon', '2230'],
                ['Ishwarganj', '2280'], ['Nandail', '2290'], ['Phulpur', '2250'],
            ],
            'Jamalpur' => [
                ['Jamalpur Sadar', '2000'], ['Sarishabari', '2050'], ['Melandaha', '2012'],
                ['Islampur', '2020'], ['Dewanganj', '2030'], ['Madarganj', '2040'], ['Bakshiganj', '2140'],
            ],
            'Netrokona' => [
                ['Netrokona Sadar', '2400'], ['Kendua', '2480'], ['Mohanganj', '2446'],
                ['Durgapur', '2420'], ['Kalmakanda', '2430'], ['Atpara', '2470'], ['Barhatta', '2440'],
            ],
            'Sherpur' => [
                ['Sherpur Sadar', '2100'], ['Nakla', '2150'], ['Nalitabari', '2110'],
                ['Sreebardi', '2120'], ['Jhenaigati', '2130'],
            ],
        ];
    }
}

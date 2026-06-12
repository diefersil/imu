<?php
/*$username = 'faculdade_facisa';
$raw = file_get_contents("https://www.instagram.com/{$username}/");

echo($raw);

exit;

 Use regex to find the 'biography' field in the embedded JSON data
preg_match('/user": ({"biography": ".*?")/', $raw, $m);

if (isset($m[1])) {
    $array = json_decode($m[1] . '}', true);
    echo "Biography: " . htmlspecialchars($array['biography']);
} else {
    echo "Could not scrape the biography.";
}*/



// Com composer: composer require postaddictme/instagram-php-scraper
require __DIR__ . '/vendor/autoload.php';

$instagram = \InstagramScraper\Instagram::withCredentials(new \GuzzleHttp\Client(), '', '');
$instagram->login();

// Pega informações da conta
$account = $instagram->getAccount('faculdade_facisa');
echo $account->getBiography();


?>
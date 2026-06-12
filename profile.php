<?php
function getInstagramProfilePic($username) {
    $url = "https://www.instagram.com/" . $username . "/?__a=1";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Necessário para seguir redirecionamentos e simular navegador
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $data = json_decode($response, true);
        // Tenta pegar a imagem HD, se não conseguir, a normal
        if (isset($data['graphql']['user']['profile_pic_url_hd'])) {
            return $data['graphql']['user']['profile_pic_url_hd'];
        } elseif (isset($data['graphql']['user']['profile_pic_url'])) {
            return $data['graphql']['user']['profile_pic_url'];
        }
    }
    return null;
}

// Uso:
$username = "mjqueijos"; // Exemplo
$profilePicUrl = getInstagramProfilePic($username);

if ($profilePicUrl) {
    echo '<img src="' . $profilePicUrl . '" alt="Foto de perfil">';
} else {
    echo "Não foi possível obter a foto.";
}
?>
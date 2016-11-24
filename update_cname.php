#!/usr/bin/env php
<?php

function ourip(){
    
    $raw = json_decode(file_get_contents("https://api.ipify.org?format=json"),true); 
    
    return $raw["ip"];
    
}

function login($user, $pass){
    
    $ses = shell_exec("curl -s 'https://ns5.hosting.reg.ru/manager/dnsmgr' -H 'Origin: https://ns5.hosting.reg.ru' -H 'Accept-Encoding: gzip, deflate, br' -H 'Accept-Language: es,en-US;q=0.8,en;q=0.6' -H 'Upgrade-Insecure-Requests: 1'  -H 'Content-Type: application/x-www-form-urlencoded' -H 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8' -H 'Cache-Control: max-age=0' -H 'Referer: https://ns5.hosting.reg.ru/manager/dnsmgr' -H 'Connection: keep-alive' --data 'username=".$user."&password=".$pass."&lang=en&func=auth' --compressed -o /dev/null --cookie-jar - | grep dnsmgr | awk '{print $7}'");
    
    return $ses;
    
}

function h2ip($host){
    
    $raw = str_replace("\n", "", shell_exec("dig @ns5.hosting.reg.ru ".$host." +short"));
    
    return $raw;
    
}

function updatedns($domain, $host, $ip, $user, $pass){
    
    $cur_ip = h2ip($host);
    
    if($cur_ip == $ip){
        
        return 0;
        
    }else{
    
        $cur = $host."%20A%20%20".$cur_ip;
        
        $ses = login($user, $pass);

        shell_exec("curl -s 'https://ns5.hosting.reg.ru/manager/dnsmgr' \
        -H 'Cookie: is_authorized=1; dnsmgrses5=".$ses."; dnsmgrlang5=orion:en' \
        -H 'Origin: https://ns5.hosting.reg.ru' -H 'Accept-Encoding: gzip, deflate, br' -H 'Accept-Language: es,en-US;q=0.8,en;q=0.6' \
        -H 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8' -H 'Accept: text/html, */*; q=0.01' -H 'Referer: https://ns5.hosting.reg.ru/manager/dnsmgr' -H 'X-Requested-With: XMLHttpRequest' -H 'Connection: keep-alive' --data 'func=domain.record.edit&elid=".$cur."&plid=".$domain."&name=".$host."&ttl=60&rtype=a&ip=".$ip."&domain=&srvdomain=&priority=&weight=&port=&value=&email=&clicked_button=ok&progressid=false&sok=ok&sfrom=ajax&operafake=".time().rand(0,999)."' --compressed");
        
        return $ip;
        
    }
}

$user = "user";
$pass = "password";

$domain = "example.com";
$host = "www.example.com."; //FQDN

$interval = "0"; //Hacerlo una vez solo o en bucle, para usar con cron poner en 0

while(true){

    $n_ip = updatedns($domain, $host, ourip(), $user, $pass);

    if($n_ip == 0){
        
        echo "IP no actualizada\n";
        
    }else{
        
        echo "IP actualizada (".$n_ip.")\n";
        
    }

    if($interval == 0){

	break;

    }else{

        sleep($interval);

    }

}

?>


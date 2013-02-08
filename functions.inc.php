<?php

	$config = require "config.inc.php";

	//Create New PDO connection variable
	try
	{
		$db = new PDO($config["pdo"]["dsn"], $config["pdo"]["username"], $config["pdo"]["password"]);
	}
	catch (PDOException $e)
	{
    	exit;
	}	
	//Set Today's Date and Current Time
	$dateTime = date("Y-m-d H:i:s");
	
	require_once "geoip.inc";
	$gi = geoip_open("GeoIP.dat", GEOIP_STANDARD);
	
	function addIp($ip)
	{
		global $db;
		global $dateTime;
		
		$ip_details = lookupIpDetails($ip);
		
		$stmt = $db->prepare("INSERT INTO `banned_ips` (`ip`, `hostname`, `iso`, `date_time`) VALUES (?, ?, ?, ?)");
		$stmt->execute(array($ip, $ip_details["hostname"], $ip_details["country"], $dateTime));
	}
	
	function lookupIpDetails($ip)
	{
		global $gi;
		
		$iso = geoip_country_code_by_addr($gi, $ip);
		$hostname = gethostbyaddr($ip);
		return array("country" => $iso, "hostname" => $hostname);
	}
	
	function getAllIps()
	{
		global $db;
		
		$stmt = $db->prepare("SELECT * FROM `banned_ips`");
		$stmt->execute();
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $result;
	}
	
	function getCountries($args)
	{
		$countries = array();
		$country_coords = array();
		
		foreach($args as $arg)
		{
			if(!in_array($arg["iso"], $countries))
			{
				array_push($countries, $arg["iso"]);
			}
		}
		
		foreach($countries as $country)
		{
			$details = getCoords($country);
			$cc = array("iso" => $country, "lat" => $details["lat"], "lng" => $details["lng"]);
			array_push($country_coords, $cc);
		}
		
		return $country_coords;
	}
	
	function getCoords($iso)
	{
		global $db;
		
		$stmt = $db->prepare("SELECT * FROM `countries` WHERE `iso` = ?");
		$stmt->execute(array($iso));
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		return $result;
	}
?>

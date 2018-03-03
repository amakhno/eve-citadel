<?php
// ESI lib
//esi-mail.read_mail.v1
//esi-characters.read_notifications.v1
//esi-alliances.read_contacts.v1
//$esi_language = "en-us";

class ESIClient {
	
	const BASE_URL = "https://esi.tech.ccp.is";
	
	function __construct($datasource = "tranquility", $token = null) {
		$this->user_agent = "EVE Citadel Auth System";
		$this->headers = array("Accept: application/json",);
		$this->datasource = $datasource;
		$this->token = $token;
		
		$this->curl = curl_init();
		curl_setopt_array(
			$this->curl,
			array(
				CURLOPT_USERAGENT => $this->user_agent,
				CURLOPT_HTTPHEADER => $this->headers,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYPEER => true,
				CURLOPT_SSL_VERIFYHOST => 2,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_TIMEOUT => 16
			)
		);
	}

    function __destruct() {
        curl_close($this->curl);
    }
	
	function request($url) {
		try {
			$esi_request = self::BASE_URL . $url;
			curl_setopt($this->curl, CURLOPT_URL, $esi_request);

			$result = curl_exec($this->curl);
			$result = json_decode($result, TRUE);

			return $result;
		}
		catch(Exception $e) {
			return null;
		}
	}
	
	public function alliance_get_contacts($id) {
		$url = "/v1/alliances/{$id}/contacts/?datasource={$this->datasource}&token={$this->token}";
		$response = $this->request($url);
		return $response;
	}

	public function alliance_get_corporations($id) {
		$url = "/v1/alliances/{$id}/corporations/?datasource={$this->datasource}";
		$response = $this->request($url);
		return $response;
	}

	public function alliance_get_details($id) {
		$url = "/v3/alliances/{$id}/?datasource={$this->datasource}";
		$response = $this->request($url);
		return $response;
	}

	public function character_get_details($id) {
		$url = "/v4/characters/{$id}/?datasource={$this->datasource}";
		$response = $this->request($url);
		if (!isset($response['alliance_id'])) {
			$response['alliance_id'] = 1;
		}
		return $response;
	}

	public function corporation_get_details($id) {
		$url = "/v4/corporations/{$id}/?datasource={$this->datasource}";
		$response = $this->request($url);
		return $response;
	}
}